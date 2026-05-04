import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

const projectRoot = process.cwd();
const configuredPublicDir = process.env.APP_PUBLIC_PATH ? path.resolve(process.env.APP_PUBLIC_PATH) : null;
const serverPublicDir = configuredPublicDir ?? path.resolve(projectRoot, '../public_html/emtq');
const localPublicDir = path.resolve(projectRoot, 'public');
const publicDir = fs.existsSync(serverPublicDir) ? serverPublicDir : localPublicDir;
const buildDir = 'build';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            publicDirectory: publicDir,
            buildDirectory: buildDir,
        }),
        tailwindcss(),
    ],
    build: {
        outDir: path.resolve(publicDir, buildDir),
        emptyOutDir: false,
    },
});
