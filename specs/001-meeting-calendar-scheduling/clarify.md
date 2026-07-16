# Clarifications: Meeting / Calendar Scheduling

> Phase 2 · CLARIFY — Source: open questions carried from `spec.md` §9.

## Q1 — Module boundary (BLOCKING, highest impact)
**Question:** Add FullCalendar as a view *inside* `Appointment` (renders existing lead bookings
only), or create a new, separate `Meeting` module for general internal scheduling with a
multi-type attendee selector (users, leads, support contacts)?
**Impact if unresolved:** Blocks the entire Plan phase — determines module boundary, data model
(polymorphic attendees vs. none), routes, permissions, and whether `Appointment`'s domain needs
touching at all.
**Status:** Asked directly to the user (see below).

## Q2 — Calendar ownership if Meeting is separate
**Question:** If `Meeting` is built, does `Appointment` keep its own calendar too (two calendar
UIs), or does `Meeting` become the single calendar surface that also displays `Appointment`
records (read-only) alongside internal meetings?
**Impact if unresolved:** Determines whether `Appointment`'s FullCalendar work is throwaway if
`Meeting` later absorbs it.
**Status:** Asked directly to the user.

## Q3 — Google Calendar sync scope
**Question:** Is `spatie/laravel-google-calendar` OAuth + sync in scope for this iteration, or
just installed ahead of time for later?
**Impact if unresolved:** Adds a full OAuth account-linking flow + token storage to the plan if
in scope; if deferred, spec/plan should explicitly mark it "installed, not wired."
**Resolved by default:** Deferred to a later iteration — this pass ships the calendar UI +
(if chosen) the Meeting module's attendee model without live Google sync. `research.md` still
captures current `spatie/laravel-google-calendar` v3.8 setup so the follow-up is fast.

## Q4 — Attendee source list
**Question:** Beyond Users, Appointment leads, and ContactSupport contacts, are there other
attendee types (e.g. ad-hoc external email guest with no system record)?
**Resolved by default:** Three source types only for v1 (User, Appointment-lead,
ContactSupport-contact). No ad-hoc free-email guests in v1 — can be added later as a fourth
polymorphic type without reshaping the model.

## Q5 — Recurring meetings
**Question:** Are recurring meetings (RRULE-style) needed in v1?
**Resolved by default:** Out of scope for v1 (already reflected in `spec.md` §8). Single
occurrence only; `scheduled_at` + `ends_at`, no recurrence rule field.

## Resolution log
- **Q1 — RESOLVED (2026-07-16, user decision):** New, separate `Meeting` module for internal
  scheduling with a mixed attendee selector. `Appointment` stays untouched as the public
  lead-intake pipeline.
- **Q2 — RESOLVED (2026-07-16, user decision):** `Meeting` is the single calendar surface. It
  renders its own meetings plus a read-only overlay of `Appointment` events via a query-only
  cross-module port. `Appointment` keeps its existing list view; no second FullCalendar instance.
- **Q3–Q5:** resolved by default as documented above; reflected into `spec.md` §9.

All blocking clarifications are closed. Proceeding to Phase 3 (Research).
