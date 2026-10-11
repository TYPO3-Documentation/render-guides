import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        // The theme's only JavaScript artifact, loaded by
        // resources/template/structure/layoutParts/footerAssets.html.twig as a
        // classic script, so the bundle has to be an IIFE (Immediately Invoked
        // Function Expression) rather than a module.
        outDir: 'resources/public/js',
        // `npm run clean` empties this directory, and `npm run copy` puts
        // bootstrap.min.js in it, so vite must leave what it finds alone.
        emptyOutDir: false,
        rolldownOptions: {
            input: 'assets/js/theme.js',
            output: {
                format: 'iife',
                entryFileNames: 'theme.min.js',
                assetFileNames: '[name][extname]'
            }
        }
    }
});
