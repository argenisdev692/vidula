# Technical plan: Meeting

> Phase 4 · PLAN — Defines HOW it's built, verified against `research.md`.
> Every technical decision here is traceable to `spec.md` or a `research.md` finding.

**Feature ID:** 001-meeting-calendar-scheduling
**Based on:** spec.md (resolved 2026-07-16), research.md

## 1. Technical summary
A new `Meeting` module (hexagonal, mirroring `Appointment`/`Availability` conventions exactly) owns
internal scheduling: staff create meetings with a polymorphic attendee list (`User`, `Appointment`
lead, `ContactSupport` contact) and view them on a single FullCalendar surface that also overlays
existing `Appointment` events, read-only, through a new anti-corruption port
(`AppointmentCalendarFeedPort`). `Appointment`'s domain, routes, and public booking contract are
untouched — the only Appointment-side reuse is its **already-existing**
`AppointmentRepositoryPort::paginate()` + `AppointmentFilterData::scheduledFrom/scheduledTo`
filters (confirmed while reading `AppointmentFilterData.php`), so zero new code is required on the
Appointment side. Google Calendar sync (FR-5) is explicitly deferred — nothing in this plan wires
`spatie/laravel-google-calendar`.

## 2. Technology stack (verified with real-time research)
| Component | Choice | Verified version | Source / justification |
|---|---|---|---|
| Calendar UI | `@fullcalendar/vue3` + `dayGrid`/`timeGrid`/`list`/`interaction` plugins | ^6.1.21 (already in package.json) | research.md §1 |
| Calendar data fetch | Dedicated JSON range endpoint (`?start&end`), fetched on FullCalendar's `datesSet`, not Inertia props | — | research.md §1 |
| Backend framework | Laravel 13 / PHP 8.5, hexagonal module (existing convention) | — | project CLAUDE.md, mirrors `Appointment`/`Availability` |
| Attendee modeling | Polymorphic `morphTo` + explicit `Relation::morphMap` registered in `MeetingServiceProvider::boot()` | — | research.md §3 (no existing morphMap in codebase — this module introduces the first one) |
| Attendee DTO | Flat `MeetingAttendeeData[]` with `type` discriminator (`Rule::in`), no custom polymorphic caster | — | research.md §4 (`spatie/laravel-data` has no first-class polymorphic support) |
| Cross-module Appointment read | Reuse existing `AppointmentRepositoryPort::paginate()` + `AppointmentFilterData` (no new Appointment code) | — | confirmed by reading `AppointmentFilterData.php` during planning |
| Google Calendar sync | **Deferred**, not built | `spatie/laravel-google-calendar` ^3.8 (installed, unused) | research.md §2, clarify.md Q3 |
| Authorization | Spatie `permission:*` gates, `MEETINGS` added to `RolePermissionSeeder::MODULES` | — | research.md §5 (API1/API5), existing seeder pattern |

No stack choice in this table is `[UNVERIFIED]`.

## 3. Architecture
Hexagonal module at `src/Modules/Meeting`, identical layering to `Appointment`:

- **Domain**: `Meeting` aggregate (organizer + time range + status), `MeetingAttendee` value
  concept (polymorphic), `AttendeeType` enum (`User='user'`, `Lead='lead'`, `Contact='contact'` —
  doubles as the `morphMap` keys), ports (`MeetingRepositoryPort`,
  `AppointmentCalendarFeedPort`).
