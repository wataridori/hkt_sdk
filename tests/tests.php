<?php

use wataridori\HktSdk\HKT_SDK;

class HKTSDKTestCase extends PHPUnit_Framework_TestCase
{
    const CLIENT_ID = '117743971608120';
    const CLIENT_SECRET = '9c8ea2071859659bea1246d33a9207cf';

    public function testConstructor()
    {
        $hkt_sdk = new HKT_SDK(self::CLIENT_ID, self::CLIENT_SECRET);
        $this->assertEquals(get_class($hkt_sdk), 'wataridori\HktSdk\HKT_SDK');
        $this->assertEquals($hkt_sdk->getClientId(), self::CLIENT_ID);
        $this->assertEquals($hkt_sdk->getClientSecret(), self::CLIENT_SECRET);
    }

}

