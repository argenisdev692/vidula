# Tasks: Video Export Pipeline

> Phase 5 · TASKS — ordered, independently verifiable units derived from `plan.md`.
> `[P]` = parallelizable. Status updated after Phase 7 implementation (2026-07-25).

**Feature ID:** 002-video-export-pipeline  
**Based on:** plan.md  
**Implementation status:** v1 shipped — see [SSD-SUMMARY.md](./SSD-SUMMARY.md)

## 0. Pre-task note

- **No migrations** — Cache + object storage only (FR-9).
- **No DataTable CRUD** — processing panel, not `/frontend-new`.
- **WSL-only Sail** for pint/tests/horizon (developer runs + pastes).
- Docs from [Write research plan tasks docs](bb834edc-0fb8-4f56-bd1c-b5795e5015ca) consumed; YAGNI deviations noted below.

## 1. Foundations

- [x] T001 `src/Modules/VideoExport/` skeleton
- [x] T002 `VideoExportServiceProvider` + `bootstrap/providers.php`
- [x] T003 `Domain/Enums/ExportMode.php` (`merge` | `clean` | `ai`)
- [x] T004 Nest constants in `config/video-export.php` (not a Domain `RenderSpec` VO — YAGNI)
- [x] T005 `config/laravel-ffmpeg.php` published; `FFMpeg` via Package Discovery
- [x] T006 Permissions via `matrix(['VIDEO_EXPORTS'], VIEW_ANY/CREATE/DOWNLOAD)` in `RolePermissionSeeder`
- [x] T007 `.env.example` + provider docblock (`FFMPEG_BINARIES`, `VIDEO_EXPORT_*`)

## 2. Domain — cut logic

- [x] T010 `TimeRange.php`
- [x] T011 `CutPlanner` + `FillerCutDetector` + `SilenceCutParser` (Nest port)
- [x] T012 `tests/Unit/Modules/VideoExport/CutPlannerTest.php` + `FillerCutDetectorTest.php`

## 3. Storage / presign

- [x] T020–T021 **Superseded:** extended Shared `StoragePort::temporaryUploadUrl` + `R2StorageAdapter` (no duplicate ObjectStoragePort)
- [x] T022 `PresignUploadData.php`
- [x] T023 `PresignUploadHandler.php`
- [x] T024 `InputResolver.php` (URL-only, SSRF/host allowlist, size cap)
- [x] T025 Partial: enqueue rejects non-http paths (`VideoExportAccessTest`); MIME edge case covered by Spatie Data rules

## 4. Queue / pipeline

- [x] T030 `VideoExportJobStore` (Application service over Cache — no separate Domain port)
- [x] T031 `FfmpegBinaryRunner` (Symfony Process Nest filter_complex parity; laravel-ffmpeg for binaries config)
- [x] T032 `OpenAiWhisperTranscriber` (Infrastructure; no Domain TranscriptionPort — YAGNI)
- [x] T033 `ScriptReviewService` + `ReviewScriptAgainstTranscriptAgent` (Gemini via `AIClientInterface`)
- [x] T034 `VideoExportPipeline.php`
- [x] T035 `ProcessVideoExportJob` on `video-export`, `Timeout(3600)`
- [x] T036 Horizon `supervisor-video-export`
- [ ] T037 Unit test pipeline mode branching with mocked ports — **gap / follow-up**

## 5. HTTP layer

- [x] T040–T042 Spatie Data DTOs (no FormRequests — project convention)
- [x] T043 `EnqueueExportHandler`
- [x] T044 `GetJobStatusHandler` (owner → `not_found`)
- [x] T045 `VideoExportController`
- [x] T046 `web.php` routes
- [x] T047 Partial: `VideoExportAccessTest` (authz, local-path reject, enqueue merge, status not_found); full duplicate/presign matrix — **gap / follow-up**

## 6. Permissions / audit

- [x] T050 `AuditPort` events: `upload_presigned`, `queued`, `completed`, `failed`
- [x] T051 403 without permission (presign + index)
- [ ] T052 Cross-user job UUID isolation feature test — **gap / follow-up**

## 7. Frontend panel

- [x] T060 `modules/video-export/types.ts` + `api.ts`
- [x] T061–T067 Inlined in `pages/video-export/Index.vue` (single panel — YAGNI vs many component files)
- [x] T068 `Index.vue` — mode cards, dropzone, silence, script, poller; token CSS only
- [x] T069 Nav + `VIEW_ANY_VIDEO_EXPORTS`

## 8. Tests

- [x] T070 Enum / cut-logic unit coverage (CutPlanner + Filler)
- [ ] T071 Throttle 429 feature tests — **gap / follow-up**
- [ ] T072 AI + script review integration feature test — **gap / follow-up** (needs keys/ffmpeg)
- [ ] T073 Merge diagnostics exclude AI cuts — **gap / follow-up**

## 9. Closeout

- [ ] T080 `./vendor/bin/sail bin pint --dirty --format agent` — **awaiting developer**
- [ ] T081 `./vendor/bin/sail artisan test --compact --filter=VideoExport` — **awaiting developer**
- [ ] T082 ffmpeg in Sail — **awaiting developer**
- [x] T083 Traceability documented in SSD-SUMMARY / analyze (code mapped; some tests pending)
- [ ] T084 Manual smoke — **awaiting developer**

---

**Logged gaps (not blocking v1 panel):** T037, T047 (full), T052, T071–T073, T080–T082, T084.

**Suggested commit:** `feat(video-export): async merge/clean/ai pipeline with R2 presign and Inertia panel`
