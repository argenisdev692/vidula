<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Clients\Application\DTOs\ClientData;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

final readonly class CreateClientHandler
{
    public function __construct(private ClientRepositoryPort $clients) {}

    #[\NoDiscard]
    public function handle(ClientData $data, int $userId): ClientEloquentModel
    {
        return DB::transaction(fn () => $this->clients->create([
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
            'user_id' => $userId,
        ]));
    }
}
