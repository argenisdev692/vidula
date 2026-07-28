<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Export;

use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Shared\Infrastructure\Support\PhoneFormatter;

/**
 * Maps a student row to CSV / Excel / PDF columns (BACKEND-PHP §8). Soft-delete
 * Status is Active|Suspended; lifecycle stays in a separate Lifecycle column.
 */
final readonly class StudentExportTransformer
{
    /**
     * @return array{Name: string, Email: string, Phone: string, DNI: string, Lifecycle: string, Active: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForTable(StudentEloquentModel $student): array
    {
        return $student
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    /**
     * @return array{Name: string, Email: string, Phone: string, DNI: string, Lifecycle: string, Active: string, Created: string, Status: string}
     */
    #[\NoDiscard]
    public static function transformForPdf(StudentEloquentModel $student): array
    {
        return self::transformForTable($student);
    }

    /**
     * @return array{Name: string, Email: string, Phone: string, DNI: string, Lifecycle: string, Active: string, Created: string, Status: string}
     */
    private static function extractBaseData(StudentEloquentModel $student): array
    {
        return [
            'Name' => $student->name,
            'Email' => $student->email ?? '',
            'Phone' => PhoneFormatter::national($student->phone),
            'DNI' => $student->dni ?? '',
            'Lifecycle' => $student->status,
            'Active' => $student->active ? 'Yes' : 'No',
            'Created' => $student->created_at?->toIso8601String() ?? '',
            'Status' => $student->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * @param  array{Name: string, Email: string, Phone: string, DNI: string, Lifecycle: string, Active: string, Created: string, Status: string}  $data
     * @return array{Name: string, Email: string, Phone: string, DNI: string, Lifecycle: string, Active: string, Created: string, Status: string}
     */
    private static function formatDates(array $data): array
    {
        if ($data['Created'] !== '') {
            try {
                $data['Created'] = (new \DateTimeImmutable($data['Created']))->format('F j, Y');
            } catch (\Exception) {
                // Keep original value if parsing fails.
            }
        }

        return $data;
    }

    /**
     * @param  array{Name: string, Email: string, Phone: string, DNI: string, Lifecycle: string, Active: string, Created: string, Status: string}  $data
     * @return array{Name: string, Email: string, Phone: string, DNI: string, Lifecycle: string, Active: string, Created: string, Status: string}
     */
    private static function sanitizeOutput(array $data): array
    {
        return array_map(
            static fn (string $value): string => $value === '' ? '—' : $value,
            $data,
        );
    }
}
