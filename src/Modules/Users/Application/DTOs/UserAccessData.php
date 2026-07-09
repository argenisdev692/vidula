<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Access payload for a user (PUT /users/{uuid}/access). `roles` is the full set
 * of role NAMES the user should hold, and `directPermissions` the set of
 * permission NAMES granted directly (Spatie "top-up" on top of the role grants).
 * Both are SYNCED, not merged — an empty array revokes every direct grant of that
 * kind. Names are validated against the live, non-suspended catalogue so a client
 * can never attach an unknown, suspended, or mismatched-guard entry.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UserAccessData extends Data
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, string>  $directPermissions
     */
    public function __construct(
        public array $roles = [],
        public array $directPermissions = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web')->whereNull('deleted_at'),
            ],
            'direct_permissions' => ['array'],
            'direct_permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web')->whereNull('deleted_at'),
            ],
        ];
    }
}
