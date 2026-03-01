<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * VerifyOtpRequest — Validates OTP verification requests.
 *
 * Rules:
 * - identifier: required string, max 255
 * - code: required string, exactly 6 digits
 */
final class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guest route
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'The OTP code must be exactly 6 digits.',
            'code.size' => 'The OTP code must be 6 characters long.',
        ];
    }
}
