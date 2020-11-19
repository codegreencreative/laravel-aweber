<?php

namespace Codegreencreative\Aweber\Aweber;

use CodeGreenCreative\Aweber\Aweber\AweberOauthAdapter;
use CodeGreenCreative\Aweber\Aweber\CurlResponse;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberApiException;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberOauthDataMissing;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberOauthException;
use CodeGreenCreative\Aweber\Aweber\Exceptions\AweberResponseError;
use Codegreencreative\Aweber\Aweber\OauthUser;
use Exception;

/**
 * OAuthApplication
 *
 * Base class to represent an OAuthConsumer application.  This class is
 * intended to be extended and modified for each ServiceProvider. Each
 * OAuthServiceProvider should have a complementary OAuthApplication
 *
 * The OAuthApplication class should contain any details on preparing
 * requires that is unique or specific to that specific service provider's
 * implementation of the OAuth model.
 *
 * This base class is based on OAuth 1.0, designed with AWeber's implementation
 * as a model.  An OAuthApplication built to work with a different service
 * provider (especially an OAuth2.0 Application) may alter or bypass portions
 * of the logic in this class to meet the needs of the service provider it
 * is designed to interface with.
 *
 * @package
 * @version $id$
 */
class OauthApplication implements AweberOauthAdapter
{
    public $debug = false;

    public $userAgent = 'AWeber OAuth Consumer Application 1.0 - https://labs.aweber.com/';

    public $format = false;

    public $requiresTokenSecret = true;

    public $signatureMethod = 'HMAC-SHA1';
    public $version         = '1.0';

    /**
     * @var OAuthUser User currently interacting with the service provider
     */
    public $user = false;

    // Data binding this OAuthApplication to the consumer application it is acting
    // as a proxy for
    public $consumerKey = false;
    public $consumerSecret = false;

    /**
     * __construct
     *
     * Create a new OAuthApplication, based on an OAuthServiceProvider
     * @access public
     * @return void
     */
    public function __construct($parentApp = false)
    {
        if ($parentApp) {
            if (!is_a($parentApp, 'Codegreencreative\Aweber\Aweber\AweberServiceProvider')) {
                throw new Exception('Parent App must be a valid OAuthServiceProvider!');
            }
            $this->app = $parentApp;
        }
        $this->user = new OauthUser;
    }

