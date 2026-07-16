# SSD Summary: Meeting / Calendar Scheduling

**Feature ID:** 001-meeting-calendar-scheduling · **Date:** 2026-07-16 (amended 2026-07-17)
**Status:** Implemented, pending developer verification (migrate + pint + tests via WSL)

This document stands alone; each section links back to its source file for more detail.

## 0. Amendment (2026-07-17): FR-5 un-deferred — Google Calendar push-sync + attendee emails
The user decided to build both previously-deferred pieces now:
- **Google Calendar push-sync**: `Domain\Ports\GoogleCalendarSyncPort` +
  `Infrastructure\GoogleCalendar\SpatieGoogleCalendarSyncAdapter`, pushing to the single shared
  calendar configured in `config('google-calendar.calendar_id')` (OAuth2 mode is not natively
  multi-tenant per staff member — confirmed in research.md — so this is one company calendar, not
  per-organizer personal calendars). Every method degrades gracefully (catches `\Throwable`,
  logs, no-ops) — a Google outage or missing OAuth token never blocks meeting CRUD. `meetings`
  gained a `google_event_id` nullable column (merged directly into the original
  `create_meetings_table` migration, not a separate ALTER, since it hadn't been migrated yet).
- **Attendee email notifications**: `MeetingInvitationMail` / `MeetingUpdatedMail` /
  `MeetingCancelledMail`, extending the project's shared `emails.layout` (the newer, actively-used
  layout — not the older bespoke `emails/appointments/partials/*` system). Resolved per-attendee
  via the new `AttendeeEmailResolver` (column-scoped queries, same data-minimization discipline as
  `AttendeeOptionMapper`).
- **Wiring**: three new Domain Events (`MeetingScheduled`, `MeetingUpdated`, `MeetingCancelled`)
  dispatched from `CreateMeetingHandler`/`UpdateMeetingHandler`/`CancelMeetingHandler`, each with
  two independent queued listeners (Google sync + email) registered in
  `MeetingServiceProvider::boot()` — a failure in one never blocks the other.
- **Finding surfaced, not silently fixed**: `config/horizon.php`'s only supervisor watches the
  `default` queue, but several existing `Appointment` mail listeners target an `emails` queue
  Horizon doesn't watch. The new Meeting listeners deliberately do NOT set a custom `$queue`
  (so they land on `default` and actually run under Horizon) — the Appointment discrepancy is
  flagged for the developer's attention, not touched, since it's out of this feature's scope.
- Also in this pass: `User::meetings()` (`HasMany`) added as the reciprocal of
  `MeetingEloquentModel::organizer()` (`BelongsTo`), matching the existing convention
  (`campaigns()`, `portfolios()`, etc. on `User`).
