<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Meeting scheduling
|--------------------------------------------------------------------------
|
| Fixed slot length for internal meetings. The UI collects a single start
| datetime; Create/Update handlers derive `ends_at = starts_at + duration`.
| Keep `ends_at` persisted for calendar range queries and Google sync.
|
*/

return [

    'duration_minutes' => (int) env('MEETING_DURATION_MINUTES', 30),

];
