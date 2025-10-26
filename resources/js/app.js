import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { useDarkModeStore } from '@/Stores/darkMode.js';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from './Pages/Components/Toast.vue';
import FlashMessage from './Pages/Components/FlashMessage.vue';
import CKEditor from '@ckeditor/ckeditor5-vue'; // Correção aqui

const appName = import.meta.env.VITE_APP_NAME || 'ACADEPOL';

const pinia = createPinia();

createInertiaApp({
  title: title => `${title} - ${appName}`,
  resolve: name =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue')
    ),
  setup({ el, App, props, plugin }) {
    axios.defaults.withCredentials = true;
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, Ziggy)
      .use(Toast, FlashMessage)
      .use(CKEditor)
      .mount(el);
  },
  progress: {
    color: '#0076ff',
  },
});

const darkModeStore = useDarkModeStore(pinia);

if (
  (!localStorage['darkMode'] &&
    window.matchMedia('(prefers-color-scheme: dark)').matches) ||
  localStorage['darkMode'] === '0'
) {
  darkModeStore.set(true);
}
