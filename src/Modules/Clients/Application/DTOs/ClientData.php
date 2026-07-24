<?php

declare(strict_types=1);

namespace Modules\Clients\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Fused create/update DTO for a CRM client (Store/Update share the same fields).
 * Phone is required E.164 (`phone:INTERNATIONAL`). Lifecycle `status` is distinct
 * from soft-delete tombstone filtering.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ClientData extends Data
{
    public function __construct(
        public string $clientName,
        public string $phone,
        public string $status = 'DRAFT',
        public ?string $email = null,
        public ?string $address = null,
        public ?string $taxId = null,
        public ?string $nif = null,
        public ?string $website = null,
        public ?string $facebookLink = null,
        public ?string $instagramLink = null,
        public ?string $linkedinLink = null,
        public ?string $twitterLink = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'string', 'in:DRAFT,ACTIVE,ARCHIVED'],
            'phone' => ['required', 'string', 'max:20', 'phone:INTERNATIONAL'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'facebook_link' => ['nullable', 'string', 'max:255', 'url'],
            'instagram_link' => ['nullable', 'string', 'max:255', 'url'],
            'linkedin_link' => ['nullable', 'string', 'max:255', 'url'],
            'twitter_link' => ['nullable', 'string', 'max:255', 'url'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
