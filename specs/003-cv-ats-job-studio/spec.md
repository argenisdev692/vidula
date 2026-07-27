# Specification: CV ATS Studio & Job Search

> Phase 1 · SPECIFY — Defines WHAT is built and WHY. No technical stack here.

**Feature ID:** 003-cv-ats-job-studio
**Date:** 2026-07-27
**Status:** In review — Clarify complete; next Research → Plan

**Related existing work:** Module 1 `Cvs` (product surface: **Career Files** — upload/CRUD with niches `fullstack` | `other`) is already shipped. This feature is Module 2 — optimization, matching, scheduled search, and outreach drafts. Source notes live in `docs/CV-MODULE-ATS/` (SPEC-1, SPEC-2, ARCHITECTURE, flujo, prompts).

## 1. Summary

Build an **AI Resume Studio** that turns **Career Files** base CVs into ATS-optimized versions with a transparent score report, finds relevant job openings (without repeating links), and prepares outreach messages for human review. The product has two entry modes: **Career** (owner’s fullstack track — GitHub API key, **user-selected projects**, optional extra prompts) and **Other Niche** (any other CV + a free-form targeting prompt). Scheduled job discovery runs **once per day**, and emails are **drafted only — never auto-sent**. Deep page scrape (Firecrawl-class) is an **optional second stage**, not required for every URL.

## 2. Motivation / Business context

Module 1 already stores CVs. Without Module 2, the owner still rewrites resumes manually, hunts jobs ad hoc, and risks spam if outreach is automated. `flujo.md` currently mixes **lead-generation CRM** (find clients → auto-email) with employability; that conflation would produce the wrong product. This module focuses on **job-seeker employability**: better ATS CVs, ranked job matches, and human-approved outreach drafts.

## 3. Actors

- **Career owner (primary user):** uses the fullstack Career Studio for their own developer CV, GitHub enrichment, daily job digests, and application drafts.
- **Niche operator:** uses Other Niche Studio for a non-career CV (marketing, data, etc.), supplies a targeting prompt, and gets the same ATS → jobs → draft flow without GitHub.
- **System scheduler:** runs the daily job-search pass for enabled search configs.
- **Admin / permissioned staff (if multi-user later):** manages permissions; v1 may be single-owner. [NEEDS CLARIFICATION]

## 4. User stories

### US-1: Enter Career Studio from a fullstack CV (Priority: High)
**As a** career owner, **I want** to open Resume Studio from my primary (or selected) fullstack CV, **so that** I can optimize and search jobs for my developer niche without re-uploading.

**Acceptance criteria:**
- [ ] Given a CV with niche `fullstack`, when I open Career Studio, then I see a guided flow with progress (enrich → refine → search → drafts).
- [ ] Given no fullstack CV exists, when I open Career Studio, then I am guided to upload one first.

### US-2: Enrich Career mode with selected GitHub projects + optional prompt (Priority: High)
**As a** career owner, **I want** to enter a GitHub API key, **select which projects** to include, and optionally add an **extra prompt**, **so that** ATS rewrites use only the evidence I choose — plus any extra instructions I type.

**Acceptance criteria:**
- [ ] Given a valid GitHub credential, when I connect, then I see a selectable list of repositories/projects (not an automatic “use everything” dump).
- [ ] Given I select one or more projects (and optionally type an extra prompt), when I continue, then enrichment context includes only those selections plus the optional prompt text.
- [ ] Given I skip GitHub or sync fails, when I continue, then ATS refine still runs from the Career Files CV alone.
- [ ] Given a credential is saved, when displayed later, then the secret is never shown in full (masked).

### US-3: Other Niche Studio with CV + targeting prompt (Priority: High)
**As a** niche operator, **I want** a separate panel where I pick/upload an `other` niche CV and enter a free-form targeting prompt (role, industry, location, tone), **so that** non-career CVs get the same ATS + jobs pipeline tailored to that brief.

**Acceptance criteria:**
- [ ] Given niche `other` and a non-empty targeting prompt, when I start analysis, then the system uses CV content + prompt (not Career GitHub defaults).
- [ ] Given prompt is empty, when I submit, then validation blocks the run with a clear message.
- [ ] Given Career Studio UI, when I navigate, then Other Niche is a distinct entry (not mixed into one confusing form).

### US-4: Generate ATS version + score report (Priority: High)
**As a** user in either mode, **I want** an ATS-optimized Markdown CV plus a score report (0–100 heuristic) with feedback, **so that** I know what changed and why.

**Acceptance criteria:**
- [ ] Given a successful refine run, when complete, then I can view refined Markdown, ATS score, and structured feedback (gaps, keywords added, weak lines fixed).
- [ ] Given the refine run, when content is produced, then the system must not invent employers, degrees, or metrics not supported by source CV / GitHub evidence (truthfulness rule).
- [ ] Given multiple refine runs on the same CV, when saved, then versions are distinguishable (version number or timestamp).

