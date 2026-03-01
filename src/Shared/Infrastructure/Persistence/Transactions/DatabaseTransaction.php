<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Transactions;

use Illuminate\Support\Facades\DB;

final class DatabaseTransaction implements TransactionInterface
{
    public function begin(): void
    {
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
    }

    public function rollback(): void
    {
        DB::rollBack();
    }

    public function wrap(callable $action): mixed
    {
        return DB::transaction($action);
    }
}
