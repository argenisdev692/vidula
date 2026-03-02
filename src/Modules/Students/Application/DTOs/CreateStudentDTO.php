<?php
declare(strict_types=1);

namespace Modules\Students\Application\DTOs;

use Spatie\LaravelData\Data;

final class CreateStudentDTO extends Data
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $dni = null,
        public ?string $birthDate = null,
        public ?string $address = null,
        public ?string $avatar = null,
        public ?string $notes = null,
        public bool $active = true
    ) {}
}