### US-5: One-shot or scheduled job search with unique links (Priority: High)
**As a** user, **I want** to search jobs by keywords (and optional toggle for deep extraction), **so that** I get ranked matches with URLs — without seeing the same link twice.

**Acceptance criteria:**
- [ ] Given keywords and search enabled, when a search runs, then job matches are stored with title, company, URL, match score, and short reasoning.
- [ ] Given a URL already stored for that user (or search config), when a later search finds it again, then it is skipped (deduplicated).
- [ ] Given search fails partially, when some URLs succeed, then successful matches are kept and failures are logged without wiping prior matches.

### US-6: Daily scheduled search (not high-frequency) (Priority: High)
**As a** career owner, **I want** job search to run once per day for enabled configs, **so that** I get fresh opportunities without hammering APIs or creating noise.

**Acceptance criteria:**
- [ ] Given a search config with schedule enabled, when the daily window arrives, then exactly one search pass runs for that config (not every minute).
- [ ] Given schedule disabled, when the daily window arrives, then no automatic search runs; manual “Run now” still works.
- [ ] Given a run completes, when I open Jobs, then only new (non-duplicate) matches appear as “new since last run”.

### US-7: Prepare outreach drafts (covers + digest); send is user-controlled (Priority: High)
**As a** user, **I want** both per-job application drafts and a daily digest of new matches, **so that** I can copy-paste to send myself — or turn on automated send only when I explicitly choose to.

**Acceptance criteria:**
- [ ] Given a job match, when I request a draft, then a cover/application message (subject + body) is saved with status `draft`.
- [ ] Given a daily search finds new matches, when the run completes, then a self-digest draft summarizing new jobs is available.
- [ ] Given default settings, when drafts exist, then I can copy, edit, mark `sent_manually`, or discard — with **no** automatic send.
- [ ] Given I explicitly enable automated send, when the system is allowed to send, then only then may status become `sent_automated` (default remains OFF).

### US-8: Review studio results with clear UX (Priority: Medium)
**As a** user, **I want** a dual-pane studio (CV / score on one side; jobs / drafts on the other) with a progress indicator during long runs, **so that** I understand status and next actions without leaving the page.

**Acceptance criteria:**
- [ ] Given a long-running refine or search, when processing, then I see step progress (queued → enriching → refining → searching → scoring → drafting).
- [ ] Given results, when I open a match, then I see match %, reasoning, link out, and “Prepare draft”.
- [ ] Given mobile viewport, when I use Studio, then panes stack and primary actions remain reachable.

### US-9: Job application tracker light (Priority: Medium)
**As a** user, **I want** to mark matches as saved / applied / skipped / dismissed, **so that** daily digests stay actionable.

**Acceptance criteria:**
- [ ] Given a match, when I change status, then the status persists and filtered views work (new / applied / dismissed).
- [ ] Given dismissed matches, when a later search rediscovers the same URL, then it remains suppressed.

## 5. Functional requirements

- **FR-1**: The system MUST support two distinct Studio modes: Career (`fullstack`) and Other Niche (`other` + targeting prompt).
- **FR-2**: The system MUST produce an ATS-optimized CV artifact and a score report with structured feedback per refine run.
- **FR-3**: The system MUST ground rewrites in source CV text and optional GitHub evidence; it MUST NOT fabricate credentials or metrics.
- **FR-4**: The system MUST discover job openings from web search inputs derived from keywords / niche / targeting prompt.
- **FR-5**: The system MUST deduplicate job URLs per user (canonical URL) across runs.
- **FR-6**: The system MUST support a **daily** schedule for enabled job searches and MUST NOT run on a sub-hourly default cadence.
- **FR-7**: The system MUST prepare outreach drafts of **both** kinds: per-job employer covers and a daily self-digest. Default send mode MUST be manual (copy). Automated send MUST require an explicit user-enabled setting (default OFF).
- **FR-8**: The system MUST allow optional deep page extraction (Firecrawl-class) for job URLs when enabled; daily discovery MUST still work with search snippets only when deep extract is off.
- **FR-8b**: When deep extract is on, the system SHOULD limit full scrapes to new/top-ranked URLs per run (not every historical match) to control cost and latency.
- **FR-9**: The system MUST authorize studio actions with **Spatie permissions** following the same patterns as Campaigns / Posts.
- **FR-10**: The system MUST keep Career Files (Module 1 `Cvs`) as the source of truth for original base CVs; Studio selects those records and does not replace upload CRUD.
- **FR-10b**: Career GitHub enrichment MUST be driven by user-selected projects plus an optional extra prompt field.
- **FR-11**: The system MUST record run status and failures visibly to the user (pending / running / completed / failed).
- **FR-12**: The system MUST NOT auto-apply to job boards or scrape networks that violate their terms as a first-class v1 feature (e.g. bulk LinkedIn harvest / auto-apply from prompts-2).

