import './bootstrap';
import { createApp } from 'vue';
import AppShell from './pages/AppShell.vue';
import AdminShell from './pages/admin/AdminShell.vue';
import '../css/app.css';

const mount = document.getElementById('app');

if (mount) {
  const component = mount.dataset.page === 'admin' ? AdminShell : AppShell;
  createApp(component).mount(mount);
}
