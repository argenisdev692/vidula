<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Persistence\Eloquent\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Appointment\Application\DTOs\AppointmentFilterData;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\ValueObjects\ClientType;
use Modules\Appointment\Domain\ValueObjects\MeetingStatus;
use Modules\Appointment\Domain\ValueObjects\ProjectType;
use Modules\Appointment\Domain\ValueObjects\StatusLead;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingAttendeeEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @internal Application must depend on {@see AppointmentRepositoryPort}, not this model.
 *
 * @property int $id
 * @property string $uuid
 * @property string $first_name
 * @property string $last_name
 * @property ClientType $client_type
 * @property string|null $company_name
 * @property ProjectType|null $project_type
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $address_2
 * @property string|null $zip_code
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $country_code
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $sms_consent
 * @property bool $readed
 * @property bool $is_spam
 * @property int $spam_score
 * @property array<int, string>|null $spam_reasons
 * @property StatusLead|null $status_lead
 * @property MeetingStatus|null $meeting_status
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $previous_scheduled_at
 * @property array<int, array{at: string, note: string}>|null $follow_up_calls
 * @property string|null $notes
 * @property string|null $owner
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
#[Table('appointments')]
#[Fillable([
    'uuid', 'first_name', 'last_name', 'client_type', 'company_name', 'project_type',
    'email', 'phone', 'address', 'address_2', 'zip_code', 'city', 'state', 'country',
    'country_code', 'latitude', 'longitude', 'sms_consent', 'readed', 'is_spam', 'spam_score',
    'spam_reasons', 'status_lead', 'meeting_status', 'scheduled_at', 'previous_scheduled_at',
    'follow_up_calls', 'notes', 'owner',
])]
final class AppointmentEloquentModel extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (AppointmentEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * Inverse of Meeting's polymorphic attendee morphMap key `lead`
     * ({@see MeetingAttendeeEloquentModel::attendable()}). Eager-load with
     * `meetingAttendances.meeting` to avoid N+1 when listing a lead's meetings.
     *
     * @return MorphMany<MeetingAttendeeEloquentModel, $this>
     */
    public function meetingAttendances(): MorphMany
    {
        return $this->morphMany(MeetingAttendeeEloquentModel::class, 'attendable');
    }

    /**
     * Reusable list filter (BACKEND-PHP §4.1 — single scope, no duplicated
     * `when()` chains). `suspended` status is applied via `onlyTrashed()` at the
     * repository, mirroring Availability/ContactSupport.
     *
     * @param  Builder<AppointmentEloquentModel>  $query
     * @return Builder<AppointmentEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, AppointmentFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                // Escape LIKE metacharacters (\ % _) so a user-supplied `%`/`_`
                // is matched literally instead of acting as a wildcard.
                $term = '%'.addcslashes($filters->search, '\\%_').'%';
                $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company_name', 'like', $term);
            }))
            ->when($filters->statusLead !== null, fn ($q) => $q->where('status_lead', $filters->statusLead))
            ->when($filters->meetingStatus !== null, fn ($q) => $q->where('meeting_status', $filters->meetingStatus))
            ->when($filters->clientType !== null, fn ($q) => $q->where('client_type', $filters->clientType))
            ->when($filters->projectType !== null, fn ($q) => $q->where('project_type', $filters->projectType))
            ->when($filters->read === 'read', fn ($q) => $q->where('readed', true))
            ->when($filters->read === 'unread', fn ($q) => $q->where('readed', false))
            ->when($filters->spam === 'spam', fn ($q) => $q->where('is_spam', true))
            ->when($filters->spam === 'ham', fn ($q) => $q->where('is_spam', false))
            ->when(
                $filters->dateFrom !== null && $filters->dateTo !== null,
                fn ($q) => $q->whereBetween('created_at', [
                    CarbonImmutable::parse($filters->dateFrom)->startOfDay(),
                    CarbonImmutable::parse($filters->dateTo)->endOfDay(),
                ]),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo === null,
                fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($filters->dateFrom)->startOfDay()),
            )
            ->when(
                $filters->dateTo !== null && $filters->dateFrom === null,
                fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($filters->dateTo)->endOfDay()),
            )
            ->when(
                $filters->scheduledFrom !== null && $filters->scheduledTo !== null,
                fn ($q) => $q->whereBetween('scheduled_at', [
                    CarbonImmutable::parse($filters->scheduledFrom)->startOfDay(),
                    CarbonImmutable::parse($filters->scheduledTo)->endOfDay(),
                ]),
            )
            ->when(
                $filters->scheduledFrom !== null && $filters->scheduledTo === null,
                fn ($q) => $q->where('scheduled_at', '>=', CarbonImmutable::parse($filters->scheduledFrom)->startOfDay()),
            )
            ->when(
                $filters->scheduledTo !== null && $filters->scheduledFrom === null,
                fn ($q) => $q->where('scheduled_at', '<=', CarbonImmutable::parse($filters->scheduledTo)->endOfDay()),
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_type' => ClientType::class,
            'project_type' => ProjectType::class,
            'status_lead' => StatusLead::class,
            'meeting_status' => MeetingStatus::class,
            'sms_consent' => 'boolean',
            'readed' => 'boolean',
            'is_spam' => 'boolean',
            'spam_reasons' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'scheduled_at' => 'datetime',
            'previous_scheduled_at' => 'datetime',
            'follow_up_calls' => 'array',
        ];
    }

    /**
     * Audit only the business/lifecycle fields an operator acts on. Names,
     * email, phone, address and notes are guest-submitted PII and are
     * deliberately NOT logged (mirrors ContactSupport).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status_lead',
                'meeting_status',
                'scheduled_at',
                'previous_scheduled_at',
                'readed',
                'is_spam',
                'owner',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('appointment');
    }

    protected static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }
}
