# Technical plan: Video Export Pipeline

> Phase 4 · PLAN — Defines HOW it's built, verified against `research.md`.
> Every technical decision here is traceable to `spec.md` or a `research.md` finding.

**Feature ID:** 002-video-export-pipeline  
**Based on:** spec.md (approved 2026-07-25), research.md, clarify.md

## 1. Technical summary

A new hexagonal `VideoExport` module ports the Nest reference (`docs/VIDEO-MODULE/video-export-backend`) into Laravel 13 with **no relational tables**: job metadata, status, and results live in **Redis Cache**; heavy processing runs on a dedicated Horizon queue `video-export` via `ProcessVideoExportJob`. Editors upload source clips with **presigned direct-to-R2 PUT** (`Storage::temporaryUploadUrl`), enqueue exports in one of three modes (**merge** | **clean** | **ai**), and **poll** job status until an HD MP4 URL is ready.

FFmpeg work uses `pbmedia/laravel-ffmpeg` (facade `FFMpeg`) with Nest-parity encode (**794k** video / **298k** audio, 1920×1080 @ 30 fps). AI-integrate mode calls **OpenAI Whisper** for word timestamps and **Gemini** (via `laravel/ai`) for optional script review. The frontend is a dedicated Inertia **processing panel** (not a DataTable CRUD), at `resources/js/pages/video-export/Index.vue` with supporting code under `resources/js/modules/video-export/`.

## 2. Technology stack (verified with real-time research)

| Component | Choice | Verified version | Source / justification |
|---|---|---|---|
| Language / framework | Laravel 13, PHP 8.5, hexagonal module | — | project baseline, `BACKEND-PHP/SKILL.md` |
| FFmpeg integration | `pbmedia/laravel-ffmpeg` | ^8.9 | research.md §1, `composer.json` |
| FFmpeg binaries | `ffmpeg` / `ffprobe` on PATH (`FFMPEG_BINARIES`, `FFPROBE_BINARIES`) | — | research.md §1; Sail image must include them |
| Object storage | Cloudflare R2 via Laravel `s3` disk | — | research.md §2, existing project disks |
| Presigned upload | `Storage::temporaryUploadUrl()` | Laravel 13.x | research.md §2 |
| Queue / workers | `laravel/horizon` on Redis | ^5.47 | research.md §3, `config/horizon.php` |
| Job store | `Illuminate\Support\Facades\Cache` (Redis) | — | research.md §3–4, FR-9 (no DB) |
| Transcription | OpenAI Whisper API (`OPENAI_API_KEY`) | whisper-1 | research.md §4, clarify.md Q2 |
| Script review | `laravel/ai` → Gemini (`GEMINI_*`) | v0 | research.md §6, clarify.md Q2 |
| DTOs / validation | `spatie/laravel-data` + FormRequest rules | — | project convention |
| Authorization | `spatie/laravel-permission` | — | FR-13, research.md §5 |
| Audit | `spatie/laravel-activitylog` via existing audit port | ^5 | FR-14, project baseline |
| Frontend | Vue 3 + Inertia v3, PrimeVue v4 unstyled + Volt, Zod v4 | — | `FRONTEND/SKILL.md` |
| Progress UX | HTTP poll only (no Reverb) | — | clarify.md Q3 |

No stack row is `[UNVERIFIED]` at plan level; implementation-time tuning items (Horizon memory, R2 public URL helper) are flagged in §9.

## 3. Architecture

Hexagonal module at `src/Modules/VideoExport`:

```
┌─────────────────────────────────────────────────────────────────┐
│  Infrastructure/Http (Inertia + JSON)                           │
│  VideoExportController — presign, enqueue, status               │
└───────────────────────────┬─────────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────────┐
│  Application                                                    │
│  PresignUploadHandler · EnqueueVideoExportHandler               │
│  GetVideoExportJobStatusHandler                                 │
│  DTOs: PresignUploadData, EnqueueVideoExportData, JobStatusData │
└───────────────────────────┬─────────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────────┐
│  Domain                                                         │
│  Ports: VideoExportJobStorePort, ObjectStoragePort,             │
│         FfmpegPipelinePort, TranscriptionPort, ScriptReviewPort │
│  Services: CutPlanner (pure PHP — filler/stutter/PAUSA/silence) │
│  ValueObjects: ExportMode, RenderSpec, JobStatus                │
└───────────────────────────┬─────────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────────┐
│  Infrastructure                                                 │
│  CacheVideoExportJobStore · R2ObjectStorageAdapter              │
│  LaravelFfmpegPipelineAdapter · OpenAiWhisperAdapter            │
│  GeminiScriptReviewAdapter · InputResolver (SSRF-safe download) │
│  Jobs: ProcessVideoExportJob (queue: video-export)              │
└─────────────────────────────────────────────────────────────────┘
```

