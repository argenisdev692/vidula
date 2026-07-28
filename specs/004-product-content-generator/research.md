# Research: Product Content Generator

> Phase 3 · REAL-TIME RESEARCH — Verified before Plan.  
> **Feature ID:** 004-product-content-generator  
> **Date:** 2026-07-27  
> **Tools:** Tavily MCP (`user-tavily`) + Context7 MCP (`user-context7`) + repo inspection.

## Query 1 — Tavily for LLM grounding

**Finding:** Tavily remains an AI-optimized search API (results with title/url/content/score) suited to inject into agent context. Official Search docs document `search_depth` (`basic`/`advanced`), `max_results`, `include_answer`, `time_range`, `include_raw_content`, and optional `auto_parameters`.  
**Implication for Plan:** Reuse existing `Shared\Infrastructure\Research\TavilyClientInterface` / `TavilyResearchAdapter` (already degrades to `[]` on failure with circuit breaker). Optionally add `time_range: month` for tech courses. Persist citations on scripts/topics.

**Sources:**
- https://docs.tavily.com/documentation/api-reference/endpoint/search
- https://www.firecrawl.dev/blog/brave-search-api-alternatives
- https://parallel.ai/articles/tavily-vs-parallel-search

## Query 2 — Context7 for library/docs verification

**Finding:** Context7 (Upstash) provides up-to-date library docs for AI agents via MCP and REST. Workflow: resolve library ID → query docs. Auth via `CONTEXT7_API_KEY` / Bearer `ctx7sk_…`. Purpose matches “que todo coincida” for framework APIs (.NET, Angular, etc.).  
**Implication for Plan:** Add new Shared port `DocsVerificationPort` + `Context7DocsAdapter` mirroring Tavily (HTTP + circuit breaker + empty-on-failure). Two-step resolve → docs per detected library name from topic title/bullets.

**Sources:**
- https://lobehub.com/mcp/upstash-context7-docs
- https://github.com/intellectronica/agent-skills/blob/main/skills/context7/SKILL.md
- https://www.deployhq.com/blog/context7-guide-stop-ai-hallucinations-with-live-docs

## Query 3 — Laravel async generation + file delivery

**Finding (Context7 `/websites/laravel_13_x`):** Jobs dispatched via `Job::dispatch()` / `dispatch()`; Horizon for Redis queues. Filesystem supports `Storage::temporaryUrl(...)` and `Storage::download(...)` for private artifacts.  
**Implication for Plan:** Mirror Campaigns: sync handler creates generation row → queue job runs pipeline → UI polls/broadcasts progress. ZIP built in a job; download via authorized controller + temporary URL or `Storage::download`.

**Sources:**
- https://laravel.com/docs/13.x/queues (via Context7)
- https://laravel.com/docs/13.x/filesystem (via Context7 + Tavily extract of Laravel 12/13 filesystem docs)

## Query 4 — Archive format (ZIP vs RAR)

**Finding:** PHP ships `ZipArchive`; RAR needs proprietary tooling. Security advisories around archive extraction reinforce: build ZIPs ourselves, never extract untrusted archives as input.  
**Implication for Plan:** ZIP-only packaging port; do not accept uploaded ZIP/RAR as generation seed (markdown only).

**Sources:** CISA/vuln digests mentioning ZipArchiver issues (defensive posture); project convention + clarify Q2.

## Query 5 — Repo-local building blocks (not Tavily; verified by inspection)

| Capability | Already in VIDULA |
|---|---|
| LLM bridge | `Shared\Infrastructure\AI\AIClientInterface` + `LaravelAIAdapter` (`laravel/ai`) |
| Tavily research | `TavilyClientInterface` + `TavilyResearchAdapter` |
| Async AI pattern | `Modules\Campaigns` — `GenerateCampaignHandler` → dispatcher → queue job + progress broadcast |
| PDF | `Modules\Invoices` DomPDF renderer / export transformers |
| Storage | `Shared\Domain\Ports\StoragePort` |
| CRM | `Modules\Clients`, `Modules\Students` |
| Billing | `Modules\Invoices` (items currently link `service_id`, not product) |

**Gap:** No Context7 adapter yet; no Products module; no content-generation aggregate; invoice items lack `product_id`.

## Open decisions from research (none blocking)

- Exact Context7 REST base URL/versioning: confirm against Upstash docs at implement time (`config/services.php`). Mark adapter URL as config, not hardcode.  
- Course PDF renderer: prefer DomPDF (already in project) unless research during implement finds a better shared export path — **[UNVERIFIED alternative]** other PDF engines not evaluated deeply.

## Stack rows for Plan (must map 1:1)

1. Laravel 13 + PHP 8.5 + Horizon queues — verified (project + Context7 Laravel 13.x).  
2. Tavily Search API — verified (docs.tavily.com).  
3. Context7 docs API — verified (Upstash/Context7 sources).  
4. `laravel/ai` via existing `AIClientInterface` — verified (repo).  
5. ZIP via PHP ZipArchive — verified by convention; RAR rejected.  
6. Private storage + temporary URLs — verified (Laravel filesystem docs).
