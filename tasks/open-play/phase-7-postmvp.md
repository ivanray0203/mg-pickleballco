# Phase 7 — (Post-MVP) Season Leaderboard & Ratings

**Status:** 🟡 In progress — Season leaderboard ✅ and Player self-reported scores ✅ shipped; rating, formats, and refund automation still future.
**Depends on:** Phases 0–6 shipped
**Blocks:** nothing

## Goal

Extend per-session rankings into longer-lived competition. Only start once the MVP is live
and the client wants it. Each item below is independently shippable.

## Candidate features
- ✅ **Season / all-time leaderboard** — **DONE.**
  - `seasons` table (name, window, is_active) + `Season` model/factory; admin CRUD
    (`admin.seasons.*`) + `SeasonPolicy`; `SeasonStandingsService::forSeason()` aggregates
    wins/games/point-diff across every non-cancelled session in the window, **computed on read**
    (no materialized table — data volumes are small; revisit if leaderboards get heavy).
  - Client leaderboard (`leaderboard.index`) with season selector + skill filter; admin
    per-season leaderboard view. Podium + table components reused from Phase 4. Skill filter
    applies to players before ranking. Nav items added (admin "Seasons", client "Leaderboard").
  - Tests: `SeasonLeaderboardTest` (8).
- ✅ **Player self-reported scores** — **DONE.**
  - `open_play_game_submissions` table (proposed teams as registration-id JSON + scores + status,
    reporter, resolver, resulting game) + `OpenPlayGameSubmissionStatus` enum + model/factory.
  - `OpenPlayGameSubmissionService`: `submit` (reporter must be a confirmed/checked-in participant
    **and** in the game), `confirm` (runs full `OpenPlayGameService::recordGame` validation → creates
    the authoritative game; stays pending on failure), `reject`. Client submit endpoint
    (`open-play.submissions.store`); admin confirm/reject (`admin.open-play.submissions.*`) with audit.
  - Client detail "Report a result" form (in-progress sessions, participants only); admin manage
    "Reported results" review section. Tests: `OpenPlayGameSubmissionTest` (8).
- 🟡 **Formats & scheduling helpers** — **auto-pairing match queue DONE** (rotation/paddle-board variants still future).
  - `open_play_matchups` table + `MatchupStatus` enum (Queued/Playing/Done) + model/factory.
  - `OpenPlayMatchupService`: `generate` (shuffles checked-in roster into random **2v2** matchups, chunks of four; leftovers sit out; appends to the queue and promotes the front to **Playing**), `recordResult` (scores the playing match via `OpenPlayGameService::recordGame` → real game + standings, then **auto-promotes the next queued match**), `cancel` (removes a match, advancing the queue), `clearQueue`. One match plays at a time (single court).
  - Admin routes `admin.open-play.matchups.*` (generate/record/destroy/clear) + controller with audit; `MatchQueueCard` on the manage console (Shuffle, playing match with inline score entry → "Record & next", queued list with remove, Clear). Scoring reuses the existing record path — no parallel scoring system.
  - Tests: `OpenPlayMatchupTest` (11).
- 🔮 **Computed rating (DUPR-style)** — not started. Prefer integrating real DUPR over inventing one.
- 🔮 **Refund automation** for cancelled sessions — not started; builds on the Phase 5 policy hook.

## Delivered — validation
374 Pest tests green; tsc, eslint, prettier, build clean; Larastan clean across the entire Open
Play domain (18 remaining errors are all pre-existing, in unrelated files). Requires
`php artisan migrate` (seasons, game submissions, and matchups tables). `OpenPlaySeeder` now also
seeds a live match queue.

## Notes
Keep the MVP's per-session standings as the foundation — the season leaderboard is an
aggregation on top of the same `open_play_games` data, so no rework of Phases 0–6 is expected.
