import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/reader.css'],
            refresh: [
                'resources/views/**',
                'app/Http/Controllers/**',
                'app/Http/Livewire/**',
                'routes/web.php',
                'lang/**',
            ],
        }),
        tailwindcss(),
    ],
});
