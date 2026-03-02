<?php

declare(strict_types=1);

namespace Modules\Student\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook_link' => ['nullable', 'url', 'max:255'],
            'instagram_link' => ['nullable', 'url', 'max:255'],
            'linkedin_link' => ['nullable', 'url', 'max:255'],
            'twitter_link' => ['nullable', 'url', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'signature_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
