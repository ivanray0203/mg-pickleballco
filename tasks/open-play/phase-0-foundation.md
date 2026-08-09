# Phase 0 — Foundation & Data Model

**Status:** ✅ Done
**Depends on:** nothing
**Blocks:** all later phases

## Goal

Establish the enums, tables, models, relationships, and factories that every later
phase builds on. No UI in this phase.

## Data model

### Enums (`app/Enums`)
- `SkillLevel` — self-rated buckets, three tiers: `Beginner`, `Novice`, `Expert` (with a `label()`).
- `OpenPlaySessionStatus` — `Scheduled`, `Open` (registration open), `Full`, `InProgress`, `Completed`, `Cancelled`.
- `RegistrationStatus` — `Reserved` (awaiting payment/hold), `Confirmed`, `Waitlisted`, `CheckedIn`, `Cancelled`.

### Migrations
- `users` — add `skill_level` (nullable, `SkillLevel` cast). Self-rated; editable in profile (Phase 2/5).
- `open_play_sessions`
  - `court_id` (FK), `title`, `session_date` (date), `start_time`, `end_time`
  - `capacity` (int, max players), `skill_level` (nullable target/min), `price_amount` (int centavos, 0 = free)
  - `status` (`OpenPlaySessionStatus`), `notes` (nullable), timestamps
- `open_play_registrations`
  - `open_play_session_id` (FK), `user_id` (FK), `status` (`RegistrationStatus`)
  - `payment_id` (nullable FK — reuses `payments`), `checked_in_at` (nullable), timestamps
  - unique(`open_play_session_id`, `user_id`) — a player joins a session once
- `open_play_games`
  - `open_play_session_id` (FK), `team_a_score` (int), `team_b_score` (int)
  - `winning_team` (`'a'|'b'`, derived on save), `played_at`, timestamps
- `open_play_game_players`
  - `open_play_game_id` (FK), `open_play_registration_id` (FK), `team` (`'a'|'b'`)
  - Standings are **derived** from these rows — no denormalized wins column in MVP.

### Models (`app/Models`)
- `OpenPlaySession` — `belongsTo(Court)`, `hasMany(OpenPlayRegistration)`, `hasMany(OpenPlayGame)`; helpers: `isFull()`, `confirmedCount()`, `remainingSlots()`.
- `OpenPlayRegistration` — `belongsTo(session, user, payment)`, `hasMany(gamePlayers)`.
- `OpenPlayGame` — `belongsTo(session)`, `hasMany(players)`; casts scores, computes `winning_team`.
- `OpenPlayGamePlayer` — `belongsTo(game, registration)`.
- Add `openPlayRegistrations()` relation to `User`.

### Factories & seeder
- Factory per model with sensible states (`open`, `full`, `completed`, `cancelled`).
- Optional: extend the demo seeder with a couple of sample sessions.

## Acceptance criteria
- [x] Migrations run clean (verified via `RefreshDatabase` on the full suite) and are reversible.
- [x] Models expose the relationships above; `php artisan test` green (292 tests, no behavior changed).
- [x] Factories create valid graphs (session → registrations → games → game players) — `OpenPlayFoundationTest`.
- [x] Pint clean; Larastan clean on new PHP (the 17 remaining Larastan errors are pre-existing, in unrelated files).

## Delivered
- Enums: `SkillLevel` (Beginner/Novice/Expert), `OpenPlaySessionStatus`, `RegistrationStatus`.
- Migrations: `open_play_sessions`, `open_play_registrations`, `open_play_games`, `open_play_game_players`, `users.skill_level`.
- Models + relationships: `OpenPlaySession`, `OpenPlayRegistration`, `OpenPlayGame`, `OpenPlayGamePlayer`, `User.openPlayRegistrations()`.
- Factories with states; `tests/Feature/OpenPlay/OpenPlayFoundationTest.php` (6 tests).

## Out of scope
Controllers, routes, pages, payment, notifications, standings queries — all later phases.
