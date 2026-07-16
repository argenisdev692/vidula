# Research: Meeting / Calendar Scheduling

> Phase 3 · REAL-TIME RESEARCH — findings from Tavily, translated into `plan.md` stack rows.
> All queries below were run live via `mcp__tavily__tavily_search` (`search_depth: advanced`,
> `time_range: year`) on 2026-07-16. No fallback web search was needed.

## 1. FullCalendar Vue3 v6 + Laravel/Inertia integration
**Query:** "FullCalendar Vue3 v6 event source Laravel Inertia integration best practices"
**Findings:**
- Confirmed pattern (multiple sources, incl. `stackoverflow.com/questions/74269666` and
  `74367344`): `@fullcalendar/vue3` is used as a component with a `calendarOptions` object holding
  `plugins` (`dayGridPlugin`, `timeGridPlugin`, `listPlugin`, `interactionPlugin`), and `events`
  populated either eagerly (props from the server) or lazily via an XHR fetch triggered on
  `datesSet` (the FullCalendar callback fired when the visible range changes) — **not** by
  passing a raw `url` event source, which doesn't carry CSRF/session auth cleanly in an
  Inertia/Sanctum app.
- Practical implication for this project: fetch events for the visible range via the project's
  existing `useHttp`/XHR client (Inertia v3, no axios per project convention) hitting a dedicated
  JSON endpoint (e.g. `GET /meetings/calendar-feed?start=...&end=...`), not Inertia props — the
  calendar's own pagination-by-date-range doesn't fit Inertia's page-prop model well. This mirrors
  how `AvailabilityCalendarController` already likely serves a similar range-based feed (verify
  in Plan phase).
- No official `@fullcalendar/vue3` v6-specific breaking change vs. v5 found relevant beyond
  package renames already reflected in `package.json`.

## 2. `spatie/laravel-google-calendar` v3.8 setup (deferred feature, documented for later)
**Query:** "spatie/laravel-google-calendar v3 setup OAuth service account"
**Source:** `packagist.org/packages/spatie/laravel-google-calendar` (official README, current).
**Findings:**
- Two auth modes: **Service account** (single Google Calendar shared via a service-account email
  added as a calendar collaborator — good for one company-wide calendar) or **OAuth2** (per-user
  Google account, needs `oauth-credentials.json` + `oauth-token.json`, generated via a PHP
  quickstart tool, plus an env var toggle).
- For this spec's per-staff-member sync requirement (FR-5, deferred), **OAuth2 is the only mode
  that fits** — service-account mode gives one shared calendar, not "each staff member's own
  Google Calendar." This confirms FR-5 is nontrivial (a full OAuth consent flow per user, token
  storage/refresh) and justifies deferring it out of this iteration per `clarify.md` Q3.
