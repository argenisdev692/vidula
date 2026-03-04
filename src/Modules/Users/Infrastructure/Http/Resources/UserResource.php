<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource — API output representation.
 *
 * @OA\Schema(
 *     schema="UserResource",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="uuid", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="last_name", type="string", nullable=true),
 *     @OA\Property(property="full_name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="username", type="string", nullable=true),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="profile_photo_path", type="string", nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="city", type="string", nullable=true),
 *     @OA\Property(property="state", type="string", nullable=true),
 *     @OA\Property(property="country", type="string", nullable=true),
 *     @OA\Property(property="zip_code", type="string", nullable=true),
 *     @OA\Property(property="status", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDomain   = $this->resource instanceof \Modules\Users\Domain\Entities\User;
        $isReadModel = $this->resource instanceof \Modules\Users\Application\Queries\ReadModels\UserReadModel;

        return [
            'id' => $isDomain ? $this->resource->id->value : ($isReadModel ? null : $this->resource->id),
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'last_name' => ($isDomain || $isReadModel) ? $this->resource->lastName : $this->resource->last_name,
            'full_name' => ($isDomain || $isReadModel) ? trim(($this->resource->name ?? '') . ' ' . ($this->resource->lastName ?? '')) : trim(($this->resource->name ?? '') . ' ' . ($this->resource->last_name ?? '')),
            'email' => $this->resource->email,
            'username' => $this->resource->username,
            'phone' => $this->resource->phone,
            'profile_photo_path' => ($isDomain || $isReadModel) ? $this->resource->profilePhotoPath : $this->resource->profile_photo_path,
            'address' => $this->resource->address,
            'city' => $this->resource->city,
            'state' => $this->resource->state,
            'country' => $this->resource->country,
            'zip_code' => ($isDomain || $isReadModel) ? $this->resource->zipCode : $this->resource->zip_code,
            'status' => $this->resource->status instanceof \BackedEnum ? $this->resource->status->value : ($this->resource->status ?? 'active'),
            'created_at' => ($isDomain || $isReadModel) ? $this->resource->createdAt : $this->resource->created_at,
            'updated_at' => ($isDomain || $isReadModel) ? $this->resource->updatedAt : $this->resource->updated_at,
        ];
    }
}
