#!/usr/bin/env node

const gulp = require('gulp');
const yargs = require('yargs');
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv

// The gulpfile is ESM because some of its dependencies (e.g. cssnano) are
// ESM-only, so it has to be loaded with a dynamic import.
import('./gulpfile.mjs').then(() => {
  if (argv.watch) {
    gulp.task('sass:watch')();
  }
  else {
    gulp.task('sass')();
  }
});
