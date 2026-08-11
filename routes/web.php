<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Client\BookingController;
use App\Http\Controllers\Client\CourtController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\LeaderboardController;
use App\Http\Controllers\Client\NotificationController;
use App\Http\Controllers\Client\OpenPlayController;
use App\Http\Controllers\Client\OpenPlayGameSubmissionController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Webhooks\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Social (OAuth) login. Guests only; the provider is validated in the
// controller against an allow-list.
Route::middleware('guest')->group(function () {
    Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->name('oauth.redirect');
    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->name('oauth.callback');
});

// Customer (client) area. A customer is a User with the customer role; these
// routes are not prefixed — admin stays under /admin/*.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Court discovery. Detail is bound by slug for clean public URLs without
    // changing the model's default (id) binding used by admin routes.
    Route::get('courts', [CourtController::class, 'index'])->name('courts.index');
    Route::get('courts/{court:slug}', [CourtController::class, 'show'])->name('courts.show');

    // Booking selection → review → hold. Payment is handled in a later phase.
    Route::get('book', [BookingController::class, 'create'])->name('book');
    Route::get('book/review', [BookingController::class, 'review'])->name('book.review');
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

    // GCash checkout (reuses the shared Payment domain + PaymentGateway).
    Route::get('bookings/{booking}/checkout', [PaymentController::class, 'checkout'])->name('bookings.checkout');
    Route::post('bookings/{booking}/pay', [PaymentController::class, 'pay'])->name('bookings.pay');
    Route::get('bookings/{booking}/payment/success', [PaymentController::class, 'success'])->name('bookings.payment.success');
    Route::get('bookings/{booking}/payment/cancel', [PaymentController::class, 'cancel'])->name('bookings.payment.cancel');

    // My Bookings and Payment History (customer-scoped).
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [PaymentController::class, 'detail'])->name('payments.show');

    // Open play discovery & registration (customer-facing).
    Route::get('open-play', [OpenPlayController::class, 'index'])->name('open-play.index');
    Route::delete('open-play/registrations/{openPlayRegistration}', [OpenPlayController::class, 'cancel'])
        ->name('open-play.registrations.cancel');
    Route::get('open-play/{openPlaySession}', [OpenPlayController::class, 'show'])->name('open-play.show');
    Route::post('open-play/{openPlaySession}/register', [OpenPlayController::class, 'register'])->name('open-play.register');
    Route::post('open-play/{openPlaySession}/pay', [OpenPlayController::class, 'pay'])->name('open-play.pay');
    Route::post('open-play/{openPlaySession}/submissions', [OpenPlayGameSubmissionController::class, 'store'])
        ->name('open-play.submissions.store');

    // Season leaderboard (public to signed-in customers).
    Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

    // Notification center (scoped to the authenticated customer in the controller).
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

// Public payment provider webhook. Authenticity is enforced by verifying the
// provider signature inside the controller, not by session auth; it is
// CSRF-exempt (see bootstrap/app.php).
Route::post('webhooks/paymongo', PayMongoWebhookController::class)
    ->name('webhooks.paymongo');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
