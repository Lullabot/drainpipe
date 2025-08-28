# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Drainpipe is a Composer plugin that provides build tools and testing helpers for Drupal sites. It standardizes operations through Task (taskfile.dev) and scaffolds configuration files for CI/CD, testing, and deployment to various hosting providers.

## Common Commands

### Development Commands
- `./vendor/bin/task --list` or `ddev task --list` - Show available tasks
- `task [task-name] --summary` - Get detailed task information
- `task sync` - Sync database from production and import
- `task build` - Build project for production
- `task build:dev` - Build project for development
- `task update` - Run Drupal update process

### Testing Commands
- `task test:static` - Run all static analysis tests (lint, phpstan, phpunit:static, phpcs)
- `task test:functional` - Run all functional tests requiring bootstrapped Drupal
- `task test:security` - Run security checks for packages and Drupal contrib
- `task test:autofix` - Auto-fix code style issues
- `task test:phpunit:static` - Run PHPUnit static tests
- `task test:phpunit:functional` - Run PHPUnit functional tests  
- `task test:phpcs` - Run PHP CodeSniffer
- `task test:phpstan` - Run PHPStan analysis
- `task test:lint` - Run YAML/Twig linting
- `task test:nightwatch` - Run Nightwatch browser tests

### Asset Compilation (when configured)
- `task sass:compile` - Compile SASS files
- `task sass:watch` - Watch and compile SASS files
- `task javascript:compile` - Compile JavaScript files  
- `task javascript:watch` - Watch and compile JavaScript files

### Hosting Provider Tasks
- `task pantheon:fetch-db` - Fetch database from Pantheon
- `task acquia:fetch-db` - Fetch database from Acquia
- `task tugboat:drush-uli-ready` - Configure Drush for Tugboat

## Architecture

### Core Components

**Composer Plugin System**: The project implements two main Composer plugins:
- `ScaffoldInstallerPlugin` (src/ScaffoldInstallerPlugin.php:19) - Handles file scaffolding and configuration setup
- `BinaryInstallerPlugin` (src/BinaryInstallerPlugin.php:5) - Manages binary downloads like Task runner

**Task Configuration System**: 
- Main Taskfile template at `scaffold/Taskfile.yml`
- Modular task files in `tasks/` directory (test.yml, drupal.yml, deploy.yml, etc.)
- Projects get a scaffolded Taskfile.yml that includes these modules

**Scaffolding System**:
- Files in `scaffold/` directory are templated and copied to projects
- CI configurations for GitHub Actions, GitLab CI, and Tugboat
- Host-specific configurations (Pantheon, Acquia, etc.)

### Plugin Lifecycle

1. **Installation**: `onPostInstallCmd()` and `onPostUpdateCmd()` methods scaffold files
2. **Taskfile Management**: Automatically creates/validates Taskfile.yml includes
3. **CI Integration**: Installs GitHub Actions, GitLab CI, or Tugboat configs based on composer.json extra config
4. **Binary Management**: Downloads and manages Task runner binary

### Key Directories

- `src/` - Plugin source code and conversion utilities
- `tasks/` - Modular Task configuration files
- `scaffold/` - Template files for scaffolding
- `tests/` - PHPUnit tests and fixtures
- `metapackages/` - NPM workspace packages for SASS/JS compilation

## Configuration

### Composer Extra Configuration

Enable features in `composer.json` under `extra.drainpipe`:

```json
{
  "extra": {
    "drainpipe": {
      "github": ["TestStatic", "TestFunctional", "Security"],
      "gitlab": ["Common"],
      "tugboat": {"terminus": true},
      "acquia": {"settings": true},
      "testing": ["Nightwatch"]
    }
  }
}
```

### Task Variables

Configure in project's `Taskfile.yml`:

```yaml
vars:
  DRAINPIPE_SASS: |
    web/themes/custom/mytheme/style.scss:web/themes/custom/mytheme/style.css
  DRAINPIPE_JAVASCRIPT: |
    web/themes/custom/mytheme/script.js:web/themes/custom/mytheme/script.min.js
  PANTHEON_SITE_ID: your-site-name
  DRUPAL_TEST_TRAITS: true
```

## Testing

The project uses PHPUnit for testing with fixtures in `tests/fixtures/`. When adding new functionality, include appropriate test coverage.

Test commands run through the Task runner and support JUnit XML output for CI integration.

## Development Notes

- The plugin automatically validates Taskfile.yml includes on updates
- Binary versions are managed in BinaryInstallerPlugin.php and must be updated across multiple files
- Tugboat configuration is dynamically generated from DDEV config when available
- CI configurations are completely managed by Drainpipe and should not be manually edited