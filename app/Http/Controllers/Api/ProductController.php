<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $products = Product::get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'image' => $product->image,
                    'category_id' => $product->category_id,
                    'is_active' => $product->is_active,
                    'updated_at' => $product->updated_at,
                    'created_at' => $product->created_at,
                ];
            });

            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }


    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'category_id' => 'nullable|exists:product_categories,id',
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $data['image'] = $path;
            }

            $product = Product::create($data);

            DB::commit();
            return $this->successResponse($product, 'Product created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            // Clean up uploaded file if transaction fails
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return $this->handleException($e);
        }
    }

    public function show(Product $product)
    {
        try {
            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'category_id' => 'nullable|exists:product_categories,id',
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();
            $oldImage = $product->image;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $data['image'] = $path;
            }

            $product->update($data);

            // Delete old image after successful update
            if ($request->hasFile('image') && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            DB::commit();
            return $this->successResponse($product, 'Product updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            // Clean up uploaded file if transaction fails
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return $this->handleException($e);
        }
    }

    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            $imagePath = $product->image;
            $product->delete();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            DB::commit();
            return $this->successResponse(null, 'Product deleted successfully', 204);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function restore($id)
    {
        try {
            DB::beginTransaction();

            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();

            DB::commit();
            return $this->successResponse($product, 'Product restored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
