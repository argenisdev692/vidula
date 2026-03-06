<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * SendOtpRequest — Validates OTP send requests.
 *
 * Rules:
 * - identifier: required email, max 255
 */
final class SendOtpRequest extends FormRequest
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
            'identifier' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }
}
