# Pickleball Booking

A court booking and open-play management system for a pickleball facility. Customers reserve courts, rent equipment, join open-play sessions, and pay online via GCash; administrators manage courts, schedules, sessions, seasons, payments, and reporting from a dedicated admin area.

Built with Laravel 13, Inertia.js v3, React 19, and Tailwind CSS v4.

## Purpose

Running a pickleball venue by hand means juggling a paper schedule, chat-message reservations, cash payments, and manually paired open-play games. This application replaces that with a single system that:

- Publishes real court availability derived from weekly schedules minus blocks and existing bookings.
- Holds a slot while the customer pays, then releases it automatically if payment lapses.
- Collects payment online (GCash via PayMongo) and reconciles it through a signed webhook.
- Runs open-play session days end to end: registration, check-in, automatic 2v2 pairing, score recording, and season standings.
- Keeps an audit trail and operational reports for the venue owner.

## Features

### Customer

- **Court discovery** — browse courts (indoor/outdoor) with photos, pricing, and details at clean slug-based URLs.
- **Booking flow** — pick a court, date, and time slot; review the summary; place a hold; then check out. Unpaid holds expire automatically and free the slot.
- **Equipment rental** — add paddles, balls, and other rentable items to a booking, with availability checked against the same time window.
- **GCash payments** — checkout, success/cancel handling, and a personal payment history.
- **My Bookings** — upcoming and past reservations with status and payment details.
- **Open play** — browse sessions, register (with a payment hold), cancel a registration, and self-report game scores for organizer confirmation.
- **Leaderboard** — season standings computed from recorded open-play games.
- **Notifications** — in-app notification center plus optional email for confirmations, cancellations, day-before reminders, and "starting soon" alerts.
- **Accounts** — email/password registration with verification, Google and Facebook social login, two-factor authentication, passkeys, and profile/password settings.

### Administrator

- **Dashboard & reports** — operational metrics and revenue/utilization reporting.
- **Courts** — create and edit courts, upload images, toggle active status.
- **Schedules & availability** — per-court weekly operating hours, an availability calendar, and ad-hoc blocks (maintenance, private events) with reasons.
- **Equipment** — manage rentable inventory and its availability.
- **Bookings** — daily schedule view, full booking list, booking detail, cancellation, and **walk-in booking** creation for customers who show up at the counter.
- **Open play** — create and edit sessions, cancel them, and run a session-day console: check players in, generate auto-paired 2v2 matchups across multiple courts, assign courts, record results, and complete the session.
- **Result submissions** — confirm or reject scores players submitted themselves.
- **Seasons** — define seasons and view their leaderboards.
- **Payments** — read-only monitoring of payments and their provider state.
- **Customers** — customer list and detail for operational visibility.
- **Settings** — business-level configuration.
- **Audit log** — records of significant administrative actions.

### Scheduled background jobs

| Command | Frequency | Purpose |
| --- | --- | --- |
| `bookings:expire-holds` | every minute | Release lapsed booking holds |
| `open-play:expire-holds` | every minute | Release lapsed open-play registration holds |
| `bookings:send-starting-soon` | every minute | Alert customers ~10 minutes before start |
| `bookings:send-reminders` | hourly | Remind customers of bookings within a day |
| `open-play:send-reminders` | hourly | Remind registrants of sessions within a day |

These require a running scheduler (`php artisan schedule:work`) and queue worker.

## Roles

There are two roles, defined in `app/Enums/UserRole.php`:

| Role | Value | Access |
| --- | --- | --- |
| **Administrator** | `admin` | Everything under `/admin/*`, guarded by the `admin` middleware — courts, schedules, equipment, bookings, walk-ins, open play, seasons, payments, customers, settings, and reports. |
| **Customer** | `customer` | The unprefixed customer area — dashboard, courts, booking, payments, open play, leaderboard, and notifications. This is the default role for self-registered users. |

`role` is intentionally **not mass-assignable** — it can only be set explicitly in code (see `AdminUserSeeder`). Every authenticated route also requires a verified email.

## Tech stack

