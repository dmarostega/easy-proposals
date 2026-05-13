import { existsSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

const legacyEntrypoint = 'resources/js/app.js';

if (existsSync(legacyEntrypoint)) {
    throw new Error(`${legacyEntrypoint} is deprecated. Use resources/js/app.ts as the single Vite entrypoint.`);
}

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.ts'], refresh: true }),
        tailwindcss(),
        vue(),
    ],
});
