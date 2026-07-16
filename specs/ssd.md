Updated .claude/commands/ssd-artifact.md to the current (2026) GitHub Spec Kit 8-step flow:

  Specify → Clarify → Research (Tavily) → Plan → Tasks → Analyze → Implement → Consolidate

  Changes:
  - Phase 2 Clarify (new) — writes clarify.md, a reviewable audit trail of ambiguities and how each was resolved, before
  any planning happens.
  - Phase 6 Analyze (new) — writes analyze.md, cross-checking spec↔plan↔tasks for orphan requirements/tasks and
  contradictions with resolved clarifications, gating entry into Implement.
  - Renumbered Research/Plan/Tasks/Implement accordingly; Consolidate (final summary) now also pulls in Clarify and
  Analyze sections.
  - Added an explicit "when SDD is the wrong tool" note (small fixes/prototypes/simple CRUD) — confirmed by research and
  already matches this project's SIMPLE-CRUD routing in rules.md, so no change needed there.

  The 3 templates in .claude/skills/SSD/ didn't need changes — Clarify/Analyze produce their own new files rather than
  modifying those templates.