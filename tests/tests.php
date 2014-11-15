<?php

use wataridori\HktSdk\PersistentStorage;
use wataridori\HktSdk\HKT_SDK;

define('TEST_CLIENT_ID', '117743971608120');
define('TEST_CLIENT_SECRET', '9c8ea2071859659bea1246d33a9207cf');

class PersistentStorageTestCase extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $persistent_storage = new PersistentStorage(TEST_CLIENT_ID);
        $this->assertEquals(get_class($persistent_storage), 'wataridori\HktSdk\PersistentStorage');
        $this->assertEquals($persistent_storage->getClientId(), TEST_CLIENT_ID);
    }

    public function testSetPersistentData()
    {
        $persistent_storage = new PersistentStorage(TEST_CLIENT_ID);

        $fix_key = 'user';
        $rand_key = PersistentStorage::$supported_keys[array_rand(PersistentStorage::$supported_keys)];
        $not_support_key = 'not_supported';

        $data = 'test-data';

        $this->assertEquals($persistent_storage->setPersistentData($fix_key, $data), true);
        $this->assertEquals($persistent_storage->setPersistentData($rand_key, $data), true);
        $this->assertEquals($persistent_storage->setPersistentData($not_support_key, $data), false);
    }

    public function testGetPersistentData()
    {
        $persistent_storage = new PersistentStorage(TEST_CLIENT_ID);

        $rand_key = PersistentStorage::$supported_keys[array_rand(PersistentStorage::$supported_keys)];
        $data = 'test-data';
        $persistent_storage->setPersistentData($rand_key, $data);

        $this->assertEquals($persistent_storage->getPersistentData($rand_key), $data);
    }

    public function testClearPersistentData()
    {
        $persistent_storage = new PersistentStorage(TEST_CLIENT_ID);

        $rand_key = PersistentStorage::$supported_keys[array_rand(PersistentStorage::$supported_keys)];
        $not_support_key = 'not_support';
        $data = 'test-data';
        $persistent_storage->setPersistentData($rand_key, $data);

        $this->assertEquals($persistent_storage->clearPersistentData($rand_key), true);
        $this->assertEquals($persistent_storage->getPersistentData($rand_key, 'default'), 'default');
        $this->assertEquals($persistent_storage->clearPersistentData($not_support_key), false);
    }

    public function testClearAllPersistentData()
    {
        $persistent_storage = new PersistentStorage(TEST_CLIENT_ID);

        $rand_key = PersistentStorage::$supported_keys[array_rand(PersistentStorage::$supported_keys)];
        $data = 'test-data';
        $persistent_storage->setPersistentData($rand_key, $data);

        $persistent_storage->clearAllPersistentData();
        $this->assertEquals($persistent_storage->getPersistentData($rand_key, 'default'), 'default');
    }
}

class HKTSDKTestCase extends PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $hkt_sdk = new HKT_SDK(TEST_CLIENT_ID, TEST_CLIENT_SECRET);
        $this->assertEquals(get_class($hkt_sdk), 'wataridori\HktSdk\HKT_SDK');
        $this->assertEquals($hkt_sdk->getClientId(), TEST_CLIENT_ID);
        $this->assertEquals($hkt_sdk->getClientSecret(), TEST_CLIENT_SECRET);
    }
}

