<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductCategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $categories = ProductCategory::withTrashed()->get();
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

            $category = ProductCategory::create($data);
            DB::commit();

            return $this->successResponse($category, 'Category created', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function show(ProductCategory $productCategory)
    {
        return $this->successResponse($productCategory, 'Category details');
    }

    public function update(Request $request, ProductCategory $productCategory)
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
                $oldImage = $productCategory->image;
                $data['image'] = $request->file('image')->store('product-categories', 'public');
                if ($oldImage) Storage::disk('public')->delete($oldImage);
            }

            $productCategory->update($data);
            DB::commit();

            return $this->successResponse($productCategory, 'Category updated');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(ProductCategory $productCategory)
    {
        try {
            $productCategory->delete();
            return $this->successResponse(null, 'Category deleted', 204);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function restore($id)
    {
        try {
            $category = ProductCategory::withTrashed()->findOrFail($id);
            $category->restore();
            return $this->successResponse($category, 'Category restored');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
}
