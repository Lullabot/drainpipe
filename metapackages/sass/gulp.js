const gulp = require('gulp');
const gulpfile = require('./gulpfile');
const yargs = require('yargs');
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv
if (argv.watch) {
  gulp.task('sass:watch')();
}
else {
  gulp.task('sass')();
}
