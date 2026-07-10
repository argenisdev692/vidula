<?php

declare(strict_types=1);

namespace Modules\Users\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Roles payload for a user (PUT /users/{uuid}/roles). `roles` is the FULL set of
 * role NAMES the user should hold after the operation — synced, not merged (an
 * empty array revokes every role). A user holds AT MOST ONE role (`max:1`); extra
 * access is layered on as direct permissions from the dedicated Access screen.
 * Names are validated against the live, non-suspended catalogue so a client can
 * never attach an unknown/mismatched role.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UserRolesData extends Data
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public array $roles = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'roles' => ['array', 'max:1'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web')->whereNull('deleted_at'),
            ],
        ];
    }
}
