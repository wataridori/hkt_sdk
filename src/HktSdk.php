<?php

namespace wataridori\HktSdk;

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
     * @param string $client_id
     * @param string $client_secret
     * Construction
     */
    public function __construct($client_id, $client_secret)
    {
        $this->setClientId($client_id);
        $this->setClientSecret($client_secret);
    }

    /**
     * @return string
     * Get Current URI
     */
    public function getCurrentUri()
    {
        return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
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
     * @return string
     * Generate Authorize URL
     */
    public function generateAuthorizeUrl()
    {
        $query = http_build_query(array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->getCurrentUri(),
            'state' => $this->generateState(),
        ));
        return self::HKT_OAUTH_URL . "authorize?$query";
    }

}