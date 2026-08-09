# Phase 4 — Per-Session Rankings & Podium

**Status:** ✅ Done
**Depends on:** Phase 3
**Blocks:** Phase 5

## Goal

Turn recorded games into a per-session standing and surface the **1st / 2nd / 3rd podium**
plus a full standings table. Standings reset per session (no cross-session accumulation in MVP).

## Backend
- `OpenPlayStandingsService::forSession(OpenPlaySession $session): Collection`
  - Aggregate from `open_play_games` + `open_play_game_players`:
    - per player: `wins`, `losses`, `games_played`, `points_for`, `points_against`, `point_diff`.
  - **Ordering / tiebreakers:** `wins DESC` → `point_diff DESC` → `points_for DESC` → name.
  - Returns ranked rows (rank 1..n); expose top 3 as the podium.
- Computed on read (no stored standings table in MVP). Add a lightweight cache per session
  if the query proves heavy — optional.
- Expose standings on:
  - Client `open-play-detail` (once `Completed`, or live during `InProgress`).
  - Admin `manage` page (live preview).

## Frontend
- `PodiumCard` component — 1st / 2nd / 3rd with player name, wins, point diff. Rank shown as
  **text + medal icon** (not color-only) for accessibility.
- `StandingsTable` — full ranked table (rank, player, W-L, PF/PA, diff), responsive.

## UX states
No games recorded yet ("Standings appear once games are played") · in-progress (live) vs final label.

## Acceptance criteria
- [x] Standings match hand-computed results for a known set of games (unit/feature test with fixed data).
- [x] Tiebreakers apply in the documented order.
- [x] Podium + table render on client and admin, with empty/live/final states.
- [x] Validation suite green: 335 Pest tests, tsc, eslint, pint, build.

## Delivered
- `OpenPlayStandingsService::forSession()` — derives per-player `wins`/`losses`/`games_played`/`points_for`/`points_against`/`point_diff` from `open_play_games` + `open_play_game_players`; ranks `wins DESC → point_diff DESC → points_for DESC → name ASC` with positional ranks (1..n). Computed on read (no stored table).
- Shared components `components/open-play/podium-card.tsx` (1st/2nd/3rd with medal icon **and** ordinal text — not color-only) and `components/open-play/standings-table.tsx` (rank, player, W–L, PF/PA, diff; horizontally scrollable). `OpenPlayStanding` + `OpenPlayStandingsState` types.
- Admin `manage`: replaced the client-side preview with a service-backed **Standings** card (podium + table, Live/Final badge) — single source of truth.
- Client `open-play-detail`: standings section exposed when `InProgress` (Live) or `Completed` (Final), with the "Standings appear once games are played" empty state.
- Tests: `OpenPlayStandingsTest` (5) — hand-computed round-robin totals + wins/diff/points_for ordering, name as final tiebreaker (via identical teammate stat lines), empty standings, and client/admin exposure.

## Out of scope
Season/all-time leaderboard and computed ratings (Phase 7).
