# Shown Connector

Shown connector will be used to sync data between WordPress, WooCommerce and Shown.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Follow the instructions on the settings page.

## Technical details
### Check code style
``` bash
composer php-cs-fix
composer php-cs-check
composer phpunit
```

### Deploy to svn
NOTE: This needs some changes!!
The deployment script will create a new svn tag and commit the changes to the svn repository.
``` bash
bash deploy.sh
```

### Make zip file
``` bash	
 make build-release PHP_PATH=/opt/homebrew/opt/php@7.4/bin/php
```
### WordPress SVN repository
https://plugins.svn.wordpress.org/shown-connector/
