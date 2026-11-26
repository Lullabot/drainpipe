# Testing local changes

## Drainpipe's own PHPUnit tests

From the root of your checkout:

```
❯ composer install
❯ ./vendor/bin/phpunit
```

The Functional tests run `composer update` in `tests/fixtures/drainpipe-test-project`, so they
need network access.

## CI workflows

Most of the checks in `.github/workflows` run on pull requests. To try a downstream project's
workflows locally, see the `act` instructions in the [README](../README.md).
