<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Application\DTOs\UserFilterDTO;

final class UserPdfExport
{
    public function __construct(
        private readonly UserFilterDTO $filters,
        private readonly UserRepositoryPort $repository
    ) {
    }

    public function stream(): Response
    {
        // Fetch users through repository (domain layer)
        $result = $this->repository->findAllPaginated(
            filters: $this->filters->toArray(),
            page: 1,
            perPage: 1000 // Large limit for export
        );

        // Transform domain entities to array for view using pipe operator
        $rows = $result['data']
            |> (fn($users) => array_map(self::transformUserForPdf(...), $users));

        $pdf = Pdf::loadView('exports.pdf.users', [
            'title' => 'Users Report',
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'rows' => $rows,
        ]);

        return $pdf->stream('users-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Transform domain User entity to array for PDF view
     */
    private static function transformUserForPdf($user): array
    {
        return [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'last_name' => $user->lastName,
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'city' => $user->city ?? '',
            'created_at' => $user->createdAt ?? '',
        ];
    }
}

