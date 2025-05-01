<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceTypeCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class ServiceTypeCategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $categories = ServiceTypeCategory::withTrashed()->get();
            return $this->successResponse($categories, 'Categories retrieved');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->only(['name']);
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('service-type-categories', 'public');
            }

            $category = ServiceTypeCategory::create($data);
            DB::commit();

            return $this->successResponse($category, 'Category created', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function show(ServiceTypeCategory $serviceTypeCategory)
    {
        return $this->successResponse($serviceTypeCategory, 'Category details');
    }

    public function update(Request $request, ServiceTypeCategory $serviceTypeCategory)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();
            $data = $request->only(['name']);

            if ($request->hasFile('image')) {
                $oldImage = $serviceTypeCategory->image;
                $data['image'] = $request->file('image')->store('service-type-categories', 'public');
                if ($oldImage) Storage::disk('public')->delete($oldImage);
            }

            $serviceTypeCategory->update($data);
            DB::commit();

            return $this->successResponse($serviceTypeCategory, 'Category updated');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(ServiceTypeCategory $serviceTypeCategory)
    {
        try {
            $serviceTypeCategory->delete();
            return $this->successResponse(null, 'Category deleted', 204);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function restore($id)
    {
        try {
            $category = ServiceTypeCategory::withTrashed()->findOrFail($id);
            $category->restore();
            return $this->successResponse($category, 'Category restored');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
