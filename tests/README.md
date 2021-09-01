# Everest Forms Tests

TODO: Implement testing process docs.

PHPUnit 8.x is unsupported and Ticket is raised in core: https://core.trac.wordpress.org/ticket/46149

Also in Docker setup to use with `wp-env` we have to upgrade composer PHPUnit package to "^8.5" because of this issue https://github.com/WordPress/gutenberg/issues/29323

## Developing on a local environment

Any WAMP/MAMP/LAMP local environment with a WordPress installation will be suited for local development.

### Running PHP unit tests

**Warning**: For running tests, you need a **dedicated test database**. This is important to separate it from your production databases because the tests will drop the complete database each time they are run!

You will then need to adapt the DB related environment variable in `tests/.env` file to match your test database. If `tests/.env` is not added then run `composer update` which basically adds the

```php
define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASS' ) ?: '' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: 'localhost' );
```

You also need a local installation of [Composer](https://getcomposer.org/doc/00-intro.md). This will let you install the development dependencies, including [PHPUnit](https://phpunit.de/).

```bash
composer install
```

And you can run the PHP tests locally using this command:

```bash
composer run test
```

To re-run tests automatically when files change (similar to Jest), run:

```
composer run test:watch
```

## Developing with wp-env

The [wp-env package](https://developer.wordpress.org/block-editor/packages/packages-env/) was developed with the Gutenberg project as a quick way to create a standard WordPress environment using Docker. It is also published as the `@wordpress/env` npm package.

You can use it for contributing to the WP Notify project, but you need to install it on your computer first. read the [prerequisites](https://developer.wordpress.org/block-editor/packages/packages-env/#prerequisites) and the [install as a global package](https://developer.wordpress.org/block-editor/packages/packages-env/#installation-as-a-global-package) from its manual.

### Running PHP unit tests

An npm script is provided in order to start the PHP unit tests:

```bash
npm run test-unit-php
```
