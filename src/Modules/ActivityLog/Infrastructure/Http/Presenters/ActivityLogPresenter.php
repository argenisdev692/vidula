<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Infrastructure\Http\Presenters;

use Spatie\Activitylog\Models\Activity;

/**
 * Maps a spatie {@see Activity} row to the snake_case shape the Vue module
 * consumes. Kept out of the Eloquent model (the user chose NOT to extend the
 * Spatie model), so mapping lives here and is reused by the list paginator, the
 * detail view and the export transformer (DRY).
 */
final readonly class ActivityLogPresenter
{
    /**
     * Lean projection for the DataTable list (no heavy JSON blobs).
     *
     * @return array<string, mixed>
     */
    public static function toListItem(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => self::shortType($activity->subject_type),
            'subject_id' => $activity->subject_id,
            'causer_id' => $activity->causer_id,
            'causer_label' => self::causerLabel($activity),
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    /**
     * Full projection for the detail screen — includes the properties and
     * attribute-change JSON blobs.
     *
     * @return array<string, mixed>
     */
    public static function toDetail(Activity $activity): array
    {
        return [
            ...self::toListItem($activity),
            'causer_type' => self::shortType($activity->causer_type),
            'properties' => $activity->properties?->toArray() ?? null,
            'attribute_changes' => self::rawJson($activity, 'attribute_changes'),
            'updated_at' => $activity->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Human label for the actor. `causer` is polymorphic; Users expose
     * first/last name, other models may expose `name` or `email`.
     */
    public static function causerLabel(Activity $activity): ?string
    {
        $causer = $activity->causer;

        return match (true) {
            $causer === null => null,
            isset($causer->first_name) => trim("{$causer->first_name} {$causer->last_name}") ?: ($causer->email ?? null),
            isset($causer->name) => (string) $causer->name,
            isset($causer->email) => (string) $causer->email,
            default => "#{$activity->causer_id}",
        };
    }

    public static function shortType(?string $type): ?string
    {
        return $type === null ? null : class_basename($type);
    }

    /**
     * Reads a JSON column that the default Spatie model does not cast (this
     * project's migration adds `attribute_changes`).
     *
     * @return array<string, mixed>|null
     */
    private static function rawJson(Activity $activity, string $column): ?array
    {
        $value = $activity->getAttribute($column);

        return match (true) {
            $value === null => null,
            is_array($value) => $value,
            is_string($value) => json_decode($value, true) ?: null,
            default => null,
        };
    }
}
