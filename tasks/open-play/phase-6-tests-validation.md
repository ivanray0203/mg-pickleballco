# Phase 6 — Tests & Final Validation

**Status:** ✅ Done
**Depends on:** Phase 5
**Blocks:** ships the MVP

## Goal

Comprehensive coverage and a clean full validation run for the whole Open Play feature.

## Test coverage
- **Sessions (admin):** CRUD, collision rejection vs bookings/other sessions, cancel frees window, authorization.
- **Registration:** register/cancel, capacity enforcement, **concurrency** (parallel registrations don't oversell), waitlist promotion, ownership.
- **Payment:** paid session confirms only on verified webhook; free session confirms immediately; failed/expired handled.
- **Game recording:** valid/invalid teams, scores, ties rejected; delete/edit; authorization.
- **Standings:** fixed-data fixture → exact ranks; tiebreaker order; podium selection.
- **Notifications:** each event dispatched (queued) and scoped to the right user; reminder duplicate-safe.
- **Skill level:** self-rating persists; session filtering by level.

## Final validation (run all, fix regressions)
- [x] `php artisan test --compact` — **347 passed**, 1653 assertions
- [x] `npm run types:check` — clean
- [x] `npm run lint:check` — clean
- [x] `npm run format` (prettier) — clean (reformatted 3 new files)
- [x] `vendor/bin/pint --dirty` — clean
- [x] Larastan — **entire Open Play domain 0 errors**; 18 pre-existing errors remain only in unrelated files (`ReportService`, `DashboardService`, `SocialAuthController`, `DailyScheduleService`, `Client\BookingController`) — untouched by this feature
- [x] `npm run build` — succeeds

## Coverage added this phase
- Closed the two checklist gaps that lacked explicit tests: **client list filtering by skill level** (`ClientOpenPlayTest`) and **profile skill self-rating persistence** (`ProfileUpdateTest`).
- Fixed the one new Larastan error (`OpenPlayStandingsService` return shape) and cleaned the pre-existing `OpenPlayService::persist` `findOrFail` union so the whole feature domain is static-analysis clean.

### Note on the checklist's "waitlist promotion"
Waitlist promotion was **explicitly deferred in Phase 2** (full → hard reject in the MVP); there is no waitlist code to test. Concurrency is covered structurally by the row-locked count-before-insert (`OpenPlayService::register`) and the capacity-rejection test, not a true parallel-process test (not feasible in the suite).

## Deliverable — Open Play MVP feature summary

**Overview.** Drop-in session play layered on the existing court-booking system: an admin opens a
court window as a session, customers register individually (free or paid via GCash), the organizer
checks players in and records games, and each session produces a wins-ranked 1st/2nd/3rd podium.

**Data model** (Phase 0). Enums `SkillLevel` (Beginner/Novice/Expert), `OpenPlaySessionStatus`
(Scheduled/Open/Full/InProgress/Completed/Cancelled), `RegistrationStatus`
(Reserved/Confirmed/Waitlisted/CheckedIn/Cancelled). Tables `open_play_sessions`,
`open_play_registrations` (unique per session+user; `payment_id`, `checked_in_at`, `expires_at`,
`reminder_sent_at`), `open_play_games`, `open_play_game_players`; `users.skill_level`. Standings are
derived — no denormalized wins column.

**Routes.**
- Admin (`admin.open-play.*`, behind `auth`+`verified`+`admin`): resource `index/create/store/edit/update`,
  `patch cancel`, `get manage`, `patch complete`, `patch registrations/{registration}/check-in` +
  `delete` (undo), `post {session}/games`, `delete games/{game}`.
- Client (`open-play.*`, behind `auth`+`verified`): `index/show/register/pay`,
  `delete registrations/{registration}` (cancel). Notification center gained a `session_id` link target.

**Services.**
- `OpenPlayService` — session CRUD with a transactional `lockForUpdate` collision guard vs bookings
  **and** other sessions; concurrency-safe `register` (count-before-insert, free→Confirmed,
  paid→Reserved hold) / `cancelRegistration`; `cancel` notifies slot-holders.
- `OpenPlayGameService` — `checkIn`/`undoCheckIn`, `recordGame` (membership + even-team + no-tie
  validation, derives winner, first game → InProgress), `deleteGame`, `complete` (→ Completed, notifies).
- `OpenPlayStandingsService::forSession` — derives per-player W/L/GP/PF/PA/diff, ranks
  wins→point_diff→points_for→name. Computed on read.
- **Reused:** `CourtAvailabilityService` (single source of truth for court windows), `PaymentService`
  + `PayMongoGateway` (per-player fees; webhook confirmation), `AuditLogger`, the notification center.

**Flows.**
- *Registration:* free → Confirmed immediately (+notify); paid → Reserved hold → GCash checkout →
  confirmed only on the **verified webhook** (+notify); lapsed holds released by
  `open-play:expire-holds`. Capacity never oversold (row-locked count).
- *Session day:* check players in → record 1v1/2v2 games (server-validated) → live standings preview →
  Complete to finalize (locks recording, posts results).
- *Rankings:* podium + standings table on the admin console (Live) and client detail (Live while
  InProgress, Final when Completed).

**Notifications** (queued `database`+`mail`): `OpenPlayRegistrationConfirmed`, `OpenPlaySessionReminder`,
`OpenPlaySessionCancelled`, `OpenPlayResultsPosted` — all surfaced in `/notifications` via `session_id`.

**Tests** (Open Play–specific): `OpenPlayFoundationTest`, `AdminOpenPlaySessionTest`, `ClientOpenPlayTest`,
`AdminOpenPlayGameTest`, `OpenPlayStandingsTest`, `OpenPlayReminderTest`, `OpenPlayNotificationsTest`,
plus open-play cases in `PaymentWebhookTest` and skill-level cases in `ProfileUpdateTest`. Full suite: 347 green.

**Queue + scheduler requirements.**
- A **queue worker** must run (notifications implement `ShouldQueue`): `php artisan queue:work`.
- **Scheduler** (`php artisan schedule:run` every minute) drives: `open-play:expire-holds` (every minute),
  `open-play:send-reminders` (hourly), alongside the booking commands.

**Env / deploy notes.** Requires the same PayMongo config as bookings
(`services.paymongo.secret_key`, `services.paymongo.webhook_secret`) and the registered
`webhooks.paymongo` endpoint — no new payment source. Local dev needs a public URL (ngrok) for the
PayMongo webhook. After pulling: `php artisan migrate`, `php artisan wayfinder:generate --with-form`,
`npm run build`. Refunds on session cancellation are a **policy hook only** (not automated).

## Out of scope
Anything in Phase 7 (season/all-time leaderboard, computed ratings). Waitlist promotion and refund
automation remain deferred.
