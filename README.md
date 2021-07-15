# TreviPay Magento 2 Module

## Requirements

- Magento 2
- PHP 7.1 or higher (7.2+ recommended)

## Installation

### Using Composer

Install the TreviPay TreviPayMagento module with the following command:

```
composer require trevipay/trevipay-magento
```

If your php is < 7.2 or does not have `libsodium` installed, install the `paragonie/sodium_compat` package from composer:
```
composer require paragonie/sodium_compat
```

Run the Magento upgrade command:

```
php ./bin/magento setup:upgrade
```

Then flush the Magento cache:

```
php ./bin/magento cache:flush;
php ./bin/magento cache:clean;
```

If you are running Magento 2 in production mode, you will also be required to run compilation and static content deployment steps:

```
php ./bin/magento setup:di:compile;
php ./bin/magento setup:static-content:deploy;
```

#### How to update

Run the Composer update command:

```
composer update trevipay/trevipay-magento
```

Then flush the Magento cache as above.

Recompile as above if running in production mode.

## Testing

Run Tests

```
./vendor/bin/phpunit
```

## License

[MPL 2.0](https://www.mozilla.org/en-US/MPL/2.0/)
