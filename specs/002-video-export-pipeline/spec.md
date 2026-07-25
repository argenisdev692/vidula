# Specification: Video Export Pipeline

> Phase 1 · SPECIFY — Defines WHAT is built and WHY. No technical stack here.

**Feature ID:** 002-video-export-pipeline  
**Date:** 2026-07-25  
**Status:** Approved (clarifications resolved 2026-07-25)  
**Sources analyzed:** `docs/VIDEO-MODULE/video-export-backend`, `docs/VIDEO-MODULE/video-export-frontend`  
**Note:** `docs/VIDEO-MODULE/TUTORIAL-CLEANUP-API` was requested but **not found** in the repository (no matching file/folder).

## 1. Summary

A video-export workspace lets an authenticated editor upload one or more source clips, choose a processing mode (merge-only, cleaning-only, or AI-integrated cleaning), and receive a single HD finished video plus diagnostics. Processing is asynchronous: the editor starts a job, monitors progress, and downloads the result when ready. There is **no persistent video catalog or database entity** for exports — job state is ephemeral and results live in object storage.

## 2. Motivation / Business context

Editors currently stitch and clean talking-head / guion-based recordings manually (or via a separate Nest reference service). Without this module inside the main product, they cannot:

- Merge multi-part recordings into one HD deliverable.
- Automatically remove long silences.
- Optionally apply speech-aware cuts (fillers, stutters, spoken “PAUSA” / “PAUSA ACÁ” bad takes) and optional script compliance review from a uploaded guion.

If it does not exist, export quality stays inconsistent and expensive in editor time, and the Nest reference remains a disconnected system.

## 3. Actors

- **Editor (authenticated user):** uploads source videos (and optionally a script), chooses mode and silence threshold, starts jobs, polls status, downloads results.
- **Background processor:** executes the chosen pipeline (merge / clean / AI), writes the final artifact to storage, cleans temporary parts, records audit events.
- **AI analysis capability (conceptual):** when AI mode is on, produces speech timestamps / cut suggestions and (when a script is provided) a script-vs-speech review report.
- **Administrator / permission granter:** assigns who may create and view export jobs (no need to manage a video DB).

## 4. User stories

### US-1: Upload source parts without streaming through the app server (Priority: High)

**As an** editor, **I want** to upload source video files directly to object storage via short-lived upload slots, **so that** large files do not exhaust application memory.

**Acceptance criteria:**

- [x] Given I am authorized, when I request an upload slot for a video file, then I receive a time-limited upload target and a public reference URL/key I can later pass as a source.
- [x] Given an upload completes, when I start an export, then the processor can read that source by URL/reference only (no local path from the client).
- [x] Given processing finishes successfully, when temporary source parts are no longer needed, then they are deleted from staging storage while the final export remains available.

### US-2: Merge-only HD export (Priority: High)

**As an** editor, **I want** to merge one or more uploaded clips into a single HD video with no cleaning, **so that** I get a quick concatenated deliverable.

**Acceptance criteria:**

- [x] Given ≥1 uploaded source references, when I select **Merge only** and submit, then a job is accepted and I can poll until completion or failure.
- [x] Given completion, when I open the result, then I receive a storage URL for an HD 16:9 1920×1080 @ 30 fps export and merge diagnostics (source count, order).
- [x] Given a single source, when I choose merge-only, then the system still produces a conforming HD render (merge of one).

### US-3: Cleaning-only (long silence removal) (Priority: High)

**As an** editor, **I want** to merge clips and remove long silences without AI speech analysis, **so that** I tighten pacing without paid transcription cost.

**Acceptance criteria:**

- [x] Given ≥1 sources and mode **Cleaning only**, when I set silence threshold to 1, 2, or 3 seconds (default 1), then silences at least that long are removed from the timeline.
- [x] Given cleaning-only mode, when the job runs, then fillers / stutters / PAUSA cuts and script review are **not** applied.
- [x] Given completion, when I inspect diagnostics, then silence cut counts and durations are reported.

### US-4: AI-integrated cleaning (Priority: High)

**As an** editor, **I want** an AI-integrated mode that analyzes speech and removes fillers, stutters, and spoken pause markers, **so that** bad takes and verbal clutter are cut automatically.

**Acceptance criteria:**

- [x] Given mode **AI integrate**, when the job runs, then speech is analyzed with word-level timing and cuts are planned for: configured fillers, stutters, and PAUSA / PAUSA ACÁ (and documented variants).
- [x] Given AI mode, when silence threshold is set (1–3s, default 1), then long silences are also removed (combined with AI cuts).
- [x] Given AI cut plan is produced, when rendering runs, then only “keep” segments remain in the final HD export.
- [x] Given completion, when I inspect diagnostics, then filler / stutter / pause / silence cut counts are reported.

### US-5: Optional script (guion) upload and review in AI mode (Priority: High)

**As an** editor, **I want** to upload a PDF (or markdown) guion when AI mode is selected, **so that** the system can review cleaned speech against the script and return a review artifact.

**Acceptance criteria:**

