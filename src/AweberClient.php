<?php

namespace CodeGreenCreative\Aweber;

use Cache;
use Config;
use CodeGreenCreative\Aweber\Exceptions\AweberException;

class AweberClient
{
    public $classes = array(
        'accounts' => '\CodeGreenCreative\Aweber\Api\Accounts',
        'lists' => '\CodeGreenCreative\Aweber\Api\Lists',
    );
    public $client;
    protected $store;
    protected $api_url;
    protected $base_uri;
    protected $oauth_url;
    protected $client_id;
    protected $client_secret;
    protected $token;
    protected $account_id;

    /**
     * [methodName description]
     * @return [type] [description]
     */
    public function __construct()
    {
        $this->store = Config::get('laravel-aweber::cache');
        $this->oauth_url = Config::get('laravel-aweber::oauth_url');
        $this->client_id = Config::get('laravel-aweber::client_id');
        $this->client_secret = Config::get('laravel-aweber::client_secret');
        if (empty($this->client_id)) {
            throw new AweberException('Client ID is not set');
        }
        if (empty($this->client_secret)) {
            throw new AweberException('Client secret is not set');
        }
        Cache::forget('aweber.token');
        // If no token object is stored in the cache, request a new token object
        if (! Cache::has('aweber.token')) {
            // Generate access token
            $this->getNewAccessToken();
        } else {
            // Retrieve the token object from the cache
            $this->token = unserialize(Cache::get('aweber.token'));
        }
        // If the token is expired, request a new access token using the refresh token
        if (empty($this->token->expires_at) || $this->token->expires_at->lt(\Carbon\Carbon::now())) {
            $this->refreshAccessToken();
        }
        // Define Aweber API URL
        $this->api_url = Config::get('laravel-aweber::api_url');
        // Find account
        $this->account_id = $this->findAccount();
        // Transform base URI
        $this->base_uri = $this->api_url . 'accounts/' . $this->account_id . '/';
    }

    /**
     * [getNewAccessToken description]
     * @return [type] [description]
     */
    private function getNewAccessToken()
    {
        $redirect_uri = Config::get('laravel-aweber::redirect_uri');
        if (empty($redirect_uri)) {
            throw new AweberException('Redirect URI is not set');
        }
        $username = Config::get('laravel-aweber::username');
        if (empty($username)) {
            throw new AweberException('Aweber username is not set');
        }
        $password = Config::get('laravel-aweber::password');
        if (empty($password)) {
            throw new AweberException('Aweber password is not set');
        }
        $scopes = Config::get('laravel-aweber::scopes');
        // Set up form parameters to use when requesting a code
        $form_params = array(
            'username' => $username,
            'password' => $password,
            'submit' => 'Allow access'
        );
        // Create a OAuth2 client configured to use OAuth for authentication
        $params = array(
            // 'state' => '6ffd88e3ca5cfda6f96423856cb29ef1',
            'scope' => implode(' ', $scopes),
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'redirect_uri' => $redirect_uri,
            'client_id' => $this->client_id
        );
        // Build authorization URL
        $authorization_url = str_replace('+', '%20', sprintf(
            '%s/authorize?%s',
            $this->oauth_url,
            http_build_query($params)
        ));

        $handle = curl_init($authorization_url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
        curl_setopt($handle, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
        curl_setopt($handle, CURLOPT_COOKIE, '');
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_VERBOSE, false);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if ($info['http_code'] != 200) {
            throw new AweberException('There was a problem authorizing with Aweber');
        }

        // Load the HTML for parsing
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'utf-8');
        libxml_clear_errors();
        $doc->loadHTML(substr($response, strpos($response, '<!DOCTYPE html>'), strlen($response)));
        $xp = new \DOMXpath($doc);
        $nodes = $xp->query('//input[@type="hidden"]');
        foreach ($nodes as $node) {
            $form_params[$node->getAttribute('name')] = $node->getAttribute('value');
        }

        // Attempt to authorize
        $handle = curl_init($this->oauth_url . '/authorize');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, str_replace('+', '%20', http_build_query($form_params)));
        curl_setopt($handle, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
        curl_setopt($handle, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
        curl_setopt($handle, CURLOPT_COOKIE, '');
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_VERBOSE, false);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if ($info['http_code'] != 302) {
            throw new AweberException('There was a problem authorizing with Aweber');
        }

        // Find the location containing the authorization code
        $headers = explode("\n", $response);
        $location = array_values(array_filter($headers, function ($header) {
            return stristr($header, 'Location:');
        }));

        if (! empty($location)) {
            $location = trim(substr($location[0], strpos($location[0], 'http'), strlen($location[0])));
            $location = parse_url($location, PHP_URL_QUERY);
            parse_str($location, $output);

            $handle = curl_init($this->oauth_url . '/token');
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_HEADER, false);
            curl_setopt($handle, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($handle, CURLOPT_USERPWD, sprintf('%s:%s', $this->client_id, $this->client_secret));
            curl_setopt($handle, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
            curl_setopt($handle, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
            curl_setopt($handle, CURLOPT_COOKIE, '');
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode(array(
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect_uri,
                'code' => $output['code']
            )));
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handle, CURLOPT_VERBOSE, true);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($handle, CURLOPT_TIMEOUT, 90);
            $response = curl_exec($handle);
            $info = curl_getinfo($handle);

            if ($info['http_code'] != 200) {
                throw new AweberException('There was a problem authorizing with Aweber');
            }

            // Get the code and store it
            $this->token = json_decode($response);
            $this->token->expires_at = \Carbon\Carbon::now()->addSeconds($this->token->expires_in);

            Cache::forever('aweber.token', serialize($this->token));
        }
    }

