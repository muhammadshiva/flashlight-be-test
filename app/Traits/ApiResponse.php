<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ApiResponse
{
    protected function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message, $code = 400, $details = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'details' => $details
        ], $code);
    }

    protected function validationErrorResponse($errors)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'data' => $errors
        ], 422);
    }

    protected function handleException(Exception $e)
    {
        if ($e instanceof ValidationException) {
            return $this->validationErrorResponse($e->errors());
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return $this->errorResponse('Resource not found', 404);
        }

        // Log the error for debugging
        Log::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Return detailed error information in development environment
        if (config('app.env') === 'local') {
            return $this->errorResponse(
                'An unexpected error occurred',
                500,
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }

        return $this->errorResponse('An unexpected error occurred. Please try again later.', 500);
    }
}
