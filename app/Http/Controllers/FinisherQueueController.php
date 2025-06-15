<?php

namespace App\Http\Controllers;

use App\Models\WashTransaction;
use Illuminate\Http\Request;

class FinisherQueueController extends Controller
{
    /**
     * Display the finisher queue
     */
    public function index()
    {
        $transactions = WashTransaction::with([
            'customer.user',
            'customer.membershipType',
            'customerVehicle.vehicle',
            'products',
            'primaryProduct'
        ])
            ->whereIn('status', [
                WashTransaction::STATUS_PENDING,
                WashTransaction::STATUS_IN_PROGRESS,
                WashTransaction::STATUS_COMPLETED
            ])
            ->orderBy('wash_date', 'asc')
            ->get()
            ->map(function ($transaction) {
                // Get additional services (excluding primary service)
                $additionalServices = $transaction->products()
                    ->where('product_id', '!=', $transaction->product_id)
                    ->whereHas('category', function ($query) {
                        $query->whereNotIn('name', ['Food', 'Drinks', 'Makanan', 'Minuman']);
                    })
                    ->get()
                    ->pluck('name')
                    ->toArray();

                // Get food and drinks
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

    /**
     * Get queue data for AJAX updates
     */
    public function data()
    {
        $transactions = WashTransaction::with([
            'customer.user',
            'customer.membershipType',
            'customerVehicle.vehicle',
            'products',
            'primaryProduct'
        ])
            ->whereIn('status', [
                WashTransaction::STATUS_PENDING,
                WashTransaction::STATUS_IN_PROGRESS,
                WashTransaction::STATUS_COMPLETED
            ])
            ->orderBy('wash_date', 'asc')
            ->get()
            ->map(function ($transaction) {
                // Get additional services (excluding primary service)
                $additionalServices = $transaction->products()
                    ->where('product_id', '!=', $transaction->product_id)
                    ->whereHas('category', function ($query) {
                        $query->whereNotIn('name', ['Food', 'Drinks', 'Makanan', 'Minuman']);
                    })
                    ->get()
                    ->pluck('name')
                    ->toArray();

                // Get food and drinks
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

        return response()->json($transactions);
    }
}
