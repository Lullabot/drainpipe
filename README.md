# Drainpipe

See https://github.com/lullabot/drainpipe first.

This is a composer package containing the testing helpers for Drainpipe i.e.
should be included in `require-dev` and not installed in production
environments.

## Usage

### `test:static`

Runs all static tests i.e. those which don't require a running Drupal
environment.

The static tests consist of:

#### `test:security`

Runs security checks for composer packages against the [FriendsOfPHP Security
Advisory Database](https://github.com/FriendsOfPHP/security-advisories) and
Drupal core and contributed modules against
[Drupal's Security Advisories](https://www.drupal.org/security).

#### `test:lint`

- YAML lint on `.yml` files in the `web` directory
- Twig lint on files in `web/modules`, `web/profiles`, and `web/themes`
- `composer validate`

These cannot currently be customised.
See [#9](https://github.com/Lullabot/drainpipe-dev/issues/9).

#### `test:phpstan`

Runs [PHPStan](https://phpstan.org/) with
[mglaman/phpstan-drupal](https://github.com/mglaman/phpstan-drupal) on
`web/modules/custom`, `web/themes/custom`, and `web/sites`.

#### `test:phpunit`

Runs PHPUnit tests in `web/modules/custom`, `web/themes/custom`, and
`web/sites`.

#### `test:phpcs`

Runs PHPCS with Drupal coding standards provided by
[Coder module](https://www.drupal.org/project/coder).

### `test:functional`

Runs all functional tests i.e. those which require a running Drupal environment.

#### `test:config`

Verifies that exported configuration matches the current configuration in
Drupal's database.

#### `test:nightwatch`

Runs functional browser tests with [Nightwatch](https://nightwatchjs.org/).

If you are using DDEV, Drainpipe will have created a
`.ddev/docker-compose.selenium.yaml` file that provides Firefox and Chrome as
containers, as well as an example test in
`test/nightwatch/example.nightwatch.js`.

Nightwatch tests should have the suffixed `.nightwatch.js` to be recognised by
the test runner.

### `test:autofix`

Attempts to autofix any issues discovered by tests. Currently, this is just
fixing PHPCS errors with PHPCBF.


