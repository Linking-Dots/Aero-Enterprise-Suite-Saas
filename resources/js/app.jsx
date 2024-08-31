import './bootstrap';
import '../css/app.css';
import React from 'react';
import {createRoot} from 'react-dom/client';
import {createInertiaApp} from '@inertiajs/react';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';

import { InertiaProgress } from '@inertiajs/progress';

const appName =  'DBEDC ERP';


createInertiaApp({
    progress: {
        // Use the `CustomProgressBar` here if needed
        color: '#fff', // Custom progress color
    },
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

