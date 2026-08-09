# Phase 3 — Admin: Check-in & Game Recording

**Status:** ✅ Done
**Depends on:** Phase 2
**Blocks:** Phase 4

## Goal

On session day, the organizer checks players in and records each game's teams and score.
This produces the raw match data that Phase 4 turns into rankings.

## Backend
- Extend `OpenPlayService` (or a focused `OpenPlayGameService`):
  - `checkIn(registration)` / `undoCheckIn(...)` — set `checked_in_at`, status `CheckedIn`.
  - `recordGame(session, [teamA registrationIds], [teamB registrationIds], scoreA, scoreB)`:
    - Validate all players are **confirmed/checked-in registrations of this session**.
    - Validate team sizes (1v1 or 2v2), scores are non-negative ints, no tie (win by ≥1/≥2 per rules — configurable).
    - Persist `OpenPlayGame` + `OpenPlayGamePlayer` rows in a transaction; derive `winning_team`.
  - `deleteGame(game)` / edit — organizer can correct mistakes.
  - Transition session status `InProgress` → `Completed`.
- Routes under admin: `open-play/{session}/manage` (game console), `POST games`, `DELETE games/{game}`, `PATCH registrations/{registration}/check-in`.
- `AuditLogger` for game create/delete.

## Frontend (`resources/js/pages/admin/open-play/manage.tsx`)
- Roster with check-in toggles.
- "Record game" form: pick team A / team B players, enter scores, submit.
- List of recorded games with delete/edit.
- Live running standings preview (reads Phase 4 query once it exists; stub until then).

## UX states
Empty roster · no games yet · validation errors (invalid teams/scores) · session already completed (read-only).

## Acceptance criteria
- [x] Organizer can check in players and record/delete games.
- [x] Cannot record a game with players not in the session, wrong team sizes, or a tie.
- [x] Games persist correctly with a derived winner.
- [x] Feature tests cover validation + authorization.
- [x] Validation suite green: 330 Pest tests, tsc, eslint, pint, build.

## Delivered
- `OpenPlayGameService`: `checkIn`/`undoCheckIn`, `recordGame` (validates session membership + confirmed/checked-in status, even 1v1/2v2 teams, no shared players, non-negative non-tied scores; derives `winning_team`; first game moves session → `InProgress`), `deleteGame`, `complete` (→ `Completed`).
- `StoreOpenPlayGameRequest` (shape) with deep cross-field validation in the service (single source of truth). `OpenPlayRegistrationResource` + `OpenPlayGameResource` (players grouped by team with names).
- `Admin\OpenPlayController` gains `manage`/`checkIn`/`undoCheckIn`/`complete`; new `Admin\OpenPlayGameController` (`store`/`destroy`). Routes declared before the resource; `AuditLogger` on game create/delete + session completion. Authorization reuses `OpenPlaySessionPolicy@update` (admin-only via `before()`).
- Admin page `admin/open-play/manage.tsx`: roster with check-in toggles, record-game form (A/B team pickers + scores), recorded-games list with delete, client-side standings preview; "Manage" action added to the index. TS types + Wayfinder helpers regenerated.
- Tests: `AdminOpenPlayGameTest` (12) — manage view + authz, check-in/undo, 1v1 & 2v2 recording + session transition, tie/outsider/uneven-teams/both-teams rejection, delete, complete-locks-recording, customer 403.

## Decisions / deferred
- Win rule enforced as **non-negative scores + no tie** (higher score wins). A configurable win-by-2 margin is deferred (acceptance only requires rejecting ties); wire it through `AppSettings` if needed later.
- Team pickers offer **checked-in** players (the intended session-day flow); the service still accepts any confirmed/checked-in member.

## Out of scope
Ranking computation and podium display (Phase 4); player self-reporting (post-MVP).