**Request flows:**

1. **Presign (US-1):** `POST /video-export/uploads/presign` → validate `video/*` → generate R2 key under `video-exports/_parts/{uuid}/` → `temporaryUploadUrl` → audit → return `{ upload_url, public_url, key, expires_in_seconds }`.
2. **Enqueue (US-2–5):** `POST /video-export` with `mode` → validate → write Cache job record (`queued`, `owner_id`) → dispatch `ProcessVideoExportJob` → return `202` + `{ job_uuid, status: 'queued'|'duplicate' }`.
3. **Poll (US-6):** `GET /video-export/jobs/{job_uuid}` → load Cache → verify owner → return status union (Nest parity).
4. **Worker:** resolve URLs → merge → (mode-dependent cuts) → render HD → upload final MP4 → update Cache → delete staging parts → audit completion/failure.

**Mode branching (FR-1):**

| `mode` | Pipeline |
|---|---|
| `merge` | merge + HD render only; no silence/AI/audio enhance |
| `clean` | merge → silence removal → optional audio enhance → render |
| `ai` | merge → silence → Whisper cuts (fillers/stutters/PAUSA) → optional audio enhance → render → optional Gemini script review |

`script_path` in `ai` mode forces transcription (same as Nest). Cleaning-only exposes only silence threshold (1–3 s) + audio enhance toggle (clarify.md Q6).

**BullMQ → Cache + Horizon (research.md §3):** Nest stores full job state in Redis via BullMQ; Laravel stores a serializable `VideoExportJobRecord` in Cache at `video-export:job:{uuid}` with TTL 24 h (completed) / 7 d (failed). Horizon retains failed job metadata separately for ops.

## 4. Data model (physical schema)

**No migrations.** FR-9 / spec §7.

**Cache payload (`VideoExportJobRecord`):**

```
video-export:job:{job_uuid}
- job_uuid: uuid
- owner_id: int (users.id)
- mode: 'merge' | 'clean' | 'ai'
- status: 'queued' | 'active' | 'completed' | 'failed'
- options: { silence_threshold_seconds, audio_enhancement_enabled, language, ai_toggles, script_path?, ... }
- result?: { storage_url, duration_seconds, diagnostics, review?, review_pdf_url? }
- error?: string (client-safe)
- created_at, updated_at: ISO8601
- TTL: 86400s when completed, 604800s when failed
```

**Object storage keys (R2):**

```
video-exports/_parts/{upload_uuid}/{safe_filename}   # temporary sources
video-exports/{job_uuid}/export.mp4                 # final artifact
video-exports/{job_uuid}/review.pdf                 # optional script review
```

## 5. API contracts

All routes: `web` middleware group, `auth`, Spatie `permission:*_VIDEO_EXPORT`, `throttle` per endpoint. UUID segments use `->whereUuid('job_uuid')`.

### GET /video-export

- **Story:** US-7 (panel page)
- **Auth:** `VIEW_VIDEO_EXPORT` (or `CREATE_VIDEO_EXPORT` — align with seeder)
- **Response:** Inertia `video-export/Index` with permissions prop

### POST /video-export/uploads/presign

- **Story:** US-1
- **Auth:** `CREATE_VIDEO_EXPORT`
- **Throttle:** `60,1` (Nest parity)
- **Request:**
  ```json
  {
    "filename": "clip-01.mp4",
    "content_type": "video/mp4",
    "size_bytes": 104857600
  }
  ```
- **Response 200:**
  ```json
  {
    "upload_url": "https://…",
    "public_url": "https://…",
    "key": "video-exports/_parts/…",
    "expires_in_seconds": 900
  }
  ```
- **Errors:** 422 validation; 403 forbidden; 401 unauthenticated

### POST /video-export

- **Story:** US-2, US-3, US-4, US-5
- **Auth:** `CREATE_VIDEO_EXPORT`
- **Throttle:** `5,1` (Nest parity)
- **Request:**
  ```json
  {
    "job_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "mode": "merge",
    "video_paths": ["https://r2…/part1.mp4"],
    "silence_threshold_seconds": 1,
    "audio_enhancement_enabled": true,
    "sort_by_creation_time": true,
    "detect_fillers": true,
    "detect_stutters": true,
    "detect_pause": true,
    "language": "es",
    "script_path": "https://r2…/guion.pdf",
    "script_format": "pdf"
  }
  ```
  - `mode`: required enum `merge` | `clean` | `ai`
  - `video_paths`: 1–50 http(s) URLs (FR-2, FR-11)
  - `silence_threshold_seconds`: 1|2|3, default 1 — required for `clean`/`ai`
  - AI sub-toggles + `script_path` only honored when `mode: ai`
  - `script_path` forces Whisper pass even if client omits AI toggles
