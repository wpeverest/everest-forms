# Everest Forms Tests

TODO: Implement testing process docs.

PHPUnit 8.x is unsupported and Ticket is raised in core: https://core.trac.wordpress.org/ticket/46149

### Running PHP unit tests

**Warning**: For running tests, you need a **dedicated test database**. This is important to separate it from your production databases because the tests will drop the complete database each time they are run!

You will then need to add a `WP_LOCAL_DIR` environment variable that points to the local WordPress directory. Also adapt the DB related environment variable in `wp-config.php` to match your test database:

```php
define( 'DB_NAME', getenv( 'WP_DB_NAME' ) ?: 'tests' );
define( 'DB_USER', getenv( 'WP_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_DB_PASS' ) ?: 'root' );
```

You also need a local installation of [Composer](https://getcomposer.org/doc/00-intro.md). This will let you install the development dependencies, including [PHPUnit](https://phpunit.de/).

```bash
composer install
```

And you can run the tests from the PHPUnit package:

```bash
composer run phpunit
```

## Developing with wp-env

The [wp-env package](https://developer.wordpress.org/block-editor/packages/packages-env/) was developed with the Gutenberg project as a quick way to create a standard WordPress environment using Docker. It is also published as the `@wordpress/env` npm package.

You can use it for contributing to the WP Notify project, but you need to install it on your computer first. read the [prerequisites](https://developer.wordpress.org/block-editor/packages/packages-env/#prerequisites) and the [install as a global package](https://developer.wordpress.org/block-editor/packages/packages-env/#installation-as-a-global-package) from its manual.

### Running PHP unit tests

An npm script is provided in order to start the PHP unit tests:

```bash
npm run test-unit-php
```
