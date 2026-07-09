<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Modules\ContactSupport\Application\DTOs\ContactSupportData;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

/**
 * Persists a new contact-support submission. New rows are always unread
 * (`readed` defaults to false at the DB level). Authorization
 * (permission:CREATE_CONTACT_SUPPORTS) is enforced at the route — never here.
 */
final readonly class CreateContactSupportHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    #[\NoDiscard]
    public function handle(ContactSupportData $data): ContactSupportEloquentModel
    {
        $email = $data->email |> trim(...) |> strtolower(...);

        return $this->contactSupports->create([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $email,
            'phone' => $data->phone,
            'subject' => $data->subject,
            'message' => $data->message,
            'sms_consent' => $data->smsConsent,
        ]);
    }
}
