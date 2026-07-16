# Tasks: Meeting

> Phase 5 · TASKS — ordered, independently verifiable units derived from `plan.md`.
> `[P]` = parallelizable (no shared file, no dependency on a sibling task in the same group).
> Status as of implementation pass (2026-07-16): all code tasks done; T080–T082 are WSL-only
> commands the developer must run (this environment cannot execute Sail — see rules.md).

## 0. Frontend convention correction (pre-task note)
Codebase reconnaissance during planning (reading `CrudIndexShell.vue`, `useResourceList.ts`,
`useConfirmAction.ts`, `useFormDialog.ts`, `Pages/appointments/*`) shows the **actual, established**
convention for every one of the 12 existing CRUD modules is **Inertia partial-reload** (server-side
DataTable via `router.get({ only: [...] })`) with `useForm` + Zod `safeParse` on submit — NOT Pinia
Colada `useQuery`/`useMutation`, and NOT `@primevue/forms`. This contradicts the generic
`/frontend-new` skill checklist but matches CLAUDE.md's "follow existing code conventions" rule
and the user's explicit DRY/KISS/YAGNI instruction. **Decision: Meeting's forms and list/bulk UI
follow the real convention** (`CrudIndexShell` + `useResourceList` + `useConfirmAction` +
`useForm` + Zod). The attendee search uses the project's existing `apiFetch` helper (`lib/http.ts`),
the same pattern `useFieldAvailability` already uses for ad-hoc XHR — not `useHttp`/Pinia Colada.

## 1. Foundations `[P]`
- [x] T001 Migration `create_meetings_table`
- [x] T002 Migration `create_meeting_attendees_table`
- [x] T003 `Domain/ValueObjects/AttendeeType.php`
- [x] T004 `Domain/ValueObjects/MeetingStatus.php`
- [x] T005 `Domain/Exceptions/AttendeeNotEligibleException.php`

## 2. Data model / persistence
- [x] T010 `MeetingEloquentModel` (+ `scopeApplyFilters`, `MeetingFactory`)
- [x] T011 `MeetingAttendeeEloquentModel`
- [x] T012 `Domain/Ports/MeetingRepositoryPort.php`
- [x] T013 `Domain/Ports/AppointmentCalendarFeedPort.php`
- [x] T014 `EloquentMeetingRepository`
- [x] T015 `AppointmentCalendarFeedAdapter` — reuses existing, unmodified `AppointmentRepositoryPort`

## 3. US-1 — Calendar view of existing appointments
- [x] T020 `Application/DTOs/CalendarEventData.php`
- [x] T021 `Application/Queries/GetMeetingCalendarFeedHandler.php`
- [x] T022 `Infrastructure/Http/Controllers/MeetingCalendarController.php` (92-day cap)
- [x] T023 Feature test `MeetingCalendarFeedTest::test_the_feed_combines_own_meetings_with_the_appointment_overlay`

## 4. US-2 — Internal multi-attendee meetings
- [x] T030 `Application/DTOs/MeetingAttendeeData.php`
- [x] T031 `CreateMeetingData.php` / `UpdateMeetingData.php` / `MeetingFilterData.php` — a
      standalone `MeetingData.php` output DTO was dropped (YAGNI): every other module in this
      codebase returns the raw Eloquent model to Inertia (see `AppointmentController::show`), so
      Meeting does the same instead of adding an unused resource DTO.
- [x] T032 `Application/Commands/CreateMeetingHandler.php` (organizer from auth user; dangling
      attendee uuid → 422 `ValidationException`, not a raw 500)
- [x] T033 `Application/Commands/UpdateMeetingHandler.php`
- [x] T034 `Application/Commands/CancelMeetingHandler.php`
- [x] T035 `DeleteMeetingHandler.php` / `RestoreMeetingHandler.php`
- [x] T036 `BulkDeleteMeetingsHandler.php` / `BulkRestoreMeetingsHandler.php`
- [x] T037 `GetMeetingHandler.php` / `ListMeetingsHandler.php`
- [x] T038 `SearchAttendeesHandler.php`
- [x] T039 `Infrastructure/Http/Controllers/MeetingController.php` — plus object-level
      organizer-or-`VIEW_ANY_MEETINGS` authorization (`authorizeMeetingAccess()`), beyond the
      original task scope (OWASP API1/BOLA — a route permission gate alone was not enough)
