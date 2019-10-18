 # scalar-plugin-harvardsettings

To use this plugin:

1. Copy `harvardsettings_pi.php` to `system/application/plugins/`
2. Add the following to `system/application/config/plugins.php`:

```php
$config['plugins']['dashboard'][] = 'harvardsettings';
```

If it works, you should see a new tab appear on the Scalar dashboard labeled _Harvard Settings_.
