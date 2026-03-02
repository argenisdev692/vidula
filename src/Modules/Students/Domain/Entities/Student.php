<?php

declare(strict_types=1);

namespace Modules\Students\Domain\Entities;

use Modules\Students\Domain\Events\StudentCreated;
use Modules\Students\Domain\Events\StudentUpdated;
use Modules\Students\Domain\ValueObjects\StudentId;
use Shared\Domain\Entities\AggregateRoot;

final class Student extends AggregateRoot
{
    private function __construct(
        public readonly StudentId $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $dni,
        public readonly ?string $birthDate,
        public readonly ?string $address,
        public readonly ?string $avatar,
        public readonly ?string $notes,
        public readonly bool $active,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $deletedAt = null
    ) {}

    public static function create(
        StudentId $id,
        string $name,
        ?string $email = null,
        ?string $phone = null,
        ?string $dni = null,
        ?string $birthDate = null,
        ?string $address = null,
        ?string $avatar = null,
        ?string $notes = null,
        bool $active = true
    ): self {
        $student = new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            dni: $dni,
            birthDate: $birthDate,
            address: $address,
            avatar: $avatar,
            notes: $notes,
            active: $active,
            createdAt: now()->toIso8601String()
        );

        $student->recordEvent(new StudentCreated(
            aggregateId: $id->value,
            name: $name,
            occurredOn: now()->toDateTimeString()
        ));

        return $student;
    }

    public function update(
        string $name,
        ?string $email,
        ?string $phone,
        ?string $dni,
        ?string $birthDate,
        ?string $address,
        ?string $notes,
        bool $active
    ): self {
        $updated = clone($this, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dni' => $dni,
            'birthDate' => $birthDate,
            'address' => $address,
            'notes' => $notes,
            'active' => $active,
            'updatedAt' => now()->toIso8601String()
        ]);

        $updated->recordEvent(new StudentUpdated(
            aggregateId: $this->id->value,
            name: $name,
            occurredOn: now()->toDateTimeString()
        ));

        return $updated;
    }

    public function deactivate(): self
    {
        if (!$this->active) {
            return $this;
        }

        $deactivated = clone($this, [
            'active' => false,
            'updatedAt' => now()->toIso8601String()
        ]);

        return $deactivated;
    }

    public function activate(): self
    {
        if ($this->active) {
            return $this;
        }

        $activated = clone($this, [
            'active' => true,
            'updatedAt' => now()->toIso8601String()
        ]);

        return $activated;
    }

    public function updateAvatar(?string $avatar): self
    {
        return clone($this, [
            'avatar' => $avatar,
            'updatedAt' => now()->toIso8601String()
        ]);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function hasEmail(): bool
    {
        return $this->email !== null && $this->email !== '';
    }

    public function hasPhone(): bool
    {
        return $this->phone !== null && $this->phone !== '';
    }
}
