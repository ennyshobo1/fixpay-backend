<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    // ── FixPay custom providers ─────────────────────────────────────────────

    'kyc' => [
        'provider' => env('KYC_PROVIDER', 'mock'),
    ],

    'aml' => [
        'provider' => env('AML_PROVIDER', 'mock'),
    ],

    'prembly' => [
        'api_key' => env('PREMBLY_API_KEY', ''),
        'app_id' => env('PREMBLY_APP_ID', ''),
        'base_url' => env('PREMBLY_BASE_URL', 'https://api.prembly.com/identitypass'),
    ],

    'youverify' => [
        'api_key' => env('YOUVERIFY_API_KEY', ''),
        'base_url' => env('YOUVERIFY_BASE_URL', 'https://api.youverify.co/v2'),
    ],

    'smileid' => [
        'api_key' => env('SMILEID_API_KEY', ''),
        'partner_id' => env('SMILEID_PARTNER_ID', ''),
        'base_url' => env('SMILEID_BASE_URL', 'https://testapi.smileidentity.com/v1'),
    ],

    'providus' => [
        'client_id' => env('PROVIDUS_CLIENT_ID', ''),
        'client_secret' => env('PROVIDUS_CLIENT_SECRET', ''),
        'base_url' => env('PROVIDUS_BASE_URL', 'https://api.providusbank.com'),
        'mock' => env('PROVIDUS_MOCK', true),
    ],

    'vtpass' => [
        'api_key' => env('VTPASS_API_KEY', ''),
        'secret_key' => env('VTPASS_SECRET_KEY', ''),
        'public_key' => env('VTPASS_PUBLIC_KEY', ''),
        'base_url' => env('VTPASS_BASE_URL', 'https://sandbox.vtpass.com/api'),
    ],


    'digital_bank' => [

        'base_url' => env('DIGITAL_BANK_BASE_URL'),

        'username' => env('DIGITAL_BANK_USERNAME'),

        'password' => env('DIGITAL_BANK_PASSWORD'),

        'client_id' => env('DIGITAL_BANK_CLIENT_ID'),

        'client_secret' => env('DIGITAL_BANK_CLIENT_SECRET'),

    ],

    'payfixy' => [

        'base_url' => env('PAYFIXY_BASE_URL'),
        'secret_key' => env('PAYFIXY_SECRET_KEY'),
        'callback_url' => env('PAYFIXY_CALLBACK_URL'),

    ],

    'insurance' => [
        'base_url' => env('INSURANCE_BASE_URL'),
        'secret_key' => env('INSURANCE_SECRET_KEY'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
        'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'qoreid' => [
        'base_url' => env('QOREID_BASE_URL'),

        'client_id' => env('QOREID_CLIENT_ID'),

        'secret' => env('QOREID_SECRET'),
    ],

];
