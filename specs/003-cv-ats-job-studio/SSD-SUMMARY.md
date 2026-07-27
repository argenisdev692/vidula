# SSD Summary: CV ATS Studio & Job Search

> Phase 8 · CONSOLIDATE  
**Feature ID:** 003-cv-ats-job-studio  
**Date:** 2026-07-27

## Specify
Dual-mode Resume Studio on Career Files (`Cvs`): Career (GitHub project picker + optional prompt) and Other Niche (upload aparte + targeting prompt). ATS + heuristic score → Tavily daily jobs (deduped) → cover + digest drafts; Firecrawl optional; send default manual.  
Source: [spec.md](./spec.md)

## Clarify
Resolved: Career Files base; GitHub select projects; Firecrawl optional; drafts C; Spatie like Campaigns; 09:00 Europe/Lisbon via config; no hardcoded secrets; auto-send only if toggle ON.  
Source: [clarify.md](./clarify.md)

## Research
laravel/ai ^0.8.1; Laravel 13 schedule timezone/dailyAt; Tavily in-repo; Firecrawl PHP; ATS scores are heuristic.  
Source: [research.md](./research.md)

## Plan
Module `AiResumeStudio` intermediate hexagonal; 6 tables; Horizon job; Spatie `RESUME_STUDIOS` + `RUN`; reuse AIClient + Tavily.  
Source: [plan.md](./plan.md)

## Tasks
Backend scaffold T001–T028 largely complete; frontend Inertia deferred.  
Source: [tasks.md](./tasks.md)

## Analyze
No blocking gaps; RAG/`cv_chunks` deferred YAGNI.  
Source: [analyze.md](./analyze.md)

## Implement
Backend generated under `src/Modules/AiResumeStudio/` (~55 PHP files) + migration `2026_07_27_000100_create_resume_studio_tables.php`, config, seeders, schedule, feature tests. Frontend Volt pages not in this pass.
