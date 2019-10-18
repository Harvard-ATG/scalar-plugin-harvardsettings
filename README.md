 # scalar-plugin-harvardsettings

 This [Scalar](https://github.com/anvc/scalar) plugin adds a new tab to the  dashboard for Harvard-specific settings.

To use this plugin:

1. Copy `harvardsettings_pi.php` to `system/application/plugins/`
2. Add the following to `system/application/config/plugins.php`:

```php
$config['plugins']['dashboard'][] = 'harvardsettings';
```
3. Login to scalar and visit the dashboard. You should see a new tab appear on the Scalar dashboard labeled _Harvard Settings_.