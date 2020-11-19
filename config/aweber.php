<?php

/*
|--------------------------------------------------------------------------
| Aweber configuration file
|--------------------------------------------------------------------------
|
| Use this file to configure Aweber package to work for you
|
| Create your Aweber Lab account and apps here: https://labs.aweber.com/apps
|
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Client ID
    |--------------------------------------------------------------------------
    |
    | client_id is the Client ID listed in your developer account. It uniquely
    | identifies your integration to AWeber.
    |
    */
    'client_id' => env('AWEBER_CLIENT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Client Secret
    |--------------------------------------------------------------------------
    |
    | Client secret found in your Aweber labs account for your app. It is the
    | client secret of the application.
    |
    */
    'client_secret' => env('AWEBER_CLIENT_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Aweber account token key
    |--------------------------------------------------------------------------
    |
    | Account token key
    |
    */
    'token_key' => env('AWEBER_TOKEN_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Aweber account token secret
    |--------------------------------------------------------------------------
    |
    | Account token secret
    |
    */
    'token_secret' => env('AWEBER_TOKEN_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | When you authorize an OAuth2 integration you are required to tell AWeber
    | what sorts of things your integration will need access to. These are called
    | scopes.
    |
    */
    'scopes' => array(
        'account.read',
        'list.read',
        'list.write',
        'subscriber.read',
        'subscriber.write',
        'email.read',
        'email.write',
        'subscriber.read-extended'
    ),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The caching system used to store the access and refresh token received
    | from Aweber
    |
    */
    'cache' => env('AWEBER_CACHE', env('CACHE_DRIVER'))
];
