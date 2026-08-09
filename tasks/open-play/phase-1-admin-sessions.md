# Phase 1 — Admin: Session Management

**Status:** ✅ Done
**Depends on:** Phase 0
**Blocks:** Phase 2

## Goal

Let an admin create, edit, and cancel open-play sessions, and guarantee a session's
court/time window never collides with private bookings or other sessions.

## Backend
- `App\Services\OpenPlayService`
  - `create(array $data)` / `update(...)` / `cancel(...)`.
  - **Collision guard:** reuse `CourtAvailabilityService` + a transactional `lockForUpdate()`
    overlap check against `bookings` (`reservesSlot`) **and** other `open_play_sessions`
    for the same court/date — mirror `BookingService::create`.
  - On create, optionally write a `CourtBlock` for the window so the private booking flow
    already treats the court as unavailable (single source of truth for availability).
  - Whole-hour enforcement consistent with bookings.
- `Admin\OpenPlaySessionController` — `Route::resource('open-play', ...)` under the existing
  admin group + `patch open-play/{session}/cancel`.
- Validation via a Form Request (`capacity >= 4`, valid court, future date, end > start, price ≥ 0).
- `AuditLogger` entries for create/update/cancel.

## Frontend (`resources/js/pages/admin/open-play/`)
- `index.tsx` — sessions list (desktop table + mobile cards): date, court, time, capacity/registered, status badge, actions.
- `create.tsx` / `edit.tsx` — form (court select, date, time window, capacity, skill level, price, notes).
- Status badge component with **text label** (not color-only) reusing the badge pattern.
- Wayfinder route helpers regenerated (`--with-form`).

## UX states
Loading · empty ("No sessions scheduled") · validation errors · cancel confirmation dialog.

## Acceptance criteria
- [x] Admin can CRUD + cancel sessions.
- [x] Creating a session over an existing booking (or overlapping session) is rejected with a clear error.
- [x] Cancelled sessions free the court window (verified via availability service).
- [x] Feature tests: CRUD, collision rejection, operating-hours, authorization — `AdminOpenPlaySessionTest` (11 tests).
- [x] Validation suite green: 302 Pest tests, tsc, eslint, pint, build.

## Delivered
- `OpenPlayService` (create/update/cancel) with transactional `lockForUpdate()` collision guard vs bookings **and** other sessions, plus operating-hours enforcement.
- `CourtAvailabilityService` extended: active session windows now block private bookings (single source of truth).
- `Admin\OpenPlayController` + resource routes (`admin.open-play.*`) + `cancel`; `OpenPlaySessionPolicy`; Store/Update form requests + shared `OpenPlaySessionValidationRules`; `OpenPlaySessionResource`.
- Admin pages: `admin/open-play/{index,create,edit}.tsx`, `OpenPlaySessionForm`, `SessionStatusBadge` (colored dot + text label), "Open play" nav item, cancel confirmation dialog.

## Known residual (acceptable for MVP)
A private booking and a session created in the exact same instant lock disjoint rows, so the availability check (not the lock) is what separates them — extremely unlikely in practice. Revisit if concurrent admin+customer writes ever become common.

## Out of scope
Customer-facing discovery/registration (Phase 2); game recording (Phase 3).
