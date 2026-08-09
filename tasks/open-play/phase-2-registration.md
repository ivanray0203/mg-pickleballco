# Phase 2 — Customer: Discovery & Registration

**Status:** ✅ Done (free **and** paid)
**Depends on:** Phase 1
**Blocks:** Phase 3

## Goal

Customers discover upcoming open-play sessions, register (with capacity enforced safely),
pay any per-player fee, and can cancel to free their slot.

## Backend
- `Client\OpenPlayController`
  - `index` — upcoming, registerable sessions; filter by date and skill level.
  - `show` — session detail: time, court, price, capacity/remaining, own registration state.
  - `register` — **concurrency-safe** join: transactional `lockForUpdate()` on the session,
    count confirmed registrations, reject / waitlist when at capacity. Mirrors the booking-hold
    pattern so two simultaneous registrations can't oversell the last slot.
  - `cancelRegistration` — release a slot; promote first waitlisted player if applicable.
- Payment:
  - Free session → registration `Confirmed` immediately.
  - Paid session → create a `Payment` via `PaymentService` (GCash checkout); registration is
    `Reserved` until the **webhook** confirms, then flips to `Confirmed`. Reuse the existing
    webhook path — no new confirmation source.
- Profile: let customers set their `skill_level` (settings/profile page).

## Frontend (`resources/js/pages/client/`)
- `open-play.tsx` — session list with skill/date filters, capacity + "X spots left".
- `open-play-detail.tsx` — details, register / cancel button, payment handoff, states.
- Add an "Open Play" item to `clientNavItems`.

## UX states (required)
Loading · empty ("No open play sessions") · full ("Session full — join waitlist" / disabled) ·
already registered · payment pending · payment failed · registration confirmed · session cancelled/closed.

## Acceptance criteria
- [x] Customer can browse, filter, register, pay, and cancel.
- [x] Capacity is never exceeded (row-locked count-before-insert; verified to reject overflow).
- [x] Paid registrations confirm only via verified webhook (not the redirect).
- [x] Ownership enforced (can't cancel another player's registration → 403).
- [x] Validation suite green: 318 Pest tests, tsc, eslint, pint, build.

## Delivered
- **Payment domain generalized (additive, all prior tests still green):** `payments.booking_id` nullable + new nullable `open_play_registration_id`; `PayMongoGateway.createCheckout` branches its line-item name + success/cancel URLs by target; `PaymentService` gains `startCheckoutForRegistration`/`initiateForRegistration` + a shared `openCheckout`; `processWebhook` confirms the registration on paid and releases it (Cancelled) on failed/expired; booking notifications unchanged.
- **Registration hold expiry:** `open_play_registrations.expires_at` + `open-play:expire-holds` command (scheduled every minute).
- `OpenPlayService::register` (concurrency-safe capacity via `lockForUpdate`, free→Confirmed, paid→Reserved hold, reactivates a cancelled row) + `cancelRegistration`.
- `Client\OpenPlayController` (index/show/register/pay/cancel) + routes; customer pages `client/open-play.tsx` (grid + skill/date filters) and `client/open-play-detail.tsx` (register/pay/cancel, payment-pending polling, all UX states); "Open Play" nav item.
- **Profile self-rating:** skill level select on `settings/profile`, `users.skill_level` update wired through `ProfileUpdateRequest`.
- Tests: `ClientOpenPlayTest` (11) + 2 registration webhook cases in `PaymentWebhookTest`.

## Deferred to later phase
Waitlist promotion (currently full → hard reject); registration/confirmation notifications (Phase 5).

## Out of scope
Check-in and score recording (Phase 3); standings (Phase 4).
