<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Export;

use Modules\Students\Application\Queries\ReadModels\StudentReadModel;

final class StudentExportTransformer
{
    #[\NoDiscard]
    public static function transformForExcel(StudentReadModel $student): array
    {
        return $student
            |> self::extractBaseData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    #[\NoDiscard]
    public static function transformForPdf(StudentReadModel $student): array
    {
        return $student
            |> self::extractPdfData(...)
            |> self::formatDates(...)
            |> self::sanitizeOutput(...);
    }

    private static function extractBaseData(StudentReadModel $student): array
    {
        return [
            'uuid' => $student->uuid,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->phone,
            'dni' => $student->dni,
            'birth_date' => $student->birthDate,
            'address' => $student->address,
            'status' => $student->status,
            'active' => $student->active ? 'Yes' : 'No',
            'created_at' => $student->createdAt,
        ];
    }

    private static function extractPdfData(StudentReadModel $student): array
    {
        return [
            'uuid' => $student->uuid,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->phone,
            'dni' => $student->dni,
            'status' => $student->status,
            'active' => $student->active ? 'Yes' : 'No',
            'created_at' => $student->createdAt,
        ];
    }

    private static function formatDates(array $data): array
    {
        foreach (['birth_date', 'created_at'] as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && $data[$field] !== '') {
                try {
                    $date = new \DateTimeImmutable($data[$field]);
                    $data[$field] = $date->format('F j, Y');
                } catch (\Exception) {
                }
            }
        }

        return $data;
    }

    private static function sanitizeOutput(array $data): array
    {
        return array_map(static fn (mixed $value): string => (string) ($value ?? '—'), $data);
    }
}
