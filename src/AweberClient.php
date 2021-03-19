<?php

namespace CodeGreenCreative\Aweber;

use Cache;
use CodeGreenCreative\Aweber\Aweber\AweberApi;
use CodeGreenCreative\Aweber\Exceptions\AweberException;
use Config;

class AweberClient
{
    public $client;
    protected $store;
    protected $api_url;
    protected $base_uri;
    protected $oauth_url;
    protected $client_id;
    protected $client_secret;
    protected $token;
    protected $account_id;
    protected $aweber_api;

    /**
     * [methodName description]
     * @return [type] [description]
     */
    public function __construct()
    {
        // Define Aweber API URL
        $this->api_url = 'https://api.aweber.com/1.0';
        $this->oauth_url = 'https://auth.aweber.com/1.0/oauth';

        $this->store = Config::get('laravel-aweber::cache', Config::get('aweber.cache', null));
        $this->client_id = Config::get('laravel-aweber::client_id', Config::get('aweber.client_id', null));
        $this->client_secret = Config::get('laravel-aweber::client_secret', Config::get('aweber.client_secret', null));
        $this->token_key = Config::get('laravel-aweber::token_key', Config::get('aweber.token_key', null));
        $this->token_secret = Config::get('laravel-aweber::token_secret', Config::get('aweber.token_secret', null));

        if (empty($this->client_id)) {
            throw new AweberException('Client ID is not set');
        }
        if (empty($this->client_secret)) {
            throw new AweberException('Client secret is not set');
        }
        $this->aweber_api = new AweberApi($this->client_id, $this->client_secret);
        $aweber_account = $this->aweber_api->getAccount($this->token_key, $this->token_secret);
        $this->account_id = $aweber_account->data['id'];

        // Transform base URI
        $this->base_uri = $this->api_url . $aweber_account->url . '/';
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
    public function request($method, $path, $data = array(), $options = array())
    {
        $uri = $this->base_uri . $path;

        return $this->aweber_api->adapter->request($method, $uri, $data, $options);

        // $uri .= $method == 'GET' ? '?' . str_replace('+', '%20', http_build_query($data)) : '';
        // $handle = curl_init($uri);
        // curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($handle, CURLOPT_HEADER, true);
        // if (in_array($method, array('POST', 'PATCH'))) {
        //     curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
        //     if ($method == 'PATCH') {
        //         curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PATCH');
        //     } else {
        //         curl_setopt($handle, CURLOPT_POST, true);
        //     }
        // }
        // curl_setopt($handle, CURLOPT_HTTPHEADER, array(
        //     'Content-Type: application/json',
        //     'Authorization: Bearer ' . $this->token->access_token
        // ));
        // curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($handle, CURLOPT_VERBOSE, false);
        // curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        // curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        // $response = curl_exec($handle);
        // $info = curl_getinfo($handle);

        // $header_size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        // $header = substr($response, 0, $header_size);
        // $body = substr($response, $header_size);

        // $body = json_decode($body);

        // if (isset($body->error)) {
        //     if (isset($body->error->message)) {
        //         throw new AweberException($body->error->message);
        //     }
        //     throw new AweberException($body->error_description);
        // }

        // if ($info['http_code'] == 200) {
        //     return $body;
        // } elseif ($info['http_code'] == 201) {
        //     // Created
        //     $headers = explode("\n", $header);
        //     $location = array_values(array_filter($headers, function ($header) {
        //         return stristr($header, 'Location:');
        //     }));
        //     $location = trim(substr($location[0], strpos($location[0], 'http'), strlen($location[0])));
        //     $path_parts = explode('/', parse_url($location, PHP_URL_PATH));
        //     // Return the ID of the resource
        //     return array_pop($path_parts);
        // }
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
