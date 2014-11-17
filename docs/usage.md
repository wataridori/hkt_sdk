Framgia Hyakkaten SDK for PHP Documentation
==========


### Requirement
* PHP 5.4 or newer

### Installation
You can install HKT SDK for PHP by using Composer.

Just run the following command.

```bash
composer require wataridori/hkt_sdk dev-master
```

Or

* Add the `“wataridori/hkt_sdk”: “dev-master”` into the require section of your `composer.json`.
* Run `composer install`.

### Usage
* After installing HKT SDK from Composer, all needed classes are loaded automatically, just start to using it.
* Add `use` state at the beginning of the php file.

```php
use wataridori\HktSdk\HKT_SDK;
```

* Initialize HKT_SDK instance

```php
$hkt_sdk = HKT_SDK(YOUR_CLIENT_ID, YOUR_CLIENT_SECRET);
```

* Create Login URL

```php
// If there is no $callback url added, HKT will redirect to the url you registered when created app. 
$hkt_sdk->getLoginUrl($callback);
```

* Get User Information

```php
$hkt_sdk->getUser();
if ($user) {
    // Get userinfo successfully.
} else {
    // Fail to get userinfo.
}
```

* Logout

```php
$hkt_sdk->logout();
```

