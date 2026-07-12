<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;

final readonly class ListAppointmentsHandler
{
    public function __construct(private AppointmentRepositoryPort $appointments) {}

    public function handle(AppointmentFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->appointments->paginate($filters, $perPage);
    }
}
