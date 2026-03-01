<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource — API output representation.
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id->value ?? $this->resource->id,
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'last_name' => $this->resource->lastName ?? $this->resource->last_name,
            'full_name' => method_exists($this->resource, 'fullName') ? $this->resource->fullName() : trim(($this->resource->name ?? '') . ' ' . ($this->resource->lastName ?? $this->resource->last_name ?? '')),
            'email' => $this->resource->email,
            'username' => $this->resource->username,
            'phone' => $this->resource->phone,
            'profile_photo_path' => $this->resource->profilePhotoPath ?? $this->resource->profile_photo_path,
            'address' => $this->resource->address,
            'city' => $this->resource->city,
            'state' => $this->resource->state,
            'country' => $this->resource->country,
            'zip_code' => $this->resource->zipCode ?? $this->resource->zip_code,
            'status' => $this->resource->status->value ?? $this->resource->status ?? 'active',
            'created_at' => $this->resource->createdAt ?? $this->resource->created_at,
            'updated_at' => $this->resource->updatedAt ?? $this->resource->updated_at,
        ];
    }
}
