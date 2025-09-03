import './bootstrap';
import { createApp } from 'vue';
import QuoteDemo from './components/QuoteDemo.vue';

const app = createApp({
    components: {
        QuoteDemo,
    }
});

app.mount('#app');