## 6. Non-functional requirements

- **Performance**: Refine and search are asynchronous; UI shows progress within seconds of enqueue; full pipeline may take minutes depending on external APIs.
- **Security**: GitHub and search API credentials encrypted at rest / never logged in full; CV files remain private; authorize every studio action; no secrets in activity logs.
- **Availability**: External API failures degrade gracefully (skip enrichment, keep prior matches).
- **Scalability**: v1 targets a single power user / small staff; daily schedule keeps API spend predictable.
- **Compliance**: Treat CVs and outreach drafts as personal data; support soft-delete of studio artifacts with the parent CV where applicable.
- **Honesty of scoring**: ATS and match scores are **heuristic product scores**, not a universal ATS vendor score — UI must label them as such.

## 7. Data entities (conceptual, not a physical schema)

- **Cv / Career File (existing):** original upload, niche, primary flag, raw text / file — Studio input.
- **GithubEnrichmentSession:** API connection, **selected project IDs/names**, optional extra prompt, last synced snapshot used for a Studio run.
- **RefinedCv:** ATS Markdown, heuristic score, feedback, version, target role/title, mode.
- **JobSearchConfig:** keywords, schedule enabled, daily window preference, deep-extract toggle (Firecrawl-class), linked CV, mode-specific prompt.
- **JobMatch:** title, company, URL, source, match score, reasoning, application status, first_seen / last_seen.
- **OutreachDraft:** linked match (or digest run), kind (`cover` | `digest`), subject, body, language, status (`draft` | `edited` | `sent_manually` | `sent_automated` | `discarded`).
- **SendPreference:** user/config toggle for automated send (default OFF).
- **StudioRun:** audit of a pipeline execution (steps, status, error summary) for UX progress and debugging.

## 8. Out of scope

- Auto-sending emails by default / silent Gmail OAuth blasts (drafts first; automated send only behind explicit user toggle, default OFF).
- Auto-apply to LinkedIn / Indeed / company ATS forms.
- Full lead-generation CRM from `flujo.md` (finding *clients* who need freelancers).
- Generating 20 simultaneous role-specific CV variants and 20 cover letters in one click (prompts-2 mega-flow) — v1 generates one master ATS + on-demand per-job tailoring.
- Interview simulation and 30-day coaching plans.
- Public multi-tenant SaaS billing (unless clarified later).
- Converting MD↔PDF as a required pipeline step (original file formats remain; export may come later).

## 9. Assumptions and open decisions

- Resolved: Career Files = Module 1 `Cvs` is the base CV source.
- Resolved: GitHub flow = API key → list repos → **user selects projects** → optional extra prompt → then ATS.
- Assumption: Career mode defaults targeting to fullstack web (Laravel/Vue/React/PHP/remote) unless the user overrides keywords.
- Assumption: Daily schedule is enough; drafts live in-app first.
- Assumption: `flujo.md` scheduler “every 30 min” and auto-send steps are **rejected** for this product.
- Resolved: Firecrawl is **optional deep extract** (ON / top-N / on demand) for **both** Career and Other Niche; daily discovery is Tavily-first.
- Resolved: LLM = **Laravel AI SDK** + existing provider panel (`openai` | `anthropic` | `gemini`), **default OpenAI GPT** (same pattern as Campaigns/Posts/Social Media).
- Resolved: Other Niche = **upload that CV aparte** in Career Files (`niche=other`) + targeting prompt → ATS + score → Tavily (daily, deduped) → drafts (no auto-send) → optional Firecrawl. **No GitHub.**
- Resolved: Outreach drafts = **(C) both** — per-job employer covers **and** daily self-digest. Default action = **copy & send manually**; automated send only if the user explicitly enables a toggle (default OFF).
- Resolved: Authorization = **Spatie multi-user**, same patterns as Campaigns / Posts.
- Resolved: Daily search window = **09:00 Europe/Lisbon**, value loaded from **config/env** (not hardcoded in source).
- Resolved: **No hardcoded** API keys, schedule clock, or integration secrets in PHP/Vue — config + existing AI provider panel only.

## 10. Success criteria (measurable)

- Career and Other Niche studios are reachable from CVs list/detail without confusing the two modes.
- A refine run produces Markdown + labeled heuristic ATS score + feedback in one StudioRun.
- A daily scheduled search adds only new unique URLs; re-runs do not duplicate rows.
- Zero automatic outbound emails in v1 acceptance tests.
- User can prepare and copy at least one outreach draft per selected match.
- Prompt pipeline used in production is staged (audit → rewrite → match → draft), not the unedited 14+16 mega-prompts as a single call.
