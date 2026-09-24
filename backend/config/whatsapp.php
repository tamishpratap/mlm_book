<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notifications Enabled
    |--------------------------------------------------------------------------
    |
    | Master toggle for WhatsApp post report notifications. Defaults to false
    | until valid credentials and endpoints are supplied in the environment.
    |
    */
    'enabled' => filter_var(env('WHATSAPP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Gateway Endpoint
    |--------------------------------------------------------------------------
    |
    | Base URL for the WhatsApp API gateway. Provider-neutral so any HTTP API
    | (Meta Graph Cloud API, Twilio, UltraMsg, Wasapi, etc.) can be configured.
    |
    */
    'api_base_url' => env('WHATSAPP_API_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Authentication Token / Secret
    |--------------------------------------------------------------------------
    |
    | Bearer token, access token, or API secret used to authenticate requests.
    | Kept strictly server-side and never exposed in frontend code or logs.
    |
    */
    'api_token' => env('WHATSAPP_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Phone Number & Account Identifiers
    |--------------------------------------------------------------------------
    |
    | Optional identifiers commonly required by WhatsApp Cloud API providers.
    |
    */
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Notification Recipient
    |--------------------------------------------------------------------------
    |
    | The default WhatsApp phone number (with country code, e.g. +1234567890)
    | to which moderation post reports will be delivered.
    |
    */
    'to_number' => env('WHATSAPP_TO_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Official Member WhatsApp Verification Destination
    |--------------------------------------------------------------------------
    |
    | The official WhatsApp number to which members send "Hi" for manual
    | account verification. Defaults to WHATSAPP_VERIFICATION_NUMBER,
    | WHATSAPP_TO_NUMBER, or the fallback official business number.
    |
    */
    'verification_number' => env('WHATSAPP_VERIFICATION_NUMBER', env('WHATSAPP_TO_NUMBER', '+918439992660')),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | API version used when constructing provider URLs (e.g. v20.0 for Meta).
    |
    */
    'api_version' => env('WHATSAPP_API_VERSION', 'v20.0'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for HTTP requests to the WhatsApp API gateway.
    |
    */
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 15),

];
