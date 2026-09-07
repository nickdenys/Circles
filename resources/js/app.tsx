import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AuthenticatedLayout from './Layouts/AuthenticatedLayout';

interface PageModule {
    default: {
        layout?: (page: React.ReactNode) => React.ReactNode;
    };
}

const appName = import.meta.env.VITE_APP_NAME || 'Circles';

createInertiaApp({
    /** Mirrors the brand suffix Blade prints on the first load. */
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
        const page = pages[`./Pages/${name}.tsx`] as PageModule;

        if (!page.default.layout && !name.startsWith('Auth/')) {
            page.default.layout = (page: React.ReactNode) => (
                <AuthenticatedLayout>{page}</AuthenticatedLayout>
            );
        }

        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
