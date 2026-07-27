# Research: CV ATS Studio & Job Search

> Phase 3 · REAL-TIME RESEARCH — Verified Jul 2026 via Tavily + Context7.

**Feature ID:** 003-cv-ats-job-studio
**Date:** 2026-07-27

## 1. Laravel AI SDK (structured ATS / match / drafts)

**Finding:** Official `laravel/ai` is the first-party multi-provider SDK (OpenAI, Anthropic, Gemini) with agents, tools, structured output, embeddings. Project already pins `laravel/ai: ^0.8.1` and wraps it in `Shared\Infrastructure\AI\AIClientInterface::generateStructured()`.

**Sources:**
- https://laravel.com/blog/introducing-the-laravel-ai-sdk
- https://packagist.org/packages/laravel/ai (updated Jul 2026)
- https://redberry.international/Discussing-Laravel-AI-SDK (Jul 21, 2026)

**Plan implication:** Reuse `AIClientInterface` + provider panel (`openai` default). Do **not** add Prism/LarAgent as a second LLM stack.

## 2. Task scheduling (daily 09:00 Europe/Lisbon)

**Finding:** Laravel 13 `Schedule::command(...)->timezone('Europe/Lisbon')->dailyAt('09:00')` (or `at`). Prefer reading timezone/time from config. Horizon + Redis remain the queue backbone.

**Sources:**
- Context7 `/laravel/docs/__branch__13.x` — scheduling.md (`timezone`, `dailyAt`, `Schedule::job`)
- https://laravel.0x123.com/docs/13.x/scheduling

**Plan implication:** `config/cv_studio.php` keys for schedule hour/timezone (env-backed). Artisan command dispatches queued studio search jobs. Never hardcode Lisbon in PHP strings outside config defaults.

## 3. Tavily for job discovery

**Finding:** Project already has `TavilyResearchAdapter` (circuit breaker, config keys, snippet results). n8n “Automated job hunt with Tavily” pattern = schedule → search → AI extract → digest.

**Sources:**
- In-repo `Shared\Infrastructure\Research\TavilyResearchAdapter`
- https://n8n.io/workflows/8616-automated-job-hunt-with-tavily

**Plan implication:** Extend/reuse Tavily client for job queries; dedupe by canonical URL before insert.

## 4. Firecrawl as optional deep scrape

**Finding:** Firecrawl is scrape/clean Markdown for AI; Tavily is search-first. Official `firecrawl/firecrawl-php` exists (updated Jul 2026). Cost rises if every URL is scraped.

**Sources:**
- https://www.context.dev/blog/website-crawler-api-comparison-for-ai-agents-2026
- https://github.com/firecrawl/firecrawl-php
- https://www.firecrawl.dev (scrape → markdown)

**Plan implication:** Port `JobPageScraperPort` with Firecrawl HTTP adapter behind `config('services.firecrawl.*')`. Only invoke when `deep_extract_enabled` and for top-N new URLs.

## 5. ATS scoring honesty (product UX)

**Finding:** No universal ATS score across vendors; keyword match ~significant but stuffing penalized; single-column / standard headers / natural keyword use.

**Sources:**
- https://www.jobscan.co/blog/20-ats-friendly-resume-templates
- https://resumebold.com/ats-resume-guide
- Careerkit / Indeed ATS guides (Jul 2026 window)

**Plan implication:** Persist heuristic `ats_score` / `match_score`; UI label “heuristic”; prompts enforce no fabricated metrics + MD single-column sections.

## 6. Auth / permissions pattern (project)

**Finding:** Spatie Permission matrix in `RolePermissionSeeder` — modules get VIEW_ANY/VIEW/CREATE/UPDATE/DELETE/RESTORE/BULK_*/EXPORT. Campaigns add PUBLISH; Video Export is a tool module.

**Sources:** In-repo `database/seeders/RolePermissionSeeder.php`, Cvs web routes.

**Plan implication:** Add `RESUME_STUDIOS` to MODULES (+ ADMIN_MODULES) and extra `RUN_RESUME_STUDIOS` for pipeline start (like CAMPAIGNS PUBLISH).

## Unverified / deferred

- Exact Firecrawl PHP client method names — confirm against packagist README at implement time `[UNVERIFIED until adapter written]`.
- GitHub REST API pagination for repo list — standard Octokit REST; adapter will use Laravel HTTP + config token `[UNVERIFIED fine-grained scopes]`.
