# Technical plan: AiResumeStudio

> Phase 4 · PLAN — HOW, traceable to `spec.md` + `research.md`.

**Feature ID:** 003-cv-ats-job-studio  
**Based on:** spec.md, clarify.md, research.md  
**Module name:** `AiResumeStudio` (`src/Modules/AiResumeStudio/`)  
**Architecture tier:** Intermediate (ARCHITECTURE-PHP) — ≥2 integrations + queues + multi-entity lifecycle

## 1. Technical summary

Module 2 sits on Career Files (`Modules\Cvs`). It runs Career vs Other Niche studio pipelines asynchronously on Horizon/Redis: optional GitHub project selection → Laravel AI ATS refine → Tavily job discovery (daily 09:00 Europe/Lisbon from config) → optional Firecrawl deep extract → match scoring → cover + digest drafts. Default send is manual copy; automated send only behind an explicit toggle (default OFF). Authorization mirrors Campaigns/Posts via Spatie.

## 2. Technology stack (verified)

| Component | Choice | Verified | Source |
|---|---|---|---|
| Language/Framework | PHP 8.5 + Laravel 13 | Project pin | research / composer |
| LLM | `laravel/ai` ^0.8.1 via `AIClientInterface` | Packagist Jul 2026 | research.md #1 |
| Provider UI | `openai` \| `anthropic` \| `gemini` (default openai) | Existing modules | clarify Q7 |
| Job search | Tavily (`TavilyClientInterface`) | In-repo adapter | research.md #3 |
| Deep scrape | Firecrawl HTTP adapter (optional) | firecrawl-php exists | research.md #4 |
| GitHub | REST via Laravel HTTP + config token | Standard API | clarify Q2 |
| Queue | Redis + Horizon | Project hard rule | BACKEND-PHP |
| Schedule | `Schedule::command()->timezone()->dailyAt()` from config | Context7 Laravel 13 | research.md #2 |
| AuthZ | Spatie Permission 7.x | RolePermissionSeeder | research.md #6 |
| DTOs | Spatie Laravel Data | Project standard | skills |
| Storage | Existing Cvs R2 files | Module 1 | ARCHITECTURE.md |

## 3. Architecture

```text
Inertia/Web Controllers (permission middleware)
        ↓
Application Commands / Queries (CQRS)
        ↓
Domain Ports ←── Infrastructure Adapters
        │              ├── Eloquent repositories
        │              ├── LaravelAI agents (ATS, Match, Draft)
        │              ├── TavilyClientInterface
        │              ├── FirecrawlJobPageScraper
        │              └── GithubPortfolioAdapter
        ↓
Horizon Jobs: ProcessStudioRunJob (orchestrates steps)
Artisan: resume-studio:run-daily  (09:00 Europe/Lisbon via config)
```

**Modes:**
- Career: Cv (`fullstack`) + GitHub selections + optional extra prompt
- Other: Cv (`other`) + targeting prompt (no GitHub)

**StudioRun steps:** `queued → enriching → refining → searching → scoring → drafting → completed|failed`

## 4. Data model (physical)

### `github_enrichments`
- id, uuid (unique), user_id (FK), cv_id (FK nullable), github_username, selected_repos (json), extra_prompt (text nullable), repos_summary (json), last_synced_at, timestamps, softDeletes
- indexes: `[user_id, created_at]`, `[deleted_at, created_at]`

### `refined_cvs`
- id, uuid, user_id, cv_id, studio_run_id (nullable FK), mode (`career`|`other`), target_job_title, provider, ats_score (unsignedTiny), refined_md (longText), feedback (json), version (unsignedInt), timestamps, softDeletes
- indexes: `[cv_id, version]`, `[deleted_at, created_at]`

### `job_search_configs`
- id, uuid, user_id, cv_id, mode, keywords, targeting_prompt (nullable), schedule_enabled (bool), deep_extract_enabled (bool), auto_send_enabled (bool default false), provider (string), status, timestamps, softDeletes
- indexes: `[schedule_enabled, deleted_at]`, `[user_id, created_at]`

### `job_matches`
- id, uuid, user_id, job_search_config_id, studio_run_id nullable, job_title, company_name, job_url, canonical_url (unique per user via unique index `[user_id, canonical_url]`), raw_snippet, raw_md nullable, match_score, match_reasoning, source (`tavily`|`firecrawl`), application_status (`new`|`saved`|`applied`|`skipped`|`dismissed`), first_seen_at, last_seen_at, timestamps, softDeletes

### `outreach_drafts`
- id, uuid, user_id, job_match_id nullable, studio_run_id nullable, kind (`cover`|`digest`), subject, body (longText), language, status (`draft`|`edited`|`sent_manually`|`sent_automated`|`discarded`), provider, timestamps, softDeletes

