<?php

namespace CodeGreenCreative\Aweber\Aweber;

/**
 * AWeberAPI
 *
 * Creates a connection to the AWeberAPI for a given consumer application.
 * This is generally the starting point for this library.  Instances can be
 * created directly with consumerKey and consumerSecret.
 * @uses AWeberAPIBase
 * @package
 * @version $id$
 */
class AweberApi extends AweberApiBase
{
    /**
     * @var String Consumer Key
     */
    public $consumerKey = false;

    /**
     * @var String Consumer Secret
     */
    public $consumerSecret = false;

    /**
     * @var Object - Populated in setAdapter()
     */
    public $adapter = false;

    /**
     * Uses the app's authorization code to fetch an access token
     *
     * @param String Authorization code from authorize app page
     */
    public static function getDataFromAweberID($string)
    {
        list($consumerKey, $consumerSecret, $requestToken, $tokenSecret, $verifier) = AweberApi::_parseAweberID(
            $string
        );

        if (!$verifier) {
            return null;
        }
        $aweber = new AweberApi($consumerKey, $consumerSecret);
        $aweber->adapter->user->requestToken = $requestToken;
        $aweber->adapter->user->tokenSecret = $tokenSecret;
        $aweber->adapter->user->verifier = $verifier;
        list($accessToken, $accessSecret) = $aweber->getAccessToken();
        return [$consumerKey, $consumerSecret, $accessToken, $accessSecret];
    }

    protected static function _parseAWeberID($string)
    {
        $values = explode('|', $string);
        if (count($values) < 5) {
            return null;
        }
        return array_slice($values, 0, 5);
    }

    /**
     * Sets the consumer key and secret for the API object.  The
     * key and secret are listed in the My Apps page in the labs.aweber.com
     * Control Panel OR, in the case of distributed apps, will be returned
     * from the getDataFromAweberID() function
     *
     * @param String Consumer Key
     * @param String Consumer Secret
     * @return null
     */
    public function __construct($key, $secret)
    {
        // Load key / secret
        $this->consumerKey = $key;
        $this->consumerSecret = $secret;

        $this->setAdapter();
    }

    /**
     * Returns the authorize URL by appending the request
     * token to the end of the Authorize URI, if it exists
     *
     * @return string The Authorization URL
     */
    public function getAuthorizeUrl()
    {
        $requestToken = $this->user->requestToken;
        return empty($requestToken)
            ? $this->adapter->app->getAuthorizeUrl()
            : $this->adapter->app->getAuthorizeUrl() . "?oauth_token={$this->user->requestToken}";
    }

    /**
     * Sets the adapter for use with the API
     */
    public function setAdapter($adapter = null)
    {
        if (empty($adapter)) {
            $serviceProvider = new AweberServiceProvider();
            $adapter = new OauthApplication($serviceProvider);
            $adapter->consumerKey = $this->consumerKey;
            $adapter->consumerSecret = $this->consumerSecret;
        }
        $this->adapter = $adapter;
    }

    /**
     * Fetches account data for the associated account
     *
     * @param String Access Token (Only optional/cached if you called getAccessToken() earlier
     *      on the same page)
     * @param String Access Token Secret (Only optional/cached if you called getAccessToken() earlier
     *      on the same page)
     * @return Object AWeberCollection Object with the requested
     *     account data
     */
    public function getAccount($token = false, $secret = false)
    {
        $body = $this->adapter->request('GET', '/accounts');
        $accounts = $this->readResponse($body, '/accounts');
        return $accounts[0];
    }

    /**
     * Set the user for the adapter
     *
     * @param string $access_token
     * @param string $access_secret
     * @return void
     */
    public function setUser(string $access_token, string $access_secret)
    {
        $user = new OauthUser();
        $user->accessToken = $access_token;
        $user->tokenSecret = $access_secret;
        $this->adapter->user = $user;
    }

    /**
     * PHP Automagic
     */
    public function __get($item)
    {
        if ($item == 'user') {
            return $this->adapter->user;
        }
        trigger_error("Could not find \"{$item}\"");
    }

    /**
     * Request a request token from AWeber and associate the
     * provided $callbackUrl with the new token
     * @param String The URL where users should be redirected
     *     once they authorize your app
     * @return Array Contains the request token as the first item
     *     and the request token secret as the second item of the array
     */
    public function getRequestToken($callbackUrl)
    {
        $requestToken = $this->adapter->getRequestToken($callbackUrl);
        return [$requestToken, $this->user->tokenSecret];
    }

    /**
     * Request an access token using the request tokens stored in the
     * current user object.  You would want to first set the request tokens
     * on the user before calling this function via:
     *
     *    $aweber->user->tokenSecret  = $_COOKIE['requestTokenSecret'];
     *    $aweber->user->requestToken = $_GET['oauth_token'];
     *    $aweber->user->verifier     = $_GET['oauth_verifier'];
     *
     * @return Array Contains the access token as the first item
     *     and the access token secret as the second item of the array
     */
    public function getAccessToken()
    {
        return $this->adapter->getAccessToken();
    }
}
