<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Default authorization for API requests.
     * Override in child classes if needed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return JSON validation response instead of redirect.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errorCode' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Return JSON authorization response.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
            ], 403)
        );
    }
}