- New test file: `MeetingNotificationsTest` (invitation email + Google push on create, cancellation
  email + Google delete on cancel, graceful no-op when Google Calendar isn't configured).

## 1. Specify — [`spec.md`](spec.md)
The team had installed `spatie/laravel-google-calendar` and `@fullcalendar/vue3` without deciding
where the calendar UI lives. The central question: extend `Appointment` (public lead-intake
pipeline, one implicit attendee) with a calendar, or build a separate `Meeting` module for general
internal scheduling with a mixed attendee selector (users, leads, support contacts)?

**Resolved architecture (§1.1):** a new, separate `Meeting` module. `Appointment` stays untouched.
`Meeting` is the single calendar surface — it owns the only FullCalendar instance, rendering its
own meetings plus a read-only overlay of `Appointment` events via a query-only port.

Actors: staff (organizer/attendee), leads (`Appointment`), support contacts (`ContactSupport`),
system users. Three user stories: US-1 calendar view, US-2 mixed-attendee meetings, US-3 Google
Calendar sync (deferred).

## 2. Clarify — [`clarify.md`](clarify.md)
- **Q1 (blocking):** Separate `Meeting` module vs. calendar-only-inside-Appointment → **resolved
  by direct user decision**: separate module.
- **Q2 (blocking):** Calendar ownership if separate → **resolved by direct user decision**:
  `Meeting` is the single calendar surface, read-only overlay of `Appointment`.
- **Q3:** Google Calendar sync scope → default: deferred to a later iteration.
- **Q4:** Attendee source list → default: exactly three types (user/lead/contact), no ad-hoc guests.
- **Q5:** Recurring meetings → default: out of scope, single occurrence only.

## 3. Research — [`research.md`](research.md)
Five live Tavily queries (`search_depth: advanced`, `time_range: year`):
1. **FullCalendar Vue3 + Laravel/Inertia**: use the built-in JSON-feed event source
   (`events: { url }`), which auto-sends `start`/`end` GET params — no manual `datesSet`/fetch
   wiring needed.
2. **spatie/laravel-google-calendar v3.8**: OAuth2 mode is single-account, not natively
   multi-tenant — confirms deferring per-staff-member sync was the right call.
3. **Polymorphic attendees**: `morphTo` + explicit `Relation::morphMap()` is the correct pattern;
   this is the first polymorphic relation in the codebase.
4. **spatie/laravel-data**: no first-class polymorphic DTO support — a flat `{type, uuid}`
   discriminator (`MeetingAttendeeData`) is the right shape, matching the codebase's existing
   `Rule::in`-validated-enum style.
5. **OWASP API Top 10**: BOLA (API1), mass assignment (API3), unrestricted resource consumption
   (API4), and broken function-level auth (API5) all map directly to concrete design decisions
   (object-level auth check, server-set `organizer_id`, 92-day feed cap, dedicated bulk permissions).

Codebase grounding (not Tavily, found while reading the actual repo): `AppointmentFilterData`
already has `scheduledFrom`/`scheduledTo` filters, so the Appointment overlay needed **zero new
code** on the Appointment side — just a new adapter in `Meeting/Infrastructure` calling the
existing `AppointmentRepositoryPort::paginate()`.

## 4. Plan — [`plan.md`](plan.md)
Hexagonal `Meeting` module mirroring `Appointment`/`Availability` exactly: `Meeting` +
`MeetingAttendee` (polymorphic) entities; `MeetingRepositoryPort` + `AppointmentCalendarFeedPort`
(anti-corruption boundary, same pattern as the existing `AvailabilityPort`); API contracts for
CRUD, bulk ops, calendar feed, and attendee search; security section applying the OWASP findings
above; full traceability table linking every spec requirement to a plan section.

## 5. Tasks — [`tasks.md`](tasks.md)
83 tasks across foundations → persistence → per-user-story → cross-cutting → frontend → closeout.
All code tasks are complete; three closeout tasks (T080–T082: pint, migrate, test) are WSL-only
commands this environment cannot execute (per this repo's absolute rule) and are left for the
developer to run — commands are listed below.

## 6. Analyze — [`analyze.md`](analyze.md)
No gaps or contradictions found between spec, plan, and tasks before implementation began.

## 7. Implement
**Backend** (`src/Modules/Meeting/`): full hexagonal module — Domain (2 enums, 2 ports, 1
exception), Application (6 DTOs, 7 commands, 4 queries), Infrastructure (2 Eloquent models +
factory, 1 repository, 1 cross-module adapter, 2 attendee resolvers, 4 controllers, 1 export
transformer, routes), `MeetingServiceProvider`. Plus 2 new migrations, a new PDF export view,
`MEETINGS` added to `RolePermissionSeeder`, and provider registration in `bootstrap/providers.php`.
2 feature test files (`MeetingManagementTest`, `MeetingCalendarFeedTest`) covering CRUD, bulk
ops, validation, the calendar feed/overlay, attendee-search data-minimization, and the BOLA
regression case (non-organizer without `VIEW_ANY_MEETINGS` gets 403).

**Frontend** (`resources/js/`): `modules/meeting/` (types, query-param builder, date formatter,
Zod schema), `common/meeting/AttendeePicker.vue`, `Pages/meetings/` (Index with a Calendar/List
toggle, Create, Edit, Show, plus `MeetingsTable`/`MeetingForm`/`MeetingCalendar` components), and
a new nav entry. Two deliberate, logged deviations from the generic `/frontend-new` skill
checklist in favor of this codebase's actual, established convention (see `tasks.md` §0): Inertia
partial-reload + `useForm`/Zod instead of Pinia Colada/`@primevue/forms`.

**Notable decisions made during implementation** (beyond the original task list, all logged in
`tasks.md`): object-level organizer-or-permission authorization on every mutating endpoint (T039);
`AttendeeOptionMapper` resolves the Edit form's attendee prefill via column-scoped queries instead
of eager-loading the full `attendable` morph target, because a `User` row's
`two_factor_secret`/`two_factor_recovery_codes` are not in `User::$hidden` and would otherwise have
leaked into a cached Inertia payload (T055); dangling attendee uuids return a 422
`ValidationException`, not an uncaught 500.

**Traceability check (spec → code):**

| Requirement | Delivered |
|---|---|
| US-1 (calendar view) | `MeetingCalendarController` + `MeetingCalendar.vue` |
| US-2 (mixed attendees) | `AttendeeResolver` + `MeetingAttendeeData` + `AttendeePicker.vue` |
| US-3 (Google sync) | Not built — deferred per Q3, `spatie/laravel-google-calendar` remains uninstalled-in-use |
| FR-1–FR-4 | Delivered as designed |
| FR-5 (Google sync) | Deferred |
| NFR-Security | Object-level auth, minimal-field attendee search, capped calendar range |
| NFR-Performance | No N+1 (column-scoped `with()`, windowed queries), 92-day feed cap |

**Known gap flagged for a fast-follow (not blocking):** no double-booking/overlap validation for
an organizer or attendee across meetings — explicitly out of scope per `spec.md` §8.

## 8. Outstanding — developer action required
This environment cannot execute `./vendor/bin/sail` (WSL-only per this repo's rules). Run in WSL:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail bin pint --dirty --format agent
./vendor/bin/sail artisan test --compact --filter=Meeting
./vendor/bin/sail artisan db:seed --class="Database\Seeders\RolePermissionSeeder"
./vendor/bin/sail npm run build
```

Once these pass, `tasks.md` T080–T083 can be checked off and this summary's status updated to
"Verified."
