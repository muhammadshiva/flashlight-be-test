<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MembershipType;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MembershipTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $membershipTypes = MembershipType::all();
            return $this->successResponse($membershipTypes, 'Membership types retrieved successfully');
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
                'duration_days' => 'required|integer|min:1',
                'benefits' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $membershipType = MembershipType::create($request->all());

            DB::commit();
            return $this->successResponse($membershipType, 'Membership type created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function show(MembershipType $membershipType)
    {
        try {
            return $this->successResponse($membershipType, 'Membership type retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, MembershipType $membershipType)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'duration_days' => 'sometimes|required|integer|min:1',
                'benefits' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            DB::beginTransaction();

            $membershipType->update($request->all());

            DB::commit();
            return $this->successResponse($membershipType, 'Membership type updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function destroy(MembershipType $membershipType)
    {
        try {
            DB::beginTransaction();

            $membershipType->delete();

            DB::commit();
            return $this->successResponse(null, 'Membership type deleted successfully', 204);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function restore($id)
    {
        try {
            DB::beginTransaction();

            $membershipType = MembershipType::withTrashed()->findOrFail($id);
            $membershipType->restore();

            DB::commit();
            return $this->successResponse($membershipType, 'Membership type restored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
