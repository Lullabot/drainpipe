#!/usr/bin/env node

const gulp = require('gulp');
const yargs = require('yargs');
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv

// Lets tasks/sass.yml check whether the installed version supports the
// options it passes. Older versions don't know this flag and print nothing.
if (argv.features) {
  console.log('sdc');
  process.exit(0);
}

// The gulpfile is ESM because some of its dependencies (e.g. cssnano) are
// ESM-only, so it has to be loaded with a dynamic import.
// Without a listener, gulp throws task errors as unhandled "error" events.
// Logging them instead also keeps watch mode running after a failed build.
gulp.on('error', ({ error, branch }) => {
  if (!branch) {
    console.error(error && error.message ? error.message : error);
  }
});

import('./gulpfile.mjs').then(() => {
  if (argv.watch) {
    gulp.task('sass:watch')();
  }
  else {
    gulp.task('sass')((error) => {
      if (error) {
        process.exitCode = 1;
      }
    });
  }
});
