<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic attendee link. `attendable_type` stores the morphMap key
 * ('user' | 'lead' | 'contact', registered in `MeetingServiceProvider`), never
 * a raw FQCN — see research.md §3.
 *
 * @property int $id
 * @property int $meeting_id
 * @property string $attendable_type
 * @property int $attendable_id
 *
 * @mixin \Eloquent
 */
#[Table('meeting_attendees')]
#[Fillable(['meeting_id', 'attendable_type', 'attendable_id'])]
final class MeetingAttendeeEloquentModel extends Model
{
    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    /**
     * @return BelongsTo<MeetingEloquentModel, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(MeetingEloquentModel::class, 'meeting_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }
}
