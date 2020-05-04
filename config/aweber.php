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
    | Aweber username
    |--------------------------------------------------------------------------
    |
    | The username you wish to have your application get access to
    |
    */
    'username' => env('AWEBER_USERNAME', null),

    /*
    |--------------------------------------------------------------------------
    | Aweber password
    |--------------------------------------------------------------------------
    |
    | The password you wish to have your application get access to
    |
    */
    'password' => env('AWEBER_PASSWORD', null),

    /*
    |--------------------------------------------------------------------------
    | Base API URL
    |--------------------------------------------------------------------------
    |
    | The base API URL for Aweber API endpoints
    |
    */
    'api_url' => env('AWEBER_API_URL', 'https://api.aweber.com/1.0/'),

    /*
    |--------------------------------------------------------------------------
    | Base OAuth URL
    |--------------------------------------------------------------------------
    |
    | The base OAuth URL for Aweber authorization
    |
    */
    'oauth_url' => env('AWEBER_OAUTH_URL', 'https://auth.aweber.com/oauth2'),

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
    | Redirect URI
    |--------------------------------------------------------------------------
    |
    | This is where the user-agent (in most cases the customer’s browser) will
    | be sent after the customer clicks authorize. This should be a uri that
    | your application can read because that’s where we’ll provide our response.
    | When you provide your callback, make sure it’s the same one you specified
    | when creating your integration.
    |
    | This must match the redirect URL of your Aweber app
    |
    */
    'redirect_uri' => env('AWEBER_REDIRECT_URI', null),

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
