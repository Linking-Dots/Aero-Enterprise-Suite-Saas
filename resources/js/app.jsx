import './bootstrap';
import '../css/app.css';
// import "bootstrap/dist/css/bootstrap.css";
// import 'bootstrap/dist/js/bootstrap.bundle.js';
import React from 'react';
import "primereact/resources/themes/lara-dark-cyan/theme.css";
import 'remixicon/fonts/remixicon.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';


const appName =  'DBEDC ERP';


createInertiaApp({
    progress: true,
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App style={{
            overflow: 'hidden',
            height: '100%',
            margin: 0,
        }} {...props} />);
    },

});
