<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence;

use Shared\Infrastructure\Persistence\Transactions\TransactionInterface;

final class UnitOfWork
{
    private array $actions = [];

    public function __construct(
        private readonly TransactionInterface $transaction
    ) {
    }

    public function register(callable $action): void
    {
        $this->actions[] = $action;
    }

    public function commit(): void
    {
        $this->transaction->wrap(function () {
            foreach ($this->actions as $action) {
                $action();
            }
        });
        $this->actions = [];
    }
}
