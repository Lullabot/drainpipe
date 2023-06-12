const path = require('path');
const fs = require('fs');
const yargs = require('yargs');
const { hideBin } = require('yargs/helpers')
const { src, dest, task, watch, series } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const dartSass = require('sass');
const sassGlob = require('gulp-sass-glob');
const postcss = require('gulp-postcss');
const sourcemaps = require('gulp-sourcemaps');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

const argv = yargs(hideBin(process.argv)).argv
const modernNormalizePath = path.join(path.dirname(require.resolve('modern-normalize')), '..');

const files = argv.files ? argv.files.split(' ') : '';
if (!files.length) {
  console.log('No files to compile');
  process.exit(0);
}

const srcs = files
  .map(file => file.split(':', 2).map(file => path.resolve(file)))
  .reduce((prev, curr) => {
    prev[curr.shift()] = curr.shift();
    return prev;
  }, {});

console.log('🪠 Autoprefixer info:');
console.log(autoprefixer.info());

// Compile once with dart Sass directly to get a list of includes/partials.
const includes = Object.keys(srcs)
  .map(file => {
    const result = dartSass.renderSync({
      file: file,
      includePaths: [modernNormalizePath],
    });
    return result.stats.includedFiles.filter(file => typeof file === "string");
  })
  .reduce((prev, curr) => prev.concat(curr), []);

task('sass', function() {
  return src(Object.keys(srcs))
    .pipe(sourcemaps.init())
    .pipe(sassGlob())
    .pipe(sass.sync({
      outputStyle: 'compressed',
      includePaths: [modernNormalizePath],
    }).on('error', sass.logError))
    .pipe(postcss([
      autoprefixer(),
      cssnano(),
    ]))
    .pipe(sourcemaps.write('./'))
    .pipe(dest((file) => {
      const originalFile = file.history.shift();
      const destFile = path.dirname(srcs[originalFile]);
      console.log(`🪠 Writing ${path.relative(process.cwd(), file.path)}`);
      return destFile;
    }));
});

task('development', function() {
  return src(Object.keys(srcs))
    .pipe(sourcemaps.init())
    .pipe(sassGlob())
    .pipe(sass.sync({
      outputStyle: 'expanded',
      includePaths: [modernNormalizePath],
    }).on('error', sass.logError))
    .pipe(postcss([
      autoprefixer(),
    ]))
    .pipe(sourcemaps.write('./'))
    .pipe(dest((file) => {
      const originalFile = file.history.shift();
      const destFile = path.dirname(srcs[originalFile]);
      console.log(`🪠 Writing ${path.relative(process.cwd(), file.path)}`);
      return destFile;
    }));
});

task('sass:watch', function() {
  console.log('🪠 Watching for changes');
  watch(includes, {
      usePolling: true,
  }, series('development'));
});

series(['sass']);
