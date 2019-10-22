 # scalar-plugin-harvardsettings

A plugin for [Scalar](https://github.com/anvc/scalar). 

This plugin adds a tab to the admin dashboard for Harvard-specific settings.

## Requirements

[Scalar](https://github.com/anvc/scalar) must be installed and configured. 

## Quickstart

1. Download and unzip `harvardsettings_pi.php` to `system/application/plugins/`
2. Add plugin to `system/application/config/plugins.php`:

```php
$config['plugins']['dashboard'][] = 'harvardsettings';
```
3. Visit the scalar dashboard and click on the tab that says _Harvard Settings_. If that tab does not appear, something is wrong. 

