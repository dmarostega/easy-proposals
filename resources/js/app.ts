import './bootstrap';
import { createApp } from 'vue';
import AppShell from './pages/AppShell.vue';
import '../css/app.css';

const mount = document.getElementById('app');

if (mount) {
  createApp(AppShell).mount(mount);
}
