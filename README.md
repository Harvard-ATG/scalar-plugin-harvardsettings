 # scalar-plugin-harvardsettings

A plugin for [Scalar](https://github.com/anvc/scalar). 

This plugin adds a tab to the admin dashboard for Harvard-specific settings.

## Requirements

Scalar must be installed and configured.

## Quickstart

1. Download and unzip `harvardsettings_pi.php` to `system/application/plugins/`
2. Update plugin configuration in `system/application/config/plugins.php`:

```php
$config['plugins']['dashboard'][] = 'harvardsettings';
```
3. Visit the scalar dashboard. You should see a new tab appear on the Scalar dashboard labeled _Harvard Settings_.
