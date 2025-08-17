<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceItem;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceItemController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $items = ServiceItem::query()->orderBy('name')->paginate(50);
            return $this->successResponse($items);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_main_wash' => 'boolean',
                'is_premium' => 'boolean',
                'applies_to' => 'required|in:motor,car,helmet,apparel,general',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();
            $item = ServiceItem::create($request->all());
            DB::commit();
            return $this->successResponse($item, 'Service item created', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function show(ServiceItem $serviceItem)
    {
        try {
            return $this->successResponse($serviceItem);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, ServiceItem $serviceItem)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'is_main_wash' => 'boolean',
                'is_premium' => 'boolean',
                'applies_to' => 'sometimes|required|in:motor,car,helmet,apparel,general',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();
            $serviceItem->update($request->all());
            DB::commit();
            return $this->successResponse($serviceItem, 'Service item updated');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(ServiceItem $serviceItem)
    {
        try {
            DB::beginTransaction();
            $serviceItem->delete();
            DB::commit();
            return $this->successResponse(null, 'Service item deleted', 204);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
