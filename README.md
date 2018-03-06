# Secret Keeper

PHP Library for reading Docker Secrets

## Installation

```bash
composer require icosillion/secret-keeper
```

## Examples

### Load Single Secret

```php
<?php

use Icosillion\SecretKeeper\SecretKeeper;

$secretKeeper = new SecretKeeper();
echo $secretKeeper->load('testsecret');
```

### Load All Secrets

```php
<?php

use Icosillion\SecretKeeper\SecretKeeper;

$secretKeeper = new SecretKeeper();
$secrets = $secretKeeper->loadAll();

foreach ($secrets as $key => $value) {
    echo "$key => $value\n";
}

```

### Load All Secrets into Environment
```php
<?php

use Icosillion\SecretKeeper\SecretKeeper;

$secretKeeper = new SecretKeeper();
$secretKeeper->populateEnvironment();

echo "testsecret (superglobal): {$_ENV['testsecret']}\n";
echo 'testsecret (getenv): ' . getenv('testsecret') . "\n";

```