<?php

use wataridori\HktSdk\HKT_SDK;

define('TEST_CLIENT_ID', '8816766544d0efd1eca51.00070416');
define('TEST_CLIENT_SECRET', 'ES55Gk5AOChY4UmfsIKY65gEziwecKq3N3IIfr0NgbBW2lm7M47W5sJ7zi1oqkJY');

class HKTSDKTestCase extends PHPUnit_Framework_TestCase
{

    private function assertArrayEquals($expected, $actual)
    {
        $this->assertEquals(count($expected), count($actual));
        foreach ($expected as $key => $value) {
            $actual_value = isset($actual[$key]) ? $actual[$key] : null;
            $this->assertEquals($value, $actual_value);
        }
    }

    public function testConstructor()
    {
        $hkt_sdk = new HKT_SDK(TEST_CLIENT_ID, TEST_CLIENT_SECRET);
        $this->assertEquals(get_class($hkt_sdk), 'wataridori\HktSdk\HKT_SDK');
        $this->assertEquals($hkt_sdk->getClientId(), TEST_CLIENT_ID);
        $this->assertEquals($hkt_sdk->getClientSecret(), TEST_CLIENT_SECRET);

        $this->assertEquals(get_class($hkt_sdk->getPersistentStorage()), 'wataridori\HktSdk\PersistentStorage');
        $this->assertEquals($hkt_sdk->getPersistentStorage()->getClientId(), TEST_CLIENT_ID);

        $this->assertEquals(get_class($hkt_sdk->getHttpClient()), 'GuzzleHttp\Client');

        $this->assertEquals($hkt_sdk->getState(), null);

    }

    public function testGetLoginUrl()
    {
        $hkt_sdk = new HKT_SDK(TEST_CLIENT_ID, TEST_CLIENT_SECRET);

        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $_SERVER['REQUEST_URI'] = '/unit-tests.php';

        $login_url = parse_url($hkt_sdk->getLoginUrl());
        $this->assertEquals($login_url['scheme'], 'https');
        $this->assertEquals($login_url['host'], 'hkt.thangtd.com');
        $this->assertEquals($login_url['path'], '/oauth/authorize');

        // Check state is generated successfully or not. It should be a MD5 string with 32 characters long.
        $this->assertEquals(strlen($hkt_sdk->getState()), 32);

        $expected_login_params = [
            'client_id' => TEST_CLIENT_ID,
            'redirect_uri' => 'http://www.example.com/unit-tests.php',
            'response_type' => 'code',
            'state' => $hkt_sdk->getState(),
        ];

        $query_params = [];
        parse_str($login_url['query'], $query_params);

        $this->assertArrayEquals($query_params, $expected_login_params);
    }

    public function testGetLoginUrlWithRedirectUri()
    {
        $hkt_sdk = new HKT_SDK(TEST_CLIENT_ID, TEST_CLIENT_SECRET);
        $redirect_uri = 'www.example.com/redirect_uri';

        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $_SERVER['REQUEST_URI'] = '/unit-tests.php';

        $login_url = parse_url($hkt_sdk->getLoginUrl($redirect_uri));
        $this->assertEquals($login_url['scheme'], 'https');
        $this->assertEquals($login_url['host'], 'hkt.thangtd.com');
        $this->assertEquals($login_url['path'], '/oauth/authorize');

        // Check state is generated successfully or not. It should be a MD5 string with 32 characters long.
        $this->assertEquals(strlen($hkt_sdk->getState()), 32);

        $expected_login_params = [
            'client_id' => TEST_CLIENT_ID,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'state' => $hkt_sdk->getState(),
        ];

        $query_params = [];
        parse_str($login_url['query'], $query_params);

        $this->assertArrayEquals($query_params, $expected_login_params);
    }

    public function testGetLogout()
    {
        $hkt_sdk = new HKT_SDK(TEST_CLIENT_ID, TEST_CLIENT_SECRET);

        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $_SERVER['REQUEST_URI'] = '/unit-tests.php';

        $hkt_sdk->getLoginUrl();
        $hkt_sdk->setAccessToken(md5(time()));

        $hkt_sdk->logout();

        $this->assertEquals($hkt_sdk->getState(), null);
        $this->assertEquals($hkt_sdk->getAccessToken(), null);
        $this->assertEquals($hkt_sdk->getPersistentStorage()->getPersistentData('state'), null);
        $this->assertEquals($hkt_sdk->getPersistentStorage()->getPersistentData('access_token'), null);
    }
}

