const yargs = require('yargs');
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv
const { build } = require('esbuild');
const chokidar = require('chokidar');
const themes = argv.themes;
const plugins = [];

try {
  const { pnpPlugin } = require('@yarnpkg/esbuild-plugin-pnp');
  plugins.push(pnpPlugin());
} catch(e) {
}


(async() => {
  try {
    let builder = await build({
      plugins,
      entryPoints: themes.split(' ').map(theme => `web/themes/custom/${theme}/script.js`),
      outdir: 'web/themes/custom',
      entryNames: '[dir]/[name].min',
      bundle: true,
      sourcemap: true,
      minify: !!argv.minify,
      incremental: !!argv.watch,
      logLevel: 'info',
    });

    if (!!argv.watch) {
      let ready = false;
      chokidar.watch(themes.split(' ').map(theme => `web/themes/custom/${theme}/**/*.js`), {
        ignored: themes.split(' ').map(theme => `web/themes/custom/${theme}/**/script.min.js`),
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
  }
  catch(err) {
    console.error(err);
    process.exit(1);
  }
})();
