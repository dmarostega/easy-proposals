<script lang="ts">
import { computed, defineComponent, onMounted, reactive, ref } from 'vue';
import axios, { type AxiosResponse } from 'axios';

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

const formatDate = (value: string | null | undefined) => {
  if (! value) return 'Sem validade definida';

  return new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(value));
};

const settingLabel = (key: string | number) => String(key).replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const settingInputType = (key: string | number) => String(key).includes('color') ? 'color' : 'text';

const jsonHeaders = { Accept: 'application/json' };

const itemsFromResponse = (payload: RecordData | RecordData[]) => Array.isArray(payload) ? payload : (payload.data ?? []);

const loadPaginated = async (url: string) => {
  const items: RecordData[] = [];
  let nextUrl: string | null = url;

  while (nextUrl) {
    const requestUrl = nextUrl;
    const response: AxiosResponse<RecordData | RecordData[]> = await axios.get(requestUrl, { headers: jsonHeaders });
    const payload: RecordData | RecordData[] = response.data;
    items.push(...itemsFromResponse(payload));
    nextUrl = Array.isArray(payload) ? null : (payload.next_page_url ?? null);
  }

  return items;
};

export default defineComponent({
  setup() {
    const mount = document.getElementById('app');
    const page = mount?.dataset.page ?? 'app';
    const user = ref<RecordData>(JSON.parse(mount?.dataset.user ?? '{}'));
    const isAdmin = mount?.dataset.admin === 'true';
    const initialSection = () => {
      if (page === 'admin' || window.location.pathname.startsWith('/admin')) return 'admin';
      if (window.location.pathname.startsWith('/clientes')) return 'customers';
      if (window.location.pathname.startsWith('/servicos')) return 'services';
      if (window.location.pathname.startsWith('/propostas')) return 'proposals';
      if (window.location.pathname.startsWith('/perfil')) return 'profile';

      return 'dashboard';
    };
    const active = ref(initialSection());
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

    const customerForm = reactive<RecordData>({ id: null, name: '', email: '', phone: '', document: '', address: '', notes: '' });
    const serviceForm = reactive<RecordData>({ id: null, name: '', description: '', unit_price: 0, is_active: true });
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
    const profileForm = reactive<RecordData>({
      business_name: user.value.business_name ?? '',
      contact_details: user.value.contact_details ?? '',
      default_footer_text: user.value.default_footer_text ?? '',
      primary_color: user.value.primary_color ?? '#2563eb',
      secondary_color: user.value.secondary_color ?? '#0f172a',
      logo: null,
    });

    const proposalSubtotal = computed(() => proposalForm.items.reduce((total: number, item: RecordData) => total + Number(item.quantity || 0) * Number(item.unit_price || 0), 0));
    const proposalTotal = computed(() => Math.max(0, proposalSubtotal.value - Number(proposalForm.discount || 0)));
    const currentProposalCustomer = computed(() => customers.value.find((customer) => customer.id == proposalForm.customer_id));

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
        const [dashboardResponse, allCustomers, allServices, allProposals] = await Promise.all([
          axios.get('/dashboard', { headers: jsonHeaders }),
          loadPaginated('/clientes'),
          loadPaginated('/servicos'),
          loadPaginated('/propostas'),
        ]);
        stats.value = dashboardResponse.data;
        customers.value = allCustomers;
        services.value = allServices;
        proposals.value = allProposals;

        if (isAdmin) {
          const [allPlans, allUsers, settingsResponse, reportsResponse] = await Promise.all([
            loadPaginated('/admin/planos'),
            loadPaginated('/admin/usuarios'),
            axios.get('/admin/configuracoes', { headers: jsonHeaders }),
            axios.get('/admin/relatorios', { headers: jsonHeaders }),
          ]);
          plans.value = allPlans;
          users.value = allUsers;
          settings.value = settingsResponse.data;
          stats.value = { ...stats.value, ...reportsResponse.data };
        }
      } catch (exception) {
        setError(exception);
      } finally {
        loading.value = false;
      }
    };

    const resetCustomer = () => Object.assign(customerForm, { id: null, name: '', email: '', phone: '', document: '', address: '', notes: '' });

    const resetService = () => Object.assign(serviceForm, { id: null, name: '', description: '', unit_price: 0, is_active: true });

    const editCustomer = (customer: RecordData) => {
      Object.assign(customerForm, customer);
      active.value = 'customers';
    };

    const editService = (service: RecordData) => {
      Object.assign(serviceForm, service);
      active.value = 'services';
    };

    const resetProposal = () => {
      Object.assign(proposalForm, { id: null, customer_id: '', title: '', description: '', valid_until: '', discount: 0, notes: '', commercial_terms: '', items: [{ description: '', quantity: 1, unit_price: 0 }] });
    };

    const editProposal = async (proposal: RecordData) => {
      const response = await axios.get(`/propostas/${proposal.id}`, { headers: jsonHeaders });
      Object.assign(proposalForm, response.data, {
        customer_id: response.data.customer_id,
        valid_until: response.data.valid_until?.slice(0, 10) ?? '',
        items: response.data.items.map((item: RecordData) => ({ description: item.description, quantity: item.quantity, unit_price: item.unit_price })),
      });
      active.value = 'proposals';
    };

    const saveCustomer = async () => {
      try {
        if (customerForm.id) {
          await axios.put(`/clientes/${customerForm.id}`, customerForm);
          setMessage('Cliente atualizado com sucesso.');
        } else {
          await axios.post('/clientes', customerForm);
          setMessage('Cliente criado com sucesso.');
        }
        resetCustomer();
        await load();
      } catch (exception) { setError(exception); }
    };

    const saveService = async () => {
      try {
        if (serviceForm.id) {
          await axios.put(`/servicos/${serviceForm.id}`, serviceForm);
          setMessage('Serviço atualizado com sucesso.');
        } else {
          await axios.post('/servicos', serviceForm);
          setMessage('Serviço criado com sucesso.');
        }
        resetService();
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

    const saveProfile = async () => {
      try {
        const payload = new FormData();
        payload.append('_method', 'PUT');
        ['business_name', 'contact_details', 'default_footer_text', 'primary_color', 'secondary_color'].forEach((key) => {
          payload.append(key, profileForm[key] ?? '');
        });
        if (profileForm.logo instanceof File) {
          payload.append('logo', profileForm.logo);
        }

        const response = await axios.post('/perfil', payload, { headers: { Accept: 'application/json', 'Content-Type': 'multipart/form-data' } });
        user.value = response.data;
        Object.assign(profileForm, {
          business_name: response.data.business_name ?? '',
          contact_details: response.data.contact_details ?? '',
          default_footer_text: response.data.default_footer_text ?? '',
          primary_color: response.data.primary_color ?? '#2563eb',
          secondary_color: response.data.secondary_color ?? '#0f172a',
          logo: null,
        });
        setMessage('Perfil atualizado com sucesso.');
      } catch (exception) { setError(exception); }
    };

    const selectLogo = (event: Event) => {
      const input = event.target as HTMLInputElement;
      profileForm.logo = input.files?.[0] ?? null;
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
      active, currentProposalCustomer, customerForm, customers, destroy, editCustomer, editPlan, editProposal, editService, error, field, formatDate, formatStatLabel, formatStatValue, isAdmin, loading, logout, message, money,
      planForm, plans, proposalForm, proposalSubtotal, proposalTotal, proposals, resetProposal, saveCustomer,
      savePlan, saveProfile, saveProposal, saveService, saveSettings, saveUser, selectLogo, sendProposal, serviceForm, services,
      settingInputType, settingLabel, settings, stats, user, userForm, users, profileForm, resetCustomer, resetService,
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
          <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" @click="active = 'profile'">Perfil da marca</button>
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
              <button class="rounded bg-slate-900 px-3 py-2 text-white" @click="active='profile'">Perfil</button>
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
              <div class="flex items-center justify-between"><h2 class="text-xl font-bold">{{ customerForm.id ? 'Editar cliente' : 'Novo cliente' }}</h2><button v-if="customerForm.id" type="button" class="text-sm text-blue-600" @click="resetCustomer">Novo</button></div>
              <input v-model="customerForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required>
              <input v-model="customerForm.email" class="mt-3 w-full rounded-xl border p-3" placeholder="E-mail">
              <input v-model="customerForm.phone" class="mt-3 w-full rounded-xl border p-3" placeholder="Telefone">
              <input v-model="customerForm.document" class="mt-3 w-full rounded-xl border p-3" placeholder="Documento">
              <textarea v-model="customerForm.address" class="mt-3 w-full rounded-xl border p-3" placeholder="Endereço"></textarea>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ customerForm.id ? 'Atualizar cliente' : 'Salvar cliente' }}</button>
            </form>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
              <h2 class="text-xl font-bold">Clientes cadastrados</h2>
              <div v-for="customer in customers" :key="customer.id" class="mt-4 flex items-center justify-between gap-4 rounded-2xl border p-4">
                <div><strong>{{ customer.name }}</strong><p class="text-sm text-slate-500">{{ customer.email || customer.phone || 'Sem contato' }}</p></div>
                <div class="flex gap-3 text-sm"><button class="text-blue-600" @click="editCustomer(customer)">Editar</button><button class="text-rose-600" @click="destroy('/clientes/' + customer.id)">Excluir</button></div>
              </div>
            </div>
          </section>

          <section v-if="active === 'services'" class="grid gap-6 lg:grid-cols-[1fr_2fr]">
            <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveService">
              <div class="flex items-center justify-between"><h2 class="text-xl font-bold">{{ serviceForm.id ? 'Editar serviço' : 'Novo serviço' }}</h2><button v-if="serviceForm.id" type="button" class="text-sm text-blue-600" @click="resetService">Novo</button></div>
              <input v-model="serviceForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required>
              <textarea v-model="serviceForm.description" class="mt-3 w-full rounded-xl border p-3" placeholder="Descrição"></textarea>
              <input v-model.number="serviceForm.unit_price" type="number" step="0.01" class="mt-3 w-full rounded-xl border p-3" placeholder="Preço" required>
              <label class="mt-3 flex gap-2"><input v-model="serviceForm.is_active" type="checkbox"> Ativo</label>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ serviceForm.id ? 'Atualizar serviço' : 'Salvar serviço' }}</button>
            </form>
            <div class="rounded-3xl bg-white p-6 shadow-sm">
              <h2 class="text-xl font-bold">Catálogo</h2>
              <div v-for="service in services" :key="service.id" class="mt-4 flex items-center justify-between gap-4 rounded-2xl border p-4">
                <div><strong>{{ service.name }}</strong><p class="text-sm text-slate-500">{{ money(service.unit_price) }}</p></div>
                <div class="flex gap-3 text-sm"><button class="text-blue-600" @click="editService(service)">Editar</button><button class="text-rose-600" @click="destroy('/servicos/' + service.id)">Excluir</button></div>
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
              <textarea v-model="proposalForm.notes" class="mt-3 w-full rounded-xl border p-3" placeholder="Observações internas ou detalhes adicionais"></textarea>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ proposalForm.id ? 'Atualizar proposta' : 'Criar proposta' }}</button>
            </form>

            <div class="grid gap-6">
              <article class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Pré-visualização</h2>
                <p class="mt-4 text-sm text-slate-500">Cliente: {{ currentProposalCustomer?.name ?? 'Não selecionado' }}</p>
                <p class="mt-1 text-sm text-slate-500">Validade: {{ formatDate(proposalForm.valid_until) }}</p>
                <h3 class="mt-2 text-2xl font-black">{{ proposalForm.title || 'Título da proposta' }}</h3>
                <p class="mt-2 text-slate-600">{{ proposalForm.description || 'Descrição da proposta aparecerá aqui.' }}</p>
                <div class="mt-5 overflow-x-auto rounded-2xl border"><table class="w-full min-w-[520px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Item</th><th>Qtd.</th><th>Unitário</th><th>Total</th></tr></thead><tbody><tr v-for="item in proposalForm.items" class="border-t"><td class="p-3">{{ item.description || 'Item' }}</td><td>{{ item.quantity }}</td><td>{{ money(item.unit_price) }}</td><td>{{ money(Number(item.quantity || 0) * Number(item.unit_price || 0)) }}</td></tr></tbody></table></div>
                <p class="mt-4 text-right text-sm">Subtotal: {{ money(proposalSubtotal) }}</p><p class="text-right text-sm">Desconto: {{ money(proposalForm.discount) }}</p><p class="text-right text-2xl font-black">Total: {{ money(proposalTotal) }}</p>
                <div v-if="proposalForm.commercial_terms" class="mt-5 rounded-2xl bg-slate-50 p-4"><h4 class="font-semibold">Condições comerciais</h4><p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ proposalForm.commercial_terms }}</p></div>
                <div v-if="proposalForm.notes" class="mt-3 rounded-2xl bg-slate-50 p-4"><h4 class="font-semibold">Observações</h4><p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ proposalForm.notes }}</p></div>
              </article>
              <article class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Propostas</h2>
                <div v-for="proposal in proposals" :key="proposal.id" class="mt-4 rounded-2xl border p-4">
                  <div class="flex justify-between gap-3"><div><strong>{{ proposal.title }}</strong><p class="text-sm text-slate-500">{{ proposal.customer?.name }} · {{ proposal.status }} · {{ money(proposal.total) }}</p></div><a class="text-blue-600" :href="'/propostas/' + proposal.id" @click.prevent="editProposal(proposal)">Editar</a></div>
                  <div class="mt-3 flex flex-wrap gap-3 text-sm"><a v-if="proposal.public_token" class="text-blue-600" :href="'/p/' + proposal.public_token.token" target="_blank">Link público</a><a class="text-emerald-600" :href="'/propostas/' + proposal.id + '/enviar'" @click.prevent="sendProposal(proposal)">Enviar e-mail</a><a class="text-rose-600" :href="'/propostas/' + proposal.id" @click.prevent="destroy('/propostas/' + proposal.id)">Excluir</a></div>
                </div>
              </article>
            </div>
          </section>


          <section v-if="active === 'profile'" class="grid gap-6 lg:grid-cols-[1fr_.9fr]">
            <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveProfile">
              <h2 class="text-xl font-bold">Perfil da marca</h2>
              <p class="mt-1 text-sm text-slate-500">Personalize os dados usados nas propostas e na comunicação com clientes.</p>
              <input v-model="profileForm.business_name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome da empresa">
              <textarea v-model="profileForm.contact_details" class="mt-3 w-full rounded-xl border p-3" placeholder="Dados de contato"></textarea>
              <textarea v-model="profileForm.default_footer_text" class="mt-3 w-full rounded-xl border p-3" placeholder="Texto padrão de rodapé"></textarea>
              <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="grid gap-1 text-sm"><span>Cor primária</span><input v-model="profileForm.primary_color" type="color" class="h-12 rounded-xl border p-1"></label>
                <label class="grid gap-1 text-sm"><span>Cor secundária</span><input v-model="profileForm.secondary_color" type="color" class="h-12 rounded-xl border p-1"></label>
              </div>
              <label class="mt-3 grid gap-1 text-sm"><span>Logo</span><input type="file" accept="image/*" class="rounded-xl border p-3" @change="selectLogo"></label>
              <p v-if="!user.plan?.allows_custom_logo" class="mt-2 text-sm text-amber-600">Seu plano atual não permite enviar logo customizada.</p>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">Salvar perfil</button>
            </form>
            <article class="rounded-3xl bg-white p-6 shadow-sm">
              <p class="text-sm uppercase tracking-wide text-slate-500">Prévia da identidade</p>
              <div class="mt-4 rounded-3xl p-6 text-white" :style="{ background: `linear-gradient(135deg, ${profileForm.primary_color}, ${profileForm.secondary_color})` }">
                <strong class="text-2xl">{{ profileForm.business_name || user.name }}</strong>
                <p class="mt-4 whitespace-pre-line text-sm text-white/90">{{ profileForm.contact_details || 'Dados de contato aparecerão aqui.' }}</p>
              </div>
              <p class="mt-4 whitespace-pre-line text-sm text-slate-500">{{ profileForm.default_footer_text || 'Rodapé padrão das propostas.' }}</p>
            </article>
          </section>

          <section v-if="active === 'admin' && isAdmin" class="grid gap-6">
            <div class="grid gap-4 md:grid-cols-4"><article v-for="(value, key) in stats" :key="key" class="rounded-3xl bg-slate-950 p-5 text-white"><p class="text-xs uppercase text-slate-400">{{ formatStatLabel(key) }}</p><strong class="mt-2 block break-words text-2xl">{{ formatStatValue(value, key) }}</strong></article></div>
            <div class="grid gap-6 xl:grid-cols-2">
              <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="savePlan"><h2 class="text-xl font-bold">Planos</h2><input v-model="planForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required><input v-model="planForm.slug" class="mt-3 w-full rounded-xl border p-3" placeholder="Slug" required><input v-model.number="planForm.monthly_price_cents" type="number" class="mt-3 w-full rounded-xl border p-3" placeholder="Preço em centavos" required><div class="mt-3 grid gap-3 md:grid-cols-2"><input v-model="planForm.monthly_proposal_limit" type="number" class="rounded-xl border p-3" placeholder="Limite propostas"><input v-model="planForm.customer_limit" type="number" class="rounded-xl border p-3" placeholder="Limite clientes"></div><label class="mt-3 flex gap-2"><input v-model="planForm.allows_pdf" type="checkbox"> PDF</label><label class="mt-2 flex gap-2"><input v-model="planForm.allows_custom_logo" type="checkbox"> Logo customizada</label><label class="mt-2 flex gap-2"><input v-model="planForm.is_active" type="checkbox"> Ativo</label><button class="mt-4 rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">{{ planForm.id ? 'Atualizar plano' : 'Salvar plano' }}</button><div v-for="plan in plans" :key="plan.id" class="mt-3 flex justify-between gap-3 rounded-xl border p-3"><span>{{ plan.name }} · {{ money(plan.monthly_price_cents / 100) }}</span><div class="flex gap-3 text-sm"><button type="button" class="text-blue-600" @click="editPlan(plan)">Editar</button><button type="button" class="text-rose-600" @click="destroy('/admin/planos/' + plan.id)">Excluir</button></div></div></form>
              <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveUser"><h2 class="text-xl font-bold">Usuários</h2><select v-model="userForm.id" class="mt-4 w-full rounded-xl border p-3" @change="Object.assign(userForm, users.find(u => u.id == userForm.id) ?? userForm)"><option value="">Selecione</option><option v-for="item in users" :value="item.id">{{ item.name }}</option></select><input v-model="userForm.name" class="mt-3 w-full rounded-xl border p-3" placeholder="Nome" required><input v-model="userForm.email" class="mt-3 w-full rounded-xl border p-3" placeholder="E-mail" required><select v-model="userForm.plan_id" class="mt-3 w-full rounded-xl border p-3"><option value="">Sem plano</option><option v-for="plan in plans" :value="plan.id">{{ plan.name }}</option></select><select v-model="userForm.role" class="mt-3 w-full rounded-xl border p-3"><option value="user">Usuário</option><option value="admin">Admin</option></select><label class="mt-3 flex gap-2"><input v-model="userForm.is_active" type="checkbox"> Ativo</label><div class="mt-4 flex flex-wrap gap-3"><button class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white" :disabled="!userForm.id">Atualizar usuário</button><button type="button" class="rounded-xl border border-rose-200 px-5 py-3 font-semibold text-rose-600" :disabled="!userForm.id" @click="destroy('/admin/usuarios/' + userForm.id, 'Confirma a desativação deste usuário?')">Desativar</button></div></form>
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
