<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'userUuid' => ['required', 'string', 'uuid', 'exists:users,uuid'],
            'companyName' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebookLink' => ['nullable', 'url', 'max:255'],
            'instagramLink' => ['nullable', 'url', 'max:255'],
            'linkedinLink' => ['nullable', 'url', 'max:255'],
            'twitterLink' => ['nullable', 'url', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }
}
