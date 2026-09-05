import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { globSync } from 'glob';
import path from 'path';
import iconsPlugin from './vite.demo-vuexy.icons.plugin.js';

const resourceRoot = 'resources/demo-vuexy';

function getFilesArray(query) {
  return globSync(query);
}

const pageJsFiles = getFilesArray(`${resourceRoot}/assets/js/*.js`);
const vendorJsFiles = getFilesArray(`${resourceRoot}/assets/vendor/js/*.js`);
const libsJsFiles = getFilesArray(`${resourceRoot}/assets/vendor/libs/**/*.js`);
const libsScssFiles = getFilesArray(`${resourceRoot}/assets/vendor/libs/**/!(_)*.scss`);
const libsCssFiles = getFilesArray(`${resourceRoot}/assets/vendor/libs/**/*.css`);
const coreScssFiles = getFilesArray(`${resourceRoot}/assets/vendor/scss/**/!(_)*.scss`);
const fontsScssFiles = getFilesArray(`${resourceRoot}/assets/vendor/fonts/!(_)*.scss`);
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
  plugins: [
    laravel({
      buildDirectory: 'static/demo-vuexy/build',
      hotFile: 'public/static/demo-vuexy.hot',
      input: [
        `${resourceRoot}/css/app.css`,
        `${resourceRoot}/assets/css/demo.css`,
        `${resourceRoot}/js/app.js`,
        ...pageJsFiles,
        ...vendorJsFiles,
        ...libsJsFiles,
        `${resourceRoot}/js/laravel-user-management.js`,
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
