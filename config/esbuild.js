const yargs = require('yargs');
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv
const { build } = require('esbuild');
const chokidar = require('chokidar');
const { pnpPlugin } = require('@yarnpkg/esbuild-plugin-pnp');
const themes = argv.themes;
const modules = argv.modules;

if (!themes.length && !modules.length) {
  console.log('No files to compile');
  process.exit(0);
}

const scripts = themes.split(' ').filter(theme => theme !== '').map(theme => `web/themes/custom/${theme}`)
  .concat(modules.split(' ').filter(module => module !== '').map(module => `web/modules/custom/${module}`));

(async() => {
  try {
    let builder = await build({
      plugins: [pnpPlugin()],
      entryPoints: scripts.map(script => `${script}/script.js`),
      outdir: 'web',
      entryNames: '[dir]/[name].min',
      bundle: true,
      sourcemap: true,
      minify: !!argv.minify,
      incremental: !!argv.watch,
      logLevel: 'info',
    });

    if (!!argv.watch) {
      let ready = false;
      chokidar.watch(scripts.map(script => `${script}/**/*.js`), {
        ignored: scripts.map(script => `${script}/**/script.min.js`),
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
