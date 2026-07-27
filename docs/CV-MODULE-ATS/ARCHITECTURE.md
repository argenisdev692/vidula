# CV Module — Architecture (Module 1 shipped / Module 2 backend scaffolded)

## Two modules

| | Module 1: `Cvs` (SHIPPED) | Module 2: `AiResumeStudio` (BACKEND SCAFFOLDED) |
|---|---|---|
| Purpose | Upload / CRUD PDF or MD CVs | ATS refine, job discovery, match scoring, outreach drafts |
| Table(s) | `cvs` only | `github_enrichments`, `refined_cvs`, `job_search_configs`, `job_matches`, `outreach_drafts`, `studio_runs` |
| Integrations | R2 (`StoragePort`) | Laravel AI + Tavily + Firecrawl + GitHub |
| Async | None | Horizon `ProcessStudioRunJob` + `resume-studio:run-daily` cron |

## Table `cvs` (Module 1)

- `uuid`, `user_id`, `title`, `niche` (`fullstack` \| `other`), `is_primary`
- `file_path` (private R2), `file_type` (`pdf` \| `md`), `original_filename`
- `raw_text` (filled for MD now; PDF deferred to Module 2)
- soft deletes + indexes on `[deleted_at, created_at]`, `[user_id, is_primary]`

## Module 2 tables (scaffolded — migration `2026_07_27_000100_create_resume_studio_tables`)

- `github_enrichments` → optional GitHub portfolio selections for career mode
- `refined_cvs` → ATS-optimized MD + score + feedback JSON
- `job_search_configs` → keywords, schedule/deep-extract toggles, **`auto_send_enabled` default false**
- `job_matches` → URL dedupe via unique `[user_id, canonical_url]`, score, reasoning, source (`tavily` \| `firecrawl`)
- `outreach_drafts` → cover + digest drafts; manual send via `sent_manually` status
- `studio_runs` → async pipeline steps (`queued` → … → `completed` \| `failed`)

## Flow (Module 2 backend)

1. Select CV → career (optional GitHub) or other (targeting prompt)
2. `POST /resume-studio/runs` → `ProcessStudioRunJob` → AI ATS refine → `refined_cvs`
3. Tavily job search → optional Firecrawl deep extract (top-N from `config/cv_studio.php`)
4. Match scoring → `job_matches` with canonical URL dedupe
5. Cover + digest drafts in `outreach_drafts` — **no automated email send** unless `auto_send_enabled` (default off; not wired yet)
6. Daily cron `resume-studio:run-daily` at `CV_STUDIO_SCHEDULE_TIME` / `CV_STUDIO_SCHEDULE_TIMEZONE`

## Permissions

`RESUME_STUDIOS` module: standard CRUD + bulk + export + distinct `RUN_RESUME_STUDIOS` action.

## Frontend

Inertia/Volt pages under `resume-studio/*` are **not** part of this backend scaffold — follow `/frontend-new` when ready.
