<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Services;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Appointment\Domain\ValueObjects\ProjectType;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;

/**
 * Resolves a public {@see ServiceRepositoryPort} UUID into the internal
 * `appointments.service_id` FK. The landing page and CRM forms send only
 * `service_uuid`; numeric `services.id` never crosses the public boundary.
 */
final readonly class AppointmentServiceResolver
{
    public function __construct(private ServiceRepositoryPort $services) {}

    /**
     * @return array<int, mixed>
     */
    public static function uuidValidationRules(bool $required = false): array
    {
        return [
            ...($required ? ['required'] : ['nullable']),
            'string',
            'uuid',
            Rule::exists('services', 'uuid')->where(
                fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'),
            ),
        ];
    }

    /**
     * @return array{service_id: int|null, project_type: ProjectType|null}
     */
    public function resolve(?string $serviceUuid): array
    {
        if ($serviceUuid === null || $serviceUuid === '') {
            return [
                'service_id' => null,
                'project_type' => null,
            ];
        }

        $service = $this->services->findActiveByUuid($serviceUuid);

        if ($service === null) {
            throw ValidationException::withMessages([
                'service_uuid' => [__('The selected service is invalid or no longer available.')],
            ]);
        }

        return [
            'service_id' => $service->id,
            'project_type' => ProjectType::tryFrom($service->slug),
        ];
    }
}
