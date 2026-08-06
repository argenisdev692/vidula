# Specification: Meeting / Calendar Scheduling

> Phase 1 · SPECIFY — Defines WHAT is built and WHY. No technical stack here.

**Feature ID:** 001-meeting-calendar-scheduling
**Date:** 2026-07-16
**Status:** Draft

## 1. Summary
The team has already installed `spatie/laravel-google-calendar` (backend) and `@fullcalendar/vue3`
(frontend). **Decision (resolved 2026-07-16, see `clarify.md` Q1/Q2):** a new, separate `Meeting`
module is built for general-purpose internal scheduling with a mixed attendee selector (system
users, existing leads, support contacts). `Appointment` remains the untouched public lead-intake
pipeline (Astro landing page → `POST /api/appointments/public`) and keeps its own list-based management
UI — it does **not** grow a FullCalendar of its own. `Meeting` is the single calendar surface: it
renders its own internal meetings plus a read-only overlay of existing `Appointment` records
(via a query-only port/adapter, no write coupling), giving staff one place to see everything on
their schedule.

## 1.1 Architecture decision record
- **Chosen:** New `Meeting` module (hexagonal, same conventions as `Appointment`/`Availability`).
- **Calendar ownership:** `Meeting` owns the only FullCalendar instance. It reads `Appointment`
  events through a new read-only port (e.g. `AppointmentCalendarFeedPort`) implemented by an
  adapter inside `Meeting/Infrastructure`, mirroring the existing
  `Appointment\Infrastructure\Availability\AvailabilityResolverAdapter` cross-module pattern —
  `Meeting` depends on a port it defines, `Appointment` is never modified to know about `Meeting`.
- **Why not extend Appointment instead:** `Appointment`'s domain models a single implicit
  attendee (the lead) end-to-end (`BookAppointmentData`, `ClientType`, public booking contract).
  Forcing polymorphic multi-attendee support into it would break that contract's simplicity for a
  concern (internal team scheduling) it was never meant to own.

## 2. Motivation / Business context
Staff currently manage appointments as a list (`AppointmentController@index`), with no calendar
view of bookings, follow-ups, or staff availability. As the team grows, staff need to see
day/week/month views of commitments and, potentially, schedule internal meetings (not just
lead-intake consultations) with colleagues, existing leads, or support contacts — something the
current `Appointment` module was never designed for (it has one implicit attendee: the lead).

## 3. Actors
- **Staff member (internal user)**: views/manages their calendar, books appointments and
  (if in scope) internal meetings, selects attendees.
- **Lead** (`Appointment` entity): the existing public-booking client; may become a selectable
  attendee type in a broader meeting flow.
- **Support contact** (`ContactSupport` module entity): a non-lead, non-staff contact who may need
  to be invited to a meeting.
- **System administrator**: configures Google Calendar connection(s) and permissions.

## 4. User stories

### US-1: Calendar view of existing appointments (Priority: High)
**As a** staff member, **I want** to see booked appointments on a FullCalendar view (day/week/
month), **so that** I don't have to read a flat list to understand my schedule.

**Acceptance criteria:**
- [ ] Given confirmed/pending appointments exist, when I open the calendar, then each appears as
      an event positioned at its `scheduled_at` with status-based styling.
- [ ] Given I click an event, then I see the same detail available today on the appointment show
      page (or a summary + link to it).

### US-2: Internal multi-attendee meetings (Priority: High)
**As a** staff member, **I want** to create a meeting that is not a public lead-intake booking,
**so that** I can schedule internal syncs, calls with a lead, or check-ins with a support contact
without forcing it through the public `Appointment` booking pipeline.

**Acceptance criteria:**
- [ ] Given I create a meeting, when I search attendees, then I can add any mix of: system users,
      existing leads (`Appointment` clients), and `ContactSupport` contacts (per Q4 default: no
      ad-hoc free-email guests in v1).
- [ ] Given a meeting is created, when I open the `Meeting` calendar, then it appears alongside a
      read-only overlay of existing `Appointment` events, in one unified view.
- [ ] Given a meeting has attendees with staff Google accounts connected, when it's saved, then it
      optionally syncs to their Google Calendar (deferred per Q3 — see §9).

### US-3: Google Calendar sync (Priority: Medium)
**As a** staff member, **I want** my meetings/appointments to optionally sync to my Google
Calendar, **so that** I see them alongside my personal calendar without duplicate data entry.

**Acceptance criteria:**
- [ ] Given a staff member has connected a Google account, when a meeting/appointment they attend
      is created or updated, then the corresponding Google Calendar event is created/updated.
- [ ] Given a staff member has not connected a Google account, then sync is silently skipped (no
      errors surfaced for a feature they opted out of).

