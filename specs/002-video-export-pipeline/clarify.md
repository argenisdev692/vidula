# Clarify: Video Export Pipeline

> Phase 2 · CLARIFY — Ambiguities resolved before Plan.

**Feature ID:** 002-video-export-pipeline  
**Date:** 2026-07-25  
**Status:** Resolved

## Open questions → resolutions

| # | Question | Impact | Resolution |
|---|----------|--------|------------|
| Q1 | Encode bitrate: Nest 794k/298k vs user ~320 kbps? | Final MP4 quality/size | **Resolved by default:** Nest parity — video **794k**, audio **298k**, 1920×1080 @ 30 fps. |
| Q2 | AI provider split? | Transcription + script review adapters | **Resolved by default:** **OpenAI Whisper** for word timestamps; **Gemini** (`GEMINI_*` / `AI_PROVIDER`) for script review. |
| Q3 | Poll vs WebSocket progress? | Frontend + Reverb | **Resolved by default:** **Poll-only** (Nest parity). Reverb deferred. |
| Q4 | Audio enhancement default? | Clean/AI UX | **Resolved by default:** **ON** for cleaning-only and AI-integrate; hidden for merge-only. |
| Q5 | `TUTORIAL-CLEANUP-API` missing | Extra contracts | **Deferred** until file is provided. Nest folders are source of truth. |
| Q6 | Cleaning-only sub-toggles? | UI complexity | **Resolved by default:** Only silence threshold (1–3s) + audio enhance. No filler toggles in cleaning-only. |
| Q7 | FFMpeg facade aliases in `config/app.php`? | Boot | **Resolved:** Laravel 13 has no `aliases` array. `pbmedia/laravel-ffmpeg` registers `FFMpeg` via **Package Discovery** (`composer.json` extra.laravel.aliases). Config already published at `config/laravel-ffmpeg.php`. |

## Spec updates applied

- §9 clarification markers for Q1–Q4, Q6 replaced with decisions above.
- Encode FR-7 locked to Nest bitrates.
- Scaffold path confirmed: SDD service module (not `/backend-new-crud` / `/frontend-new` DataTable).
