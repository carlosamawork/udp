import { defineConfig } from 'vite';
import path from 'path';
import liveReload from 'vite-plugin-live-reload';
import { glob } from 'glob';

// Detecta todos los entry points JS en src/js/
// Así puedes crear archivos como src/js/page-home.js, src/js/page-contact.js
// y se compilarán automáticamente como entradas separadas.
const jsEntries = glob.sync('src/js/*.js').reduce((entries, file) => {
    const name = path.basename(file, '.js');
    entries[name] = path.resolve(__dirname, file);
    return entries;
}, {});

export default defineConfig(({ command }) => {
    const isDev = command === 'serve';

    return {
        // Base path para los assets compilados
        base: isDev ? '/' : '/wp-content/themes/starter-theme/dist/',

        plugins: [
            // Recarga el navegador cuando cambien archivos PHP
            liveReload([
                path.resolve(__dirname, './**/*.php'),
            ]),
        ],

        // Directorios fuente
        root: '',
        publicDir: false,

        build: {
            outDir: 'dist',
            emptyOutDir: true,
            manifest: true, // Genera manifest.json para WordPress
            sourcemap: isDev,

            rollupOptions: {
                input: {
                    // Entry principal (SCSS + JS)
                    theme: path.resolve(__dirname, 'src/js/main.js'),
                    editor: path.resolve(__dirname, 'src/scss/editor.scss'),
                    // Entries adicionales auto-detectados
                    ...jsEntries,
                },
                output: {
                    // Nombres con hash para cache busting en producción
                    entryFileNames: 'js/[name].[hash].js',
                    chunkFileNames: 'js/chunks/[name].[hash].js',
                    assetFileNames: (assetInfo) => {
                        if (/\.css$/.test(assetInfo.name)) {
                            return 'css/[name].[hash][extname]';
                        }
                        if (/\.(woff2?|ttf|eot|otf)$/.test(assetInfo.name)) {
                            return 'fonts/[name].[hash][extname]';
                        }
                        if (/\.(png|jpe?g|gif|svg|webp|avif|ico)$/.test(assetInfo.name)) {
                            return 'img/[name].[hash][extname]';
                        }
                        return 'assets/[name].[hash][extname]';
                    },
                },
            },
        },

        // Dev server con proxy a WordPress local
        server: {
            // Puerto para Vite dev server
            port: 5173,
            strictPort: true,
            // CORS para que WordPress pueda cargar los assets
            cors: true,
            // Proxy: ajusta la URL a tu entorno local
            origin: 'http://localhost:5173',
        },

        css: {
            preprocessorOptions: {
                scss: {
                    // loadPaths permite a Sass resolver paths relativos a src/scss
                    loadPaths: [path.resolve(__dirname, 'src/scss')],
                    // Inyecta automáticamente variables y mixins en todos los SCSS.
                    // Usamos @import (legacy, scope global) en lugar de @use para que
                    // las variables sean visibles dentro del scope de los mixins.
                    // Modernizar a @use requiere refactor de todos los SCSS — fuera de F0.
                    additionalData: `
                        @import "utilities/variables";
                        @import "utilities/mixins";
                    `,
                },
            },
            postcss: {
                plugins: [
                    // Autoprefixer se configura con browserslist en package.json
                ],
            },
        },

        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'src'),
                '@js': path.resolve(__dirname, 'src/js'),
                '@scss': path.resolve(__dirname, 'src/scss'),
                '@modules': path.resolve(__dirname, 'src/js/modules'),
                '@utils': path.resolve(__dirname, 'src/js/utils'),
            },
        },
    };
});
