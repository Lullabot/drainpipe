# Drainpipe Release Guidelines

Outlines the process for releasing new versions of Drainpipe.

## Pre-Release Verification

Before creating a new release, complete all verification steps below. **All checks must pass before proceeding with the release.**

### 1. Fresh Installation Test

Verify that Drainpipe can be installed successfully in a new project.

```bash
./tests/prerelease.sh
```

**What this validates:**
- Drainpipe installs cleanly without dependency conflicts
- All required files and configurations are generated correctly
- Composer dependencies resolve properly

**Expected result:** Script completes without errors.

### 2. Update Path Test

Verify that existing projects can upgrade to the latest version without issues.

```bash
./tests/prerelease.sh update
```

**What this validates:**
- Upgrade path from the current stable version works correctly
- No breaking changes in configuration files or directory structure
- Existing project functionality remains intact after update

**Expected result:** Script completes without errors.

### 3. Integration Testing

Test Drainpipe integration with production hosting environments using the test repository.

**Steps:**
1. Clone the [`Lullabot/drainpipe-test`](https://github.com/Lullabot/drainpipe-test) repository
2. Update Drainpipe to the latest `main` branch:
   ```bash
   composer require lullabot/drainpipe:dev-main
   ```
3. Commit and push changes to test deployment workflows
4. Verify successful deployment to **Acquia**:
   - Go to [Drainpipe Test Actions](https://github.com/Lullabot/drainpipe-test/actions) and trigger the _Deploy to Acquia_ workflow
   - Check Acquia Cloud deployment logs
   - Confirm site is accessible and functional
   - Validate build artifacts are correct
5. Verify successful deployment to **Pantheon**:
   - Go to [Drainpipe Test Actions](https://github.com/Lullabot/drainpipe-test/actions) and trigger the _Pantheon Deploy_ workflow
   - Check Pantheon deployment logs
   - Confirm site is accessible and functional
   - Validate build artifacts are correct

**What this validates:**
- Drainpipe works correctly with production hosting platforms
- Deployment workflows execute without errors
- No integration issues with hosting-specific tooling

**Expected result:** Both Acquia and Pantheon deployments complete successfully with functional sites.

## Creating a Release

Once all pre-release checks pass, create the release through the GitHub UI. If any verification step fails, **do not proceed with the release** until issues are resolved and all checks pass.

### Version Number Guidelines

Follow [Semantic Versioning (SemVer)](https://semver.org/) principles:

- **Patch version (`x.y.Z`)**: Increment for bug fixes, documentation updates, or minor improvements that don't affect functionality
  - Example: `1.2.3` → `1.2.4`

- **Minor version (`x.Y.0`)**: Increment for new features, new checks/tests, or backward-compatible functionality additions
  - Example: `1.2.4` → `1.3.0`

- **Major version (`X.0.0`)**: Increment for breaking changes that require user action or modifications to existing projects
  - Example: `1.3.0` → `2.0.0`
  - Breaking changes include: API changes, removed features, incompatible configuration changes, or required manual migration steps

### Release Steps

1. Determine the version number based on the guidelines above

2. Create a pull request from `main` branch to `stable`

3. Verify all checks are green before merging the pull request

4. Navigate to the GitHub Releases page:
   - Go to https://github.com/Lullabot/drainpipe/releases
   - Click "Draft a new release"

5. Create and select the tag:
   - Click "Choose a tag"
   - Enter the new version number (e.g., `v1.2.5`)
   - Select "Create new tag: vX.Y.Z on publish"
   - Ensure target is set to `stable` branch

6. Generate release notes:
   - Click "Generate release notes" to auto-populate from merged PRs
   - Highlight breaking changes at the top if this is a major release
   - Remove irrelevant or minor internal changes

7. Add release title:
   - Use format: `vX.Y.Z`

8. Review and publish:
   - Double-check the version number and target branch
   - Verify release notes are accurate and complete
   - Click "Publish release"