- Action for when FR-5 is picked up: publish config, decide OAuth2 token storage (encrypted per
  OWASP baseline — never store `oauth-token.json` in plaintext on disk in production; needs a
  DB-backed token store, which is a customization beyond the package's file-based default).

## 3. Polymorphic attendee modeling in Laravel
**Query:** "Laravel polymorphic morphTo many attendee types meeting invitees pattern"
**Findings (multiple sources incl. `desarrollolibre.net`, `simple-code.agency`, Stack Overflow):**
- Standard fit: `MeetingAttendee` model with `attendable_id` / `attendable_type` columns and a
  `morphTo('attendable')` relation; each attendee type (`User`, `Appointment`, `ContactSupport`)
  gets a `morphMany`/inverse if needed.
- **Best practice confirmed:** register an explicit `Relation::morphMap([...])` (e.g. in a service
  provider's `boot()`) mapping short strings (`'user'`, `'lead'`, `'contact'`) to FQCNs, instead of
  storing raw class names in `attendable_type`. This decouples the DB from internal namespace
  structure (`Modules\Users\...\User` vs `App\Models\User`) and is safer if classes move.
  **This project has no existing `morphMap` usage** (verified via grep) — Meeting will be the
  first polymorphic relation in the codebase, so the map should be registered in
  `MeetingServiceProvider::boot()`, not a shared location, to keep the module self-contained.
- Caveat noted in `simple-code.agency`: polymorphic relations "shine when relation semantics
  behave the same way across types" and lose FK constraints. Since `User`, `Appointment`, and
  `ContactSupport` attendees behave identically here (just "is invited to a meeting"), polymorphic
  is the right fit — not over-engineering.
- UUID caveat (Stack Overflow `79712376`): default `morphTo()` assumes an integer/auto-increment
  `id` owner key. Since this project's aggregates use UUID route binding
  (`whereUuid`), verify whether `attendable_id` should reference the UUID column or an internal
  auto-increment PK — **flagged for Plan phase**, since `User`/`Appointment`/`ContactSupport` may
  differ in whether their UUID is also their PK or a secondary unique column.

## 4. Spatie Laravel Data (DTOs) + polymorphic/nested payloads
**Query:** "Laravel spatie/laravel-data polymorphic DTO nested data casting"
**Findings:**
- No first-class "polymorphic DTO" feature in `spatie/laravel-data` (confirmed via
  `packagist.org/packages/spatie/laravel-data` docs index and GitHub discussions — a discussion
  thread titled "Deeply nested Data: Morphable data magical creation" exists but no merged
  built-in solution).
- Practical pattern for this project (consistent with existing `AppointmentData`/
  `AvailabilityRuleData` style): the attendee payload in `CreateMeetingData`/`MeetingData` should
  be a flat array of `MeetingAttendeeData` objects each carrying a discriminator string
  (`type: 'user'|'lead'|'contact'`) + `uuid`, validated with `Rule::in([...])` on `type` — the
  same explicit-discriminator style already used in this codebase (e.g.
  `ClientType`/`ProjectType` enums validated via `Rule::in`). No custom polymorphic caster needed.

## 5. OWASP API security — authorization for meeting/calendar invite endpoints
**Query:** "OWASP API security top 10 2025 meeting invite calendar API authorization IDOR"
**Source:** `vefasec.com` API Top 10 2025 guide, `owasp.org` IDOR page (both current, cite
OWASP API Top 10 2023 categories still active going into 2025/2026).
**Findings, applied to this module:**
- **API1 (BOLA/IDOR)** is the top risk for a meeting/attendee endpoint: every meeting
  read/update/delete MUST check the requesting user is either the organizer or an invited
  attendee (or holds `VIEW_ANY_MEETINGS`), not just that they're authenticated. Applies directly
  to `GET /meetings/{uuid}` and the calendar feed endpoint.
- **API3 (Broken Object Property Level Authorization / mass assignment)**: `UpdateMeetingData`
  must not allow a non-organizer attendee to silently reassign `organizer_id` or attendee list —
  matches this project's existing `$fillable` + FormRequest/Data validation convention.
- **API4 (Unrestricted resource consumption)**: the calendar feed endpoint (range query) must
  cap the requestable date range (e.g. reject `end - start > 1 year`) to prevent an unbounded
  full-table scan, and stay under existing `throttle:60,1` conventions already used on
  `Appointment`/`Availability` web routes.
- **API5 (Broken function-level authorization)**: bulk-delete/bulk-restore for meetings (if
  built per this project's bulk-operations standard) must be gated by dedicated
  `BULK_DELETE_MEETINGS`/`BULK_RESTORE_MEETINGS` permissions, not just `DELETE_MEETINGS`.

## Summary — what changes in Plan
| Area | Verified choice | Source |
|---|---|---|
| Calendar events fetch | Range-based JSON endpoint (`?start&end`), not Inertia props, not raw FullCalendar `url` source | §1 |
| Google Calendar sync | Deferred (FR-5); OAuth2 mode required when built, not service-account | §2 |
| Attendee model | `MeetingAttendee` polymorphic (`morphTo`) + `Relation::morphMap` registered in `MeetingServiceProvider` | §3 |
| Attendee DTO | Flat `MeetingAttendeeData[]` with `type` discriminator + `Rule::in`, no custom polymorphic caster | §4 |
| Authorization | Organizer-or-attendee-or-permission check on every meeting endpoint; ranged feed capped; bulk ops need dedicated permissions | §5 |
| Cross-module read of Appointment | `AppointmentCalendarFeedPort` interface in `Meeting/Domain/Ports`, adapter in `Meeting/Infrastructure` — mirrors verified existing `AvailabilityPort`/`AvailabilityResolverAdapter` pattern already in this codebase | codebase grounding, not Tavily |

**RESOLVED** (was flagged unverified in §3, confirmed by reading migrations directly —
`0001_01_01_000000_create_users_table.php`, `2026_07_11_090000_create_appointments_table.php`,
`2026_07_09_120000_create_contact_supports_table.php`): all three tables use `$table->id()` as
the real auto-increment PK plus a separate `uuid` unique string column used only for route
binding (`whereUuid`). Default `morphTo()`/`morphMany()` behavior (owner key = internal `id`) is
correct as-is — no custom owner key needed. `attendable_id` stores the internal auto-increment id,
never the UUID; API responses still expose the attendee's `uuid` (never the internal id), matching
this project's existing convention on every other module.
