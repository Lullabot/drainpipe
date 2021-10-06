const path = require('path');
const fs = require('fs');
// So they can be easily printed to the user.
const deps = [
  'autoprefixer',
  'cssnano',
  'gulp',
  'gulp-sass',
  'gulp-postcss',
  'gulp-sourcemaps',
  'modern-normalize',
  'postcss',
  'sass',
  'yargs',
].join(' ');

try {
  const yargs = require('yargs');
  const { hideBin } = require('yargs/helpers')
  const argv = yargs(hideBin(process.argv)).argv
  const { src, dest, task, watch, series } = require('gulp');
  const sass = require('gulp-sass')(require('sass'));
  const dartSass = require('sass');
  const postcss = require('gulp-postcss');
  const sourcemaps = require('gulp-sourcemaps');
  const cssnano = require('cssnano');
  const autoprefixer = require('autoprefixer');
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

  task('sass:watch', function() {
    watch(includes, series('sass'));
  });
}
catch (error) {
  if (error.code !== 'MODULE_NOT_FOUND') {
    throw error;
  }
  console.error('🪠 Missing node dependency! Please run:');
  if (fs.existsSync(path.join(process.cwd(), `package-lock.json`))) {
    console.error(`npm install ${deps} --save-dev`);
  }
  else if (fs.existsSync(path.join(process.cwd(), 'yarn.lock'))) {
    console.error(`yarn add ${deps} @yarnpkg/esbuild-plugin-pnp --dev`);
  }
  else {
    console.error('yarn init');
    console.error(`yarn add ${deps} @yarnpkg/esbuild-plugin-pnp --dev`);
  }
  process.exit(1);
}

module.exports = deps;
