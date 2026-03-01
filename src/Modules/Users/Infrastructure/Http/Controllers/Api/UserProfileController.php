<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Users\Application\Queries\ReadModels\UserProfileReadModel;
use Modules\Users\Domain\Ports\UserProfileRepositoryPort;
use Modules\Users\Domain\ValueObjects\UserId;
use Modules\Users\Infrastructure\Persistence\Mappers\UserProfileMapper;

/**
 * UserProfileController — Authenticated user's own profile.
 */
final class UserProfileController
{
    public function __construct(
        private readonly UserProfileRepositoryPort $repository
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $userId = new UserId($request->user()->id);
        $profile = $this->repository->findByUserId($userId);

        if (null === $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        return response()->json([
            'data' => $profile
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        // To be implemented with UpdateProfileCommand
        return response()->json(['message' => 'Not implemented yet']);
    }
}