- [x] Given AI mode is selected, when I enable script review, then uploading a script file is required before submit.
- [x] Given a script reference is provided, when cleaning completes, then a script-vs-speech review is produced (report text and/or PDF URL in the job result).
- [x] Given script review fails after a successful video export, when I poll the job, then the export URL may still be present and the failure is surfaced in diagnostics (export is not silently discarded).
- [x] Given merge-only or cleaning-only mode, when I open the panel, then script upload is not required (and may be hidden).

### US-6: Job status tracking and result download (Priority: High)

**As an** editor, **I want** to track my export job until it finishes, **so that** I know when the download is ready or why it failed.

**Acceptance criteria:**

- [x] Given I submitted a job with a client-supplied job UUID, when I poll status, then I see queued / active / completed / failed (or equivalent stable states).
- [x] Given completed status, when I read the payload, then I get `storage_url`, duration, and diagnostics appropriate to the mode.
- [x] Given failed status, when I read the payload, then I get an error message suitable for display (no internal secrets or stack traces).
- [x] Given another user’s job UUID, when I poll, then I am denied or receive not-found (no cross-user leakage).

### US-7: Panel mode selection UX (Priority: High)

**As an** editor, **I want** a clear panel with three exclusive options — Merge only / Cleaning only / AI integrate — **so that** I understand cost and behavior before submitting.

**Acceptance criteria:**

- [x] Given the export panel, when I choose a mode, then only that mode’s options are active (silence threshold for cleaning and AI; script upload for AI when review is on).
- [x] Given AI mode off, when I try to submit with only silence cleaning, then the job runs as cleaning-only without speech AI.
- [x] Given no files uploaded, when I try to submit any mode, then submit is blocked with validation feedback.

### US-8: Authorization and abuse limits (Priority: High)

**As an** security-conscious product owner, **I want** export actions gated by permissions and rate limits, **so that** only allowed users can burn CPU/AI quota.

**Acceptance criteria:**

- [x] Given an unauthenticated user, when they hit upload/enqueue/status endpoints, then they are rejected.
- [x] Given a user without export permissions, when they attempt create or read, then they are forbidden.
- [x] Given rapid enqueue attempts, when rate limits are exceeded, then further jobs are rejected until the window resets.

### US-9: Optional audio enhancement (Priority: Medium)

**As an** editor, **I want** optional local audio enhancement on clean/AI jobs, **so that** voice sounds cleaner without changing the visual timeline.

**Acceptance criteria:**

- [x] Given cleaning-only or AI mode, when audio enhancement is enabled (default on unless clarified otherwise), then enhancement is applied during final render.
- [x] Given merge-only mode, when I submit, then audio enhancement is not offered / not applied.  
  ```
  *[Assumption pending clarification — see §9]*
  ```

## 5. Functional requirements

- **FR-1**: The system MUST accept export jobs in three exclusive modes: **merge-only**, **cleaning-only**, **AI-integrate**.
- **FR-2**: The system MUST accept an ordered list of source video references that are http(s) object-storage URLs (or equivalent public references), never client-supplied local filesystem paths.
- **FR-3**: The system MUST support silence threshold of integer seconds **1, 2, or 3**, default **1**, for cleaning-only and AI-integrate modes.
- **FR-4**: Cleaning-only MUST remove long silences and MUST NOT call speech-AI cut detection.
- **FR-5**: AI-integrate MUST detect and cut (when toggles allow): fillers, stutters, and PAUSA / PAUSA ACÁ style markers, in addition to silence removal.
- **FR-6**: When a script is provided in AI mode, the system MUST produce a script review artifact after cleaning (report and/or PDF).
- **FR-7**: Final video MUST be exported as **16:9, 1920×1080, 30 fps** HD with Nest-parity encode (**794k** video / **298k** audio AAC @ 48 kHz stereo).
- **FR-8**: Jobs MUST be asynchronous: accept immediately, process in background, expose pollable status by job UUID.
- **FR-9**: The system MUST NOT persist export catalog tables for this module (no video entity CRUD). Ephemeral job state and object-storage artifacts only.
- **FR-10**: Temporary uploaded source parts MUST be cleaned up after a successful final upload (best-effort; failures logged in diagnostics).
- **FR-11**: Source count MUST be bounded (reference: max 50) to limit resource abuse.
- **FR-12**: The system MUST return mode-appropriate diagnostics (merge order, cut counts, AI flags, optional review errors).
- **FR-13**: The system MUST authorize upload, enqueue, and status with permission checks; status MUST be scoped to the owning user (or equivalent policy).
- **FR-14**: Audit events MUST be recorded for presign, enqueue, completion, and failure (without logging secrets or raw media).
- **FR-15**: Duplicate enqueue of the same job UUID MUST be handled safely (return duplicate / ignore second start — same contract as reference).

## 6. Non-functional requirements

