<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingStartingSoon;

beforeEach(function () {
    $this->booking = Booking::factory()
        ->for(User::factory())
        ->for(Court::factory())
        ->create();
});

test('the mail channel is included when email notifications are enabled', function () {
    config()->set('mail.notifications.email_enabled', true);

    $channels = (new BookingConfirmed($this->booking))->via($this->booking->user);

    expect($channels)->toBe(['database', 'mail']);
});

test('the mail channel is dropped when email notifications are disabled', function () {
    config()->set('mail.notifications.email_enabled', false);

    $confirmed = (new BookingConfirmed($this->booking))->via($this->booking->user);
    $startingSoon = (new BookingStartingSoon($this->booking))->via($this->booking->user);

    expect($confirmed)->toBe(['database']);
    expect($startingSoon)->toBe(['database']);
});
