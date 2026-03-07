<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'guard_name' => $this->resource->guardName ?? $this->resource->guard_name,
            'permissions' => $this->resource->permissions,
            'users_count' => $this->resource->usersCount ?? $this->resource->users_count ?? 0,
            'created_at' => $this->resource->createdAt ?? $this->resource->created_at,
            'updated_at' => $this->resource->updatedAt ?? $this->resource->updated_at,
            'available_permissions' => $this->resource->availablePermissions ?? [],
        ];
    }
}
