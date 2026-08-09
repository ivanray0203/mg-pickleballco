# Phase 5 — Notifications & UX Polish

**Status:** ✅ Done
**Depends on:** Phase 4
**Blocks:** Phase 6

## Goal

Wire open-play events into the existing notification center and finish responsive/accessibility polish.

## Notifications (reuse queued `database` + `mail` pattern)
- `OpenPlayRegistrationConfirmed` — after free registration or verified payment.
- `OpenPlaySessionReminder` — scheduled command before an upcoming session; duplicate-safe
  via a `reminder_sent_at` column on `open_play_sessions` (mirror `SendBookingReminders`).
  Register in `routes/console.php` scheduler.
- `OpenPlaySessionCancelled` — notify all confirmed registrants when a session is cancelled
  (and trigger refunds/void per policy — hook only in MVP).
- `OpenPlayResultsPosted` — optional: notify players when standings are finalized.
- Surface all of these in the existing `/notifications` center (already generic on `data.title/message/booking_id`; add an optional `session_id` link target).

## Polish
- Responsive review of every new page (list, detail, admin index/create/edit/manage): desktop / tablet / mobile — no horizontal overflow, tables → cards on mobile.
- Accessibility: keyboard nav, focus states, form labels, button semantics, contrast, disabled states, screen-reader status text; **status never conveyed by color alone**.
- Loading / empty / error / success / unavailable states verified on each page.

## Acceptance criteria
- [x] All four events dispatch (queued) and appear in the notification center.
- [x] Reminder command is duplicate-safe and scheduled.
- [x] New pages pass a responsive + a11y pass.
- [x] Validation suite green: 345 Pest tests, tsc, eslint, pint, build.

## Delivered
- Four queued (`ShouldQueue`) `database`+`mail` notifications, mirroring the booking payload shape but keyed on `session_id` instead of `booking_id`: `OpenPlayRegistrationConfirmed`, `OpenPlaySessionReminder`, `OpenPlaySessionCancelled`, `OpenPlayResultsPosted`.
- Dispatch sites: free registration → `Client\OpenPlayController::register`; paid registration → `PaymentService::notifyCustomer` (post-commit, alongside the booking branch); session cancel → `OpenPlayService::cancel` (all occupying registrants); session complete → `OpenPlayGameService::complete` (all occupying registrants).
- `open-play:send-reminders` command — one-time, duplicate-safe via `reminder_sent_at`, targets **Confirmed/CheckedIn** registrants (not unpaid Reserved holds) of sessions within the next day; scheduled hourly in `routes/console.php`.
- Notification center taught the `session_id` key: `NotificationController` forwards it, `types/notification.ts` + `notifications.tsx` render a "View session" link; the shared `unreadNotifications` badge already covers the new types. Empty-state copy updated.
- Tests: `OpenPlayReminderTest` (5 — window, duplicate-safety, status/reserved exclusions), `OpenPlayNotificationsTest` (4 — free confirm, cancel, results, center link), plus a paid-webhook confirmation-notification case in `PaymentWebhookTest`.

## A11y / responsive measures in place
Status conveyed by dot **+ text** label (never color-only); `aria-live` on the notification summary; `aria-label`s on the team A/B toggles; tables wrapped in `overflow-x-auto` with mobile card fallbacks on the index; keyboard-focusable shadcn primitives with disabled states on all mutating actions.

## Out of scope
Refund automation (policy hook only); real-time updates.
