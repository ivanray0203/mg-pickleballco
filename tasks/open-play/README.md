# Open Play — Feature Plan & Phase Tracker

Open Play adds **drop-in session play** on top of the existing private court-booking
system: a facility opens a court for a time window, many players register individually,
the organizer records game results, and each session produces a **1st / 2nd / 3rd podium**
ranked by wins.

This folder is the **source of truth for phase progress**. Update the status table below
and the `Status` line inside each phase file as work proceeds.

---

## Scope decisions (locked)

| Decision | Choice |
|---|---|
| Ranking scope | **Per-session podium first** — standings reset each session; season/all-time leaderboard is a future phase |
| Score entry | **Admin/organizer only** — staff record match results; no player self-reporting in MVP |
| Player rating | **Self-rated skill buckets** — three tiers: **Beginner / Novice / Expert** — no computed rating in MVP |
| Payment | Flat per-player fee reusing the existing `PaymentService` + GCash; free sessions allowed |

## What we reuse (do not rebuild)

- `Court`, `CourtBlock`, `CourtAvailabilityService` — sessions occupy a court window and must not collide with private bookings.
- `PaymentService` + `PayMongoGateway` — per-player registration fees.
- Laravel notifications (queued) — registration/reminder/cancelled/results.
- Admin `Route::resource` + desktop-table / mobile-card UI conventions.
- `AuditLogger` for admin actions.

---

## Phase status

| # | Phase | File | Status |
|---|---|---|---|
| 0 | Foundation & Data Model | [phase-0-foundation.md](phase-0-foundation.md) | ✅ Done |
| 1 | Admin — Session Management | [phase-1-admin-sessions.md](phase-1-admin-sessions.md) | ✅ Done |
| 2 | Customer — Discovery & Registration | [phase-2-registration.md](phase-2-registration.md) | ✅ Done |
| 3 | Admin — Check-in & Game Recording | [phase-3-game-recording.md](phase-3-game-recording.md) | ✅ Done |
| 4 | Per-Session Rankings & Podium | [phase-4-rankings.md](phase-4-rankings.md) | ✅ Done |
| 5 | Notifications & UX Polish | [phase-5-notifications-polish.md](phase-5-notifications-polish.md) | ✅ Done |
| 6 | Tests & Final Validation | [phase-6-tests-validation.md](phase-6-tests-validation.md) | ✅ Done |
| 7 | (Post-MVP) Season Leaderboard & Ratings | [phase-7-postmvp.md](phase-7-postmvp.md) | 🟡 Leaderboard + self-report ✅; rating/formats/refunds future |

**Status legend:** ⬜ Not started · 🟡 In progress · ✅ Done · 🔮 Future/optional

**Current phase:** 🎉 **MVP complete — Phases 0–6 ✅ shipped.** Phase 7 (season leaderboard & ratings) is optional/future.

Phases 0→6 are sequential (each depends on the previous). Phase 7 is optional and only
begins after the MVP (0–6) ships.
