<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\ContactSupport\Application\DTOs\ContactSupportData;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

/**
 * Updates an existing contact-support submission (typo fixes on the captured
 * data). The `readed` flag is left untouched — it is owned by the mark-as-read
 * action. Authorization (permission:UPDATE_CONTACT_SUPPORTS) is enforced at the
 * route.
 */
final readonly class UpdateContactSupportHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(ContactSupportEloquentModel $contactSupport, ContactSupportData $data): ContactSupportEloquentModel
    {
        $email = $data->email |> trim(...) |> strtolower(...);

        return DB::transaction(fn () => $this->contactSupports->update($contactSupport, [
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $email,
            'phone' => $data->phone,
            'subject' => $data->subject,
            'message' => $data->message,
            'sms_consent' => $data->smsConsent,
        ]));
    }
}
