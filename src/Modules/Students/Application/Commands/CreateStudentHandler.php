<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Students\Application\DTOs\StudentData;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

final readonly class CreateStudentHandler
{
    public function __construct(private StudentRepositoryPort $students) {}

    #[\NoDiscard]
    public function handle(StudentData $data): StudentEloquentModel
    {
        return DB::transaction(fn () => $this->students->create([
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
    }
}
