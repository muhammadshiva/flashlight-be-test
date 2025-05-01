<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ServiceTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $serviceTypes = ServiceType::withTrashed()->get();
            return $this->successResponse($serviceTypes, 'Service types retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('service-types', 'public');
                $data['image'] = $path;
            }

            $serviceType = ServiceType::create($data);

            DB::commit();
            return $this->successResponse($serviceType, 'Service type created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            // Clean up uploaded file if transaction fails
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return $this->handleException($e);
        }
    }

    public function show(ServiceType $serviceType)
    {
        try {
            return $this->successResponse($serviceType, 'Service type retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();
            $oldImage = $serviceType->image;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('service-types', 'public');
                $data['image'] = $path;
            }

            $serviceType->update($data);

            // Delete old image after successful update
            if ($request->hasFile('image') && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            DB::commit();
            return $this->successResponse($serviceType, 'Service type updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            // Clean up uploaded file if transaction fails
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            return $this->handleException($e);
        }
    }

    public function destroy(ServiceType $serviceType)
    {
        try {
            DB::beginTransaction();

            $imagePath = $serviceType->image;
            $serviceType->delete();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            DB::commit();
            return $this->successResponse(null, 'Service type deleted successfully', 204);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function restore($id)
    {
        try {
            DB::beginTransaction();

            $serviceType = ServiceType::withTrashed()->findOrFail($id);
            $serviceType->restore();

            DB::commit();
            return $this->successResponse($serviceType, 'Service type restored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
