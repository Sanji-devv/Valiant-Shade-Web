import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { globSync } from 'glob';
import path from 'path';
import iconsPlugin from './vite.valiant-shade.icons.plugin.js';
import { globSyncEntries } from './vite.glob-entries.js';

const resourceRoot = 'resources/valiant-shade';

function getFilesArray(query) {
  return globSync(query);
}

const pageJsFiles = getFilesArray(`${resourceRoot}/assets/js/*.js`);
const vendorJsFiles = getFilesArray(`${resourceRoot}/assets/vendor/js/*.js`);
const libsJsFiles = getFilesArray(`${resourceRoot}/assets/vendor/libs/**/*.js`);
const libsScssFiles = globSyncEntries(`${resourceRoot}/assets/vendor/libs/**/!(_)*.scss`);
const libsCssFiles = getFilesArray(`${resourceRoot}/assets/vendor/libs/**/*.css`);
const coreScssFiles = globSyncEntries(`${resourceRoot}/assets/vendor/scss/**/!(_)*.scss`);
const fontsScssFiles = globSyncEntries(`${resourceRoot}/assets/vendor/fonts/**/!(_)*.scss`);
const fontsJsFiles = getFilesArray(`${resourceRoot}/assets/vendor/fonts/**/!(_)*.js`);
const fontsCssFiles = getFilesArray(`${resourceRoot}/assets/vendor/fonts/**/!(_)*.css`);

function libsWindowAssignment() {
  return {
    name: 'libsWindowAssignment',
    transform(src, id) {
      if (id.includes('jkanban.js')) {
        return src.replace('this.jKanban', 'window.jKanban');
      }
      if (id.includes('vfs_fonts')) {
        return src.replaceAll('this.pdfMake', 'window.pdfMake');
      }
    },
  };
}

export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
  },
  plugins: [
    laravel({
      buildDirectory: 'static/valiant-shade/build',
      hotFile: 'public/static/valiant-shade.hot',
      input: [
        `${resourceRoot}/css/app.css`,
        `${resourceRoot}/assets/css/demo.css`,
        `${resourceRoot}/js/app.js`,
        'resources/css/valiant-shade/map.css',
        'resources/js/valiant-shade/map.js',
        'resources/css/valiant-shade/image-map.css',
        'resources/js/valiant-shade/image-map.js',
        'resources/css/valiant-shade/newspaper.css',
        'resources/js/valiant-shade/newspaper.js',
        ...pageJsFiles,
        ...vendorJsFiles,
        ...libsJsFiles,
        ...coreScssFiles,
        ...libsScssFiles,
        ...libsCssFiles,
        ...fontsScssFiles,
        ...fontsJsFiles,
        ...fontsCssFiles,
      ],
      refresh: true,
    }),
    html(),
    libsWindowAssignment(),
    iconsPlugin(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, resourceRoot),
    },
  },
  json: {
    stringify: true,
  },
  build: {
    commonjsOptions: {
      include: [/node_modules/],
    },
  },
});
