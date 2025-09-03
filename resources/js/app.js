import '../css/app.css';       // Importa TailwindCSS
import './bootstrap';
import { createApp } from 'vue';
import QuoteDemo from './components/QuoteDemo.vue';

const app = createApp({
    components: {
        'quote-demo': QuoteDemo,
    },
    template: `
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">
                Precious Metals E-commerce Demo
            </h1>
            <quote-demo></quote-demo>
        </div>
    `
});

app.mount('#app');
