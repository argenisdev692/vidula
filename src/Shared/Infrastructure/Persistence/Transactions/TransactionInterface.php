<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Transactions;

interface TransactionInterface
{
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
    public function wrap(callable $action): mixed;
}