## 5. Functional requirements
- **FR-1**: The `Meeting` module MUST render a calendar view (day/week/month/list) that combines
  its own internal meetings with a read-only overlay of existing `Appointment` records (via a
  query-only cross-module port), reusing current status semantics (`MeetingStatus`, `StatusLead`)
  for the Appointment overlay.
- **FR-2**: The system MUST NOT change the public booking contract (`BookAppointmentData`,
  `POST /api/appointments/public`) used by the Astro landing page, and MUST NOT modify the `Appointment`
  module's domain to be aware of `Meeting`.
- **FR-3**: The system MUST allow staff to create `Meeting` entries with attendees drawn from
  three source types: system `User`, `Appointment` lead, `ContactSupport` contact.
- **FR-4**: The system MUST authorize calendar/meeting actions through Spatie `permission:*`
  gates (e.g. `VIEW_ANY_MEETINGS`, `CREATE_MEETINGS`, ...), consistent with
  `Appointment`/`Availability` conventions.
- **FR-5** *(deferred — see §9 Q3)*: The system MAY let a staff member connect/disconnect their
  own Google Calendar account and control per-meeting whether an event syncs. Not built in this
  iteration; `research.md` documents the `spatie/laravel-google-calendar` v3.8 setup so this can
  be picked up later without re-research.

## 6. Non-functional requirements
- **Performance**: Calendar month view must not trigger N+1 queries; use eager-loaded, windowed
  queries (`whereBetween` on the visible range) consistent with `BACKEND-PHP` N+1 rules.
- **Security**: Google OAuth tokens (via `spatie/laravel-google-calendar`) must be stored
  encrypted; only the owning staff member's meetings sync to their account. See OWASP baseline.
- **Availability**: Google Calendar sync failures must degrade gracefully — a Google API outage
  must never block creating/editing an appointment or meeting.
- **Scalability**: Design for the current team size (solo-dev/small internal team per project
  memory); no need to design for thousands of concurrent calendar users.
- **Compliance**: No new PII categories beyond what `Appointment`/`ContactSupport`/`Users` already
  store.

## 7. Data entities (conceptual, not a physical schema)
- **Appointment** *(existing)*: a lead's booked consultation. Single implicit attendee (the lead).
- **Meeting** *(new, pending Q1)*: a scheduled event with N attendees of mixed type (user, lead,
  support contact), a time range, and optional Google Calendar sync state.
- **MeetingAttendee** *(new, pending Q1)*: polymorphic link between a Meeting and a User /
  Appointment(lead) / ContactSupport contact.
- **GoogleCalendarAccount** *(new)*: a staff member's connected Google account/token, used by
  `spatie/laravel-google-calendar` to push events.

## 8. Out of scope
- Two-way real-time sync conflict resolution with Google Calendar (last-write-wins is acceptable
  for v1).
- Video-call link generation (Zoom/Meet) — not requested.
- Calendar sharing/delegation between staff members.
- Recurring meetings (RRULE) — unless Q5 resolves this in scope.

## 9. Assumptions and open decisions
- **RESOLVED Q1** (2026-07-16, user decision): New, separate `Meeting` module. See §1.1.
- **RESOLVED Q2** (2026-07-16, user decision): `Meeting` is the single calendar surface; it reads
  `Appointment` records read-only. `Appointment` keeps its list view, no FullCalendar of its own.
- **RESOLVED Q3** (default, `clarify.md`): Google Calendar sync deferred to a later iteration.
  `GoogleCalendarAccount` entity and OAuth flow are NOT built now; `research.md` documents current
  `spatie/laravel-google-calendar` v3.8 setup for when this is picked up.
- **RESOLVED Q4** (default, `clarify.md`): Attendee sources limited to `User`, `Appointment`
  (lead), `ContactSupport` (contact) for v1. No ad-hoc free-email guests.
- **RESOLVED Q5** (default, `clarify.md`): No recurring meetings in v1. Single occurrence only
  (`scheduled_at` + `ends_at`, no recurrence rule).
- Assumption: Existing `Appointment` public booking flow and its permissions
  (`VIEW_ANY_APPOINTMENTS`, etc.) are untouched.
- Assumption: "System users" for attendee selection means the existing `Users` module
  (`src/Modules/Users`), not a new actor concept.

## 10. Success criteria (measurable)
- Staff can view all current-month appointments on a calendar without leaving the app, in ≤2
  clicks from the dashboard.
- If Meeting module is built: staff can create a mixed-attendee meeting (1 user + 1 lead, or 1
  user + 1 support contact) in a single form submission.
- Zero regressions in existing `Appointment` public booking test suite
  (`PublicAppointmentApiTest`, `AppointmentManagementTest`).