- **Performance**: HTTP accept path returns quickly (async). Heavy merge/render runs off-request. Worker concurrency for CPU-heavy jobs should stay low (reference: 1) unless dedicated hardware exists.
- **Memory**: Prefer URL-based / streaming access from object storage; avoid holding full multi-GB uploads in the web process.
- **Security**: Authn + permission authorization; presigned uploads with expiry; reject local paths (anti-LFI); throttle enqueue; no stack traces to clients; signed/controlled download URLs for outputs as required by storage policy.
- **Availability**: Failed jobs remain inspectable for a retention window; completed job payloads retained long enough for clients to poll (reference: ~24h complete / ~7d fail).
- **Scalability**: Solo/editor volume initially; queue must not unbounded-grow Redis (retention caps).
- **Compliance**: Treat uploaded videos/scripts as sensitive user content; minimize retention of staging parts; do not log transcript PII beyond operational need.
- **Observability**: Structured logs correlating job UUID + actor; Horizon-visible queue health (implementation detail deferred to Plan).

## 7. Data entities (conceptual, not a physical schema)

> No relational tables for this module. Concepts below live in job payloads, queue state, and object storage.

- **ExportJob**: `job_uuid`, mode, owner, status, options (silence threshold, AI toggles, language), timestamps, error, result summary.
- **SourcePart**: staging object reference (key/URL), content type, size, upload status; temporary.
- **ScriptDocument** (optional): object reference to PDF/markdown guion used only in AI + review path.
- **CutPlan**: list of time ranges to remove vs keep (silence + optional AI ranges).
- **SpeechAnalysis** (AI mode): word timestamps used to derive filler/stutter/pause cuts.
- **ExportArtifact**: final MP4 object URL, duration, diagnostics; optional review PDF URL + review text.
- **AuditEvent**: who started what action on which job/key (metadata only).

## 8. Out of scope

- Persistent video library / gallery CRUD, soft-delete, bulk restore, Excel/PDF **entity** exports of a video table.
- Real-time collaborative editing / timeline UI (NLE).
- Live streaming ingest.
- Automatic social-network publishing.
- Guaranteed frame-accurate multi-cam sync beyond creation-time / list-order merge rules.
- Replacing the Nest service in production deployment (this is a port into the Laravel/Vue product).
- Full Whisper/Gemini provider billing dashboard.
- `TUTORIAL-CLEANUP-API` content (file missing from repo) — any behaviors unique to that doc are deferred until the file is provided.

## 9. Assumptions and open decisions

- **Resolved:** Encode = Nest parity (794k video / 298k audio).
- **Resolved:** OpenAI Whisper for transcription timestamps; Gemini for script review.
- **Resolved:** Job UX = poll-only (no Reverb in v1).
- **Resolved:** Audio enhancement default ON for cleaning-only and AI-integrate; not offered in merge-only.
- **Resolved:** Cleaning-only UI = silence threshold (1–3s) + audio enhance only.
- Assumption: **Cleaning only** = silence removal (+ optional audio enhancement), no speech AI.
- Assumption: **AI integrate** = silence + speech cuts; script review optional; when script present, transcription runs.
- Assumption: Merge order defaults to creation-time metadata when available, else client list order.
- Assumption: Default speech language is Spanish (`es`).
- Assumption: Filler lexicon and PAUSA keyword variants follow the Nest reference lists.
- Assumption: Panel is Inertia page (not a DataTable CRUD module).
- Assumption: FFMpeg facade via Package Discovery (`FFMpeg` alias); no manual `config/app.php` aliases on Laravel 13.
- Deferred: `TUTORIAL-CLEANUP-API` (file not in repo).
- YAGNI: Script review returns markdown in job result; PDF report generation deferred.

## 10. Success criteria (measurable)

- Editor can complete merge-only and cleaning-only jobs end-to-end with HD 1920×1080 @ 30 fps output reachable via storage URL.
- AI-integrate job removes at least silence + one of (filler | stutter | PAUSA) on a fixture recording and reports non-zero diagnostics for applied cut types.
- With script attached in AI mode, job result includes review text and/or review PDF URL (or explicit `review_error` without losing the MP4).
- Web request path never accepts multi-GB body uploads for sources (presign → direct-to-storage only).
- Zero new domain tables for video exports; job state recoverable from queue/cache for the retention window.
- Unauthorized users cannot enqueue or read another user’s job.
- Feature covered by automated tests for: validation, mode branching, cut-plan logic, authz, and happy-path enqueue/status contracts.

---

## Scaffold qualification (for implementers — not part of product “what”)


| Generator           | Verdict                                  | Why                                                                                                                                                                                                                      |
| ------------------- | ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `/backend-new-crud` | **Do not use**                           | No tables; not CRUD; multi-step media pipeline.                                                                                                                                                                          |
| `/backend-new`      | **Complexity qualifies, shape does not** | Meets promotion criteria (lifecycle + ≥2 integrations + queues), but checklist assumes Eloquent entities, FilterDTO, soft-delete, entity Excel/PDF — wrong artifact. Use SDD + hexagonal **service** module conventions. |
| `/frontend-new`     | **Do not use as-is**                     | Targets DataTable CRUD + bulk delete/restore. Need a Volt/Inertia **processing panel** (dropzone, mode select, silence threshold, job poller). Reuse FRONTEND skill tokens/a11y/security, not the CRUD scaffold.         |


**Correct path:** continue SDD Phases 2→8, then implement backend as a job-based module and frontend as a dedicated export panel.