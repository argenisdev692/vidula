<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Http\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Clients\Application\Queries\ReadModels\ClientReadModel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Clients\Application\DTOs\ClientFilterDTO;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

/**
 * ClientExcelExport — Excel export using repository and transformer
 * 
 * Follows hexagonal architecture by using repository port instead of direct Eloquent access.
 */
final class ClientExcelExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithStyles
{
    use Exportable;

    public function __construct(
        private readonly ClientFilterDTO $filters,
    ) {
    }

    /**
     * Get collection of clients from repository
     */
    public function query(): Builder
    {
        $filters = $this->filters->toArray();

        $query = ClientEloquentModel::query()
            ->select([
                'id',
                'uuid',
                'user_id',
                'client_name',
                'email',
                'phone',
                'address',
                'website',
                'facebook_link',
                'instagram_link',
                'linkedin_link',
                'twitter_link',
                'latitude',
                'longitude',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->with(['user:id,uuid']);

        $status = is_string($filters['status'] ?? null) ? $filters['status'] : '';

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status !== 'active') {
            $query->withTrashed();
        }

        return $query
            ->when($this->filters->userUuid, function (Builder $builder, string $userUuid): Builder {
                return $builder->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->where('uuid', $userUuid));
            })
            ->when($this->filters->search, fn (Builder $builder, string $search): Builder => $builder->where('client_name', 'like', "%{$search}%"))
            ->inDateRange($this->filters->dateFrom, $this->filters->dateTo)
            ->orderBy($this->filters->sortBy ?? 'created_at', $this->filters->sortDir ?? 'desc');
    }

    /**
     * Define Excel headings
     */
    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'Client Name',
            'Email',
            'Phone',
            'Address',
            'Website',
            'Facebook',
            'Instagram',
            'LinkedIn',
            'Twitter',
            'Latitude',
            'Longitude',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map client entity to array using transformer with pipe operator
     */
    public function map($client): array
    {
        return ClientExportTransformer::transformForExcel(new ClientReadModel(
            uuid: $client->uuid,
            userUuid: $client->user?->uuid ?? '',
            clientName: $client->client_name,
            email: $client->email,
            phone: $client->phone,
            address: $client->address,
            nif: null,
            socialLinks: [
                'website' => $client->website,
                'facebook' => $client->facebook_link,
                'instagram' => $client->instagram_link,
                'linkedin' => $client->linkedin_link,
                'twitter' => $client->twitter_link,
            ],
            coordinates: [
                'latitude' => $client->latitude,
                'longitude' => $client->longitude,
            ],
            createdAt: $client->created_at?->toIso8601String(),
            updatedAt: $client->updated_at?->toIso8601String(),
            deletedAt: $client->deleted_at?->toIso8601String(),
        ));
    }

    /**
     * Excel sheet title
     */
    public function title(): string
    {
        return 'Clients Export';
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

