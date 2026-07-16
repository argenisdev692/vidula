<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Queries;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Modules\Meeting\Domain\ValueObjects\AttendeeType;

/**
 * Attendee typeahead across the three eligible sources. Returns ONLY
 * `{type, uuid, label}` — never a full User/Appointment/ContactSupport
 * record — regardless of the searching staff member's other permissions
 * (OWASP data-minimization, research.md §5 API3).
 */
final readonly class SearchAttendeesHandler
{
    private const int LIMIT = 10;

    /**
     * @return Collection<int, array{type: string, uuid: string, label: string}>
     */
    public function handle(string $term, ?string $type = null): Collection
    {
        $requested = AttendeeType::tryFrom((string) $type);

        $results = collect();

        if ($requested === null || $requested === AttendeeType::User) {
            $results = $results->concat($this->searchUsers($term));
        }
        if ($requested === null || $requested === AttendeeType::Lead) {
            $results = $results->concat($this->searchLeads($term));
        }
        if ($requested === null || $requested === AttendeeType::Contact) {
            $results = $results->concat($this->searchContacts($term));
        }

        return $results->take(self::LIMIT)->values();
    }

    /**
     * @return Collection<int, array{type: string, uuid: string, label: string}>
     */
    private function searchUsers(string $term): Collection
    {
        $like = '%'.addcslashes($term, '\\%_').'%';

        return User::query()
            ->where(fn ($q) => $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like))
            ->limit(self::LIMIT)
            ->get(['uuid', 'first_name', 'last_name'])
            ->map(fn (User $user): array => [
                'type' => AttendeeType::User->value,
                'uuid' => $user->uuid,
                'label' => trim("{$user->first_name} {$user->last_name}"),
            ]);
    }

    /**
     * @return Collection<int, array{type: string, uuid: string, label: string}>
     */
    private function searchLeads(string $term): Collection
    {
        $like = '%'.addcslashes($term, '\\%_').'%';

        return AppointmentEloquentModel::query()
            ->where(fn ($q) => $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('company_name', 'like', $like))
            ->limit(self::LIMIT)
            ->get(['uuid', 'first_name', 'last_name', 'company_name'])
            ->map(fn (AppointmentEloquentModel $lead): array => [
                'type' => AttendeeType::Lead->value,
                'uuid' => $lead->uuid,
                'label' => trim("{$lead->first_name} {$lead->last_name}").($lead->company_name ? " ({$lead->company_name})" : ''),
            ]);
    }

    /**
     * @return Collection<int, array{type: string, uuid: string, label: string}>
     */
    private function searchContacts(string $term): Collection
    {
        $like = '%'.addcslashes($term, '\\%_').'%';

        return ContactSupportEloquentModel::query()
            ->where(fn ($q) => $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like))
            ->limit(self::LIMIT)
            ->get(['uuid', 'first_name', 'last_name'])
            ->map(fn (ContactSupportEloquentModel $contact): array => [
                'type' => AttendeeType::Contact->value,
                'uuid' => $contact->uuid,
                'label' => trim("{$contact->first_name} {$contact->last_name}"),
            ]);
    }
}
