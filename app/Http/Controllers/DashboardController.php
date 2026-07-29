<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

/**
 * Authenticated application home. Renders the Vue `Dashboard` page with
 * software-oriented stats (Users / Students / Classrooms / AI Generations),
 * a New Products monthly chart, and an Activity Log-style feed.
 *
 * When a table is missing or empty, counts fall back to small demo fillers
 * (6–14) so the UI is never stuck on zeros during early development.
 */
final class DashboardController extends Controller
{
    /** @var array{users: int, students: int, classrooms: int, ai_generations: int} */
    private const array FILLER_COUNTS = [
        'users' => 12,
        'students' => 8,
        'classrooms' => 6,
        'ai_generations' => 10,
    ];

    /** @var list<int> */
    private const array FILLER_PRODUCT_SERIES = [6, 8, 7, 10, 9, 12, 11, 8, 10, 14, 12, 9];

    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->stats(),
            'productSeries' => $this->productSeries(),
            'activities' => $this->activities(),
        ]);
    }

    /**
     * @return array{
     *     users: int,
     *     students: int,
     *     classrooms: int,
     *     ai_generations: int
     * }
     */
    private function stats(): array
    {
        return [
            'users' => $this->countOrFiller('users', User::class, self::FILLER_COUNTS['users']),
            'students' => $this->countOrFiller('students', StudentEloquentModel::class, self::FILLER_COUNTS['students']),
            'classrooms' => $this->countOrFiller('classrooms', ClassroomEloquentModel::class, self::FILLER_COUNTS['classrooms']),
            'ai_generations' => $this->countOrFiller('content_generations', ContentGenerationEloquentModel::class, self::FILLER_COUNTS['ai_generations']),
        ];
    }

    /**
     * @param  class-string  $model
     */
    private function countOrFiller(string $table, string $model, int $filler): int
    {
        if (! Schema::hasTable($table)) {
            return $filler;
        }

        $count = (int) $model::query()->count();

        return $count > 0 ? $count : $filler;
    }

    /**
     * Monthly new-product counts for the chart (Jan→Dec). Falls back to a
     * modest demo series when the products table is empty or missing.
     *
     * @return list<int>
     */
    private function productSeries(): array
    {
        if (! Schema::hasTable('products')) {
            return self::FILLER_PRODUCT_SERIES;
        }

        $monthExpression = $this->monthOfCreatedAtExpression();

        $rows = ProductEloquentModel::query()
            ->selectRaw("{$monthExpression} as month, COUNT(*) as total")
            ->whereYear('created_at', now()->year)
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month');

        if ($rows->isEmpty()) {
            return self::FILLER_PRODUCT_SERIES;
        }

        $series = [];

        for ($month = 1; $month <= 12; $month++) {
            $series[] = (int) ($rows[$month] ?? $rows[(string) $month] ?? 0);
        }

        return array_sum($series) > 0 ? $series : self::FILLER_PRODUCT_SERIES;
    }

    /**
     * Latest activity-log rows shaped for the marquee feed. Demo fillers when
     * the log is empty so the panel never looks broken.
     *
     * @return list<array{
     *     id: string,
     *     user: string,
     *     initials: string,
     *     action: string,
     *     target: string,
     *     time: string,
     *     icon: string,
     *     iconColor: string
     * }>
     */
    private function activities(): array
    {
        if (! Schema::hasTable('activity_log')) {
            return $this->fillerActivities();
        }

        $rows = DB::table('activity_log')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'description', 'event', 'log_name', 'created_at', 'properties']);

        if ($rows->isEmpty()) {
            return $this->fillerActivities();
        }

        return $rows->map(function (object $row, int $index): array {
            $description = (string) ($row->description ?: $row->event ?: 'updated');
            $target = (string) ($row->log_name ?: 'system');

            return [
                'id' => (string) $row->id,
                'user' => 'System',
                'initials' => 'SY',
                'action' => $description,
                'target' => $target,
                'time' => $this->relativeTime((string) $row->created_at),
                'icon' => match (true) {
                    str_contains(strtolower($description), 'creat') => 'pi-plus-circle',
                    str_contains(strtolower($description), 'delet') => 'pi-trash',
                    str_contains(strtolower($description), 'login') => 'pi-sign-in',
                    default => 'pi-history',
                },
                'iconColor' => match ($index % 4) {
                    0 => 'var(--accent-primary)',
                    1 => 'var(--accent-info)',
                    2 => 'var(--accent-success)',
                    default => 'var(--accent-warning)',
                },
            ];
        })->values()->all();
    }

    /**
     * @return list<array{
     *     id: string,
     *     user: string,
     *     initials: string,
     *     action: string,
     *     target: string,
     *     time: string,
     *     icon: string,
     *     iconColor: string
     * }>
     */
    private function fillerActivities(): array
    {
        return [
            ['id' => '1', 'user' => 'Ana Ruiz', 'initials' => 'AR', 'action' => 'invited user', 'target' => 'dev@vidula.app', 'time' => '2 min ago', 'icon' => 'pi-user-plus', 'iconColor' => 'var(--accent-primary)'],
            ['id' => '2', 'user' => 'Luis Mora', 'initials' => 'LM', 'action' => 'enrolled student', 'target' => 'Cohort A', 'time' => '12 min ago', 'icon' => 'pi-graduation-cap', 'iconColor' => 'var(--accent-info)'],
            ['id' => '3', 'user' => 'Sofía Vega', 'initials' => 'SV', 'action' => 'opened classroom', 'target' => 'Room 3', 'time' => '35 min ago', 'icon' => 'pi-building', 'iconColor' => 'var(--accent-success)'],
            ['id' => '4', 'user' => 'Carlos Díaz', 'initials' => 'CD', 'action' => 'ran AI generation', 'target' => 'Product script', 'time' => '1 hr ago', 'icon' => 'pi-sparkles', 'iconColor' => 'var(--accent-warning)'],
            ['id' => '5', 'user' => 'Ana Ruiz', 'initials' => 'AR', 'action' => 'published product', 'target' => 'API Course', 'time' => '2 hr ago', 'icon' => 'pi-box', 'iconColor' => 'var(--accent-primary)'],
            ['id' => '6', 'user' => 'Luis Mora', 'initials' => 'LM', 'action' => 'updated permissions', 'target' => 'EDITOR role', 'time' => '3 hr ago', 'icon' => 'pi-shield', 'iconColor' => 'var(--accent-info)'],
            ['id' => '7', 'user' => 'Sofía Vega', 'initials' => 'SV', 'action' => 'restored student', 'target' => 'STU-014', 'time' => '5 hr ago', 'icon' => 'pi-check-circle', 'iconColor' => 'var(--accent-success)'],
            ['id' => '8', 'user' => 'Carlos Díaz', 'initials' => 'CD', 'action' => 'exported report', 'target' => 'products.xlsx', 'time' => '6 hr ago', 'icon' => 'pi-download', 'iconColor' => 'var(--accent-warning)'],
            ['id' => '9', 'user' => 'Ana Ruiz', 'initials' => 'AR', 'action' => 'generated social package', 'target' => 'TOFU hook', 'time' => '8 hr ago', 'icon' => 'pi-share-alt', 'iconColor' => 'var(--accent-primary)'],
            ['id' => '10', 'user' => 'Luis Mora', 'initials' => 'LM', 'action' => 'logged activity', 'target' => 'dashboard.view', 'time' => '10 hr ago', 'icon' => 'pi-history', 'iconColor' => 'var(--accent-info)'],
        ];
    }

    private function monthOfCreatedAtExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => 'CAST(EXTRACT(MONTH FROM created_at) AS INTEGER)',
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            default => 'MONTH(created_at)',
        };
    }

    private function relativeTime(string $timestamp): string
    {
        try {
            $diff = now()->diffInMinutes(\Carbon\CarbonImmutable::parse($timestamp));
        } catch (\Throwable) {
            return 'just now';
        }

        return match (true) {
            $diff < 1 => 'just now',
            $diff < 60 => "{$diff} min ago",
            $diff < 1440 => ((int) floor($diff / 60)).' hr ago',
            default => ((int) floor($diff / 1440)).' d ago',
        };
    }
}
