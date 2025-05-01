<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WashTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WashTransactionController extends Controller
{
    public function index()
    {
        $transactions = WashTransaction::with(['user', 'vehicle', 'serviceType', 'staff'])->get();
        return response()->json(['data' => $transactions]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_type_id' => 'required|exists:service_types,id',
            'staff_id' => 'required|exists:staff,id',
            'wash_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction = WashTransaction::create($request->all());
        return response()->json(['data' => $transaction], 201);
    }

    public function show(WashTransaction $washTransaction)
    {
        $washTransaction->load(['user', 'vehicle', 'serviceType', 'staff']);
        return response()->json(['data' => $washTransaction]);
    }

    public function update(Request $request, WashTransaction $washTransaction)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|required|exists:users,id',
            'vehicle_id' => 'sometimes|required|exists:vehicles,id',
            'service_type_id' => 'sometimes|required|exists:service_types,id',
            'staff_id' => 'sometimes|required|exists:staff,id',
            'wash_date' => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $washTransaction->update($request->all());
        return response()->json(['data' => $washTransaction]);
    }

    public function destroy(WashTransaction $washTransaction)
    {
        $washTransaction->delete();
        return response()->json(null, 204);
    }
}
