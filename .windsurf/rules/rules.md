---
trigger: always_on
---

# [ABSOLUTE] Non-negotiable constraints — ALWAYS apply

- **Language:** Respond in Spanish at all times.
- **CLI:** Use `./vendor/bin/sail artisan` — NEVER bare `php`.
- **PHP 8.5:** Follow `.windsurf/skills/SKILLS-FULL-STACK/BACKEND-PHP.md` §0–§3 — SINGLE SOURCE OF TRUTH for PHP 8.5 syntax.
- **TypeScript:** Strict mode enforced on ALL `.tsx` / `.ts` files.
- **Context7 (MCP):** Always resolve live docs — never rely on cached training knowledge.
- **Investigate / Investigar:** Run Tavily search immediately before responding.

# [MUST] Before writing any code — read the relevant skill

| Task type                              | Required reading                                  |
| -------------------------------------- | ------------------------------------------------- |
| PHP / Laravel / Backend / Business     | `.windsurf/skills/SKILLS-FULL-STACK/BACKEND-PHP.md`                   |
| React / Inertia / TanStack / Frontend  | `.windsurf/skills/SKILLS-FULL-STACK/FRONTEND-REACT.md`                |
| CSS / Styles / UI design tokens        | `.windsurf/skills/SKILLS-FULL-STACK/FRONTEND-REACT.md` §0–§2, §9      |
| PHP project structure / directory tree | `.windsurf/skills/SKILLS-FULL-STACK/ARCHITECTURE-INTERMEDIATE-PHP.md` |
| React directory tree / file placement  | `.windsurf/skills/SKILLS-FULL-STACK/ARCHITECTURE-REACT-INERTIA.md`    |

> **Rule:** If a skill file covers the task, read it FIRST — no exceptions.
> **Total files:** 5 (this router + 4 skills). No redundancy.

# [MUST] CSS / Styles

- Follow `FRONTEND-REACT.md` §0–§2 strictly.
- NEVER hardcode hex, `bg-red-600`, or `bg-[#hex]`. Use `var(--token)` only.
- All tokens defined in `resources/css/globals.css` (imported by `app.css`).

---

# [MUST] React / TypeScript

- Follow `FRONTEND-REACT.md` strictly.
- No `any`. No `@ts-ignore`. No hardcoded colors in components.
- Every page wrapped in correct Layout. State always explicitly typed.

---

# [MUST] Laravel / PHP

- Follow `BACKEND-PHP.md` strictly.
- No business logic in Controllers. No `php` bare CLI.
- Web routes = primary (Inertia + session). API routes = secondary (mobile/Sanctum only).

---

# [SHOULD] General quality

- Mobile-first on every UI component.
- `font-family: var(--font-sans)` everywhere.
- Prefer descriptive names over comments.
