<script lang="ts">
import { computed, defineComponent, onMounted, reactive, ref } from 'vue';
import axios, { type AxiosResponse } from 'axios';

type RecordData = Record<string, any>;

type PaginationMeta = {
  current_page: number;
  last_page: number;
  from: number | null;
  to: number | null;
  total: number;
};

const money = (value: number | string | null | undefined) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value ?? 0));

const field = (data: RecordData, name: string, fallback: any = '') => data[name] ?? fallback;

const statLabels: Record<string, string> = {
  created_in_period: 'Propostas no período',
  approved_in_period: 'Aprovadas no período',
  pending_in_period: 'Pendentes no período',
  approved_total_in_period: 'Total aprovado no período',
  plan_limit: 'Limite do plano',
  users_by_plan: 'Usuários por plano',
  proposals_created: 'Propostas criadas',
  proposals_approved: 'Propostas aprovadas',
  approved_revenue: 'Receita aprovada',
};

const formatStatLabel = (key: string | number) => statLabels[String(key)] ?? String(key).replace(/_/g, ' ').toUpperCase();

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

const formatDateTime = (value: string | null | undefined) => {
  if (! value) return '';

  return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
};

const settingLabel = (key: string | number) => String(key).replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const settingInputType = (key: string | number) => String(key).includes('color') ? 'color' : 'text';

const jsonHeaders = { Accept: 'application/json' };
const finalProposalStatuses = ['aprovada', 'recusada', 'expirada'];
const proposalStatuses = ['rascunho', 'enviada', 'visualizada', 'aprovada', 'recusada', 'expirada'];
const defaultPagination = (): PaginationMeta => ({ current_page: 1, last_page: 1, from: null, to: null, total: 0 });

const queryParams = (filters: RecordData, page = 1) => {
  const params: RecordData = { page };

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params[key] = value;
    }
  });

  return params;
};

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

const loadPage = async (url: string, filters: RecordData, page = 1) => {
  const response: AxiosResponse<RecordData> = await axios.get(url, {
    headers: jsonHeaders,
    params: queryParams(filters, page),
  });
  const payload = response.data;

  return {
    items: payload.data ?? [],
    pagination: {
      current_page: payload.current_page ?? 1,
      last_page: payload.last_page ?? 1,
      from: payload.from ?? null,
      to: payload.to ?? null,
      total: payload.total ?? 0,
    },
  };
};

const isFinalProposal = (proposal: RecordData) => finalProposalStatuses.includes(proposal.status);
const statusLabel = (status: string | null | undefined) => status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Sem status';
const proposalStatusClass = (status: string | null | undefined) => ({
  rascunho: 'border-slate-200 bg-slate-50 text-slate-700',
  enviada: 'border-blue-200 bg-blue-50 text-blue-700',
  visualizada: 'border-amber-200 bg-amber-50 text-amber-700',
  aprovada: 'border-emerald-200 bg-emerald-50 text-emerald-700',
  recusada: 'border-rose-200 bg-rose-50 text-rose-700',
  expirada: 'border-zinc-200 bg-zinc-100 text-zinc-600',
}[String(status)] ?? 'border-slate-200 bg-slate-50 text-slate-700');

const paginationSummary = (pagination: PaginationMeta) => {
  if (! pagination.total) return 'Nenhum registro encontrado';

  return `Mostrando ${pagination.from ?? 0}-${pagination.to ?? 0} de ${pagination.total}`;
};

