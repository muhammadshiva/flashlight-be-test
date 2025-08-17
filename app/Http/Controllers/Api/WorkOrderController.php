<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Models\WorkOrderFd;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class WorkOrderController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $wo = WorkOrder::with(['customer.user', 'vehicle', 'services', 'fds'])->orderByDesc('id')->paginate(50);
        return $this->successResponse($wo);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'special_request_note' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*.service_item_id' => 'required_with:services|exists:service_items,id',
            'services.*.qty' => 'nullable|integer|min:1',
            'services.*.unit_price' => 'nullable|integer|min:0',
            'services.*.is_custom' => 'nullable|boolean',
            'services.*.custom_label' => 'nullable|string|max:100',
            'fds' => 'nullable|array',
            'fds.*.fd_item_id' => 'required_with:fds|exists:fd_items,id',
            'fds.*.qty' => 'nullable|integer|min:1',
            'fds.*.unit_price' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Generate simple code and queue number for now
            $today = now()->toDateString();
            $queueNo = (int) (WorkOrder::whereDate('queue_date', $today)->max('queue_no') ?? 0) + 1;
            $code = 'WO-' . date('Ymd') . '-' . str_pad((string) $queueNo, 4, '0', STR_PAD_LEFT);

            $workOrder = WorkOrder::create([
                'code' => $code,
                'queue_no' => $queueNo,
                'queue_date' => $today,
                'customer_id' => $request->customer_id,
                'vehicle_id' => $request->vehicle_id,
                'status' => 'queued',
                'special_request_note' => $request->special_request_note,
                'created_by' => Auth::id(),
            ]);

            foreach (($request->services ?? []) as $svc) {
                // Auto pricing if unit_price not provided
                $unitPrice = $svc['unit_price'] ?? null;
                if ($unitPrice === null) {
                    $pm = \App\Models\PriceMatrix::where('service_item_id', $svc['service_item_id'])
                        ->orderByDesc('id')
                        ->first();
                    $unitPrice = (float) ($pm?->price ?? 0);
                }
                WorkOrderService::create([
                    'work_order_id' => $workOrder->id,
                    'service_item_id' => $svc['service_item_id'],
                    'qty' => $svc['qty'] ?? 1,
                    'unit_price' => $unitPrice,
                    'is_custom' => $svc['is_custom'] ?? false,
                    'custom_label' => $svc['custom_label'] ?? null,
                    'is_premium_snapshot' => $svc['is_premium_snapshot'] ?? false,
                ]);
            }

            foreach (($request->fds ?? []) as $fd) {
                WorkOrderFd::create([
                    'work_order_id' => $workOrder->id,
                    'fd_item_id' => $fd['fd_item_id'],
                    'qty' => $fd['qty'] ?? 1,
                    'unit_price' => $fd['unit_price'] ?? 0,
                ]);
            }

            DB::commit();
            return $this->successResponse($workOrder->load(['services', 'fds']), 'Work order created', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function show(WorkOrder $workOrder)
    {
        return $this->successResponse($workOrder->load(['customer.user', 'vehicle', 'services', 'fds']));
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:new,queued,washing,drying,inspection,ready,paid,done,cancelled,on_hold',
            'special_request_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $workOrder->update($request->only(['status', 'special_request_note']));
        return $this->successResponse($workOrder->fresh());
    }

    public function quote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'services' => 'required|array|min:1',
            'services.*.service_item_id' => 'required|exists:service_items,id',
            'services.*.qty' => 'nullable|integer|min:1',
            'engine_class_id' => 'nullable|exists:engine_classes,id',
            'helmet_type_id' => 'nullable|exists:helmet_types,id',
            'car_size_id' => 'nullable|exists:car_sizes,id',
            'apparel_type_id' => 'nullable|exists:apparel_types,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $engine = $request->engine_class_id;
        $helmet = $request->helmet_type_id;
        $car = $request->car_size_id;
        $apparel = $request->apparel_type_id;

        $lines = [];
        $total = 0;

        foreach ($request->services as $svc) {
            $priceRow = \App\Models\PriceMatrix::query()
                ->where('service_item_id', $svc['service_item_id'])
                ->when($engine, fn($q) => $q->where('engine_class_id', $engine))
                ->when($helmet, fn($q) => $q->where('helmet_type_id', $helmet))
                ->when($car, fn($q) => $q->where('car_size_id', $car))
                ->when($apparel, fn($q) => $q->where('apparel_type_id', $apparel))
                ->orderByDesc('id')
                ->first();

            $unit = $priceRow?->price ?? 0;
            $qty = $svc['qty'] ?? 1;
            $lineTotal = (float) $unit * (int) $qty;
            $total += $lineTotal;

            $lines[] = [
                'service_item_id' => $svc['service_item_id'],
                'qty' => $qty,
                'unit_price' => (float) $unit,
                'line_total' => (float) $lineTotal,
            ];
        }

        return $this->successResponse([
            'lines' => $lines,
            'total' => (float) $total,
        ]);
    }
}
