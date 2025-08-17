<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EngineClass;
use App\Models\HelmetType;
use App\Models\CarSize;
use App\Models\ApparelType;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DimensionController extends Controller
{
    use ApiResponse;

    public function listEngineClasses()
    {
        return $this->successResponse(EngineClass::orderBy('code')->get());
    }

    public function createEngineClass(Request $request)
    {
        $v = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:engine_classes,code',
            'name' => 'required|string|max:100',
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $row = EngineClass::create($request->only(['code', 'name']));
        return $this->successResponse($row, 'Engine class created', 201);
    }

    public function updateEngineClass(Request $request, EngineClass $engineClass)
    {
        $v = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:50|unique:engine_classes,code,' . $engineClass->id,
            'name' => 'sometimes|required|string|max:100',
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $engineClass->update($request->only(['code', 'name']));
        return $this->successResponse($engineClass, 'Engine class updated');
    }

    public function deleteEngineClass(EngineClass $engineClass)
    {
        $engineClass->delete();
        return $this->successResponse(null, 'Engine class deleted', 204);
    }

    public function listHelmetTypes()
    {
        return $this->successResponse(HelmetType::orderBy('name')->get());
    }

    public function createHelmetType(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:helmet_types,name',
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $row = HelmetType::create($request->only(['name']));
        return $this->successResponse($row, 'Helmet type created', 201);
    }

    public function updateHelmetType(Request $request, HelmetType $helmetType)
    {
        $v = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100|unique:helmet_types,name,' . $helmetType->id,
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $helmetType->update($request->only(['name']));
        return $this->successResponse($helmetType, 'Helmet type updated');
    }

    public function deleteHelmetType(HelmetType $helmetType)
    {
        $helmetType->delete();
        return $this->successResponse(null, 'Helmet type deleted', 204);
    }

    public function listCarSizes()
    {
        return $this->successResponse(CarSize::orderBy('name')->get());
    }

    public function createCarSize(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:car_sizes,name',
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $row = CarSize::create($request->only(['name']));
        return $this->successResponse($row, 'Car size created', 201);
    }

    public function updateCarSize(Request $request, CarSize $carSize)
    {
        $v = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100|unique:car_sizes,name,' . $carSize->id,
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $carSize->update($request->only(['name']));
        return $this->successResponse($carSize, 'Car size updated');
    }

    public function deleteCarSize(CarSize $carSize)
    {
        $carSize->delete();
        return $this->successResponse(null, 'Car size deleted', 204);
    }

    public function listApparelTypes()
    {
        return $this->successResponse(ApparelType::orderBy('name')->get());
    }

    public function createApparelType(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:apparel_types,name',
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $row = ApparelType::create($request->only(['name']));
        return $this->successResponse($row, 'Apparel type created', 201);
    }

    public function updateApparelType(Request $request, ApparelType $apparelType)
    {
        $v = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100|unique:apparel_types,name,' . $apparelType->id,
        ]);
        if ($v->fails()) return $this->validationErrorResponse($v->errors());
        $apparelType->update($request->only(['name']));
        return $this->successResponse($apparelType, 'Apparel type updated');
    }

    public function deleteApparelType(ApparelType $apparelType)
    {
        $apparelType->delete();
        return $this->successResponse(null, 'Apparel type deleted', 204);
    }
}
