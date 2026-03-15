import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/app.css', 
                'resources/css/NavbarSearchFilter.css', 
                'resources/css/Sidebar.css', 
                'resources/css/login.css', 'resources/js/Auth/login.js',
                'resources/css/ProjekRead.css', 
                'resources/css/Dashboard.css',
                'resources/css/EditProfile.css',
                'resources/css/EditProjek.css',
                'resources/css/Jobs.css',
                'resources/css/Kategori.css',
                'resources/css/List.css',
                'resources/css/Logbook.css',
                'resources/css/ProjekCard.css',
                'resources/js/Projek/read-projek.js',
                'resources/js/Projek/jobs.js',
                'resources/js/Projek/list.js',
                'resources/js/Projek/logbook.js',
                'resources/js/Projek/edit-projek.js',
                'resources/js/Auth/edit-profile.js',
                'resources/js/Auth/signup.js',
                'resources/js/Components/navbar.js',
                'resources/js/Kategori/kategori.js',

            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
