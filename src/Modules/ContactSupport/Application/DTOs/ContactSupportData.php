<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Create/update payload for a contact-support submission. Store and Update share
 * 100% of the fields and rules, so a single fused DTO is used (DTO Fusion Rule).
 * The `readed` flag is intentionally NOT part of this DTO — it is toggled through
 * the dedicated mark-as-read action, never mass-assigned from a form.
 *
 * `#[MapInputName]` snake-cases the request payload (`first_name`, `sms_consent`)
 * onto the camelCase properties.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class ContactSupportData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $subject,
        public string $message,
        public bool $smsConsent = false,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'sms_consent' => ['boolean'],
        ];
    }
}
