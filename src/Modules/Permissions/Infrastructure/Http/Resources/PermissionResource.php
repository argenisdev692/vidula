<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'guard_name' => $this->guard_name ?? $this->guardName,
            'roles' => $this->roles,
            'roles_count' => $this->roles_count ?? $this->rolesCount,
            'created_at' => $this->created_at ?? $this->createdAt,
            'updated_at' => $this->updated_at ?? $this->updatedAt,
            'available_roles' => $this->available_roles ?? $this->availableRoles ?? [],
        ];
    }
}