- **Application**: Commands (Create/Update/Cancel/Delete/Restore/BulkDelete/BulkRestore),
  Queries (Get/List/GetCalendarFeed/SearchAttendees), DTOs (`MeetingData`, `CreateMeetingData`,
  `UpdateMeetingData`, `MeetingFilterData`, `MeetingAttendeeData`, `CalendarEventData` — the
  shared read-model both Meeting's own events and the Appointment overlay map into).
- **Infrastructure**: Eloquent models (`MeetingEloquentModel`, `MeetingAttendeeEloquentModel`),
  `EloquentMeetingRepository`, `AppointmentCalendarFeedAdapter` (implements
  `AppointmentCalendarFeedPort` by calling the existing `AppointmentRepositoryPort::paginate()`
  with `scheduledFrom`/`scheduledTo` — no Appointment code changes), HTTP controllers, routes.
- **Providers**: `MeetingServiceProvider` binds both ports, registers the `morphMap`, registers
  `web.php`.

Request flow for the calendar view: `GET /meetings/calendar-feed?start&end` →
`GetMeetingCalendarFeedHandler` → (a) queries `Meeting` rows in range via
`MeetingRepositoryPort`, (b) calls `AppointmentCalendarFeedPort::between($from, $to)` → both
mapped to `CalendarEventData[]` (with `source: 'meeting'|'appointment'`) → single JSON array
consumed by `@fullcalendar/vue3`.

Request flow for attendee selection: `GET /meetings/attendees/search?q=&type=` →
`SearchAttendeesHandler` queries `User`/`Appointment`/`ContactSupport` by name/email, returns only
`{ type, uuid, label }` — never full records (OWASP data-minimization, research.md §5).

## 4. Data model (physical schema)
```
meetings
- id: bigint (PK, auto-increment)
- uuid: string (unique) — route binding, never the PK
- organizer_id: bigint (FK -> users.id, indexed)
- title: string(255)
- description: text, nullable
- starts_at: datetime (indexed)
- ends_at: datetime
- status: string — enum-backed (Scheduled | Cancelled)
- created_at, updated_at, deleted_at (soft delete — bulk-restore requirement)

meeting_attendees
- id: bigint (PK)
- meeting_id: bigint (FK -> meetings.id, cascade on delete)
- attendable_type: string — morphMap key ('user' | 'lead' | 'contact')
- attendable_id: bigint — internal auto-increment id of the target row (NOT its uuid;
  confirmed via research.md §3 resolution: users/appointments/contact_supports all use
  `$table->id()` as real PK + separate `uuid` column)
- created_at, updated_at
- UNIQUE (meeting_id, attendable_type, attendable_id) — prevents duplicate invites
- INDEX (attendable_type, attendable_id) — reverse lookup ("meetings X is invited to")
```
Relationships: `Meeting belongsTo User (organizer)`, `Meeting hasMany MeetingAttendee`,
`MeetingAttendee morphTo attendable`. No FK constraint from `meeting_attendees.attendable_id` to
three different tables (polymorphic — enforced at the application layer, consistent with the
tradeoff noted in research.md §3).

## 5. API contracts
All routes under `web` + `auth` + `throttle:60,1`, `prefix('meetings')`, `permission:*_MEETINGS`
gates — mirrors `Appointment`'s `web.php` exactly (static segments before `{uuid}`).

### GET /meetings
- **Story:** US-1 (list/manage, bulk ops entry point)
- **Auth:** `VIEW_ANY_MEETINGS`
- **Response:** Inertia page, paginated list (organizer, title, range, attendee count, status)

### GET /meetings/calendar-feed?start=Y-m-d&end=Y-m-d
- **Story:** US-1, US-2
- **Auth:** `VIEW_ANY_MEETINGS`
- **Response 200:** `{ data: CalendarEventData[] }` — combines own meetings + read-only
  Appointment overlay
- **Errors:** range silently capped at 92 days (mirrors `AvailabilityCalendarController::MAX_DAYS`, research.md §5 API4)

### GET /meetings/attendees/search?q=&type=user|lead|contact
- **Story:** US-2
- **Auth:** `CREATE_MEETINGS` or `UPDATE_MEETINGS`
- **Response 200:** `{ data: { type, uuid, label }[] }` — minimal fields only (§5 API3)

### GET /meetings/create · POST /meetings
- **Story:** US-2 · **Auth:** `CREATE_MEETINGS`
- **Request (`CreateMeetingData`):** `title, description?, starts_at, ends_at, attendees: {type, uuid}[]`
- **Response:** redirect to `meetings.show`

### GET /meetings/{uuid} · GET /meetings/{uuid}/edit · PUT /meetings/{uuid}
- **Auth:** `VIEW_MEETINGS` / `UPDATE_MEETINGS`
- **Authorization rule (§5 API1):** organizer OR holder of `UPDATE_MEETINGS`/`VIEW_ANY_MEETINGS`
  — never "any authenticated user," even though the route is permission-gated
- **Request (`UpdateMeetingData`):** same shape as create; `organizer_id` never accepted from the
  client (§5 API3 — set server-side from the authenticated user at creation, immutable after)

### PATCH /meetings/{uuid}/cancel · DELETE /meetings/{uuid} · POST /meetings/{uuid}/restore
- **Auth:** `UPDATE_MEETINGS` / `DELETE_MEETINGS` / `RESTORE_MEETINGS`

### POST /meetings/bulk-delete · POST /meetings/bulk-restore
- **Auth:** `BULK_DELETE_MEETINGS` / `BULK_RESTORE_MEETINGS` (dedicated permissions, §5 API5)

### GET /meetings/export
- **Auth:** `EXPORT_MEETINGS`, `throttle:10,1` (mirrors `AppointmentExportController`)

## 6. Proposed folder structure
```
src/Modules/Meeting/
├── Application/
│   ├── Commands/ (Create|Update|Cancel|Delete|Restore|BulkDelete|BulkRestore)MeetingHandler.php
│   ├── DTOs/ MeetingData.php CreateMeetingData.php UpdateMeetingData.php
│   │        MeetingFilterData.php MeetingAttendeeData.php CalendarEventData.php
│   └── Queries/ GetMeetingHandler.php ListMeetingsHandler.php
│                GetMeetingCalendarFeedHandler.php SearchAttendeesHandler.php
├── Domain/
│   ├── Ports/ MeetingRepositoryPort.php AppointmentCalendarFeedPort.php
│   ├── ValueObjects/ AttendeeType.php MeetingStatus.php
│   └── Exceptions/ AttendeeNotEligibleException.php (thrown if uuid/type resolves to nothing)
├── Infrastructure/
│   ├── Appointment/ AppointmentCalendarFeedAdapter.php
│   ├── Http/Controllers/ MeetingController.php MeetingCalendarController.php
│   │                     MeetingAttendeeSearchController.php MeetingExportController.php
│   ├── Persistence/Eloquent/Models/ MeetingEloquentModel.php MeetingAttendeeEloquentModel.php
│   ├── Persistence/Repositories/ EloquentMeetingRepository.php
│   └── Routes/ web.php
├── Providers/ MeetingServiceProvider.php
└── Tests/Feature/ Tests/Unit/

database/migrations/ …_create_meetings_table.php  …_create_meeting_attendees_table.php

resources/js/Pages/Meeting/
├── Index.vue        (FullCalendar view — primary surface, US-1/US-2)
├── List.vue          (table view for bulk ops, reuses common/data-table)
├── Create.vue / Edit.vue  (form + AttendeePicker)
└── Show.vue

resources/js/common/meeting/AttendeePicker.vue  (PrimeVue MultiSelect/AutoComplete, backed by
                                                   /meetings/attendees/search)
```

## 7. Testing strategy
- **Unit:** `AttendeeType` enum/morphMap resolution; `CalendarEventData` mapping from both a
  `MeetingEloquentModel` and an `AppointmentEloquentModel` (source discriminator correctness).
- **Feature:** full CRUD + bulk delete/restore (mirrors `AppointmentManagementTest` structure);
  calendar-feed range cap (92 days) and overlay correctness (a `Meeting` and an `Appointment` in
  the same window both appear, correctly tagged); attendee-search minimal-field-exposure test;
  authorization test proving a non-organizer without `UPDATE_MEETINGS` gets 403 on
  update/cancel/delete (§5 API1 regression guard).
- **Regression:** existing `PublicAppointmentApiTest` / `AppointmentManagementTest` must stay
  green untouched — proves FR-2 (no Appointment contract change) held.

## 8. Security and compliance
- Authorization: every mutating/read endpoint gated by `permission:*_MEETINGS`, plus an
  organizer-or-elevated-permission object-level check (research.md §5 API1) — never permission-only.
- Mass assignment: `organizer_id` is never client-settable (§5 API3); DTOs use explicit `Rule::in`
  on `attendee.type`.
- Resource consumption: calendar feed capped at 92 days, matches existing
  `AvailabilityCalendarController` precedent; `throttle:60,1` on the module, `throttle:10,1` on
  export (existing project convention).
- Data minimization: attendee search returns `{type, uuid, label}` only, never full
  User/Appointment/ContactSupport records, regardless of the searching staff member's other
  permissions.
- Audit: `Meeting` gets `LogsActivity` with `logOnly(['title', 'starts_at', 'ends_at', 'status'])`
  + `dontLogEmptyChanges()` — matches `activitylog-method-rename` project convention (never
  `logAll()`).
- UUID routing: `->whereUuid('uuid')` on every `{uuid}` segment, matching project baseline.

## 9. Risks and open decisions
- **Risk:** double-booking / overlapping meetings for the same organizer or attendee is not
  validated in v1 (not required by spec.md). → **Mitigation:** explicitly out of scope; note as a
  fast-follow if staff report real conflicts once the module ships.
- **Risk:** polymorphic `meeting_attendees` has no DB-level FK constraint to the three target
  tables, so a deleted `User`/`Appointment`/`ContactSupport` row could leave a dangling attendee
  row. → **Mitigation:** `SearchAttendeesHandler`/read paths already tolerate a missing target
  (skip silently); a cleanup job is not needed at current scale (per project memory: single-
  developer, small internal team).
- **Pending decision (non-blocking):** should `MEETINGS` be added to `RolePermissionSeeder::
  ADMIN_MODULES` (delegating to the ADMIN role, like `APPOINTMENTS`)? Recommended yes for
  consistency; flagged as a task-level decision rather than blocking the plan.

## 10. Traceability
| Requirement (spec.md) | Covered by (section of this plan) |
|---|---|
| US-1 (calendar view) | §5 `GET /meetings/calendar-feed`, §6 `Index.vue` |
| US-2 (multi-attendee meetings) | §4 `meeting_attendees`, §5 create/update contracts, §6 `AttendeePicker.vue` |
| US-3 (Google sync) | Explicitly NOT covered — deferred per clarify.md Q3 |
| FR-1 | §3 architecture (feed combines both sources) |
| FR-2 (no Appointment contract change) | §1, §3 — reuses existing `AppointmentRepositoryPort`/`AppointmentFilterData` unmodified |
| FR-3 (mixed attendee types) | §4 polymorphic schema, §5 `attendees` payload |
| FR-4 (permission gates) | §5 every contract, §8 |
| FR-5 (Google sync) | Deferred — §2 stack table marks it explicitly unbuilt |
| NFR-Security | §8 |
| NFR-Performance (N+1) | §5 feed uses windowed `scheduledFrom/scheduledTo`, no eager-load-all |
