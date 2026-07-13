<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\ContactSupport\Application\DTOs\ContactSupportData;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Domain\Services\SpamGuard;
use Modules\ContactSupport\Infrastructure\Broadcasting\ContactSupportSubmitted;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

/**
 * Persists a submission from the PUBLIC (guest) contact form. Unlike the
 * operator-created {@see CreateContactSupportHandler}, this path scores the
 * content with {@see SpamGuard} and stores the verdict — the row is always saved
 * (never rejected) so a false positive can be reviewed and restored. The
 * spatie/laravel-honeypot bot layer runs earlier as route middleware, so bots
 * never reach this handler.
 */
final readonly class SubmitContactRequestHandler
{
    public function __construct(
        private ContactSupportRepositoryPort $contactSupports,
        private SpamGuard $spamGuard,
    ) {}

    #[\NoDiscard]
    public function handle(ContactSupportData $data): ContactSupportEloquentModel
    {
        $assessment = $this->spamGuard->assess($data->subject, $data->message);

        $email = $data->email |> trim(...) |> strtolower(...);

        $contactSupport = DB::transaction(fn () => $this->contactSupports->create([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $email,
            'phone' => $data->phone,
            'subject' => $data->subject,
            'message' => $data->message,
            'sms_consent' => $data->smsConsent,
            'is_spam' => $assessment->isSpam,
            'spam_score' => $assessment->score,
            'spam_reasons' => $assessment->reasons === [] ? null : $assessment->reasons,
        ]));

        broadcast(new ContactSupportSubmitted($contactSupport));

        return $contactSupport;
    }
}
