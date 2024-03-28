const fs = require('fs');
const path = require('path');
let yarn2 = false;
try {
  const { resolveToUnqualified } = require('pnpapi');
  yarn2 = true;
}
catch (error) {
  // Nothing
}

const yargs = require('yargs');
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv
const { build } = require('esbuild');
const chokidar = require('chokidar');
const scripts = argv.files.split(' ');

if (!scripts.length) {
  console.log('No files to compile');
  process.exit(0);
}

const targets = [];
const sources = [];

// Check that every source/target has the same basedir (probably "web") due to
// being unable to provide separate entryNames.
// See https://github.com/evanw/esbuild/issues/224
const baseDirs = [];
// Check that the output script name matches the same as the input script name,
// due to the same limitation above. It can have a different file extension,
// e.g. web/modules/custom/mymodule/script.js:web/modules/custom/mymodule/script.min.js
const fileExtensions = [];
scripts.forEach(script => {
  const [source, target] = script.split(':');
  sources.push(source);
  targets.push(target);
  baseDirs.push(source.split('/')[0]);
  baseDirs.push(target.split('/')[0]);
  const sourceFile = source.split('/').pop();
  const targetFile = target.split('/').pop();
  fileExtensions.push(targetFile.split('.', 2)[1]);
});
const uniqueBaseDir = [...new Set(baseDirs)];
if (uniqueBaseDir.length !== 1) {
  console.error('All source and target files must have a root directory in common');
  process.exit(1);
}
const uniqueFileExtension = [...new Set(fileExtensions)];
if (uniqueFileExtension.length !== 1) {
  console.error('All target files must have the same file extension e.g. "min.js"');
  process.exit(1);
}


(async () => {
  try {
    let plugins = [];
    if (yarn2) {
      const { pnpPlugin } = require('@yarnpkg/esbuild-plugin-pnp');
      plugins = [pnpPlugin()]
    }
    let builder = await build({
      plugins,
      entryPoints: scripts.map(script => script.split(':')[0]),
      outdir: uniqueBaseDir[0],
      outbase: uniqueBaseDir[0],
      entryNames: `[dir]/[name].${uniqueFileExtension[0]}`,
      bundle: true,
      sourcemap: true,
      minify: !!argv.minify,
      logLevel: 'info',
    });

    if (!!argv.watch) {
      let ready = false;
      chokidar.watch(`${uniqueBaseDir[0]}/**/*.js`, {
        ignored: targets,
      })
        .on('ready', () => {
          ready = true;
          console.log('[esbuild]', 'watching for changes');
        })
        .on('all', (event, path) => {
          if (ready) {
            console.log('[esbuild]', event, path);
            builder.rebuild()
              .then(result => {
                console.log('[esbuild]', 'watch build succeeded')
                if (result.warnings.length) {
                  console.warn('[esbuild]', 'watch build has warnings', result.warnings);
                }
              })
              .catch(err => {
                console.error('[esbuild]', 'watch build failed', err);
              });
          }
        });
    }
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
})();
