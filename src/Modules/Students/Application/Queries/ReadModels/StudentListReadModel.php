<?php
declare(strict_types=1);
namespace Modules\Students\Application\Queries\ReadModels;
use Spatie\LaravelData\Data;

class StudentListReadModel extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public bool $active,
        public ?string $created_at,
        public ?string $deleted_at
    ) {}
}