export default defineComponent({
  setup() {
    const mount = document.getElementById('app');
    const page = mount?.dataset.page ?? 'app';
    const user = ref<RecordData>(JSON.parse(mount?.dataset.user ?? '{}'));
    const isAdmin = mount?.dataset.admin === 'true';
    const requestedProposalId = new URLSearchParams(window.location.search).get('proposal');
    let openedRequestedProposal = false;
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
    const logoPreviewUrl = ref<string | null>(null);

    const stats = ref<RecordData>({});
    const customers = ref<RecordData[]>([]);
    const customerOptions = ref<RecordData[]>([]);
    const services = ref<RecordData[]>([]);
    const proposals = ref<RecordData[]>([]);
    const plans = ref<RecordData[]>([]);
    const users = ref<RecordData[]>([]);
    const settings = ref<RecordData>({});
    const pagination = reactive<Record<string, PaginationMeta>>({
      customers: defaultPagination(),
      services: defaultPagination(),
      proposals: defaultPagination(),
    });
    const dashboardFilters = reactive<RecordData>({ from: '', to: '' });
    const customerFilters = reactive<RecordData>({ q: '', per_page: 10 });
    const serviceFilters = reactive<RecordData>({ q: '', active: '', per_page: 10 });
    const proposalFilters = reactive<RecordData>({ q: '', status: '', customer_id: '', per_page: 10 });
    const customerSelectQuery = ref('');

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
    const currentProposalCustomer = computed(() => customerOptions.value.find((customer) => customer.id == proposalForm.customer_id));
    const filteredCustomerOptions = computed(() => {
      const search = customerSelectQuery.value.trim().toLowerCase();

      if (! search) return customerOptions.value;

      return customerOptions.value.filter((customer) =>
        customer.id == proposalForm.customer_id ||
        [customer.name, customer.email, customer.phone, customer.document]
          .filter(Boolean)
          .some((value) => String(value).toLowerCase().includes(search)),
      );
    });
    const savedBrandName = computed(() => user.value.business_name || user.value.name || 'Sua marca');
    const profileBrandName = computed(() => profileForm.business_name || user.value.name || 'Sua marca');

    const setMessage = (text: string) => {
      message.value = text;
      error.value = '';
    };

    const setError = (exception: any) => {
      const errors = exception.response?.data?.errors;
      error.value = errors ? Object.values(errors).flat().join(' ') : (exception.response?.data?.message ?? 'Não foi possível concluir a ação.');
      message.value = '';
    };

    const loadDashboard = async () => {
      const response = await axios.get('/dashboard', { headers: jsonHeaders, params: dashboardFilters });
      stats.value = response.data;
    };

    const loadCustomersPage = async (page = 1) => {
      const response = await loadPage('/clientes', customerFilters, page);
      customers.value = response.items;
      Object.assign(pagination.customers, response.pagination);
    };

    const loadServicesPage = async (page = 1) => {
      const response = await loadPage('/servicos', serviceFilters, page);
      services.value = response.items;
      Object.assign(pagination.services, response.pagination);
    };

    const loadProposalsPage = async (page = 1) => {
      const response = await loadPage('/propostas', proposalFilters, page);
      proposals.value = response.items;
      Object.assign(pagination.proposals, response.pagination);
    };

    const goToPage = async (resource: 'customers' | 'services' | 'proposals', page: number) => {
      if (page < 1 || page > pagination[resource].last_page) return;

      loading.value = true;
      try {
        if (resource === 'customers') await loadCustomersPage(page);
        if (resource === 'services') await loadServicesPage(page);
        if (resource === 'proposals') await loadProposalsPage(page);
      } catch (exception) {
        setError(exception);
      } finally {
        loading.value = false;
      }
    };

    const load = async () => {
      loading.value = true;
      try {
        const [dashboardResponse, customersResponse, servicesResponse, proposalsResponse, allCustomerOptions] = await Promise.all([
          axios.get('/dashboard', { headers: jsonHeaders, params: dashboardFilters }),
          loadPage('/clientes', customerFilters, pagination.customers.current_page),
          loadPage('/servicos', serviceFilters, pagination.services.current_page),
          loadPage('/propostas', proposalFilters, pagination.proposals.current_page),
          loadPaginated('/clientes'),
        ]);
        stats.value = dashboardResponse.data;
        customers.value = customersResponse.items;
        services.value = servicesResponse.items;
        proposals.value = proposalsResponse.items;
        customerOptions.value = allCustomerOptions;
        Object.assign(pagination.customers, customersResponse.pagination);
        Object.assign(pagination.services, servicesResponse.pagination);
        Object.assign(pagination.proposals, proposalsResponse.pagination);

        if (requestedProposalId && ! openedRequestedProposal) {
          openedRequestedProposal = true;
          await editProposal({ id: requestedProposalId });
        }

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
        logoPreviewUrl.value = null;
        setMessage('Perfil atualizado com sucesso.');
      } catch (exception) { setError(exception); }
    };

    const selectLogo = (event: Event) => {
      const input = event.target as HTMLInputElement;
      profileForm.logo = input.files?.[0] ?? null;
      logoPreviewUrl.value = profileForm.logo instanceof File ? URL.createObjectURL(profileForm.logo) : null;
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
      active, currentProposalCustomer, customerFilters, customerForm, customerOptions, customerSelectQuery, customers, dashboardFilters, destroy, editCustomer, editPlan, editProposal, editService, error, field, filteredCustomerOptions, formatDate, formatDateTime, formatStatLabel, formatStatValue, goToPage, isAdmin, isFinalProposal, loadCustomersPage, loadDashboard, loadProposalsPage, loadServicesPage, loading, logoPreviewUrl, logout, message, money,
      pagination, paginationSummary, planForm, plans, proposalFilters, proposalForm, proposalStatusClass, proposalStatuses, proposalSubtotal, proposalTotal, proposals, resetProposal, saveCustomer,
      profileBrandName, savePlan, savedBrandName, saveProfile, saveProposal, saveService, saveSettings, saveUser, selectLogo, sendProposal, serviceForm, services,
      serviceFilters, settingInputType, settingLabel, settings, stats, statusLabel, user, userForm, users, profileForm, resetCustomer, resetService,
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

          <section v-if="active === 'dashboard'" class="grid gap-4">
            <form class="grid gap-3 rounded-3xl bg-white p-5 shadow-sm md:grid-cols-[1fr_1fr_auto]" @submit.prevent="loadDashboard">
              <label class="grid gap-1 text-sm">
                <span class="font-medium text-slate-700">De</span>
                <input v-model="dashboardFilters.from" type="date" class="rounded-xl border p-3">
              </label>
              <label class="grid gap-1 text-sm">
                <span class="font-medium text-slate-700">Até</span>
                <input v-model="dashboardFilters.to" type="date" class="rounded-xl border p-3">
              </label>
              <button class="self-end rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">Filtrar</button>
            </form>
            <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
              <article v-for="(value, key) in stats" :key="key" class="rounded-3xl bg-white p-6 shadow-sm">
                <p class="text-sm uppercase tracking-wide text-slate-500">{{ formatStatLabel(key) }}</p>
                <strong class="mt-3 block break-words text-2xl">{{ formatStatValue(value, key) }}</strong>
              </article>
            </div>
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
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold">Clientes cadastrados</h2>
                <form class="flex flex-wrap gap-2" @submit.prevent="loadCustomersPage(1)">
                  <input v-model="customerFilters.q" class="rounded-xl border p-3 text-sm" placeholder="Buscar cliente">
                  <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Filtrar</button>
                </form>
              </div>
              <p class="mt-3 text-sm text-slate-500">{{ paginationSummary(pagination.customers) }}</p>
              <p v-if="!customers.length" class="mt-4 rounded-2xl border border-dashed p-4 text-sm text-slate-500">Nenhum cliente encontrado.</p>
              <div v-for="customer in customers" :key="customer.id" class="mt-4 flex items-center justify-between gap-4 rounded-2xl border p-4">
                <div><strong>{{ customer.name }}</strong><p class="text-sm text-slate-500">{{ customer.email || customer.phone || 'Sem contato' }}</p></div>
                <div class="flex gap-3 text-sm"><button class="text-blue-600" @click="editCustomer(customer)">Editar</button><button class="text-rose-600" @click="destroy('/clientes/' + customer.id)">Excluir</button></div>
              </div>
              <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.customers.current_page <= 1" @click="goToPage('customers', pagination.customers.current_page - 1)">Anterior</button>
                <span>Página {{ pagination.customers.current_page }} de {{ pagination.customers.last_page }}</span>
                <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.customers.current_page >= pagination.customers.last_page" @click="goToPage('customers', pagination.customers.current_page + 1)">Próxima</button>
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
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold">Catálogo</h2>
                <form class="flex flex-wrap gap-2" @submit.prevent="loadServicesPage(1)">
                  <input v-model="serviceFilters.q" class="rounded-xl border p-3 text-sm" placeholder="Buscar serviço">
                  <select v-model="serviceFilters.active" class="rounded-xl border p-3 text-sm">
                    <option value="">Todos</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                  </select>
                  <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Filtrar</button>
                </form>
              </div>
              <p class="mt-3 text-sm text-slate-500">{{ paginationSummary(pagination.services) }}</p>
              <p v-if="!services.length" class="mt-4 rounded-2xl border border-dashed p-4 text-sm text-slate-500">Nenhum serviço encontrado.</p>
              <div v-for="service in services" :key="service.id" class="mt-4 flex items-center justify-between gap-4 rounded-2xl border p-4">
                <div><strong>{{ service.name }}</strong><p class="text-sm text-slate-500">{{ money(service.unit_price) }}</p></div>
                <div class="flex gap-3 text-sm"><button class="text-blue-600" @click="editService(service)">Editar</button><button class="text-rose-600" @click="destroy('/servicos/' + service.id)">Excluir</button></div>
              </div>
              <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.services.current_page <= 1" @click="goToPage('services', pagination.services.current_page - 1)">Anterior</button>
                <span>Página {{ pagination.services.current_page }} de {{ pagination.services.last_page }}</span>
                <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.services.current_page >= pagination.services.last_page" @click="goToPage('services', pagination.services.current_page + 1)">Próxima</button>
              </div>
            </div>
          </section>

          <section v-if="active === 'proposals'" class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
            <form class="rounded-3xl bg-white p-6 shadow-sm" @submit.prevent="saveProposal">
              <div class="flex items-center justify-between"><h2 class="text-xl font-bold">Editor visual de proposta</h2><button type="button" class="text-sm text-blue-600" @click="resetProposal">Nova</button></div>
              <input v-model="customerSelectQuery" class="mt-4 w-full rounded-xl border p-3" placeholder="Pesquisar cliente">
              <select v-model="proposalForm.customer_id" class="mt-3 w-full rounded-xl border p-3" required><option value="">Selecione um cliente</option><option v-for="customer in filteredCustomerOptions" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select>
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
              <textarea v-model="proposalForm.notes" class="mt-3 w-full rounded-xl border p-3" placeholder="Observações"></textarea>
              <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ proposalForm.id ? 'Atualizar proposta' : 'Criar proposta' }}</button>
            </form>

            <div class="grid gap-6">
              <article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="px-6 py-5 text-white" :style="{ backgroundColor: user.secondary_color || '#0f172a' }">
                  <p class="text-xs font-semibold uppercase tracking-wide text-white/70">Pré-visualização do link público</p>
                  <h2 class="mt-2 text-2xl font-black">{{ proposalForm.title || 'Título da proposta' }}</h2>
                  <p class="mt-2 text-sm text-white/80">{{ proposalForm.description || 'Descrição da proposta aparecerá aqui.' }}</p>
                  <div class="mt-4 rounded-2xl bg-white/10 p-4 text-sm ring-1 ring-white/15">
                    <p class="text-white/70">Cliente</p>
                    <strong class="mt-1 block">{{ currentProposalCustomer?.name ?? 'Não selecionado' }}</strong>
                    <p class="mt-3 text-white/70">Validade</p>
                    <strong class="mt-1 block">{{ formatDate(proposalForm.valid_until) }}</strong>
                  </div>
                </div>
                <div class="p-6">
                  <div class="overflow-x-auto rounded-2xl border border-slate-200"><table class="w-full min-w-[520px] text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Item</th><th>Qtd.</th><th>Unitário</th><th>Total</th></tr></thead><tbody><tr v-for="item in proposalForm.items" class="border-t"><td class="p-3 font-medium">{{ item.description || 'Item' }}</td><td>{{ item.quantity }}</td><td>{{ money(item.unit_price) }}</td><td class="font-semibold">{{ money(Number(item.quantity || 0) * Number(item.unit_price || 0)) }}</td></tr></tbody></table></div>
                  <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                    <p class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><strong>{{ money(proposalSubtotal) }}</strong></p>
                    <p class="mt-2 flex justify-between text-sm text-slate-600"><span>Desconto</span><strong>{{ money(proposalForm.discount) }}</strong></p>
                    <p class="mt-3 border-t border-slate-200 pt-3 text-sm text-slate-500">Total da proposta</p>
                    <strong class="mt-1 block text-2xl" :style="{ color: user.secondary_color || '#0f172a' }">{{ money(proposalTotal) }}</strong>
                  </div>
                  <div v-if="proposalForm.commercial_terms" class="mt-4 rounded-2xl bg-slate-50 p-4"><h3 class="font-semibold">Condições comerciais</h3><p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ proposalForm.commercial_terms }}</p></div>
                  <div v-if="proposalForm.notes" class="mt-4 rounded-2xl bg-slate-50 p-4"><h3 class="font-semibold">Observações</h3><p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ proposalForm.notes }}</p></div>
                  <div class="mt-4 rounded-2xl border border-slate-200 p-4"><p class="text-sm text-slate-500">Enviado por</p><strong class="mt-1 block" :style="{ color: user.secondary_color || '#0f172a' }">{{ savedBrandName }}</strong><p v-if="user.contact_details" class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ user.contact_details }}</p></div>
                  <button type="button" class="mt-4 w-full rounded-xl px-5 py-3 font-semibold text-white" :style="{ backgroundColor: user.primary_color || '#2563eb' }">Aprovar proposta</button>
                </div>
              </article>
              <article v-if="proposalForm.id && proposalForm.events?.length" class="rounded-3xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold">Hist&oacute;rico da proposta</h2>
                <div class="mt-4 grid gap-3">
                  <div v-for="event in proposalForm.events" :key="event.id" class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                      <strong class="text-slate-900">{{ event.message }}</strong>
                      <span class="text-xs text-slate-500">{{ formatDateTime(event.occurred_at) }}</span>
                    </div>
                    <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">{{ event.type }}</p>
                  </div>
                </div>
              </article>
              <article class="rounded-3xl bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <h2 class="text-xl font-bold">Propostas</h2>
                  <form class="flex flex-wrap gap-2" @submit.prevent="loadProposalsPage(1)">
                    <input v-model="proposalFilters.q" class="rounded-xl border p-3 text-sm" placeholder="Buscar proposta">
                    <select v-model="proposalFilters.status" class="rounded-xl border p-3 text-sm">
                      <option value="">Todos os status</option>
                      <option v-for="status in proposalStatuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                    </select>
                    <select v-model="proposalFilters.customer_id" class="rounded-xl border p-3 text-sm">
                      <option value="">Todos os clientes</option>
                      <option v-for="customer in customerOptions" :key="customer.id" :value="customer.id">{{ customer.name }}</option>
                    </select>
                    <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Filtrar</button>
                  </form>
                </div>
                <p class="mt-3 text-sm text-slate-500">{{ paginationSummary(pagination.proposals) }}</p>
                <p v-if="!proposals.length" class="mt-4 rounded-2xl border border-dashed p-4 text-sm text-slate-500">Nenhuma proposta encontrada.</p>
                <div v-for="proposal in proposals" :key="proposal.id" class="mt-4 rounded-2xl border border-slate-200 p-4">
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <strong class="break-words text-slate-950">{{ proposal.title }}</strong>
                        <span :class="['rounded-full border px-2.5 py-1 text-xs font-semibold', proposalStatusClass(proposal.status)]">{{ statusLabel(proposal.status) }}</span>
                      </div>
                      <p class="mt-2 text-sm text-slate-500">{{ proposal.customer?.name || 'Sem cliente' }} · {{ money(proposal.total) }} · {{ proposal.events_count ?? 0 }} evento(s)</p>
                    </div>
                    <a v-if="!isFinalProposal(proposal)" class="rounded-xl border px-3 py-2 text-sm font-semibold text-blue-600" :href="'/propostas/' + proposal.id" @click.prevent="editProposal(proposal)">Editar</a>
                    <span v-else class="rounded-xl bg-slate-100 px-3 py-2 text-sm text-slate-500">Bloqueada</span>
                  </div>
                  <div class="mt-3 flex flex-wrap gap-3 text-sm"><a v-if="proposal.public_token" class="text-blue-600" :href="'/p/' + proposal.public_token.token" target="_blank">Link público</a><a v-if="user.plan?.allows_pdf" class="text-slate-700" :href="'/propostas/' + proposal.id + '/pdf'" target="_blank">PDF</a><a v-if="!isFinalProposal(proposal)" class="text-emerald-600" :href="'/propostas/' + proposal.id + '/enviar'" @click.prevent="sendProposal(proposal)">Enviar e-mail</a><a v-if="!isFinalProposal(proposal)" class="text-rose-600" :href="'/propostas/' + proposal.id" @click.prevent="destroy('/propostas/' + proposal.id)">Excluir</a></div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                  <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.proposals.current_page <= 1" @click="goToPage('proposals', pagination.proposals.current_page - 1)">Anterior</button>
                  <span>Página {{ pagination.proposals.current_page }} de {{ pagination.proposals.last_page }}</span>
                  <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.proposals.current_page >= pagination.proposals.last_page" @click="goToPage('proposals', pagination.proposals.current_page + 1)">Próxima</button>
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
            <article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
              <div class="flex items-center justify-between gap-4 p-5">
                <div class="flex items-center gap-3">
                  <img v-if="logoPreviewUrl && user.plan?.allows_custom_logo" :src="logoPreviewUrl" alt="Logo" class="h-10 max-w-32 rounded object-contain">
                  <strong class="text-lg" :style="{ color: profileForm.primary_color }">{{ profileBrandName }}</strong>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sem menu no link público</span>
              </div>
              <div class="px-6 py-6 text-white" :style="{ backgroundColor: profileForm.secondary_color }">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/70">Prévia da proposta pública</p>
                <h2 class="mt-2 text-2xl font-black">Proposta comercial</h2>
                <p class="mt-2 text-sm text-white/80">As cores, nome da marca, contato e rodapé aparecem no link enviado ao cliente.</p>
                <div class="mt-4 rounded-2xl bg-white/10 p-4 text-sm ring-1 ring-white/15">
                  <p class="text-white/70">Cliente</p>
                  <strong class="mt-1 block">Cliente exemplo</strong>
                  <p class="mt-3 text-white/70">Validade</p>
                  <strong class="mt-1 block">Sem validade definida</strong>
                </div>
              </div>
              <div class="p-6">
                <div class="rounded-2xl border border-slate-200 p-4">
                  <p class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><strong>R$ 1.000,00</strong></p>
                  <p class="mt-2 flex justify-between text-sm text-slate-600"><span>Desconto</span><strong>R$ 0,00</strong></p>
                  <p class="mt-3 border-t border-slate-200 pt-3 text-sm text-slate-500">Total da proposta</p>
                  <strong class="mt-1 block text-2xl" :style="{ color: profileForm.secondary_color }">R$ 1.000,00</strong>
                </div>
                <button type="button" class="mt-4 w-full rounded-xl px-5 py-3 font-semibold text-white" :style="{ backgroundColor: profileForm.primary_color }">Aprovar proposta</button>
                <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                  <p class="text-sm text-slate-500">Enviado por</p>
                  <strong class="mt-1 block" :style="{ color: profileForm.secondary_color }">{{ profileBrandName }}</strong>
                  <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ profileForm.contact_details || 'Dados de contato aparecerão aqui.' }}</p>
                </div>
              </div>
              <p class="border-t border-slate-200 p-5 text-sm text-slate-500">{{ profileForm.default_footer_text || 'Rodapé padrão das propostas.' }}</p>
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
