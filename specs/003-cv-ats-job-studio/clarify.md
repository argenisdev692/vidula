# Clarify: CV ATS Studio & Job Search

> Phase 2 · CLARIFY — Ambiguities from `spec.md` and mid-clarify user answers (2026-07-27).

**Feature ID:** 003-cv-ats-job-studio
**Status:** Resolved — ready for Phase 3 Research

## Resolved

### Q1 — Where do base CVs live?
**Decision:** Base CVs are loaded from the existing **Career Files** surface (= shipped Module 1 `Cvs`: upload PDF/MD, niche `fullstack` | `other`, primary flag). Studio does **not** invent a second upload store for career bases.
**Impact:** Studio always selects a `Cv` UUID; Career mode prefers `fullstack` / primary; Other Niche prefers `other`.
**Source:** User 2026-07-27.

### Q2 — How does GitHub enrichment work?
**Decision:** User provides GitHub API key/token → system lists repos → **user selects which projects** to include → **optional free-text extra prompts** (skills to emphasize, roles to target, constraints). Enrichment context = selected projects summary + optional prompt, not “all repos blindly”.
**Impact:** Changes US-2 and conceptual run inputs; UI needs repo multi-select + optional prompt field before ATS refine.
**Source:** User 2026-07-27.

### Q3 — Auto-send emails?
**Decision:** System always **prepares** drafts. **Default send mode = manual** (user copies and sends). User may later enable **automated send** explicitly via a setting/toggle they control — never silent auto-blast.
**Impact:** Outreach status includes `draft` → `sent_manually` | `sent_automated`; auto-send job only runs when the toggle is ON.
**Source:** User 2026-07-27 (“yo decido… lo que haría es copiar y enviar”).

### Q4 — Scheduler cadence?
**Decision (from original request):** Daily job search, not every minute / not 30-min lead hunter from `flujo.md`.
**Impact:** One scheduled pass per enabled `JobSearchConfig` per day + manual “Run now”.

### Q5 — Should Firecrawl be annexed?
**Decision:** **Yes, optional second stage** for **both** Career and Other Niche — not on every URL every day.
- **Tavily** = discovery (URLs + snippets) for daily cron and first-pass match.
- **Firecrawl** = deep scrape when deep extract is ON / top-N new URLs / on demand.
**Impact:** Shared job pipeline after ATS; `deep_extract_enabled` boolean; pipeline succeeds when Firecrawl is off.
**Source:** User confirmed 2026-07-27.

### Q5b — Other Niche pipeline (non-fullstack)
**Decision:** Other Niche CVs are **uploaded separately** in Career Files with niche `other` (not mixed into the fullstack Career path). Then:

```text
Upload / select Other Niche CV (apart) + targeting prompt
       ↓
ATS + score
       ↓
Tavily jobs (daily, deduped) → drafts (no silent auto-send)
       ↓
Firecrawl only when deep extract ON / top-N / on demand
```

No GitHub on this path. Shared StudioRun steps after refine with Career.
**Source:** User confirmed 2026-07-27 (upload aparte + same job pipeline).

### Q6 — Outreach draft content
**Decision:** **(C) Both** — (1) per-job employer cover/application drafts, and (2) daily digest summarizing new matches for the user. Primary v1 action = **copy & send manually**; automated send only if user explicitly enables it.
**Source:** User 2026-07-27.

### Q7 — LLM + API key UX
**Decision:** Use existing **Laravel AI SDK** (`laravel/ai` via shared AI client). Studio reuses the same **provider selection panel** pattern already used in Campaigns / Social Media / Posts (`openai` | `anthropic` | `gemini`), **default = OpenAI GPT**.
**Impact:** ATS / score / draft steps accept `provider` like other AI modules; no new LLM abstraction. Tavily already wired via `config('services.tavily.api_key')`; Firecrawl + GitHub keys follow the same secrets style (env / services config) unless a dedicated vault screen is added later.
**Source:** User 2026-07-27.

### Q9 — Multi-user vs solo
**Decision:** **Spatie multi-user** — same permission patterns as **Campaigns / Posts** (`VIEW_*`, `CREATE_*`, `UPDATE_*`, `DELETE_*`, `RESTORE_*`, plus studio-specific run/export permissions as needed).
**Source:** User 2026-07-27.

### Q10 — Daily run timezone / hour
**Decision:** Daily job search runs at **09:00 Europe/Lisbon** (Portugal). Hour and timezone MUST come from **config** (backed by env) — **never hardcoded** in PHP/Vue source.
**Source:** User 2026-07-27.

### Q11 — No hardcoded secrets / integration values
**Decision:** No API keys, schedule clock, or third-party secrets hard-coded in application source. Use Laravel `config()` + `.env` / existing AI provider panel. Code may reference config keys only (e.g. `config('cv_studio.schedule.timezone')`).
**Source:** User 2026-07-27 (“nada de env vars hardcodeadas”).

## Open (high impact) — ask user

_None. Phase 2 Clarify is complete._

## Resolved by default (low impact — override if needed)

- **D1:** Dedup key = canonical URL (strip tracking query params) per user.  
- **D2:** ATS/match scores labeled “heuristic” in UI.  
- **D3:** Other Niche requires non-empty targeting prompt.  
- **D4:** Lead-gen CRM from `flujo.md` stays out of scope.  
- **D5:** prompts-2 auto-apply / LinkedIn harvest stay out of scope.  
- **D6:** Career Files module name in product copy may say “Career Files”; code module remains `Cvs` unless a rename task is added later.
- **D7:** Automated send (when enabled) still respects rate limits and never runs without an explicit user-controlled setting (default OFF).
