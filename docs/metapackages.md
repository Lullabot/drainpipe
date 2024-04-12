# Metapackages

Drainpipe comes with packages that are published to npm:
- https://www.npmjs.com/package/@lullabot/drainpipe-javascript
- https://www.npmjs.com/package/@lullabot/drainpipe-sass

## Testing Changes

- Setup the [test site](./test-script.md) which will install the metapackages
  from your local checkout
- Copy the contents of `tests/fixtures/metapackages` to the site:
  ```
  ❯ pwd
  /home/justafish/repos/drainpipe-test
  ❯ cp -R ../drainpipe/tests/fixtures/metapackages/* .
  ```
- Install focal trap (used by `js/mobile-nav.js`)
  ```
  ddev yarn add focus-trap
  ```
- Run commands from `Taskfile.yml` e.g. `ddev task javascript`, `ddev task sass`

## Publishing Changes
- Make sure your user has access to the lullabot org on npm
- Check out a new branch and bump the version numbers in the metapackage's
  `package.json` files
- Commit the changes
- Run `yarn lerna publish`
- Enter your OTP for npm
- Once published, push the branch to GitHub and merge
