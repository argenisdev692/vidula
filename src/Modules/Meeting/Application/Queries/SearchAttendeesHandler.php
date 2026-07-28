<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Modules\Meeting\Domain\ValueObjects\AttendeeType;

/**
 * Attendee typeahead across the three eligible sources. Returns ONLY
 * `{type, uuid, label}` — never a full User/Appointment/ContactSupport
 * record — regardless of the searching staff member's other permissions
 * (OWASP data-minimization, research.md §5 API3).
 *
 * Labels always include email so remote search-by-email stays visible in the UI
 * (PrimeVue Select local filter previously hid email matches when the label
 * was name-only).
 */
final readonly class SearchAttendeesHandler
{
    private const int LIMIT = 10;

    /**
     * @return Collection<int, array{type: string, uuid: string, label: string}>
     */
    #[\NoDiscard]
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
        return User::query()
            ->where(fn (Builder $q) => $this->applyNameEmailSearch($q, $term))
            ->limit(self::LIMIT)
            ->get(['uuid', 'first_name', 'last_name', 'email'])
            ->map(fn (User $user): array => [
                'type' => AttendeeType::User->value,
                'uuid' => $user->uuid,
                'label' => $this->label(trim("{$user->first_name} {$user->last_name}"), $user->email),
            ]);
    }

    /**
     * @return Collection<int, array{type: string, uuid: string, label: string}>
     */
    private function searchLeads(string $term): Collection
    {
        return AppointmentEloquentModel::query()
            ->where(function (Builder $q) use ($term): void {
                $this->applyNameEmailSearch($q, $term);
                $like = $this->like($term);
                $q->orWhere('company_name', 'like', $like);
            })
            ->limit(self::LIMIT)
            ->get(['uuid', 'first_name', 'last_name', 'company_name', 'email'])
            ->map(function (AppointmentEloquentModel $lead): array {
                $name = trim("{$lead->first_name} {$lead->last_name}");
                if ($lead->company_name) {
                    $name .= " ({$lead->company_name})";
                }

                return [
                    'type' => AttendeeType::Lead->value,
                    'uuid' => $lead->uuid,
                    'label' => $this->label($name, $lead->email),
                ];
            });
    }

    /**
     * @return Collection<int, array{type: string, uuid: string, label: string}>
     */
    private function searchContacts(string $term): Collection
    {
        return ContactSupportEloquentModel::query()
            ->where(fn (Builder $q) => $this->applyNameEmailSearch($q, $term))
            ->limit(self::LIMIT)
            ->get(['uuid', 'first_name', 'last_name', 'email'])
            ->map(fn (ContactSupportEloquentModel $contact): array => [
                'type' => AttendeeType::Contact->value,
                'uuid' => $contact->uuid,
                'label' => $this->label(trim("{$contact->first_name} {$contact->last_name}"), $contact->email),
            ]);
    }

    private function applyNameEmailSearch(Builder $query, string $term): void
    {
        $like = $this->like($term);
        $query->where('first_name', 'like', $like)
            ->orWhere('last_name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->orWhereRaw("concat(coalesce(first_name, ''), ' ', coalesce(last_name, '')) like ?", [$like]);
    }

    private function like(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }

    private function label(string $name, ?string $email): string
    {
        $name = trim($name);
        $email = trim((string) $email);

        if ($name === '') {
            return $email;
        }

        return $email !== '' ? "{$name} · {$email}" : $name;
    }
}
