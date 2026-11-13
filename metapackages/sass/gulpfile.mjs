import fs from 'node:fs';
import path from 'node:path';
import { Transform } from 'node:stream';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import yargs from 'yargs';
import { hideBin } from 'yargs/helpers';
import gulp from 'gulp';
import * as dartSass from 'sass';
import gulpSassFactory from 'gulp-sass';
import sassGlob from 'gulp-sass-glob';
import postcss from 'gulp-postcss';
import sourcemaps from 'gulp-sourcemaps';
import cssnano from 'cssnano';
import autoprefixer from 'autoprefixer';

const { src, dest, task, watch, series, parallel } = gulp;
const require = createRequire(import.meta.url);
const gulpSass = gulpSassFactory(dartSass);

const argv = yargs(hideBin(process.argv)).argv
const modernNormalizePath = path.join(path.dirname(require.resolve('modern-normalize')), '..');

const splitList = value => (typeof value === 'string' ? value.split(/\s+/).filter(Boolean) : []);

// Explicit "source:target" pairs from DRAINPIPE_SASS.
const files = splitList(argv.files);
// Directories to scan for Single Directory Components from DRAINPIPE_SDC.
const sdcRoots = splitList(argv.sdc);

if (!files.length && !sdcRoots.length) {
  console.log('No files to compile');
  process.exit(0);
}

const explicitSrcs = files
  .map(file => file.split(':', 2).map(file => path.resolve(file)))
  .reduce((prev, curr) => {
    prev[curr.shift()] = curr.shift();
    return prev;
  }, {});

const SDC_DIRECTORY = 'components';
const POSTCSS_SOURCE_EXTENSION = '.pcss.css';
const SASS_SOURCE_EXTENSION = '.scss';
const SKIPPED_DIRECTORIES = new Set(['node_modules', 'vendor']);

const relative = file => path.relative(process.cwd(), file);

const isSdcSource = name => name.endsWith(POSTCSS_SOURCE_EXTENSION)
  || (name.endsWith(SASS_SOURCE_EXTENSION) && !name.startsWith('_'));

/**
 * Recursively finds Sass and PostCSS sources inside "components" directories.
 *
 * Sass partials (files starting with "_") are skipped, as they are only
 * compiled as part of the files that include them.
 */
function findSdcSources(directory, insideComponents = false, found = []) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    if (entry.name.startsWith('.')) {
      continue;
    }
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory() && !SKIPPED_DIRECTORIES.has(entry.name)) {
      findSdcSources(fullPath, insideComponents || entry.name === SDC_DIRECTORY, found);
    }
    else if (insideComponents && entry.isFile() && isSdcSource(entry.name)) {
      found.push(fullPath);
    }
  }
  return found;
}

/**
 * The compiled file sits next to its source, which is where Drupal looks for
 * a component's CSS: my_component.scss or my_component.pcss.css both compile
 * to my_component.css.
 */
function sdcTarget(file) {
  const extension = file.endsWith(POSTCSS_SOURCE_EXTENSION) ? POSTCSS_SOURCE_EXTENSION : SASS_SOURCE_EXTENSION;
  return `${file.slice(0, -extension.length)}.css`;
}

/**
 * Builds the source => target maps to compile.
 *
 * Runs on every compilation so that components added while watching are
 * picked up.
 */
function getSources() {
  const sassSrcs = { ...explicitSrcs };
  const postcssSrcs = {};
  const targets = new Map(Object.entries(explicitSrcs).map(([source, target]) => [target, source]));

  for (const root of sdcRoots) {
    const absoluteRoot = path.resolve(root);
    if (!fs.existsSync(absoluteRoot)) {
      console.warn(`🪠 SDC directory ${root} does not exist, skipping`);
      continue;
    }
    for (const file of findSdcSources(absoluteRoot)) {
      // Explicit mappings take precedence, and roots may overlap.
      if (sassSrcs[file] || postcssSrcs[file]) {
        continue;
      }
      const target = sdcTarget(file);
      if (targets.has(target)) {
        throw new Error(`🪠 ${relative(targets.get(target))} and ${relative(file)} both compile to ${relative(target)}`);
      }
      targets.set(target, file);
      if (file.endsWith(POSTCSS_SOURCE_EXTENSION)) {
        postcssSrcs[file] = target;
      }
      else {
        sassSrcs[file] = target;
      }
    }
  }

  return { sassSrcs, postcssSrcs };
}

/**
 * Renames each file to the name of its target, e.g. so that
 * my_component.pcss.css is written as my_component.css.
 */
function renameToTarget(srcs) {
  return new Transform({
    objectMode: true,
    transform(file, encoding, callback) {
      const target = srcs[file.history[0]];
      if (target) {
        file.path = path.join(path.dirname(file.path), path.basename(target));
      }
      callback(null, file);
    },
  });
}

function compile(srcs, { useSass, minify, rename = false }) {
  const sources = Object.keys(srcs);
  if (!sources.length) {
    return Promise.resolve();
  }

  let stream = src(sources).pipe(sourcemaps.init());
  if (useSass) {
    stream = stream
      .pipe(sassGlob())
      .pipe(gulpSass({
        style: minify ? 'compressed' : 'expanded',
        loadPaths: [modernNormalizePath],
      }).on('error', gulpSass.logError));
  }
  stream = stream.pipe(postcss([
    autoprefixer(),
    ...(minify ? [cssnano()] : []),
  ]));
  if (rename) {
    stream = stream.pipe(renameToTarget(srcs));
  }

  return stream
    .pipe(sourcemaps.write('./'))
    .pipe(dest((file) => {
      const destFile = path.dirname(srcs[file.history[0]]);
      console.log(`🪠 Writing ${relative(file.path)}`);
      return destFile;
    }));
}

// Sources are resolved once per run and shared by the Sass and PostCSS
// pipelines.
let sources = { sassSrcs: {}, postcssSrcs: {} };
function resolveSources(done) {
  sources = getSources();
  done();
}

const build = minify => series(resolveSources, parallel(
  function compileSass() {
    return compile(sources.sassSrcs, { useSass: true, minify });
  },
  function compilePostcss() {
    return compile(sources.postcssSrcs, { useSass: false, minify, rename: true });
  },
));

task('sass', build(true));
task('development', build(false));

/**
 * Lists the files a Sass file loads (itself plus any partials), for watching.
 */
function loadedFiles(file) {
  try {
    return dartSass.compile(file, { loadPaths: [modernNormalizePath] }).loadedUrls
      .filter(url => url.protocol === 'file:')
      .map(url => fileURLToPath(url));
  }
  catch (error) {
    // e.g. globbed imports that only gulp-sass-glob understands. The file
    // itself is still watched.
    return [file];
  }
}

task('sass:watch', function() {
  const includes = Object.keys(getSources().sassSrcs).flatMap(loadedFiles);
  const sdcGlobs = sdcRoots.flatMap(root => [
    `${root}/**/${SDC_DIRECTORY}/**/*${SASS_SOURCE_EXTENSION}`,
    `${root}/**/${SDC_DIRECTORY}/**/*${POSTCSS_SOURCE_EXTENSION}`,
  ]);
  console.log('🪠 Watching for changes');
  watch([...new Set(includes), ...sdcGlobs], {
      usePolling: true,
  }, series('development'));
});
