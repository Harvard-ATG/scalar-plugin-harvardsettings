 # scalar-plugin-harvardsettings

A plugin for [Scalar](https://github.com/anvc/scalar). 

This plugin adds a tab to the admin dashboard for Harvard-specific settings.

## Requirements

[Scalar](https://github.com/anvc/scalar) must be installed and configured.  
    - _Note: requires [Harvard-ATG/scalar](https://github.com/Harvard-ATG/scalar/commits/dev) fork which implements plugin actions in commit [ff43d95](https://github.com/Harvard-ATG/scalar/commit/ff43d95f4c37b40afa3c4daf452da90a4c1359ff)._

## Quickstart

1. Download and unzip `harvardsettings_pi.php` to `system/application/plugins/`
2. Add plugin to `system/application/config/plugins.php`:

```php
$config['plugins']['dashboard'][] = 'harvardsettings';
```
3. Visit the scalar dashboard. You should see a new tab appear on the Scalar dashboard labeled _Harvard Settings_.

## Tests

To install PHPUnit:

```
$ wget -O phpunit https://phar.phpunit.de/phpunit-7.phar
$ chmod u+x phpunit
$ ./phpunit --version
PHPUnit 7.5.16 by Sebastian Bergmann and contributors.
```

To run unit tests:

```
$ ./phpunit --bootstrap autoload.php tests
```
