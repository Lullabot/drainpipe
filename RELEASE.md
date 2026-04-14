# Drainpipe Release Guidelines

## Pre-Release Verification

Before merging the release-please PR, complete all verification steps below.

### 1. Fresh Installation Test

```bash
./tests/prerelease.sh
```

Verifies Drainpipe installs cleanly in a new project without dependency conflicts.

### 2. Update Path Test

```bash
./tests/prerelease.sh update
```

Verifies existing projects can upgrade to the latest version without issues.

### 3. Integration Testing

Test against production hosting environments using the [`Lullabot/drainpipe-test`](https://github.com/Lullabot/drainpipe-test) repository.

1. Update Drainpipe to the latest `main` branch:
   ```bash
   composer require lullabot/drainpipe:dev-main
   ```
2. Commit and push, then trigger and verify:
   - [Deploy to Acquia](https://github.com/Lullabot/drainpipe-test/actions) workflow
   - [Pantheon Deploy](https://github.com/Lullabot/drainpipe-test/actions) workflow

Both deployments must complete successfully with functional sites.

## Creating a Release

Releases are automated via [release-please](https://github.com/googleapis/release-please). When PRs are merged to `main`, release-please creates or updates a release PR with a changelog built from [conventional commits](https://www.conventionalcommits.org/).

The version is determined automatically from commit types:

- `fix:` → patch (`x.y.Z`)
- `feat:` → minor (`x.Y.0`)
- `feat!:` or `BREAKING CHANGE:` footer → major (`X.0.0`)

Once pre-release verification passes, merge the release-please PR. This will:

1. Create the `vX.Y.Z` tag on `main`
2. Publish the GitHub release with the generated changelog
3. Mirror the tag to [`Lullabot/drainpipe-dev`](https://github.com/Lullabot/drainpipe-dev)