    /**
     * [refreshAccessToken description]
     * @return [type] [description]
     */
    private function refreshAccessToken()
    {
        $handle = curl_init($this->oauth_url . '/token');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($handle, CURLOPT_USERPWD, sprintf('%s:%s', $this->client_id, $this->client_secret));
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query(array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->token->refresh_token
        )));
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_VERBOSE, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if ($info['http_code'] != 200) {
            throw new AweberException('There was a problem refreshing access token with refresh token.');
        }

        $this->token = json_decode($response);
        $this->token->expires_at = \Carbon\Carbon::now()->addSeconds($this->token->expires_in);

        Cache::forever('aweber.token', serialize($this->token));
    }

    /**
     * Handle an Aweber API request
     *
     * Returns an integer for the object on creation
     * Return an array on retrieval
     *
     * @param  string $method
     * @param  string $path
     * @param  array  $data
     * @return integer | array
     */
    public function request($method, $path, $data = array())
    {
        $uri = $this->base_uri . $path;
        $uri .= $method == 'GET' ? '?' . str_replace('+', '%20', http_build_query($data)) : '';

        $handle = curl_init($uri);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        if ($method == 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token->access_token
        ));
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_VERBOSE, false);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        $header_size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $body = json_decode($body);

        if (isset($body->error)) {
            if (isset($body->error->message)) {
                throw new AweberException($body->error->message);
            }
            throw new AweberException($body->error_description);
        }

        if ($info['http_code'] == 200) {
            return $body;
        } elseif ($info['http_code'] == 201) {
            // Created
            $headers = explode("\n", $header);
            $location = array_values(array_filter($headers, function ($header) {
                return stristr($header, 'Location:');
            }));
            $location = trim(substr($location[0], strpos($location[0], 'http'), strlen($location[0])));
            $path_parts = explode('/', parse_url($location, PHP_URL_PATH));
            // Return the ID of the resource
            return array_pop($path_parts);
        }
    }

    /**
     * Find an account associated with Awener API credentials
     *
     * @return integer Account ID
     */
    private function findAccount()
    {
        $handle = curl_init($this->api_url . 'accounts');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token->access_token
        ));
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_VERBOSE, false);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        $response = curl_exec($handle);
        $info = curl_getinfo($handle);

        if ($info['http_code'] != 200) {
            throw new AweberException('There was a problem authorizing with Aweber');
        }

        $data = json_decode($response);
        return $data->entries[0]->id;
    }
}
