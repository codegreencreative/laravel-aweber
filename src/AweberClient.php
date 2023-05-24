<?php

namespace CodeGreenCreative\Aweber;

use CodeGreenCreative\Aweber\Aweber\AweberApi;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberException;

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
    protected $token_key;
    protected $token_secret;

    /**
     * [methodName description]
     * @return [type] [description]
     */
    public function __construct()
    {
        // Define Aweber API URL
        $this->api_url = 'https://api.aweber.com/1.0';
        $this->oauth_url = 'https://auth.aweber.com/1.0/oauth';

        $this->store = config('laravel-aweber::cache', config('aweber.cache', null));
        $this->account_id = config('laravel-aweber::account_id', config('aweber.account_id', null));
        $this->client_id = config('laravel-aweber::client_id', config('aweber.client_id', null));
        $this->client_secret = config('laravel-aweber::client_secret', config('aweber.client_secret', null));
        $this->token_key = config('laravel-aweber::token_key', config('aweber.token_key', null));
        $this->token_secret = config('laravel-aweber::token_secret', config('aweber.token_secret', null));

        if (empty($this->client_id)) {
            throw new AweberException('Client ID is not set');
        }
        if (empty($this->client_secret)) {
            throw new AweberException('Client secret is not set');
        }

        $this->aweber_api = new AweberApi($this->client_id, $this->client_secret);

        if ($this->token_key && $this->token_secret) {
            $this->setConsumer($this->token_key, $this->token_secret);
        }
    }

    /**
     * Set up the HTTP client
     *
     * @param string $token
     * @param string $secret
     * @param int $account_id
     * @return self
     */
    public function setConsumer(string $token, string $secret, int $account_id = null): self
    {
        $this->account_id = $account_id;
        $this->aweber_api->setUser($token, $secret);

        if (is_null($account_id)) {
            $aweber_account = $this->aweber_api->getAccount($token, $secret);
            $this->account_id = $aweber_account->data['id'];
        }

        $this->base_uri = $this->api_url . "/accounts/{$this->account_id}/";

        return $this;
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
    public function request($method, $path, $data = [], $options = [])
    {
        $uri = $this->base_uri . $path;
        if (config('aweber.allow_empty')) {
            $options = array_merge($options, ['allow_empty' => true]);
        }

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
        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token->access_token,
        ]);
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
