<?php

declare(strict_types=1);

namespace Modules\Students\Domain\Entities;

use Modules\Students\Domain\Events\StudentCreated;
use Modules\Students\Domain\Events\StudentUpdated;
use Modules\Students\Domain\ValueObjects\StudentId;
use Shared\Domain\Entities\AggregateRoot;

final class Student extends AggregateRoot
{
    public function __construct(
        public StudentId $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $dni,
        public ?string $birthDate,
        public ?string $address,
        public ?string $avatar,
        public ?string $notes,
        public string $status,
        public bool $active,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null
    ) {
    }

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
        string $status = 'DRAFT',
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
            status: $status,
            active: $active,
            createdAt: date('c')
        );

        $student->recordDomainEvent(new StudentCreated(
            aggregateId: $id->value,
            name: $name,
            occurredOn: date('c')
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
        ?string $avatar,
        ?string $notes,
        string $status,
        bool $active
    ): self {
        $updated = clone($this, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'dni' => $dni,
            'birthDate' => $birthDate,
            'address' => $address,
            'avatar' => $avatar,
            'notes' => $notes,
            'status' => $status,
            'active' => $active,
            'updatedAt' => date('c'),
        ]);

        $updated->recordDomainEvent(new StudentUpdated(
            aggregateId: $this->id->value,
            name: $name,
            occurredOn: date('c')
        ));

        return $updated;
    }

    public function deactivate(): self
    {
        if (!$this->active) {
            return $this;
        }

        return clone($this, [
            'active' => false,
            'updatedAt' => date('c'),
        ]);
    }

    public function activate(): self
    {
        if ($this->active) {
            return $this;
        }

        return clone($this, [
            'active' => true,
            'updatedAt' => date('c'),
        ]);
    }

    public function updateAvatar(?string $avatar): self
    {
        return clone($this, [
            'avatar' => $avatar,
            'updatedAt' => date('c'),
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
