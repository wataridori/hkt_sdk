<?php

namespace wataridori\HktSdk;

use \GuzzleHttp\Client;

class HKT_SDK
{
    /**
     * @var string $client_id
     * The app_id at Framgia Hyakkaten
     */
    private $client_id;

    /**
     * @var string $client_secret
     * The secret key belongs to the app
     */
    private $client_secret;

    /**
     * @var string $state
     * A CSRF state variable to assist in the defense against CSRF attacks.
     */
    private $state;

    /**
     * @var string $access_token
     * The OAuth access token received in exchange for a valid authorization code.
     */
    private $access_token;

    /**
     * @var PersistentStorage
     * Use for storing persistent data
     */
    private $persistent_storage;

    /**
     * @var \GuzzleHttp\Client
     * Use for send request to HKT Server
     */
    private $http_client;

    /**
     * @var int
     * Id of the user in HKT. 0 if user_id can not be retrieved
     */
    private $user_id;

    /**
     * @const string HKT_OAUTH_URL
     * The base OAuth URL at Framgia Hyakkaten
     */
    const HKT_OAUTH_URL = 'http://hkt.testthangtd.com/oauth/';

    /**
     * @const string HKT_API_URL
     * The base API URL at Framgia Hyakkaten
     */
    const HKT_API_URL = 'http://hkt.testthangtd.com/api/';

    /**
     * @param string $client_id
     * Set Client ID
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * @return string
     * Get Client ID
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param string $client_secret
     * Set Client Secret Key
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;
    }

    /**
     * @return string
     * Get Client Secret Key
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string $state
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * @return string $access_token
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * @return PersistentStorage
     */
    public function getPersistentStorage()
    {
        return $this->persistent_storage;
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * Construction
     */
    public function __construct($client_id, $client_secret)
    {
        $this->setClientId($client_id);
        $this->setClientSecret($client_secret);
        $this->persistent_storage = new PersistentStorage($client_id);
        $this->http_client = new Client();

        $state = $this->persistent_storage->getPersistentData('state');
        if (!empty($state)) {
            $this->state = $state;
        } else {
            $this->state = null;
        }
    }

    /**
     * @return string
     * Get Current URI
     */
    public function getCurrentUri()
    {
        $server_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/";
        if ($_SERVER['REQUEST_URI'] === '/') {
            return $server_url;
        } else {
            return $server_url . $_SERVER['REQUEST_URI'];
        }
    }

    /**
     * @param int $length
     * @return string
     * Generate random string use for create URL
     */
    public function generateState($length = 10)
    {
        return substr(sha1(rand()), 0, $length);
    }

    /**
     * @param string|null $redirect_uri
     * @return string
     * Generate Authorize URL
     */
    public function generateAuthorizeUrl($redirect_uri = null)
    {
        $this->establishCSRFTokenState();
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $redirect_uri ? $redirect_uri : $this->getCurrentUri(),
            'state' => $this->state,
        ]);
        return self::HKT_OAUTH_URL . "authorize?$query";
    }

    /**
     * Generate state if it does not exist
     */
    private function establishCSRFTokenState() {
        if ($this->state === null) {
            $this->state = substr(sha1(rand()), 0, 20);
            $this->persistent_storage->setPersistentData('state', $this->state);
        }
    }

    /**
     * Get the authorization code from the query parameters, if it exists,
     * otherwise return false.
     *
     * @return string|bool The authorization code, or false if the authorization
     * code could not be determined.
     */
    private function getCode() {
        if (isset($_REQUEST['code'])) {
            if ($this->state !== null &&
                isset($_REQUEST['state']) &&
                $this->state === $_REQUEST['state']) {

                // Clear CSRF state
                $this->state = null;
                $this->persistent_storage->clearPersistentData('state');
                return $_REQUEST['code'];
            } else {
                error_log('CSRF state token does not match one provided.');
                return false;
            }
        }

        return false;
    }

    /**
     *
     * @return string A valid user access token, or false if one
     *                could not be determined.
     */
    private function getUserAccessToken() {
        $code = $this->getCode();
        if ($code && $code != $this->persistent_storage->getPersistentData('code')) {
            $access_token = $this->getAccessTokenFromCode($code);
            if ($access_token) {
                $this->persistent_storage->setPersistentData('code', $code);
                $this->persistent_storage->setPersistentData('access_token', $access_token);
                return $access_token;
            }

            // If there are any problems, clear all persistent data
            $this->persistent_storage->clearAllPersistentData();
            return false;
        }

        return $this->persistent_storage->getPersistentData('access_token');
    }

    /**
     * Retrieves an access token for the given authorization code
     * @param string $code An authorization code.
     * @return string|boolean An access token exchanged for the authorization code, or
     * false if an access token could not be generated.
     */
    private function getAccessTokenFromCode($code, $redirect_uri = null) {
        if (empty($code)) {
            return false;
        }

        if ($redirect_uri === null) {
            $redirect_uri = $this->getCurrentUri();
        }

        try {
            $access_token_response =
                $this->http_client->post(
                    self::HKT_OAUTH_URL . 'token',
                    [
                        'body' => [
                            'client_id' => $this->getClientId(),
                            'client_secret' => $this->getClientSecret(),
                            'redirect_uri' => $redirect_uri,
                            'code' => $code,
                        ]
                    ]
                );
        } catch (\Exception $e) {
            return false;
        }

        if (empty($access_token_response)) {
            return false;
        }

        $response_params = array();
        parse_str($access_token_response, $response_params);
        if (!isset($response_params['access_token'])) {
            return false;
        }

        return $response_params['access_token'];
    }

    /**
     * Get the information array of the connected user, or 0
     * if the HKT user is not connected.
     *
     * @return array HKT's user information.
     */
    public function getUser() {
        if ($this->user_id !== null) {
            return $this->user_id;
        }

        return $this->user_id = $this->getUserFromAvailableData();
    }

    /**
     *
     * @return array The information of the connected HKT user,
     * or empty array if no such user exists.
     */
    private function getUserFromAvailableData() {
        $user = $this->persistent_storage->getPersistentData('user', $default = []);
        $persisted_access_token = $this->persistent_storage->getPersistentData('access_token');

        $access_token = $this->getAccessToken();
        if ($access_token &&
            !($user && $persisted_access_token == $access_token)) {
            $user = $this->getUserFromAccessToken();
            if ($user) {
                $this->persistent_storage->setPersistentData('user_id', $user);
            } else {
                $this->persistent_storage->clearAllPersistentData();
            }
        }

        return $user;
    }

    /**
     * @return int user_id
     */
    private function getUserFromAccessToken()
    {
        return [
            'id' => 1,
            'email' => 'thangtd90@gmail.com',
            'displayed_name' => 'Tran Duc Thang',
        ];
    }
}