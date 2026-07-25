# Research: Video Export Pipeline

> Phase 3 · REAL-TIME RESEARCH — findings translated into `plan.md` stack rows.
> Queries run via Tavily (`time_range: month`) and official docs fetch on 2026-07-25.

## 1. `pbmedia/laravel-ffmpeg` ^8.9 — FFmpeg integration

**Sources:**
- [protonemedia/laravel-ffmpeg README](https://github.com/protonemedia/laravel-ffmpeg) (fetched 2026-07-25)
- Project `composer.json` (`"pbmedia/laravel-ffmpeg": "^8.9"`)
- Published `config/laravel-ffmpeg.php` (`timeout` => 3600)

**Findings:**

| Capability | Package API | Relevance to this feature |
|---|---|---|
| Facade alias | `FFMpeg` registered via **Package Discovery** (`extra.laravel.aliases` in package `composer.json`) — Laravel 13 has no `config/app.php` aliases array | Confirmed in `clarify.md` Q7; use `ProtoneMedia\LaravelFFMpeg\Support\FFMpeg` or the `FFMpeg` facade |
| Remote inputs | `FFMpeg::openUrl('https://…')` — supports custom HTTP headers; array form for multiple URLs | Matches Nest URL-only contract (FR-2): worker downloads or streams from R2 public URLs, never client local paths |
| Multi-input | `FFMpeg::open(['video1.mp4', 'video2.mp4'])` / chained `openUrl` | Merge step for multi-part exports |
| Concat | `concatWithoutTranscoding()` / `concatWithTranscoding($hasVideo, $hasAudio)` with `inFormat(new X264)` | Merge-only and intermediate merge-before-trim paths |
| Duration | `$media->getDurationInSeconds()` / `getDurationInMiliseconds()` on `Media` opener | Diagnostics + cut-plan bounds (Nest `probeDurationSeconds` parity) |
| HD encode | `inFormat(new \FFMpeg\Format\Video\X264)` + `setKiloBitrate(794)` etc. | FR-7 Nest parity: 794k video / 298k audio AAC @ 48 kHz stereo |
| Complex filters | `addFilter`, `export()` with `save()` | Trim/keep-segment render, scale/pad to 1920×1080 @ 30 fps |
| Temp files | `FFMpeg::cleanupTemporaryFiles()`; `temporary_files_root` config | Worker workspace hygiene after each job |
| Timeout | `config/laravel-ffmpeg.php` → `timeout` 3600 s | Must align Horizon worker `timeout` ≥ package timeout |

**Practical implication:** Port Nest's `ffmpeg.service.ts` filter graphs to laravel-ffmpeg `addFilter` / raw `format()` calls where the PHP wrapper lacks a helper. The package does **not** replace SSRF-safe URL fetching — that stays in a dedicated `InputResolver` adapter (download to temp dir with guards, same as Nest `input-resolver.service.ts`).

`[UNVERIFIED]` Whether `openUrl` streams without full download for all R2 URL shapes — Nest always downloads to a workspace dir first; **plan adopts the same download-then-process pattern** for parity and predictable disk caps.

## 2. Laravel 13 `temporaryUploadUrl` — direct-to-storage uploads

**Source:** [Laravel 13.x Filesystem docs](https://laravel.com/docs/13.x/filesystem) (fetched 2026-07-25)

**Findings:**
- `Storage::temporaryUploadUrl($path, $expiration)` returns `['url' => …, 'headers' => …]` for client-side PUT uploads.
- Supported drivers: **`s3` and `local` only** (per official docs).
- Primary use case: serverless / SPA direct upload without bytes flowing through the app server — exactly US-1 / FR-2.

**R2 mapping:**
- Cloudflare R2 is exposed via Laravel's `s3` disk driver (existing project R2 config).
- Presign response shape maps to Nest `PresignUploadResult`: `upload_url` ← `url`, `public_url` ← constructed from `AWS_URL`/`R2_PUBLIC_URL` + key, `expires_in_seconds` ← TTL.
- Bind `Content-Type` in presign headers when the disk supports it (Nest binds MIME into SigV4).

`[UNVERIFIED]` Whether the project's current R2 disk config already sets `throw` + public base URL for `public_url` construction — **verify in Plan implementation** against `config/filesystems.php`.

## 3. Laravel Horizon — dedicated long-timeout queue for FFmpeg jobs

**Sources:**
- Project `config/horizon.php` (existing `supervisor-1` on `default`, `timeout` 300 s for AI jobs)
- Nest `video-export.constants.ts` → worker runs merge + Whisper + render (multi-minute)

**Findings:**
- CPU-heavy video jobs must **not** share `default` with 300 s timeout — FFmpeg package default is **3600 s**.
- Add a dedicated Horizon supervisor (e.g. `supervisor-video-export`):
  - `queue` => `['video-export']`
  - `timeout` => `3600` (match `config/laravel-ffmpeg.php`)
  - `maxProcesses` => `1` locally and in production initially (spec NFR: low concurrency for CPU-bound work)
  - `tries` => `1` (re-running half-finished FFmpeg is unsafe without idempotent workspace)
  - `memory` => elevated (e.g. 512–1024 MB) `[UNVERIFIED optimal value — tune in Sail]`
- Job class: `ProcessVideoExportJob` implements `ShouldQueue`, `public string $queue = 'video-export'`, `#[Timeout(3600)]`.

**BullMQ → Laravel mapping (Nest reference):**

| Nest (BullMQ) | Laravel equivalent |
|---|---|
| `QUEUE_NAMES.VIDEO_EXPORT` queue | Horizon `video-export` queue |
| `queue.add(name, data, { jobId: uuid, removeOnComplete, removeOnFail })` | `ProcessVideoExportJob::dispatch(...)` + Cache job store |
| `queue.getJob(uuid)` for status poll | `Cache::get("video-export:job:{uuid}")` (+ optional `Bus::findBatch` not used — no DB) |
| `JOB_RETENTION` (24 h complete / 7 d fail) | Cache TTL + optional Horizon `trim` |

## 4. Nest reference (`docs/VIDEO-MODULE`) — behavioral source of truth

**Sources analyzed:**
- `video-export-backend/video-export.controller.ts` — routes, throttle, auth
- `video-export-backend/dto/*.ts` — request/response contracts
- `video-export-backend/pipeline/input-resolver.service.ts` — **URL-only inputs**, SSRF guard, 2 GB/part cap
- `video-export-backend/pipeline/transcription.service.ts` — **Whisper** (OpenAI) for word timestamps only when AI on
- `video-export-backend/pipeline/script-review.service.ts` — **Gemini** for guion review (not video bytes)
- `video-export-backend/video-export.constants.ts` — encode spec, fillers, PAUSA keywords, retention

**Key patterns to port:**

1. **URL-only inputs (anti-LFI):** `video_paths` / `script_path` must match `^https?://`. Reject filesystem paths. Download with `maxRedirects: 0`, blocked private IP ranges (SSRF).
2. **Presign flow:** Browser PUT → `public_url` returned → client passes URL in `video_paths` on enqueue.
3. **Modes:**
   - Nest `POST /video-export-merge` → **merge**
   - Nest `POST /video-export` with `ai_cleaning_enabled: false` → **clean**
   - Nest `POST /video-export` with `ai_cleaning_enabled: true` (or `script_path` present) → **ai**
4. **Whisper + Gemini split:** Whisper = cut detection; Gemini = script review artifact. `script_path` forces AI cleaning on (needs transcript).
5. **Job UUID:** Client-supplied, idempotent duplicate enqueue returns `{ status: 'duplicate' }`.
6. **Owner-scoped status:** Non-owner gets `not_found` (BOLA-safe), not 403.
7. **Encode:** `RENDER_SPEC` 1920×1080 @ 30 fps, H.264 **794k**, AAC **298k** @ 48 kHz stereo.

**Deferred:** `docs/VIDEO-MODULE/TUTORIAL-CLEANUP-API` — **not found in repo** (spec §8, clarify.md Q5). No additional contracts until file is provided.

## 5. OWASP security baseline — media pipeline

**Sources:** Project `.claude/OWASP/SKILL.md`; Nest `input-resolver.service.ts` inline SSRF comments; OWASP API Top 10 (2023, still cited in project baseline).

| Risk | Mitigation in this module |
|---|---|
| **SSRF** on URL fetch | HTTP(S)-only; block loopback/private/link-local/CGNAT; `maxRedirects: 0`; size + timeout caps on download |
| **LFI / path injection** | Reject non-URL `video_paths` / `script_path`; FFmpeg invoked with temp paths only, never user strings as shell args (laravel-ffmpeg uses `proc_open`, not shell) |
| **Unrestricted upload** | Presigned PUT scoped to `video/*` MIME; key prefix `video-exports/_parts/{uuid}/`; short TTL (Nest: 15 min) |
| **BOLA / IDOR** | Job status keyed by UUID + `owner_id` in Cache payload; foreign UUID → `not_found` |
| **Resource exhaustion** | Max 50 sources; 2 GB/part; throttle presign (60/min) and enqueue (5/min) per Nest; dedicated queue `maxProcesses: 1` |
| **Secrets in errors** | Failed jobs return generic message to client; stderr tail logged server-side only |
| **Audit without PII** | `LogsActivity` / activity log: job UUID, mode, source count — never URLs with tokens, never transcript text |

## 6. `laravel/ai` — script review adapter

**Source:** Existing `src/Modules/Campaigns/Infrastructure/Ai/LaravelAiCampaignAssistantAdapter.php` pattern in codebase.

**Findings:**
- Gemini script review can use `laravel/ai` with `GEMINI_*` env (clarify.md Q2).
- Whisper transcription likely stays on direct OpenAI SDK (word-level `timestamp_granularities[]` requirement) — same as Nest `TranscriptionService`.

`[UNVERIFIED]` Whether `laravel/ai` exposes Whisper word-timestamps in v0 — **plan keeps OpenAI SDK for transcription** unless docs prove parity during implementation.

## Summary — what changes in Plan

| Area | Verified choice | Source |
|---|---|---|
| FFmpeg wrapper | `pbmedia/laravel-ffmpeg` ^8.9, `FFMpeg` facade, `openUrl` / concat / `X264` / duration | §1 |
| Direct upload | `Storage::temporaryUploadUrl` on `s3` disk (R2) | §2 |
| Async processing | Horizon `video-export` queue, 3600 s timeout, concurrency 1 | §3 |
| Job state | Redis Cache (no DB tables), Nest retention TTLs | §3, §4 |
| AI split | OpenAI Whisper + Gemini via `laravel/ai` for review | §4, §6, clarify.md Q2 |
| Security | URL-only, SSRF guard, presign, throttle, anti-LFI | §5 |
| Encode | 794k / 298k, 1920×1080 @ 30 fps | §4, clarify.md Q1 |
| Progress UX | Poll-only (no Reverb) | clarify.md Q3 |
| Missing tutorial API | Deferred (YAGNI) | §4, clarify.md Q5 |
