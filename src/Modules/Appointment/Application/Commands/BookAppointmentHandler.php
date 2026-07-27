<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Appointment\Application\DTOs\BookAppointmentData;
use Modules\Appointment\Domain\Events\AppointmentBooked;
use Modules\Appointment\Domain\Exceptions\PastDateSchedulingException;
use Modules\Appointment\Domain\Exceptions\SlotUnavailableException;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\Services\AppointmentScheduler;
use Modules\Appointment\Domain\Services\SpamGuard;
use Modules\Appointment\Domain\ValueObjects\StatusLead;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * PUBLIC entry point (Astro landing page, no auth). A first-time booking is
 * validated by {@see AppointmentScheduler} (not a past date, inside an open
 * availability window, not already taken) before anything is persisted.
 *
 * The `appointments.email` partial unique index allows only one ACTIVE lead per
 * email. A resubmission from an email that already has an active appointment is
 * REJECTED (422) rather than silently rewriting the existing schedule — the
 * client must be rescheduled by a super-admin through the dedicated Reschedule
 * action. Only a genuinely new email creates a row and fires
 * {@see AppointmentBooked} (which notifies both the company inbox and the
 * client's own address).
 *
 * Anti-spam runs in two layers (mirroring ContactSupport): the honeypot bot
 * filter in the controller, then {@see SpamGuard} content scoring here — the
 * latter flags the lead (`is_spam`) for review but never rejects it.
 */
final readonly class BookAppointmentHandler
{
    public function __construct(
        private AppointmentRepositoryPort $appointments,
        private AppointmentScheduler $scheduler,
        private SpamGuard $spamGuard,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(BookAppointmentData $data): AppointmentEloquentModel
    {
        // Email is already lowercased + trimmed at the DTO boundary
        // (BookAppointmentData::prepareForPipeline), so it matches the stored value.
        $email = $data->email;

        if ($this->appointments->findActiveByEmail($email) !== null) {
            throw ValidationException::withMessages([
                'email' => [__('An appointment already exists for this email. Our team will contact you to reschedule it.')],
            ]);
        }

        $scheduledAt = CarbonImmutable::parse($data->scheduledAt);

        try {
            $this->scheduler->assertBookable($scheduledAt);
        } catch (PastDateSchedulingException|SlotUnavailableException $e) {
            throw ValidationException::withMessages(['scheduled_at' => [$e->getMessage()]]);
        }

        // Content-scoring layer (after the honeypot bot filter in the controller):
        // flags, never rejects — a flagged lead is still stored for review.
        $assessment = $this->spamGuard->assess(
            trim("{$data->firstName} {$data->lastName} ".($data->companyName ?? '')),
            $data->notes ?? '',
        );

        $appointment = DB::transaction(fn () => $this->appointments->create([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'client_type' => $data->clientType,
            'company_name' => $data->companyName,
            'project_type' => $data->projectType,
            'email' => $email,
            'phone' => $data->phone,
            'address' => $data->address,
            'address_2' => $data->address2,
            'zip_code' => $data->zipCode,
            'city' => $data->city,
            'state' => $data->state,
            'country' => $data->country,
            'country_code' => $data->countryCode,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'sms_consent' => $data->smsConsent,
            'notes' => $data->notes,
            'scheduled_at' => $scheduledAt,
            'status_lead' => StatusLead::New,
            'is_spam' => $assessment->isSpam,
            'spam_score' => $assessment->score,
            'spam_reasons' => $assessment->reasons === [] ? null : $assessment->reasons,
        ]));

        $this->cache->forget("appointment_{$appointment->uuid}");

        // Queued email listeners + sync Reverb broadcast listener — see
        // AppointmentServiceProvider (never broadcast from this handler).
        event(new AppointmentBooked($appointment->uuid));

        return $appointment;
    }
}
