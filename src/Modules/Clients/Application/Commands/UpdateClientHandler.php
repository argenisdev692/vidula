<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Modules\Clients\Application\DTOs\ClientData;
use Modules\Clients\Application\Support\ClientCacheKeys;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

final readonly class UpdateClientHandler
{
    public function __construct(
        private ClientRepositoryPort $clients,
        private Cache $cache,
    ) {}

    #[\NoDiscard]
    public function handle(ClientEloquentModel $client, ClientData $data): ClientEloquentModel
    {
        $updated = DB::transaction(fn () => $this->clients->update($client, [
            'client_name' => $data->clientName,
            'email' => $data->email,
            'status' => $data->status,
            'phone' => $data->phone,
            'address' => $data->address,
            'tax_id' => $data->taxId,
            'nif' => $data->nif,
            'website' => $data->website,
            'facebook_link' => $data->facebookLink,
            'instagram_link' => $data->instagramLink,
            'linkedin_link' => $data->linkedinLink,
            'twitter_link' => $data->twitterLink,
            'notes' => $data->notes,
        ]));

        $this->cache->forget(ClientCacheKeys::client($updated->uuid));

        return $updated;
    }
}
