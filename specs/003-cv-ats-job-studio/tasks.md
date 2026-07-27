# Tasks: AiResumeStudio

> Phase 5 · BREAK DOWN TASKS  
**Feature ID:** 003-cv-ats-job-studio  
**Based on:** plan.md

## Phase A — Foundations
- [ ] T001 Create `src/Modules/AiResumeStudio/` tree + `AiResumeStudioServiceProvider` + register in `bootstrap/providers.php`
- [ ] T002 [P] Add `config/cv_studio.php` (schedule time/timezone, deep_extract_top_n) + `.env.example` keys (no secrets hardcoded)
- [ ] T003 [P] Add `RESUME_STUDIOS` to `RolePermissionSeeder` MODULES + ADMIN_MODULES + `RUN_RESUME_STUDIOS` action

## Phase B — Data model
- [ ] T004 Migration: `github_enrichments`, `refined_cvs`, `job_search_configs`, `job_matches`, `outreach_drafts`, `studio_runs`
- [ ] T005 [P] Enums: StudioMode, StudioRunStatus, StudioRunStep, JobMatchSource, ApplicationStatus, OutreachKind, OutreachStatus
- [ ] T006 Eloquent models + factories + LogsActivity (no secrets in logOnly)
- [ ] T007 Domain ports: StudioRunRepositoryPort, JobSearchConfigRepositoryPort, JobMatchRepositoryPort, OutreachDraftRepositoryPort, RefinedCvRepositoryPort, GithubPortfolioPort, JobPageScraperPort

## Phase C — US-1 / US-3 Studio entry + runs
- [ ] T008 DTOs: StartStudioRunData, JobSearchConfigData, UpdateJobMatchData, StudioFilterData
- [ ] T009 Commands: StartStudioRunHandler, CreateJobSearchConfigHandler, UpdateJobMatchHandler, MarkOutreachSentHandler, BulkDelete/BulkRestore handlers
- [ ] T010 Queries: ListStudioRunsHandler, GetStudioRunHandler, ListJobMatchesHandler
- [ ] T011 Controllers + web/api routes with Spatie permissions + whereUuid + throttle on AI/run

## Phase D — US-2 GitHub enrichment
- [ ] T012 GithubPortfolioAdapter (list repos; never return raw token)
- [ ] T013 Persist GithubEnrichment on career runs when selections provided

## Phase E — US-4 ATS refine
- [ ] T014 AtsRefineAgent (structured output) via AIClientInterface
- [ ] T015 Persist RefinedCv version + heuristic score + feedback JSON

## Phase F — US-5 / US-6 Jobs + schedule
- [ ] T016 Tavily job search step + CanonicalUrl normalizer + dedupe
- [ ] T017 FirecrawlJobPageScraperAdapter (optional top-N)
- [ ] T018 JobMatchScorerAgent
- [ ] T019 Artisan `resume-studio:run-daily` + Schedule in `routes/console.php` from config
- [ ] T020 ProcessStudioRunJob (Horizon)

## Phase G — US-7 Drafts
- [ ] T021 CoverDraftAgent + DigestDraftAgent; respect auto_send_enabled default false
- [ ] T022 Mark sent manually endpoint

## Phase H — Cross-cutting
- [ ] T023 Export (Excel/PDF) for job matches via ExportTransformer `|>` chain
- [ ] T024 Activity log + structured failure logging (no PII/secrets)
- [ ] T025 Feature tests: permissions, start run, dedupe, schedule, mark-sent, auto_send default off

## Phase I — Closeout
- [ ] T026 Pint (user runs Sail) + test filter ResumeStudio
- [ ] T027 Traceability check vs spec FR/US
- [ ] T028 Update `docs/CV-MODULE-ATS/ARCHITECTURE.md` Module 2 status

---
**Suggested commit convention:** `feat(resume-studio): T0XX short description`