- **Response 202:**
  ```json
  { "job_uuid": "…", "status": "queued" }
  ```
  or `{ "job_uuid": "…", "status": "duplicate", "detail": "…" }` (FR-15)
- **Errors:** 422 (local path, bad URL, missing script when review on); 503 when `mode: ai` but `OPENAI_API_KEY` missing

### GET /video-export/jobs/{job_uuid}

- **Story:** US-6
- **Auth:** `VIEW_VIDEO_EXPORT` + owner check (or `MANAGE_VIDEO_EXPORT` for admins)
- **Response 200 (completed — clean/ai):**
  ```json
  {
    "job_uuid": "…",
    "status": "completed",
    "result": {
      "storage_url": "https://…",
      "duration_seconds": 312.4,
      "diagnostics": {
        "source_count": 3,
        "merged": true,
        "merge_order": ["https://…"],
        "silence_cuts": 12,
        "filler_cuts": 5,
        "stutter_cuts": 2,
        "pause_cuts": 1,
        "ai_cleaning_enabled": true,
        "audio_enhanced": true,
        "script_reviewed": true,
        "review_pdf_url": "https://…"
      },
      "review": "## Resumen\n…"
    }
  }
  ```
- **Response 200 (completed — merge):** subset diagnostics (no cut counts)
- **Response 200 (in-flight):** `{ "job_uuid", "status": "queued"|"active"|"delayed" }`
- **Response 200 (failed):** `{ "job_uuid", "status": "failed", "error": "…" }` — no stack trace
- **Foreign / unknown UUID:** `{ "status": "not_found" }` (BOLA-safe, Nest parity)

**Nest route mapping (adaptation note):**

| Nest | Laravel |
|---|---|
| `POST /video-export-merge` | `POST /video-export` + `mode: merge` |
| `POST /video-export` | `POST /video-export` + `mode: clean` or `mode: ai` |
| `POST /video-export/uploads/presign` | unchanged path under web prefix |
| `GET /video-export/jobs/:job_uuid` | unchanged |

## 6. Proposed folder structure

```
src/Modules/VideoExport/
├── Application/
│   ├── Commands/
│   │   ├── PresignUploadHandler.php
│   │   └── EnqueueVideoExportHandler.php
│   ├── Queries/
│   │   └── GetVideoExportJobStatusHandler.php
│   └── DTOs/
│       ├── PresignUploadData.php
│       ├── EnqueueVideoExportData.php
│       └── VideoExportJobStatusData.php
├── Domain/
│   ├── Enums/ExportMode.php
│   ├── Services/CutPlanner.php
│   ├── ValueObjects/RenderSpec.php, TimeRange.php, …
│   └── Ports/
│       ├── VideoExportJobStorePort.php
│       ├── ObjectStoragePort.php
│       ├── FfmpegPipelinePort.php
│       ├── TranscriptionPort.php
│       └── ScriptReviewPort.php
├── Infrastructure/
│   ├── Cache/CacheVideoExportJobStore.php
│   ├── Ffmpeg/LaravelFfmpegPipelineAdapter.php
│   ├── Ai/OpenAiWhisperAdapter.php
│   ├── Ai/GeminiScriptReviewAdapter.php
│   ├── Storage/R2ObjectStorageAdapter.php
│   ├── Pipeline/InputResolver.php
│   ├── Pipeline/VideoExportPipeline.php
│   ├── Jobs/ProcessVideoExportJob.php
│   ├── Http/
│   │   ├── Controllers/VideoExportController.php
│   │   └── Requests/PresignUploadRequest.php, EnqueueVideoExportRequest.php
│   └── Routes/web.php
├── Providers/VideoExportServiceProvider.php
└── Tests/
    ├── Unit/CutPlannerTest.php
    └── Feature/VideoExportApiTest.php

config/
├── laravel-ffmpeg.php          # already published
└── horizon.php                 # add supervisor-video-export

resources/js/
├── pages/video-export/
│   └── Index.vue               # main panel (US-7)
└── modules/video-export/
    ├── types.ts
    ├── schemas/exportFormSchema.ts
    ├── composables/useVideoExportJob.ts
    └── components/
        ├── SourceDropzone.vue
        ├── ModeSelector.vue
        ├── SilenceThresholdSelect.vue
        ├── ScriptUploadSection.vue
        └── JobStatusPanel.vue
```

## 7. Testing strategy

