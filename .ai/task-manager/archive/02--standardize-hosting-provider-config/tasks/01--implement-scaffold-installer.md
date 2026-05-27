---
id: "02-01"
title: "Implement ScaffoldInstallerPlugin.php changes"
status: "completed"
group: "implementation"
phase: 1
dependencies: []
---
# Implement ScaffoldInstallerPlugin.php changes

## Objective
Implement all changes to `src/ScaffoldInstallerPlugin.php` required to standardize hosting provider configuration for Acquia and Pantheon within the existing `github`/`gitlab` CI keys in Drainpipe.

## Acceptance Criteria
- [x] `normalizeHostingProviderConfig()` method added — detects and rewrites deprecated provider string values in memory, emitting warnings
- [x] `hasAnyPantheonCIConfig()` helper method added
- [x] `$pantheonIntegrationsChecked` boolean property added
- [x] `installPantheonSupport()` method added — CI-agnostic Pantheon scaffolding
- [x] `installAcquiaSupport()` method added — extracted from `installHostingProviderSupport()`
- [x] `installHostingProviderSupport()` updated — calls normalization, delegates to provider methods, accepts `$composer`
- [x] `onPostInstallCmd` and `onPostUpdateCmd` updated to pass `$composer` to `installHostingProviderSupport()`
- [x] `installGitlabCI()` updated — removes old Pantheon block, handles new object form with provider sub-keys
- [x] `installGitHubActions()` updated — removes deprecated string handling, adds provider sub-key handling
- [x] `checkPantheonSystemDrupalIntegrations()` guarded by `$pantheonIntegrationsChecked` flag
- [x] All existing functionality preserved (backwards compatible)

## Technical Requirements
- PHP, Composer plugin API
- All deprecated string values in `github`/`gitlab` arrays must be normalized to the new object form with warnings
- No composer.json modifications — normalization is in-memory only
- `installGitlabCI()` loop must use `is_string()` checks to skip provider sub-arrays

## Input Dependencies
None.

## Output Artifacts
Updated `src/ScaffoldInstallerPlugin.php` implementing the full standardized hosting provider configuration scheme.
