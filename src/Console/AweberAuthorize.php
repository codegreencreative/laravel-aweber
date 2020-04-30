<?php

namespace CodeGreenCreative\Aweber\Console;

use CodeGreenCreative\Aweber\Exceptions\AweberException;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class AweberAuthorize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aweber:authorize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up Aweber authorization';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $store = config('aweber.cache');
        $oauth_url = config('aweber.oauth_url');
        if (empty($redirect_uri = config('aweber.redirect_uri'))) {
            throw new AweberException('Redirect URI is not set');
        }
        if (empty($client_id = config('aweber.client_id'))) {
            throw new AweberException('Client ID is not set');
        }
        if (empty($client_secret = config('aweber.client_secret'))) {
            throw new AweberException('Client secret is not set');
        }
        if (empty($username = config('aweber.username'))) {
            throw new AweberException('Aweber username is not set');
        }
        if (empty($password = config('aweber.password'))) {
            throw new AweberException('Aweber password is not set');
        }
        $scopes = config('aweber.scopes');
        // Set up form parameters to use when requesting a code
        $form_params = [
            'username' => $username,
            'password' => $password,
            'submit' => 'Allow access'
        ];
        // Create a OAuth2 client configured to use OAuth for authentication
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $client_id,
            'clientSecret' => $client_secret,
            'redirectUri' => $redirect_uri,
            'scopes' => $scopes,
            'scopeSeparator' => ' ',
            'urlAuthorize' => $oauth_url . '/authorize',
            'urlAccessToken' => $oauth_url . '/token',
            'urlResourceOwnerDetails' => 'https://api.aweber.com/1.0/accounts'
        ]);

        $authorization_url = $provider->getAuthorizationUrl();
        $state = $provider->getState();

        // Create a new Guzzle client
        $client = new Client(['cookies' => true, 'allow_redirects' => false]);
        // Get authorization URL
        $response = $client->request('GET', $authorization_url);
        // $this->info('Body: ' . $response->getBody());
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
            $response = $client->request('POST', $oauth_url . '/authorize', [
                'form_params' => $form_params
            ]);
            if ($response->hasHeader('Location')) {
                $location = parse_str(parse_url($response->getHeader('Location')[0], PHP_URL_QUERY), $output);
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $output['code']
                ]);
                cache()->store($store)->forever('aweber.token', serialize($token));
            }
        }
    }
}