- [x] T040 `MeetingAttendeeSearchController.php`
- [x] T041 `Infrastructure/Routes/web.php`
- [x] T042 Feature test `MeetingManagementTest` (create/update/cancel/delete/restore/bulk/validation)
- [x] T043 Feature test `test_a_non_organizer_without_elevated_permission_cannot_modify_the_meeting`
- [x] T044 Feature test `test_attendee_search_returns_only_minimal_fields`

## 5. Cross-cutting
- [x] T050 `Providers/MeetingServiceProvider.php`
- [x] T051 Registered in `bootstrap/providers.php`
- [x] T052 `MEETINGS` added to `RolePermissionSeeder::MODULES` and `ADMIN_MODULES`
- [x] T053 `MeetingExportTransformer.php` + `MeetingExportController.php` + `exports/pdf/meetings.blade.php`
- [x] T054 `LogsActivity` on `MeetingEloquentModel`
- [x] T055 (added, not originally scoped) `AttendeeOptionMapper.php` — resolves the Edit form's
      attendee prefill via column-scoped queries (`uuid, first_name, last_name` only), explicitly
      NOT via an eager-loaded `attendable` morph relation — a full `User` row includes
      `two_factor_secret`/`two_factor_recovery_codes` (not in `User::$hidden`), which would have
      leaked into the cached Show/Edit Inertia payload otherwise (OWASP data-minimization).

## 6. Frontend
- [x] T060 `resources/js/modules/meeting/types.ts`
- [x] T061 `resources/js/modules/meeting/helpers/buildMeetingQueryParams.ts` (+ `formatDate.ts`)
- [x] T062 `resources/js/modules/meeting/schemas/meetingFormSchema.ts` — Zod v4 + `useForm` +
      `safeParse` on submit (real convention), not `@primevue/forms`/zodResolver.
- [x] T063 `resources/js/common/meeting/AttendeePicker.vue` — built on the already-committed Volt
      `Select` (server-driven via its `@filter` event) + `Tag` chips, NOT a new AutoComplete/
      MultiSelect primitive. Confirmed no missing volt component (T070 resolved this way).
- [x] T064 `resources/js/Pages/meetings/components/MeetingsTable.vue`
- [x] T065 `resources/js/Pages/meetings/components/MeetingForm.vue`
- [x] T066 `resources/js/Pages/meetings/components/MeetingCalendar.vue` — FullCalendar's built-in
      JSON-feed event source, unwrapped via `eventSourceSuccess` (backend wraps as `{data: [...]}`,
      confirmed via context7 that FullCalendar expects the raw array).
- [x] T067 `resources/js/Pages/meetings/Index.vue` — Calendar (default) / List toggle, each branch
      owning its own Head/AppHeader/PermissionGuard rather than adding a one-off slot to the shared
      `CrudIndexShell` (used by 12 other modules) — small deliberate duplication over touching a
      widely-shared component.
- [x] T068 `Create.vue` / `Edit.vue` / `Show.vue` (Show uses the shared `DetailCard`)
- [x] T069 `Meetings` entry added to `useNavGroups.ts` under "Leads & Support"
- [x] T070 Resolved — no new Volt primitive needed (see T063)

## 7. Closeout
- [ ] T080 Developer runs (WSL): `./vendor/bin/sail bin pint --dirty --format agent`
- [ ] T081 Developer runs (WSL): `./vendor/bin/sail artisan migrate`
- [ ] T082 Developer runs (WSL): `./vendor/bin/sail artisan test --compact --filter=Meeting`
- [ ] T083 Final traceability pass — blocked on T080–T082 (cannot self-verify without WSL); see
      `SSD-SUMMARY.md` Implement section for the up-front static review already performed.