    /**
     * request
     *
     * Implemented for a standard OAuth adapter interface
     * @param mixed $method
     * @param mixed $uri
     * @param array $data
     * @param array $options
     * @access public
     * @return void
     */
    public function request($method, $uri, $data = array(), $options = array())
    {
        $uri = $this->app->removeBaseUri($uri);
        $url = $this->app->getBaseUri() . $uri;

        # WARNING: non-primative items in data must be json serialized in GET and POST.
        if ($method == 'POST' or $method == 'GET') {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = json_encode($value);
                }
            }
        }

        $response = $this->makeRequest($method, $url, $data);
        if (!empty($options['return'])) {
            if ($options['return'] == 'status') {
                return $response->headers['Status-Code'];
            }
            if ($options['return'] == 'headers') {
                return $response->headers;
            }
            if ($options['return'] == 'integer') {
                return intval($response->body);
            }
        }

        $data = json_decode($response->body, true);

        if (empty($options['allow_empty']) && !isset($data)) {
            throw new AweberResponseError($uri);
        }
        return $data;
    }

    /**
     * getRequestToken
     *
     * Gets a new request token / secret for this user.
     * @access public
     * @return void
     */
    public function getRequestToken($callbackUrl = false)
    {
        $data = ($callbackUrl)? array('oauth_callback' => $callbackUrl) : array();
        $resp = $this->makeRequest('POST', $this->app->getRequestTokenUrl(), $data);
        $data = $this->parseResponse($resp);
        $this->requiredFromResponse($data, array('oauth_token', 'oauth_token_secret'));
        $this->user->requestToken = $data['oauth_token'];
        $this->user->tokenSecret  = $data['oauth_token_secret'];
        return $data['oauth_token'];
    }

    /**
     * getAccessToken
     *
     * Makes a request for access tokens.  Requires that the current user has an authorized
     * token and token secret.
     *
     * @access public
     * @return void
     */
    public function getAccessToken()
    {
        $resp = $this->makeRequest(
            'POST',
            $this->app->getAccessTokenUrl(),
            array('oauth_verifier' => $this->user->verifier)
        );
        $data = $this->parseResponse($resp);
        $this->requiredFromResponse($data, array('oauth_token', 'oauth_token_secret'));

        if (empty($data['oauth_token'])) {
            throw new AweberOauthDataMissing('oauth_token');
        }

        $this->user->accessToken = $data['oauth_token'];
        $this->user->tokenSecret = $data['oauth_token_secret'];
        return array($data['oauth_token'], $data['oauth_token_secret']);
    }

    /**
     * parseAsError
     *
     * Checks if response is an error.  If it is, raise an appropriately
     * configured exception.
     *
     * @param mixed $response   Data returned from the server, in array form
     * @access public
     * @throws AWeberOAuthException
     * @return void
     */
    public function parseAsError($response)
    {
        if (!empty($response['error'])) {
            throw new AweberOauthException($response['error']['type'], $response['error']['message']);
        }
    }

    /**
     * requiredFromResponse
     *
     * Enforce that all the fields in requiredFields are present and not
     * empty in data.  If a required field is empty, throw an exception.
     *
     * @param mixed $data               Array of data
     * @param mixed $requiredFields     Array of required field names.
     * @access protected
     * @return void
     */
    protected function requiredFromResponse($data, $requiredFields)
    {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new AweberOauthDataMissing($field);
            }
        }
    }

    /**
     * get
     *
     * Make a get request.  Used to exchange user tokens with serice provider.
     * @param mixed $url        URL to make a get request from.
     * @param array $data       Data for the request.
     * @access protected
     * @return void
     */
    protected function get($url, $data)
    {
        $url = $this->_addParametersToUrl($url, $data);
        $handle = curl_init($url);
        $resp = $this->_sendRequest($handle);
        return $resp;
    }

    /**
     * _addParametersToUrl
     *
     * Adds the parameters in associative array $data to the
     * given URL
     * @param String $url       URL
     * @param array $data       Parameters to be added as a query string to
     *      the URL provided
     * @access protected
     * @return void
     */
    protected function _addParametersToUrl($url, $data)
    {
        if (!empty($data)) {
            if (strpos($url, '?') === false) {
                $url .= '?'.$this->buildData($data);
            } else {
                $url .= '&'.$this->buildData($data);
            }
        }
        return $url;
    }

    /**
     * generateNonce
     *
     * Generates a 'nonce', which is a unique request id based on the
     * timestamp.  If no timestamp is provided, generate one.
     * @param mixed $timestamp Either a timestamp (epoch seconds) or false,
     *  in which case it will generate a timestamp.
     * @access public
     * @return string   Returns a unique nonce
     */
    public function generateNonce($timestamp = false)
    {
        if (!$timestamp) {
            $timestamp = $this->generateTimestamp();
        }
        return md5($timestamp.'-'.rand(10000, 99999).'-'.uniqid());
    }

    /**
     * generateTimestamp
     *
     * Generates a timestamp, in seconds
     * @access public
     * @return int Timestamp, in epoch seconds
     */
    public function generateTimestamp()
    {
        return time();
    }

    /**
     * createSignature
     *
     * Creates a signature on the signature base and the signature key
     * @param mixed $sigBase    Base string of data to sign
     * @param mixed $sigKey     Key to sign the data with
     * @access public
     * @return string   The signature
     */
    public function createSignature($sigBase, $sigKey)
    {
        switch ($this->signatureMethod) {
            case 'HMAC-SHA1':
            default:
                return base64_encode(hash_hmac('sha1', $sigBase, $sigKey, true));
        }
    }

    /**
     * encode
     *
     * Short-cut for utf8_encode / rawurlencode
     * @param mixed $data   Data to encode
     * @access protected
     * @return void         Encoded data
     */
    protected function encode($data)
    {
        return rawurlencode(utf8_encode($data));
    }

    /**
     * createSignatureKey
     *
     * Creates a key that will be used to sign our signature.  Signatures
     * are signed with the consumerSecret for this consumer application and
     * the token secret of the user that the application is acting on behalf
     * of.
     * @access public
     * @return void
     */
    public function createSignatureKey()
    {
        return $this->consumerSecret.'&'.$this->user->tokenSecret;
    }

    /**
     * getOAuthRequestData
     *
     * Get all the pre-signature, OAuth specific parameters for a request.
     * @access public
     * @return void
     */
    public function getOAuthRequestData()
    {
        $token = $this->user->getHighestPriorityToken();
        $ts = $this->generateTimestamp();
        $nonce = $this->generateNonce($ts);
        return array(
            'oauth_token' => $token,
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_version' => $this->version,
            'oauth_timestamp' => $ts,
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_nonce' => $nonce);
    }


    /**
     * mergeOAuthData
     *
     * @param mixed $requestData
     * @access public
     * @return void
     */
    public function mergeOAuthData($requestData)
    {
        $oauthData = $this->getOAuthRequestData();
        return array_merge($requestData, $oauthData);
    }

    /**
     * createSignatureBase
     *
     * @param mixed $method     String name of HTTP method, such as "GET"
     * @param mixed $url        URL where this request will go
     * @param mixed $data       Array of params for this request. This should
     *      include ALL oauth properties except for the signature.
     * @access public
     * @return void
     */
    public function createSignatureBase($method, $url, $data)
    {
        $method = $this->encode(strtoupper($method));
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $url = array_shift(explode('?', $url, 2));
            $items = explode('&', $query);
            foreach ($items as $item) {
                list($key, $value) = explode('=', $item);
                $data[$key] = $value;
            }
        }
        $url = $this->encode($url);
        $data = $this->encode($this->collapseDataForSignature($data));
        return $method.'&'.$url.'&'.$data;
    }

    /**
     * collapseDataForSignature
     *
     * Turns an array of request data into a string, as used by the oauth
     * signature
     * @param mixed $data
     * @access public
     * @return void
     */
    public function collapseDataForSignature($data)
    {
        ksort($data);
        $collapse = '';
        foreach ($data as $key => $val) {
            if (!empty($collapse)) {
                $collapse .= '&';
            }
            $collapse .= $key.'='.$this->encode($val);
        }
        return $collapse;
    }

    /**
     * signRequest
     *
     * Signs the request.
     *
     * @param mixed $method     HTTP method
     * @param mixed $url        URL for the request
     * @param mixed $data       The data to be signed
     * @access public
     * @return array            The data, with the signature.
     */
    public function signRequest($method, $url, $data)
    {
        $base = $this->createSignatureBase($method, $url, $data);
        $key  = $this->createSignatureKey();
        $data['oauth_signature'] = $this->createSignature($base, $key);
        ksort($data);
        return $data;
    }


    /**
     * makeRequest
     *
     * Public facing function to make a request
     * @param mixed $method
     * @param mixed $url
     * @param mixed $data
     * @access public
     * @return void
     */
    public function makeRequest($method, $url, $data = array())
    {
        if ($this->debug) {
            echo "\n** {$method}: $url\n";
        }

        switch (strtoupper($method)) {
            case 'POST':
                $oauth = $this->prepareRequest($method, $url, $data);
                $resp = $this->post($url, $oauth);
                break;

            case 'GET':
                $oauth = $this->prepareRequest($method, $url, $data);
                $resp = $this->get($url, $oauth, $data);
                break;

            case 'DELETE':
                $oauth = $this->prepareRequest($method, $url, $data);
                $resp = $this->delete($url, $oauth);
                break;

            case 'PATCH':
                $oauth = $this->prepareRequest($method, $url, array());
                $resp  = $this->patch($url, $oauth, $data);
                break;
        }

        // enable debug output
        if ($this->debug) {
            echo "<pre>";
            print_r($oauth);
            echo " --> Status: {$resp->headers['Status-Code']}\n";
            echo " --> Body: {$resp->body}";
            echo "</pre>";
        }

        if (!$resp) {
            $msg  = 'Unable to connect to the AWeber API.  (' . $this->error . ')';
            $error = array('message' => $msg, 'type' => 'APIUnreachableError',
                           'documentation_url' => 'https://labs.aweber.com/docs/troubleshooting');
            throw new AweberApiException($error, $url);
        }

        if ($resp->headers['Status-Code'] >= 400) {
            $data = json_decode($resp->body, true);
            throw new AweberApiException($data['error'], $url);
        }

        return $resp;
    }

    /**
     * put
     *
     * Prepare an OAuth put method.
     *
     * @param mixed $url    URL where we are making the request to
     * @param mixed $data   Data that is used to make the request
     * @access private
     * @return void
     */
    protected function patch($url, $oauth, $data)
    {
        $url = $this->_addParametersToUrl($url, $oauth);
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
        $resp = $this->_sendRequest($handle, array('Expect:', 'Content-Type: application/json'));
        return $resp;
    }

    /**
     * post
     *
     * Prepare an OAuth post method.
     *
     * @param mixed $url    URL where we are making the request to
     * @param mixed $data   Data that is used to make the request
     * @access private
     * @return void
     */
    protected function post($url, $oauth)
    {
        $handle = curl_init($url);
        $postData = $this->buildData($oauth);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);
        $resp = $this->_sendRequest($handle);
        return $resp;
    }

    /**
     * delete
     *
     * Makes a DELETE request
     * @param mixed $url        URL where we are making the request to
     * @param mixed $data       Data that is used in the request
     * @access protected
     * @return void
     */
    protected function delete($url, $data)
    {
        $url = $this->_addParametersToUrl($url, $data);
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $resp = $this->_sendRequest($handle);
        return $resp;
    }

    /**
     * buildData
     *
     * Creates a string of data for either post or get requests.
     * @param mixed $data       Array of key value pairs
     * @access public
     * @return void
     */
    public function buildData($data)
    {
        ksort($data);
        $params = array();
        foreach ($data as $key => $value) {
            $params[] = $key.'='.$this->encode($value);
        }
        return implode('&', $params);
    }

    /**
     * _sendRequest
     *
     * Actually makes a request.
     * @param mixed $handle     Curl handle
     * @param array $headers    Additional headers needed for request
     * @access private
     * @return void
     */
    private function _sendRequest($handle, $headers = array('Expect:'))
    {
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_VERBOSE, false);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 90);
        $resp = curl_exec($handle);
        if ($resp) {
            return new CurlResponse($resp);
        }
        $this->error = curl_errno($handle).' - '.curl_error($handle);
        return false;
    }

    /**
     * prepareRequest
     *
     * @param mixed $method     HTTP method
     * @param mixed $url        URL for the request
     * @param mixed $data       The data to generate oauth data and be signed
     * @access public
     * @return void             The data, with all its OAuth variables and signature
     */
    public function prepareRequest($method, $url, $data)
    {
        $data = $this->mergeOAuthData($data);
        $data = $this->signRequest($method, $url, $data);
        return $data;
    }

    /**
     * parseResponse
     *
     * Parses the body of the response into an array
     * @param mixed $string     The body of a response
     * @access public
     * @return void
     */
    public function parseResponse($resp)
    {
        $data = array();

        if (!$resp) {
            return $data;
        }
        if (empty($resp)) {
            return $data;
        }
        if (empty($resp->body)) {
            return $data;
        }

        switch ($this->format) {
            case 'json':
                $data = json_decode($resp->body);
                break;
            default:
                parse_str($resp->body, $data);
        }
        $this->parseAsError($data);
        return $data;
    }
}