### `studio_runs`
- id, uuid, user_id, cv_id, job_search_config_id nullable, mode, step, status (`pending`|`running`|`completed`|`failed`), error_summary nullable, meta (json), started_at, finished_at, timestamps, softDeletes

**Config file:** `config/cv_studio.php` — schedule time/timezone, deep_extract_top_n, tavily query templates — all env-backed.

## 5. API contracts (web primary)

### GET `/resume-studio`
- US-8 · List recent runs + configs (Inertia) · `VIEW_ANY_RESUME_STUDIOS`

### POST `/resume-studio/runs`
- US-1..US-5 · Body: `cv_uuid`, `mode`, `provider`, `keywords?`, `targeting_prompt?`, `github_enrichment?`, `deep_extract?` · Creates `StudioRun` + dispatches job · `RUN_RESUME_STUDIOS` · throttle AI route

### GET `/resume-studio/runs/{uuid}`
- US-8 · Show run + refined CV + matches + drafts · `VIEW_RESUME_STUDIOS` · whereUuid

### POST `/resume-studio/github/repos`
- US-2 · List repos for token (never echo full token back) · `RUN_RESUME_STUDIOS`

### POST `/resume-studio/configs`
- US-5/US-6 · Upsert JobSearchConfig · `CREATE_RESUME_STUDIOS`

### PATCH `/resume-studio/matches/{uuid}`
- US-9 · application_status · `UPDATE_RESUME_STUDIOS`

### POST `/resume-studio/drafts/{uuid}/mark-sent`
- US-7 · status `sent_manually` · `UPDATE_RESUME_STUDIOS`

### GET `/resume-studio/export`
- Export matches · `EXPORT_RESUME_STUDIOS`

### POST bulk-delete / bulk-restore
- Soft delete configs/matches/drafts as applicable · `BULK_DELETE_*` / `BULK_RESTORE_*`

## 6. Folder structure

```text
src/Modules/AiResumeStudio/
  Domain/{Enums,Ports,Exceptions}
  Application/{Commands,Queries,DTOs,Agents?}
  Infrastructure/{Http,Persistence,Queue,Ai,Integrations,Routes,Console}
  Providers/AiResumeStudioServiceProvider.php
  Tests/Feature/...
config/cv_studio.php
database/migrations/2026_07_27_*_create_resume_studio_tables.php
database/factories/...
```

## 7. Testing strategy

- Feature: authz (403 without permission), start run creates StudioRun, dedupe job URL, schedule command dispatches for enabled configs, auto_send default false, mark-sent manual.
- Unit: canonical URL normalizer; mode validation (other requires targeting_prompt; career allows GitHub payload).
- Fake AI/Tavily/Firecrawl/GitHub via container binds in tests (no live HTTP).

## 8. Security and compliance

- Spatie permissions on every route; UUID binding; throttle AI/run endpoints.
- Never log tokens/API keys; mask GitHub token in responses.
- SSRF: Firecrawl/GitHub only via allowlisted hosts / official APIs (OWASP A01).
- LLM: truthfulness in prompts; structured output schemas; no silent auto-send (LLM/agentic OWASP).
- Secrets only via `config()` / env — no hardcoding (clarify Q11).

## 9. Risks and open decisions

- **Risk:** Firecrawl cost → **Mitigation:** top-N + toggle off by default on daily cron.
- **Risk:** ATS score misuse → **Mitigation:** heuristic label in API + UI.
- **Risk:** Module size → **Mitigation:** ship backend foundations + run orchestration first; frontend Inertia pages can follow `/frontend-new`.
- **Pending:** Frontend Volt/Inertia pages are out of this `/backend-new` pass unless requested.

## 10. Traceability

| Requirement | Plan coverage |
|---|---|
| FR-1 dual modes | §3 modes, POST runs |
| FR-2 ATS + score | refined_cvs, AI agents |
| FR-3 no fabrication | prompts + structured schema |
| FR-4 Tavily discovery | §2 Tavily, ProcessStudioRunJob |
| FR-5 URL dedupe | unique `[user_id, canonical_url]` |
| FR-6 daily schedule | §2 schedule + artisan |
| FR-7 drafts C + send toggle | outreach_drafts + auto_send_enabled |
| FR-8/8b Firecrawl optional | deep_extract_enabled + top_n |
| FR-9 Spatie | RolePermissionSeeder RESUME_STUDIOS |
| FR-10/10b Cvs + GitHub select | cv_id FK + github_enrichments |
| US-1..US-9 | §5 endpoints |
