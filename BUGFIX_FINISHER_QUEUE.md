# Bugfix: Finisher Queue Error Resolution

## Problem

**Error:** `Unclosed '[' on line 204 does not match ')' at resources/views/finisher/queue.blade.php:203`

## Root Cause

Complex PHP logic was written directly inside `@json()` directive in the Blade template, causing parsing errors due to nested functions and array operations.

**Problematic code in view:**

```php
transactions: @json($transactions->map(function($transaction) {
    return [
        // Complex array with nested queries
        'additional_services' => $transaction->products()->where(...)->whereHas(...)->get()->pluck('name')->toArray(),
        'food_drinks' => $transaction->products()->whereHas(...)->get()->map(...)->toArray(),
        // ... other fields
    ];
})),
```

## Solution

Moved the complex data processing logic from the Blade view to the controller.

### 1. Updated Controller (`FinisherQueueController.php`)

```php
public function index()
{
    $transactions = WashTransaction::with([...])
        ->whereIn('status', [...])
        ->orderBy('wash_date', 'asc')
        ->get()
        ->map(function ($transaction) {
            // Process data here instead of in view
            $additionalServices = $transaction->products()
                ->where('product_id', '!=', $transaction->product_id)
                ->whereHas('category', function ($query) {
                    $query->whereNotIn('name', ['Food', 'Drinks', 'Makanan', 'Minuman']);
                })
                ->get()
                ->pluck('name')
                ->toArray();

            $foodDrinks = $transaction->products()
                ->whereHas('category', function ($query) {
                    $query->whereIn('name', ['Food', 'Drinks', 'Makanan', 'Minuman']);
                })
                ->get()
                ->map(function ($product) {
                    return $product->name . ' (' . $product->pivot->quantity . ')';
                })
                ->toArray();

            return [
                'id' => $transaction->id,
                'motorbike' => $transaction->customerVehicle->vehicle->name ?? 'N/A',
                'license_plate' => $transaction->customerVehicle->license_plate ?? '',
                'customer_name' => $transaction->customer->user->name ?? '',
                'additional_services' => $additionalServices,
                'food_drinks' => $foodDrinks,
                'membership' => $transaction->customer->membershipType->name ?? null,
                'total_amount' => $transaction->total_price,
                'status' => $transaction->status,
                'wash_date' => $transaction->wash_date,
                'finished_by' => $transaction->staff->user->name ?? null,
            ];
        });

    return view('finisher.queue', compact('transactions'));
}
```

### 2. Simplified View (`queue.blade.php`)

```php
// Simple and clean
transactions: @json($transactions),
```

## Benefits of This Fix

1. **Clean separation of concerns** - Business logic in controller, presentation in view
2. **Better maintainability** - Complex queries are easier to read and debug in controller
3. **Improved performance** - Data is processed once in controller vs. on every view render
4. **Error prevention** - Avoids Blade parsing issues with complex PHP syntax

## Testing

-   ✅ Route accessible: `http://127.0.0.1:8000/finisher/queue`
-   ✅ API endpoint working: `http://127.0.0.1:8000/finisher/queue/data`
-   ✅ JSON response valid with 17 transactions
-   ✅ No more parsing errors

## Files Modified

-   `app/Http/Controllers/FinisherQueueController.php` - Added data processing logic
-   `resources/views/finisher/queue.blade.php` - Simplified JavaScript section

## Follow-up Actions

1. Test in production environment
2. Monitor performance with larger datasets
3. Consider caching for high-traffic scenarios
4. Add error handling for missing relations
