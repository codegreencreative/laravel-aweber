<?php

namespace CodeGreenCreative\Aweber;

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
        $this->store = config('aweber.cache');
        $this->oauth_url = config('aweber.oauth_url');
        if (empty($this->client_id = config('aweber.client_id'))) {
            throw new AweberException('Client ID is not set');
        }
        if (empty($this->client_secret = config('aweber.client_secret'))) {
            throw new AweberException('Client secret is not set');
        }
        // If no token object is stored in the cache, request a new token object
        if (! cache()->store($this->store)->has('aweber.token')) {
            // Generate access token
            $this->getNewAccessToken();
        }
        // Retrieve the token object from the cache
        $this->token = unserialize(cache()->store($this->store)->get('aweber.token'));

        // If the token is expired, request a new access token using the refresh token
        if (empty($this->token['expires_at']) || $this->token['expires_at']->lt(\Carbon\Carbon::now())) {
            $this->refreshAccessToken();
        }
        $this->api_url = config('aweber.api_url');
        // Find account
        $this->account_id = $this->findAccount();

        // Create new client
        $this->client = new \GuzzleHttp\Client(array(
            'base_uri' => $this->api_url . 'accounts/' . $this->account_id . '/',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->token['access_token']
            )
        ));
    }

    /**
     * [getNewAccessToken description]
     * @return [type] [description]
     */
    private function getNewAccessToken()
    {
        if (empty($redirect_uri = config('aweber.redirect_uri'))) {
            throw new AweberException('Redirect URI is not set');
        }
        if (empty($username = config('aweber.username'))) {
            throw new AweberException('Aweber username is not set');
        }
        if (empty($password = config('aweber.password'))) {
            throw new AweberException('Aweber password is not set');
        }
        $scopes = config('aweber.scopes');
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
        $authorization_url = sprintf(
            '%s/authorize?%s',
            $this->oauth_url,
            http_build_query($params, '', '&', PHP_QUERY_RFC3986)
        );
        // Create a new Guzzle client
        $client = new \GuzzleHttp\Client(array(
            'cookies' => true,
            'allow_redirects' => false
        ));
        // Get authorization URL
        $response = $client->request('GET', $authorization_url);
        if ($response->getStatusCode() == 200) {
            libxml_use_internal_errors(true);
            $doc = new \DOMDocument('1.0', 'utf-8');
            libxml_clear_errors();
            $doc->loadHTML($response->getBody());
            $xp = new \DOMXpath($doc);
            $nodes = $xp->query('//input[@type="hidden"]');
            foreach ($nodes as $node) {
                $form_params[$node->getAttribute('name')] = $node->getAttribute('value');
            }
            // Attempt to authorize
            $response = $client->request('POST', $this->oauth_url . '/authorize', array(
                'form_params' => $form_params
            ));
            if ($response->hasHeader('Location')) {
                $header = $response->getHeader('Location');
                $location = parse_url($header[0], PHP_URL_QUERY);
                parse_str($location, $output);

                $response = $client->post($this->oauth_url . '/token', array(
                    'auth' => array(
                        $this->client_id,
                        $this->client_secret
                    ),
                    'json' => array(
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => $redirect_uri,
                        'code' => $output['code']
                    )
                ));
                $token = json_decode($response->getBody(), true);
                $token['expires_at'] = \Carbon\Carbon::now()->addSeconds($token['expires_in']);

                cache()->store($this->store)->forever('aweber.token', serialize($token));
            }
        }
    }

    /**
     * [refreshAccessToken description]
     * @return [type] [description]
     */
    private function refreshAccessToken()
    {
        $client = new \GuzzleHttp\Client;

        $response = $client->post($this->oauth_url . '/token', array(
            'auth' => array(
                $this->client_id,
                $this->client_secret
            ),
            'json' => array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->token['refresh_token']
            )
        ));
        $this->token = json_decode($response->getBody(), true);
        $this->token['expires_at'] = \Carbon\Carbon::now()->addSeconds($this->token['expires_in']);
        cache()->store($this->store)->forever('aweber.token', serialize($this->token));
    }

    /**
     * Handle an Aweber API request
     *
     * @param  string $method
     * @param  string $path
     * @param  array  $data
     * @return \Illuminate\Support\Collection
     */
    public function request($method, $path, $data = array())
    {
        $key = $method == 'GET' ? 'query' : 'json';
        $response = $this->client->request($method, $path, array(
            $key => $data,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            )
        ));
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody());
            // $data = new \Illuminate\Support\Collection(json_decode($response->getBody()));
        } elseif ($response->getStatusCode() == 201) {
            // Created
            $header = $response->getHeader('Location');
            $path_parts = explode('/', parse_url($header[0], PHP_URL_PATH));
            // Return the ID of the resource
            return array_pop($path_parts);
        }
    }

    private function findAccount()
    {
        $client = new \GuzzleHttp\Client(array(
            'base_uri' => $this->api_url,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->token['access_token']
            )
        ));
        $response = $client->request('get', 'accounts');
        $data = json_decode($response->getBody(), true);
        return $data['entries'][0]['id'];
    }
}
