---
description: Generates a Laravel 12 / PHP 8.5 CRUD module following architecture, security & test rules. Sequential thinking. 5-line summary.
---

---

## description: Generates a complete Laravel 12 / PHP 8.5 CRUD module from scratch against architecture, security, audit & test rules. Uses sequential thinking for hard reasoning. Responds with a 5-line summary only.

# BACKEND NEW MODULE AGENT — PHP 8.5 + Laravel 12

## PHASE 1 — PLAN NEW CRUD OR MODULE (produce checklist)

Before writing any code, you MUST:

1. Call context7 to resolve current docs for: Laravel 12, Spatie Laravel Data 4.x, Spatie Permission 6.x, Spatie Activitylog, Pest 3
2. Call tavily to verify the latest stable versions of all packages in §12
3. USE SEQUENTIAL THINKING TO REASON HARD about the module structure, field types, ValueObject candidates, CQRS boundaries, audit fields, permission names, export shape, and test coverage — before generating a single file

Then generate the indicated module following these rules.
For each generated item mark ✅ DONE, ❌ SKIPPED (with reason) or ⚠️ WARN.

### Required Generation Checklist

**PHP 8.5**

- [ ] `declare(strict_types=1)` in EVERY .php file
- [ ] Pipe `|>` used in sequential transformations (no nested calls)
- [ ] `clone($obj, [...])` in wither methods (no manual boilerplate)
- [ ] `array_first()` / `array_last()` (never `reset()`/`end()`)
- [ ] `#[\NoDiscard]` on methods whose return value must not be ignored
- [ ] `Uri\Rfc3986\Uri` or `Uri\WhatWg\Url` (never `parse_url()`)
- [ ] `FILTER_THROW_ON_FAILURE` in `filter_var` validations
- [ ] PSR-12: explicit return type on EVERY method

**Architecture (ARCHITECTURE-INTERMEDIATE-PHP.md)**

- [ ] Module lives in `src/Modules/{Name}/` with Domain / Application / Infrastructure
- [ ] Domain imports nothing from Infrastructure or Laravel
- [ ] Mapper is the ONLY contact point between Domain and Eloquent
- [ ] ValueObjects are `readonly` + validation via property hooks
- [ ] DTOs extend `Spatie\LaravelData\Data` and are NOT `readonly`
- [ ] Commands/Queries follow strict CQRS separation
- [ ] Repository: port defined in Domain, implementation in Infrastructure
- [ ] ServiceProvider registered in `bootstrap/providers.php`

**Audit / Observability (§11)**

- [ ] Model uses `LogsActivity` trait with explicit `logOnly([...])`
- [ ] `logOnlyDirty()` + `dontSubmitEmptyLogs()` both present
- [ ] `AuditPort` called manually in CommandHandlers for business actions
- [ ] NEVER `logAll()`, never log passwords/tokens/PII
- [ ] Structured logging via OTEL (never bare `Log::error('string')`)

**Security (§10)**

- [ ] No raw SQL with user input
- [ ] No `unserialize()` on external input
- [ ] `->whereUuid('uuid')` on UUID routes
- [ ] Permissions defined: `VIEW_X`, `CREATE_X`, `UPDATE_X`, `DELETE_X`
- [ ] `forgetCachedPermissions()` called BEFORE creating permissions

**Exports (§8)**

- [ ] `ExcelExport` implements `FromQuery, WithHeadings, WithMapping, ShouldAutoSize`
- [ ] Export route registered BEFORE `/{uuid}` in routes file
- [ ] Same `FilterDTO` reused for both Excel and PDF

**Tests (§7)**

- [ ] Unit/Domain — domain invariants
- [ ] Unit/Application — handlers with mocked repository
- [ ] Integration — DB round-trip via Mapper
- [ ] Feature — full HTTP CRUD + export

**OpenAPI (§9)**

- [ ] Every API method has `@OA\Get/Post/Put/Delete/Patch`
- [ ] Every DTO/Resource has `@OA\Schema`

---

## PHASE 2 — GENERATE

For each item in the checklist: generate the file following the exact rules in BACKEND-PHP.md.
Use context7 to confirm the correct package API before writing each file.
Use tavily to verify current best practices or check for CVEs on any security-sensitive file.
USE SEQUENTIAL THINKING on every file that involves layer boundaries, audit fields, or permission logic.

---

## PHASE 3 — VERIFICATION

After all files are generated, re-run EVERY item from Phase 1. Expected result:

✅ ALL items PASS
📊 Score: X/Y items — target 100%

If any item remains ❌, repeat Phase 2 → Phase 3 until perfect score.
Then respond with EXACTLY 5 lines — no extra output, no file listing, no explanation:

```
✅ Module {Name} generated — {N} files across Domain / Application / Infrastructure.
📦 {DTO1}, {DTO2} · FilterDTO shared with Excel + PDF exports.
🔐 Permissions seeded: VIEW_{X}, CREATE_{X}, UPDATE_{X}, DELETE_{X}.
🧪 Tests: Unit/Domain, Unit/Application (mocked), Integration (DB), Feature (HTTP+export).
📊 {score}/{total} rules passed · Sequential thinking applied on {N} reasoning steps.
```
