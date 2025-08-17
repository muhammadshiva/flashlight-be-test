<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FdItem;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FdItemController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $items = FdItem::query()->orderBy('name')->paginate(50);
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
                'price' => 'required|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();
            $item = FdItem::create($request->all());
            DB::commit();
            return $this->successResponse($item, 'F&D item created', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function show(FdItem $fdItem)
    {
        try {
            return $this->successResponse($fdItem);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, FdItem $fdItem)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();
            $fdItem->update($request->all());
            DB::commit();
            return $this->successResponse($fdItem, 'F&D item updated');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(FdItem $fdItem)
    {
        try {
            DB::beginTransaction();
            $fdItem->delete();
            DB::commit();
            return $this->successResponse(null, 'F&D item deleted', 204);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
