import './bootstrap';
import { createApp, defineComponent } from 'vue';
import '../css/app.css';

const App = defineComponent({
  template: '<slot />',
});

const mount = document.getElementById('app');
if (mount) {
  createApp(App).mount(mount);
}
