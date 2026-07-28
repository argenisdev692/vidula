<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Students\Application\DTOs\StudentData;
use Modules\Students\Application\Support\StudentCacheKeys;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

final readonly class UpdateStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $students,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(StudentEloquentModel $student, StudentData $data): StudentEloquentModel
    {
        $updated = DB::transaction(fn () => $this->students->update($student, [
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'dni' => $data->dni,
            'address' => $data->address,
            'avatar' => $data->avatar,
            'notes' => $data->notes,
            'status' => $data->status,
            'active' => $data->active,
        ]));

        $this->cache->forget(StudentCacheKeys::student($updated->uuid));

        return $updated;
    }
}
