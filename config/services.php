<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'payment' => [
        // Which PaymentGateway implementation to resolve. See AppServiceProvider.
        'driver' => env('PAYMENT_DRIVER', 'paymongo'),
    ],

    /*
     * Fallbacks only. An admin can override these from Settings → PayMongo,
     * in which case the stored (encrypted) value wins. See App\Settings\AppSettings.
     */
    'paymongo' => [
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'success_url' => env('PAYMONGO_SUCCESS_URL'),
        'cancel_url' => env('PAYMONGO_CANCEL_URL'),

        /*
         * Render the hosted checkout in an on-site modal instead of sending the
         * customer away to checkout.paymongo.com.
         *
         * Leave this off until PayMongo has added this site's domain to the
         * `frame-ancestors` directive on checkout.paymongo.com — until they do,
         * the browser refuses to render the frame and the customer sees a blank
         * modal. Ask support@paymongo.com to allowlist the domain first.
         */
        'embed_checkout' => env('PAYMONGO_EMBED_CHECKOUT', false),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

];
