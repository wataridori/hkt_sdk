<?php

use wataridori\HktSdk\PersistentStorage;

define('TEST_CLIENT_ID', '8816766544d0efd1eca51.00070416');
define('TEST_CLIENT_SECRET', 'ES55Gk5AOChY4UmfsIKY65gEziwecKq3N3IIfr0NgbBW2lm7M47W5sJ7zi1oqkJY');

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