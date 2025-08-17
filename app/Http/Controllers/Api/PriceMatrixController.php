<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceMatrix;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PriceMatrixController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = PriceMatrix::query()->with('serviceItem');
            if ($request->filled('service_item_id')) {
                $query->where('service_item_id', $request->service_item_id);
            }
            $rows = $query->orderBy('id', 'desc')->paginate(50);
            return $this->successResponse($rows);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_item_id' => 'required|exists:service_items,id',
                'engine_class_id' => 'nullable|exists:engine_classes,id',
                'helmet_type_id' => 'nullable|exists:helmet_types,id',
                'car_size_id' => 'nullable|exists:car_sizes,id',
                'apparel_type_id' => 'nullable|exists:apparel_types,id',
                'price' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();
            $row = PriceMatrix::create($request->all());
            DB::commit();
            return $this->successResponse($row, 'Price matrix created', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function update(Request $request, PriceMatrix $priceMatrix)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_item_id' => 'sometimes|required|exists:service_items,id',
                'engine_class_id' => 'nullable|exists:engine_classes,id',
                'helmet_type_id' => 'nullable|exists:helmet_types,id',
                'car_size_id' => 'nullable|exists:car_sizes,id',
                'apparel_type_id' => 'nullable|exists:apparel_types,id',
                'price' => 'sometimes|required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();
            $priceMatrix->update($request->all());
            DB::commit();
            return $this->successResponse($priceMatrix, 'Price matrix updated');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(PriceMatrix $priceMatrix)
    {
        try {
            DB::beginTransaction();
            $priceMatrix->delete();
            DB::commit();
            return $this->successResponse(null, 'Price matrix deleted', 204);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