- **Unit:** `CutPlanner` — silence ranges, filler/stutter/PAUSA detection (port Nest `cut-logic.spec.ts` cases); `ExportMode` validation rules; `RenderSpec` constants (794k/298k).
- **Feature:** presign validation (`video/*` only); enqueue rejects `file://` paths; mode branching dispatches correct pipeline stub; duplicate `job_uuid`; owner-scoped status (`not_found` for other user); throttle headers present; AI enqueue 503 without API key.
- **Integration (mocked FFmpeg/AI):** happy-path merge/clean/ai enqueue → worker updates Cache → poll returns `completed` with diagnostics shape.
- **Coverage target:** all FR-* and US-* acceptance paths in spec §4; no DB assertions.

## 8. Security and compliance

| Requirement | Implementation |
|---|---|
| Authn | `auth` middleware on all routes (US-8) |
| Authz | `CREATE_VIDEO_EXPORT`, `VIEW_VIDEO_EXPORT`; owner-scoped job read (research.md §5 API1) |
| Anti-LFI | URL-only `video_paths` / `script_path` (FR-2, research.md §5) |
| SSRF | `InputResolver` private IP blocklist, no redirects (research.md §5) |
| Presigned upload | Short TTL, MIME-bound, staged prefix (research.md §2) |
| Throttle | presign 60/min, enqueue 5/min (Nest `video-export.controller.ts`) |
| Secrets | Never log API keys, presign signatures, or full transcripts (FR-14) |
| Error disclosure | Generic client messages; `APP_DEBUG=false` in production |
| Audit | `video_export.upload_presigned`, `.queued`, `.completed`, `.failed` — metadata only |
| UUID routes | `->whereUuid('job_uuid')` |

## 9. Risks and open decisions

| Risk | Mitigation |
|---|---|
| **ffmpeg binary missing in Sail** | Document `apt-get install ffmpeg` in Sail Dockerfile or dev setup; feature test skips if binary absent; health check command in closeout |
| **Whisper cost / quota** | Fail fast when `OPENAI_API_KEY` missing; `mode: ai` clearly labeled in UI; concurrency 1 limits parallel spend |
| **Long jobs blocking worker** | Dedicated `video-export` queue + 3600 s timeout; `maxProcesses: 1` |
| **R2 `public_url` construction** | Verify `filesystems.disks.r2.url` during T010; `[UNVERIFIED]` until config read in implementation |
| **Disk pressure on worker** | 2 GB/part cap; workspace cleanup in `finally` block; monitor Sail volume |
| **WebSocket progress** | Deferred per clarify.md Q3 — poll-only v1 |

**Resolved (no longer open):** encode bitrates (Q1), AI split (Q2), audio enhance default ON (Q4), cleaning-only UI (Q6).

## 10. Traceability

| Requirement (spec.md) | Covered by (plan section) |
|---|---|
| US-1 Presign upload | §5 presign, §3 flow 1, §6 `PresignUploadHandler` |
| US-2 Merge-only | §3 mode `merge`, §5 POST body |
| US-3 Cleaning-only | §3 mode `clean`, §3 silence pipeline |
| US-4 AI-integrate | §3 mode `ai`, §2 Whisper, §6 `CutPlanner` |
| US-5 Script review | §3 Gemini step, §5 result `review` / `review_pdf_url` |
| US-6 Job polling | §5 GET status, §4 Cache record |
| US-7 Panel UX | §6 `Index.vue` + components |
| US-8 Auth + limits | §5 throttles, §8 |
| US-9 Audio enhance | §3 modes `clean`/`ai`, clarify Q4 |
| FR-1 Three modes | §3 mode table |
| FR-2 URL-only sources | §3 `InputResolver`, §8 |
| FR-3 Silence threshold 1–3 | §5 request schema |
| FR-4 Cleaning no AI | §3 `clean` branch |
| FR-5 AI cuts | §3 `ai` branch, §6 `CutPlanner` |
| FR-6 Script artifact | §3 Gemini, §5 result |
| FR-7 HD 794k/298k | §2 stack, §6 `RenderSpec` |
| FR-8 Async jobs | §3 `ProcessVideoExportJob` |
| FR-9 No DB tables | §4 |
| FR-10 Staging cleanup | §3 worker step 4 |
| FR-11 Max 50 sources | §5 validation |
| FR-12 Diagnostics | §5 response shapes |
| FR-13 Permissions | §8 |
| FR-14 Audit | §8 |
| FR-15 Duplicate UUID | §5 202 duplicate |
| NFR Performance | §3 dedicated queue, concurrency 1 |
| NFR Security | §8 |
| NFR Memory | §3 download caps, presign direct upload |
