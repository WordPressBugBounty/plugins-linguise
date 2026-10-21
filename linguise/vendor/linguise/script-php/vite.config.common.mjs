import { defineConfig } from 'vite';

/**
 * Shared Vite config for a single-entry browser IIFE build.
 */
export function defineCommonConfig({ env, inputName, outputName }) {
    return defineConfig({
        base: './',

        build: {
            outDir: 'assets',
            emptyOutDir: false,
            sourcemap: env.MODE !== 'production',
            minify: env.MODE === 'production',
            target: 'es2015',
            assetsInlineLimit: 0,
            cssCodeSplit: false,

            rollupOptions: {
                input: inputName,

                output: {
                    format: 'iife',
                    entryFileNames: `js/${outputName}.bundle.js`,
                    globals: (id) => {
                        if (id === 'jquery' || id.startsWith('jquery/')) return 'jQuery';
                    },
                    assetFileNames: (assetInfo) => {
                        const original = assetInfo.originalFileName ?? '';
                        const name = assetInfo.name ?? '';

                        if (name.endsWith('.css')) {
                            return `css/${outputName}.bundle.css`;
                        }
                        if (original.includes('flags-rounded')) {
                            return 'images/flags-rounded/[name].[ext]';
                        }
                        if (original.includes('flags-rectangular')) {
                            return 'images/flags-rectangular/[name].[ext]';
                        }
                        if (/\.(ttf|otf|woff2?|eot)$/i.test(original)) {
                            return 'fonts/[name].[ext]';
                        }
                        if (original.includes('images/vendor')) {
                            return "images/vendor/[name].[ext]";
                        }

                        return 'images/[name].[ext]';
                    },
                },

                external: (id) => id === 'jquery' || id.startsWith('jquery/'),
            },
        },

        css: { devSourcemap: true },
    });
}
