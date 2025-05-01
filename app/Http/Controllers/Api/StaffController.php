<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    public function index()
    {
        $staff = Staff::with('washTransactions')->get();
        return response()->json(['data' => $staff]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:staff',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staff = Staff::create($request->all());
        return response()->json(['data' => $staff], 201);
    }

    public function show(Staff $staff)
    {
        $staff->load('washTransactions');
        return response()->json(['data' => $staff]);
    }

    public function update(Request $request, Staff $staff)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255|unique:staff,email,' . $staff->id,
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staff->update($request->all());
        return response()->json(['data' => $staff]);
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        return response()->json(null, 204);
    }
}
