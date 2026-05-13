<script lang="ts">
import { computed, defineComponent, onMounted, reactive, ref } from 'vue';
import axios from 'axios';

type RecordData = Record<string, any>;

const money = (value: number | string | null | undefined) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value ?? 0));

const field = (data: RecordData, name: string, fallback: any = '') => data[name] ?? fallback;

const formatStatLabel = (key: string | number) => String(key).replace(/_/g, ' ').toUpperCase();

const formatStatValue = (value: any, key?: string | number) => {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  if (Array.isArray(value)) {
    if (key === 'users_by_plan') {
      return value.length
        ? value.map((plan: RecordData) => `${plan.name}: ${plan.users_count ?? 0}`).join(' · ')
        : 'Sem usuários por plano';
    }

    return value.length ? `${value.length} registro(s)` : 'Nenhum registro';
  }

  if (typeof value === 'object') {
    return Object.entries(value)
      .map(([entryKey, entryValue]) => `${formatStatLabel(entryKey)}: ${entryValue ?? '—'}`)
      .join(' · ');
  }

  return value;
};

const settingLabel = (key: string | number) => String(key).replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const settingInputType = (key: string | number) => String(key).includes('color') ? 'color' : 'text';


export default defineComponent({
  setup() {
    const mount = document.getElementById('app');
    const page = mount?.dataset.page ?? 'app';
    const user = ref<RecordData>(JSON.parse(mount?.dataset.user ?? '{}'));
    const isAdmin = mount?.dataset.admin === 'true';
    const active = ref(page === 'admin' ? 'admin' : 'dashboard');
    const message = ref('');
    const error = ref('');
    const loading = ref(false);

    const stats = ref<RecordData>({});
    const customers = ref<RecordData[]>([]);
    const services = ref<RecordData[]>([]);
    const proposals = ref<RecordData[]>([]);
    const plans = ref<RecordData[]>([]);
    const users = ref<RecordData[]>([]);
    const settings = ref<RecordData>({});

    const customerForm = reactive<RecordData>({ name: '', email: '', phone: '', document: '', address: '', notes: '' });
    const serviceForm = reactive<RecordData>({ name: '', description: '', unit_price: 0, is_active: true });
    const proposalForm = reactive<RecordData>({
      id: null,
      customer_id: '',
      title: '',
      description: '',
      valid_until: '',
      discount: 0,
      notes: '',
      commercial_terms: '',
      items: [{ description: '', quantity: 1, unit_price: 0 }],
    });
    const planForm = reactive<RecordData>({
      id: null,
      name: '',
      slug: '',
      monthly_price_cents: 0,
      monthly_proposal_limit: '',
      customer_limit: '',
      allows_pdf: false,
      allows_custom_logo: false,
      is_active: true,
    });
    const userForm = reactive<RecordData>({ id: null, name: '', email: '', plan_id: '', role: 'user', is_active: true });

    const proposalSubtotal = computed(() => proposalForm.items.reduce((total: number, item: RecordData) => total + Number(item.quantity || 0) * Number(item.unit_price || 0), 0));
    const proposalTotal = computed(() => Math.max(0, proposalSubtotal.value - Number(proposalForm.discount || 0)));

    const setMessage = (text: string) => {
      message.value = text;
      error.value = '';
    };

    const setError = (exception: any) => {
      const errors = exception.response?.data?.errors;
      error.value = errors ? Object.values(errors).flat().join(' ') : (exception.response?.data?.message ?? 'Não foi possível concluir a ação.');
      message.value = '';
    };

    const load = async () => {
      loading.value = true;
      try {
        const [dashboardResponse, customersResponse, servicesResponse, proposalsResponse] = await Promise.all([
          axios.get('/dashboard', { headers: { Accept: 'application/json' } }),
          axios.get('/clientes', { headers: { Accept: 'application/json' } }),
          axios.get('/servicos', { headers: { Accept: 'application/json' } }),
          axios.get('/propostas', { headers: { Accept: 'application/json' } }),
        ]);
        stats.value = dashboardResponse.data;
        customers.value = customersResponse.data.data ?? customersResponse.data;
        services.value = servicesResponse.data.data ?? servicesResponse.data;
        proposals.value = proposalsResponse.data.data ?? proposalsResponse.data;

        if (isAdmin) {
          const [plansResponse, usersResponse, settingsResponse, reportsResponse] = await Promise.all([
            axios.get('/admin/planos', { headers: { Accept: 'application/json' } }),
            axios.get('/admin/usuarios', { headers: { Accept: 'application/json' } }),
            axios.get('/admin/configuracoes', { headers: { Accept: 'application/json' } }),
            axios.get('/admin/relatorios', { headers: { Accept: 'application/json' } }),
          ]);
          plans.value = plansResponse.data.data ?? plansResponse.data;
          users.value = usersResponse.data.data ?? usersResponse.data;
          settings.value = settingsResponse.data;
          stats.value = { ...stats.value, ...reportsResponse.data };
        }
      } catch (exception) {
        setError(exception);
      } finally {
        loading.value = false;
      }
    };

    const resetProposal = () => {
      Object.assign(proposalForm, { id: null, customer_id: '', title: '', description: '', valid_until: '', discount: 0, notes: '', commercial_terms: '', items: [{ description: '', quantity: 1, unit_price: 0 }] });
    };

    const editProposal = async (proposal: RecordData) => {
      const response = await axios.get(`/propostas/${proposal.id}`, { headers: { Accept: 'application/json' } });
      Object.assign(proposalForm, response.data, {
        customer_id: response.data.customer_id,
        valid_until: response.data.valid_until?.slice(0, 10) ?? '',
        items: response.data.items.map((item: RecordData) => ({ description: item.description, quantity: item.quantity, unit_price: item.unit_price })),
      });
      active.value = 'proposals';
    };

    const saveCustomer = async () => {
      try {
        await axios.post('/clientes', customerForm);
        Object.assign(customerForm, { name: '', email: '', phone: '', document: '', address: '', notes: '' });
        setMessage('Cliente criado com sucesso.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const saveService = async () => {
      try {
        await axios.post('/servicos', serviceForm);
        Object.assign(serviceForm, { name: '', description: '', unit_price: 0, is_active: true });
        setMessage('Serviço criado com sucesso.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const saveProposal = async () => {
      try {
        const payload = { ...proposalForm, discount: Number(proposalForm.discount || 0) };
        if (proposalForm.id) {
          await axios.put(`/propostas/${proposalForm.id}`, payload);
          setMessage('Proposta atualizada com sucesso.');
        } else {
          await axios.post('/propostas', payload);
          setMessage('Proposta criada com sucesso.');
        }
        resetProposal();
        await load();
      } catch (exception) { setError(exception); }
    };

    const sendProposal = async (proposal: RecordData) => {
      try {
        await axios.post(`/propostas/${proposal.id}/enviar`);
        setMessage('Proposta enviada por e-mail quando o cliente possui e-mail cadastrado.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const destroy = async (url: string, confirmation = 'Confirma a exclusão?') => {
      if (! confirm(confirmation)) return;
      try {
        await axios.delete(url);
        setMessage('Registro removido com sucesso.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const savePlan = async () => {
      try {
        const payload = {
          ...planForm,
          monthly_proposal_limit: planForm.monthly_proposal_limit === '' ? null : Number(planForm.monthly_proposal_limit),
          customer_limit: planForm.customer_limit === '' ? null : Number(planForm.customer_limit),
        };
        if (planForm.id) {
          await axios.put(`/admin/planos/${planForm.id}`, payload);
        } else {
          await axios.post('/admin/planos', payload);
        }
        Object.assign(planForm, { id: null, name: '', slug: '', monthly_price_cents: 0, monthly_proposal_limit: '', customer_limit: '', allows_pdf: false, allows_custom_logo: false, is_active: true });
        setMessage('Plano salvo com sucesso.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const editPlan = (plan: RecordData) => Object.assign(planForm, plan, {
      monthly_proposal_limit: plan.monthly_proposal_limit ?? '',
      customer_limit: plan.customer_limit ?? '',
    });

    const saveUser = async () => {
      try {
        await axios.put(`/admin/usuarios/${userForm.id}`, { ...userForm, plan_id: userForm.plan_id || null });
        Object.assign(userForm, { id: null, name: '', email: '', plan_id: '', role: 'user', is_active: true });
        setMessage('Usuário atualizado com sucesso.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const saveSettings = async () => {
      try {
        await axios.put('/admin/configuracoes', { settings: settings.value });
        setMessage('Configurações atualizadas com sucesso.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const logout = () => {
      const form = document.createElement('form');
      const csrfToken = document.querySelector<HTMLMetaElement>('meta[name=\"csrf-token\"]')?.content ?? '';

      form.method = 'POST';
      form.action = '/logout';

      if (csrfToken) {
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken;
        form.appendChild(token);
      }

      document.body.appendChild(form);
      form.submit();
    };

    onMounted(load);

    return {
      active, customerForm, customers, destroy, editPlan, editProposal, error, field, formatStatLabel, formatStatValue, isAdmin, loading, logout, message, money,
      planForm, plans, proposalForm, proposalSubtotal, proposalTotal, proposals, resetProposal, saveCustomer,
      savePlan, saveProposal, saveService, saveSettings, saveUser, sendProposal, serviceForm, services,
      settingInputType, settingLabel, settings, stats, user, userForm, users,
    };
  },
});
</script>

<template>
    <div class="min-h-screen">
      <aside class="fixed inset-y-0 left-0 hidden w-72 flex-col bg-slate-950 p-6 text-white lg:flex">
        <a href="/dashboard" class="text-2xl font-black">Proposta Fácil</a>
        <p class="mt-2 text-sm text-slate-300">{{ user.name }} · {{ user.plan?.name ?? 'Sem plano' }}</p>
        <nav class="mt-8 grid gap-2 text-sm">
          <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" @click="active = 'dashboard'">Dashboard</button>
          <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" @click="active = 'proposals'">Editor de propostas</button>
          <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" @click="active = 'customers'">Clientes</button>
          <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" @click="active = 'services'">Serviços</button>
          <button v-if="isAdmin" class="rounded-xl px-4 py-3 text-left hover:bg-white/10" @click="active = 'admin'">Admin</button>
          <a class="rounded-xl px-4 py-3 hover:bg-white/10" href="/perfil">Perfil da marca</a>
          <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" type="button" @click="logout">Sair</button>
        </nav>
      </aside>

      <main class="lg:ml-72">
        <header class="sticky top-0 z-10 border-b bg-white/90 px-6 py-4 backdrop-blur">
          <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <div>
              <p class="text-sm text-slate-500">Área autenticada</p>
              <h1 class="text-2xl font-bold">{{ active === 'admin' ? 'Painel admin' : 'Gestão de propostas' }}</h1>
            </div>
            <div class="flex gap-2 text-sm lg:hidden">
              <button class="rounded bg-slate-900 px-3 py-2 text-white" @click="active='dashboard'">Dashboard</button>
              <button class="rounded bg-slate-900 px-3 py-2 text-white" @click="active='proposals'">Propostas</button>
              <button v-if="isAdmin" class="rounded bg-slate-900 px-3 py-2 text-white" @click="active='admin'">Admin</button>
              <button class="rounded bg-rose-600 px-3 py-2 text-white" type="button" @click="logout">Sair</button>
            </div>
          </div>
        </header>

        <section class="mx-auto grid max-w-7xl gap-6 p-6">
          <div v-if="message" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">{{ message }}</div>
          <div v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-700">{{ error }}</div>
          <div v-if="loading" class="rounded-2xl bg-white p-4 shadow-sm">Carregando dados...</div>

          <section v-if="active === 'dashboard'" class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
            <article v-for="(value, key) in stats" :key="key" class="rounded-3xl bg-white p-6 shadow-sm">
              <p class="text-sm uppercase tracking-wide text-slate-500">{{ formatStatLabel(key) }}</p>
              <strong class="mt-3 block break-words text-2xl">{{ formatStatValue(value, key) }}</strong>
            </article>
          </section>

          <section v-if="active === 'customers'" class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveCustomer">
              <h2 class="text-xl font-bold">Novo cliente</h2>
              <input v-model="customerForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required>
              <input v-model="customerForm.email" class="mt-3 w-full rounded-xl border p-3" placeholder="E-mail">
              <input v-model="customerForm.phone" class="mt-3 w-full rounded-xl border p-3" placeholder="Telefone">
              <input v-model="customerForm.document" class="mt-3 w-full rounded-xl border p-3" placeholder="Documento">
              <textarea v-model="customerForm.address" class="mt-3 w-full rounded-xl border p-3" placeholder="Endereço"></textarea>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">Salvar cliente</button>
            </form>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
              <h2 class="text-xl font-bold">Clientes cadastrados</h2>
              <div v-for="customer in customers" :key="customer.id" class="mt-4 flex items-center justify-between rounded-2xl border p-4">
                <div><strong>{{ customer.name }}</strong><p class="text-sm text-slate-500">{{ customer.email || customer.phone || 'Sem contato' }}</p></div>
                <button class="text-rose-600" @click="destroy('/clientes/' + customer.id)">Excluir</button>
              </div>
            </div>
          </section>

          <section v-if="active === 'services'" class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveService">
              <h2 class="text-xl font-bold">Novo serviço</h2>
              <input v-model="serviceForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required>
              <textarea v-model="serviceForm.description" class="mt-3 w-full rounded-xl border p-3" placeholder="Descrição"></textarea>
              <input v-model.number="serviceForm.unit_price" type="number" step="0.01" class="mt-3 w-full rounded-xl border p-3" placeholder="Preço" required>
              <label class="mt-3 flex gap-2"><input v-model="serviceForm.is_active" type="checkbox"> Ativo</label>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">Salvar serviço</button>
            </form>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
              <h2 class="text-xl font-bold">Catálogo</h2>
              <div v-for="service in services" :key="service.id" class="mt-4 flex items-center justify-between rounded-2xl border p-4">
                <div><strong>{{ service.name }}</strong><p class="text-sm text-slate-500">{{ money(service.unit_price) }}</p></div>
                <button class="text-rose-600" @click="destroy('/servicos/' + service.id)">Excluir</button>
              </div>
            </div>
          </section>

          <section v-if="active === 'proposals'" class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
            <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveProposal">
              <div class="flex items-center justify-between"><h2 class="text-xl font-bold">Editor visual de proposta</h2><button type="button" class="text-sm text-blue-600" @click="resetProposal">Nova</button></div>
              <select v-model="proposalForm.customer_id" class="mt-4 w-full rounded-xl border p-3" required><option value="">Selecione um cliente</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select>
              <input v-model="proposalForm.title" class="mt-3 w-full rounded-xl border p-3" placeholder="Título" required>
              <textarea v-model="proposalForm.description" class="mt-3 w-full rounded-xl border p-3" placeholder="Descrição"></textarea>
              <div class="mt-3 grid gap-3 md:grid-cols-2"><input v-model="proposalForm.valid_until" type="date" class="rounded-xl border p-3"><input v-model.number="proposalForm.discount" type="number" step="0.01" class="rounded-xl border p-3" placeholder="Desconto"></div>
              <div class="mt-5 rounded-2xl border p-4">
                <div class="flex items-center justify-between"><h3 class="font-semibold">Itens</h3><button type="button" class="text-blue-600" @click="proposalForm.items.push({ description: '', quantity: 1, unit_price: 0 })">+ Item</button></div>
                <div v-for="(item, index) in proposalForm.items" :key="index" class="mt-3 grid gap-3 md:grid-cols-[1fr_100px_130px_70px]">
                  <input v-model="item.description" class="rounded-xl border p-3" placeholder="Descrição" required>
                  <input v-model.number="item.quantity" type="number" step="0.01" class="rounded-xl border p-3" placeholder="Qtd." required>
                  <input v-model.number="item.unit_price" type="number" step="0.01" class="rounded-xl border p-3" placeholder="Unitário" required>
                  <button type="button" class="text-rose-600" @click="proposalForm.items.splice(index, 1)">Remover</button>
                </div>
              </div>
              <textarea v-model="proposalForm.commercial_terms" class="mt-3 w-full rounded-xl border p-3" placeholder="Condições comerciais"></textarea>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ proposalForm.id ? 'Atualizar proposta' : 'Criar proposta' }}</button>
            </form>

            <div class="grid gap-6">
              <article class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Pré-visualização</h2>
                <p class="mt-4 text-sm text-slate-500">Cliente: {{ customers.find(c => c.id == proposalForm.customer_id)?.name ?? 'Não selecionado' }}</p>
                <h3 class="mt-2 text-2xl font-black">{{ proposalForm.title || 'Título da proposta' }}</h3>
                <p class="mt-2 text-slate-600">{{ proposalForm.description || 'Descrição da proposta aparecerá aqui.' }}</p>
                <div class="mt-5 overflow-hidden rounded-2xl border"><table class="w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Item</th><th>Qtd.</th><th>Total</th></tr></thead><tbody><tr v-for="item in proposalForm.items" class="border-t"><td class="p-3">{{ item.description || 'Item' }}</td><td>{{ item.quantity }}</td><td>{{ money(Number(item.quantity || 0) * Number(item.unit_price || 0)) }}</td></tr></tbody></table></div>
                <p class="mt-4 text-right text-sm">Subtotal: {{ money(proposalSubtotal) }}</p><p class="text-right text-sm">Desconto: {{ money(proposalForm.discount) }}</p><p class="text-right text-2xl font-black">Total: {{ money(proposalTotal) }}</p>
              </article>
              <article class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Propostas</h2>
                <div v-for="proposal in proposals" :key="proposal.id" class="mt-4 rounded-2xl border p-4">
                  <div class="flex justify-between gap-3"><div><strong>{{ proposal.title }}</strong><p class="text-sm text-slate-500">{{ proposal.customer?.name }} · {{ proposal.status }} · {{ money(proposal.total) }}</p></div><button class="text-blue-600" @click="editProposal(proposal)">Editar</button></div>
                  <div class="mt-3 flex flex-wrap gap-3 text-sm"><a v-if="proposal.public_token" class="text-blue-600" :href="'/p/' + proposal.public_token.token" target="_blank">Link público</a><button class="text-emerald-600" @click="sendProposal(proposal)">Enviar e-mail</button><button class="text-rose-600" @click="destroy('/propostas/' + proposal.id)">Excluir</button></div>
                </div>
              </article>
            </div>
          </section>

          <section v-if="active === 'admin' && isAdmin" class="grid gap-6">
            <div class="grid gap-4 md:grid-cols-4"><article v-for="(value, key) in stats" :key="key" class="rounded-3xl bg-slate-950 p-5 text-white"><p class="text-xs uppercase text-slate-400">{{ formatStatLabel(key) }}</p><strong class="mt-2 block break-words text-2xl">{{ formatStatValue(value, key) }}</strong></article></div>
            <div class="grid gap-6 xl:grid-cols-2">
              <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="savePlan"><h2 class="text-xl font-bold">Planos</h2><input v-model="planForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required><input v-model="planForm.slug" class="mt-3 w-full rounded-xl border p-3" placeholder="Slug" required><input v-model.number="planForm.monthly_price_cents" type="number" class="mt-3 w-full rounded-xl border p-3" placeholder="Preço em centavos" required><div class="mt-3 grid gap-3 md:grid-cols-2"><input v-model="planForm.monthly_proposal_limit" type="number" class="rounded-xl border p-3" placeholder="Limite propostas"><input v-model="planForm.customer_limit" type="number" class="rounded-xl border p-3" placeholder="Limite clientes"></div><label class="mt-3 flex gap-2"><input v-model="planForm.allows_pdf" type="checkbox"> PDF</label><label class="mt-2 flex gap-2"><input v-model="planForm.allows_custom_logo" type="checkbox"> Logo customizada</label><label class="mt-2 flex gap-2"><input v-model="planForm.is_active" type="checkbox"> Ativo</label><button class="mt-4 rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">Salvar plano</button><div v-for="plan in plans" class="mt-3 flex justify-between rounded-xl border p-3"><span>{{ plan.name }} · {{ money(plan.monthly_price_cents / 100) }}</span><button type="button" class="text-blue-600" @click="editPlan(plan)">Editar</button></div></form>
              <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveUser"><h2 class="text-xl font-bold">Usuários</h2><select v-model="userForm.id" class="mt-4 w-full rounded-xl border p-3" @change="Object.assign(userForm, users.find(u => u.id == userForm.id) ?? userForm)"><option value="">Selecione</option><option v-for="item in users" :value="item.id">{{ item.name }}</option></select><input v-model="userForm.name" class="mt-3 w-full rounded-xl border p-3" placeholder="Nome" required><input v-model="userForm.email" class="mt-3 w-full rounded-xl border p-3" placeholder="E-mail" required><select v-model="userForm.plan_id" class="mt-3 w-full rounded-xl border p-3"><option value="">Sem plano</option><option v-for="plan in plans" :value="plan.id">{{ plan.name }}</option></select><select v-model="userForm.role" class="mt-3 w-full rounded-xl border p-3"><option value="user">Usuário</option><option value="admin">Admin</option></select><label class="mt-3 flex gap-2"><input v-model="userForm.is_active" type="checkbox"> Ativo</label><button class="mt-4 rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white" :disabled="!userForm.id">Atualizar usuário</button></form>
            </div>
            <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveSettings">
              <h2 class="text-xl font-bold">Configurações</h2>
              <p class="mt-1 text-sm text-slate-500">Ajuste nome, domínio, contatos, SEO e cores globais usadas quando não há marca de usuário.</p>
              <div class="mt-4 grid gap-3 md:grid-cols-2">
                <label v-for="(_, key) in settings" :key="key" class="grid gap-1 text-sm">
                  <span>{{ settingLabel(key) }}</span>
                  <input v-model="settings[key]" :type="settingInputType(key)" class="rounded-xl border p-3">
                </label>
              </div>
              <button class="mt-4 rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">Salvar configurações</button>
            </form>
          </section>
        </section>
      </main>
    </div>
</template>