- **Backend** — PHP 8.4, Laravel 13, Laravel Fortify (auth), Laravel Socialite (OAuth), Laravel Wayfinder (typed route helpers)
- **Frontend** — Inertia.js v3, React 19, TypeScript, Tailwind CSS v4, Radix UI, Vite
- **Payments** — PayMongo (GCash)
- **Testing & quality** — Pest 5, Larastan (PHPStan), Pint, ESLint, Prettier

## Requirements

- PHP **8.4** (8.3 minimum) with the usual Laravel extensions
- Composer 2
- Node.js 20+ and npm
- SQLite (default) or MySQL/PostgreSQL

## Clone & setup

```bash
git clone <repository-url> picke-ball
cd picke-ball
```

### Quick path

```bash
composer setup
```

This runs `composer install`, copies `.env.example` to `.env`, generates the app key, migrates, then runs `npm install` and `npm run build`.

### Manual path

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

# SQLite (default) — create the database file
touch database/database.sqlite   # Windows: type nul > database\database.sqlite

php artisan migrate
php artisan db:seed

npm install
```

To use MySQL instead, set these in `.env` before migrating:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pickleball
DB_USERNAME=root
DB_PASSWORD=
```

### Seeded accounts

`php artisan db:seed` creates a default administrator plus sample courts, bookings, and open-play data:

- **Email:** `admin@example.com`
- **Password:** `password`

Change these before deploying anywhere real. Customer accounts are created by registering through the app.

## Environment configuration

Everything below is optional for local development — the app runs without it, using log-based mail and no live payments.

```dotenv
# Payments (GCash via PayMongo). Leave blank locally.
PAYMENT_DRIVER=paymongo
PAYMONGO_SECRET_KEY=
PAYMONGO_WEBHOOK_SECRET=

# Social login (OAuth)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=

# Master switch for outgoing email (in-app notifications are unaffected)
ENABLE_EMAIL_NOTIFICATION=true
```

The PayMongo webhook endpoint is `POST /webhooks/paymongo`; it is CSRF-exempt and authenticated by verifying the provider signature. For local testing, expose it with a tunnel (ngrok, Expose) and register that URL with PayMongo.

## Running the app

```bash
composer dev
```

Starts the PHP server, the queue worker, and Vite together on http://localhost:8000.

Or run each piece yourself:

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

To exercise the scheduled jobs (hold expiry, reminders), also run:

```bash
php artisan schedule:work
```

## Common commands

```bash
# Tests
php artisan test --compact
php artisan test --compact --filter=BookingTest

# Full check: format, static analysis, tests
composer ci:check

# Formatting & static analysis
vendor/bin/pint            # PHP formatting
composer types:check       # PHPStan / Larastan
npm run lint               # ESLint
npm run format             # Prettier
npm run types:check        # TypeScript

# Production assets
npm run build
```

## Project layout

```
app/
  Console/Commands/   Scheduled jobs (hold expiry, reminders)
  Enums/              Roles, statuses, court/payment/skill types
  Http/Controllers/
    Admin/            Admin area
    Client/           Customer area
    Auth/             Social login
    Webhooks/         PayMongo webhook
  Models/             Courts, bookings, payments, open play, seasons
  Notifications/      Booking & open-play notifications
  Services/           Domain logic (availability, booking, payments, standings)
database/
  migrations/  factories/  seeders/
resources/js/
  pages/admin/  pages/client/  pages/auth/  pages/settings/
routes/
  web.php      Customer routes
  admin.php    Admin routes (/admin/*)
  settings.php Profile & security settings
  console.php  Schedule definitions
tests/          Pest feature and unit tests
```

## Troubleshooting

- **"Unable to locate file in Vite manifest"** — run `npm run dev` or `npm run build`.
- **Frontend changes not showing** — Vite isn't running; start `npm run dev` or rebuild.
- **Route helpers missing from `@/actions` or `@/routes`** — run `php artisan wayfinder:generate`, or just start Vite (the Wayfinder plugin regenerates them).
- **Emails not arriving locally** — `MAIL_MAILER=log` by default; check `storage/logs/laravel.log`.
- **Holds never expiring / reminders never sending** — the scheduler isn't running; start `php artisan schedule:work`.
