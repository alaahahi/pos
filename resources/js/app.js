import '../css/app.css';
import '../../public/dashboard-assets/js/main.js';

import axios from 'axios';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, {
            // إعدادات افتراضية
            position: "bottom-right",
            timeout: 5000,
            rtl: true,
            });

        // Global session checker
        app.config.globalProperties.$checkSession = async () => {
            try {
                await axios.get('/api/check-session');
            } catch (error) {
                if (error.response && error.response.status === 401) {
                    window.location.href = '/login'; // Redirect if session expired
                }
            }
        };

        // Run session check on app initialization
        app.config.globalProperties.$checkSession();

        // Register Service Worker for Offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('✅ Service Worker registered:', registration.scope);
                    })
                    .catch(error => {
                        console.log('❌ Service Worker registration failed:', error);
                    });
            });
        }


        app.mount(el);
    },
    progress: {
        color: '#4B5563',
        delay: 100, // تأخير صغير قبل إظهار شريط التقدم
    },
});