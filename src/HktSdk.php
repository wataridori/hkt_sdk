<?php

namespace wataridori\HktSdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
     * Information about HKT's user
     */
    private $user;

    /**
     * @const string HKT_OAUTH_URL
     * The base OAuth URL at Framgia Hyakkaten
     */
    const HKT_OAUTH_URL = 'https://hkt.thangtd.com/oauth/';

    /**
     * @const string HKT_API_URL
     * The base API URL at Framgia Hyakkaten
     */
    const HKT_API_URL = 'https://hkt.thangtd.com/api/';

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
        if ($this->access_token !== null) {
            return $this->access_token;
        }

        $user_access_token = $this->getUserAccessToken();
        if ($user_access_token) {
            $this->setAccessToken($user_access_token);
        }

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
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->http_client;
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * Construction
     */
    public function __construct($client_id, $client_secret)
    {
        if (!session_id()) {
            session_start();
        }
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
     * @param bool $params
     * @return string
     * Get Current URI
     */
    private function getCurrentUri($params = true)
    {
        $server_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}";
        if ($params) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $vars = parse_url($_SERVER['REQUEST_URI']);
            $uri = isset($vars['path']) ? $vars['path'] : $_SERVER['REQUEST_URI'];
        }
        if ($uri[0] === '/') {
            return $server_url . $uri;
        } else {
            return $server_url . '/' . $uri;
        }
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
            $redirect_uri = $this->getCurrentUri(false);
        }

        try {
            $access_token_response =
                $this->http_client->post(
                    self::HKT_OAUTH_URL . 'token', [
                        'body' => [
                            'grant_type'    => 'authorization_code',
                            'client_id' => $this->getClientId(),
                            'client_secret' => $this->getClientSecret(),
                            'redirect_uri' => $redirect_uri,
                            'code' => $code,
                        ]
                ]);

        } catch (RequestException $e) {
            echo $e->getRequest() . "\n";
            if ($e->hasResponse()) {
                echo $e->getResponse() . "\n";
            }
            exit();
        }

        $response = (string) $access_token_response->getBody();
        if (empty($response)) {
            return false;
        }

        $response_params = json_decode($response, true);
        if (!isset($response_params['access_token'])) {
            return false;
        }

        return $response_params['access_token'];
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
                $this->persistent_storage->setPersistentData('user', $user);
            } else {
                $this->persistent_storage->clearAllPersistentData();
            }
        }

        return $user;
    }

    private function generateApiQuery($params = [])
    {
        $default_params = [
            'access_token' => $this->getAccessToken(),
        ];
        return http_build_query(array_merge($default_params, $params));
    }

    /**
     * @return array user information
     * under construction
     */
    private function getUserFromAccessToken()
    {
        $query = $this->generateApiQuery();
        $url = self::HKT_API_URL . 'user?' . $query;

        try {
            $response = $this->http_client->get($url);
        } catch (RequestException $e) {
            echo $e->getRequest() . "\n";
            if ($e->hasResponse()) {
                echo $e->getResponse() . "\n";
            }
            exit();
        }

        $response_text = (string) $response->getBody();
        if ($response_text) {
            $response_data = json_decode($response_text, true);
            if ($response_data['status'] === 'OK') {
                return $response_data['data'];
            }
        }
        return [];
    }

    /**
     * @param string|null $redirect_uri
     * @return string
     * Generate Authorize URL
     */
    public function getLoginUrl($redirect_uri = null)
    {
        $this->establishCSRFTokenState();
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $redirect_uri ? $redirect_uri : $this->getCurrentUri(),
            'state' => $this->state,
        ]);
        return self::HKT_OAUTH_URL . "authorize?$query";
    }

    /**
     * Get the information array of the connected user, or 0
     * if the HKT user is not connected.
     *
     * @return array HKT's user information.
     */
    public function getUser() {
        if (!empty($this->user)) {
            return $this->user;
        }

        return $this->user = $this->getUserFromAvailableData();
    }

    /**
     * Clear all persistent data. Log out
     */
    public function logout()
    {
        $this->persistent_storage->clearAllPersistentData();
    }
}