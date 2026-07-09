<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Single direct-permission toggle for a user (PUT /users/{uuid}/permissions).
 * `granted = true` grants the permission directly, `false` revokes the direct
 * grant. The name is validated against the live, non-suspended catalogue. Used by
 * the dedicated per-user permissions screen (instant toggle per checkbox).
 */
final class SetPermissionData extends Data
{
    public function __construct(
        public string $permission,
        public bool $granted,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'permission' => [
                'required',
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web')->whereNull('deleted_at'),
            ],
            'granted' => ['required', 'boolean'],
        ];
    }
}
