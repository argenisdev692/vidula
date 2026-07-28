<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Products\Application\DTOs\UpdateScriptData;
use Modules\Products\Domain\Ports\ProductScriptRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductScriptEloquentModel;
use Shared\Domain\Ports\AuditPort;

/**
 * Human review pass over one generated script (spec US-6). Only the sections
 * the operator actually sent are written, so patching the outro never blanks
 * the body.
 */
final readonly class UpdateScriptHandler
{
    public function __construct(
        private ProductScriptRepositoryPort $scripts,
        private AuditPort $audit,
    ) {}

    #[\NoDiscard]
    public function handle(
        ProductScriptEloquentModel $script,
        UpdateScriptData $data,
        ?object $causer = null,
    ): ProductScriptEloquentModel {
        $attributes = $data->toAttributes();

        if ($attributes === []) {
            return $script;
        }

        $updated = DB::transaction(fn (): ProductScriptEloquentModel => $this->scripts->update($script, $attributes));

        $this->audit->log(
            event: 'products.script.reviewed',
            subject: $updated,
            properties: [
                'sections' => array_keys($attributes),
                'status' => $data->status,
            ],
            causer: $causer,
            logName: 'products',
        );

        return $updated;
    }
}
