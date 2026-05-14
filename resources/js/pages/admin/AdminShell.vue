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

const jsonHeaders = { Accept: 'application/json' };
const finalProposalStatuses = ['aprovada', 'recusada', 'expirada'];
const proposalStatuses = ['rascunho', 'enviada', 'visualizada', 'aprovada', 'recusada', 'expirada'];
const defaultPagination = (): PaginationMeta => ({ current_page: 1, last_page: 1, from: null, to: null, total: 0 });

const money = (value: number | string | null | undefined) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value ?? 0));

const formatDate = (value: string | null | undefined) => {
  if (! value) return 'Sem validade definida';

  return new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(value));
};

const formatDateTime = (value: string | null | undefined) => {
  if (! value) return '';

  return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
};

const statLabels: Record<string, string> = {
  users: 'Usuarios',
  active_users: 'Usuarios ativos',
  plans: 'Planos',
  users_by_plan: 'Usuarios por plano',
  proposals_created: 'Propostas criadas',
  proposals_approved: 'Propostas aprovadas',
  approved_revenue: 'Receita aprovada',
  created_in_period: 'Propostas no periodo',
  approved_in_period: 'Aprovadas no periodo',
  pending_in_period: 'Pendentes no periodo',
  approved_total_in_period: 'Total aprovado no periodo',
  customers: 'Clientes',
  services: 'Servicos',
  plan_limit: 'Limite do plano',
};

const formatStatLabel = (key: string | number) => statLabels[String(key)] ?? String(key).replace(/_/g, ' ').toUpperCase();

const formatStatValue = (value: any, key?: string | number) => {
  if (value === null || value === undefined || value === '') return 'Ilimitado';

  if (key === 'approved_revenue' || key === 'approved_total_in_period') {
    return money(value);
  }

  if (Array.isArray(value)) {
    if (key === 'users_by_plan') {
      return value.length
        ? value.map((plan: RecordData) => `${plan.name}: ${plan.users_count ?? 0}`).join(' · ')
        : 'Sem usuarios por plano';
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

const statusLabel = (status: string | null | undefined) => status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Sem status';
const isFinalProposal = (proposal: RecordData) => finalProposalStatuses.includes(proposal.status);
const paginationSummary = (pagination: PaginationMeta) => ! pagination.total
  ? 'Nenhum registro encontrado'
  : `Mostrando ${pagination.from ?? 0}-${pagination.to ?? 0} de ${pagination.total}`;

const settingLabel = (key: string | number) => String(key).replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const settingInputType = (key: string | number) => String(key).includes('color') ? 'color' : 'text';

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
    const response: AxiosResponse<RecordData | RecordData[]> = await axios.get(nextUrl, { headers: jsonHeaders });
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

export default defineComponent({
  setup() {
    const mount = document.getElementById('app');
    const adminUser = ref<RecordData>(JSON.parse(mount?.dataset.user ?? '{}'));

    const active = ref('overview');
    const message = ref('');
    const error = ref('');
    const loading = ref(false);
    const logoPreviewUrl = ref<string | null>(null);

    const platformStats = ref<RecordData>({});
    const accountStats = ref<RecordData>({});
    const users = ref<RecordData[]>([]);
    const plans = ref<RecordData[]>([]);
    const settings = ref<RecordData>({});
    const targetUser = ref<RecordData | null>(null);
    const auditLogs = ref<RecordData[]>([]);

    const customers = ref<RecordData[]>([]);
    const customerOptions = ref<RecordData[]>([]);
    const services = ref<RecordData[]>([]);
    const serviceOptions = ref<RecordData[]>([]);
    const proposals = ref<RecordData[]>([]);

    const pagination = reactive<Record<string, PaginationMeta>>({
      customers: defaultPagination(),
      services: defaultPagination(),
      proposals: defaultPagination(),
      audit: defaultPagination(),
    });

    const dashboardFilters = reactive<RecordData>({ from: '', to: '' });
    const customerFilters = reactive<RecordData>({ q: '', per_page: 10 });
    const serviceFilters = reactive<RecordData>({ q: '', active: '', per_page: 10 });
    const proposalFilters = reactive<RecordData>({ q: '', status: '', customer_id: '', per_page: 10 });
    const customerSelectQuery = ref('');

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
      items: [{ service_id: '', description: '', quantity: 1, unit_price: 0 }],
    });
    const profileForm = reactive<RecordData>({
      business_name: '',
      contact_details: '',
      default_footer_text: '',
      primary_color: '#2563eb',
      secondary_color: '#0f172a',
      logo: null,
    });

    const contextBase = computed(() => targetUser.value ? `/admin/usuarios/${targetUser.value.id}` : '');
    const targetLabel = computed(() => targetUser.value ? `${targetUser.value.name} · ${targetUser.value.email}` : 'Nenhuma conta selecionada');
    const proposalSubtotal = computed(() => proposalForm.items.reduce((total: number, item: RecordData) => total + Number(item.quantity || 0) * Number(item.unit_price || 0), 0));
    const proposalTotal = computed(() => Math.max(0, proposalSubtotal.value - Number(proposalForm.discount || 0)));
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

    const setMessage = (text: string) => {
      message.value = text;
      error.value = '';
    };

    const setError = (exception: any) => {
      const errors = exception.response?.data?.errors;
      error.value = errors ? Object.values(errors).flat().join(' ') : (exception.response?.data?.message ?? 'Nao foi possivel concluir a acao.');
      message.value = '';
    };

    const requireContext = () => {
      if (! targetUser.value) {
        throw new Error('Selecione uma conta antes de continuar.');
      }
    };

    const loadOverview = async () => {
      const [reportsResponse, allUsers, allPlans, settingsResponse] = await Promise.all([
        axios.get('/admin/relatorios', { headers: jsonHeaders }),
        loadPaginated('/admin/usuarios'),
        loadPaginated('/admin/planos'),
        axios.get('/admin/configuracoes', { headers: jsonHeaders }),
      ]);
      platformStats.value = reportsResponse.data;
      users.value = allUsers;
      plans.value = allPlans;
      settings.value = settingsResponse.data;
    };

    const loadAccountDashboard = async () => {
      requireContext();
      const response = await axios.get(`${contextBase.value}/dashboard`, { headers: jsonHeaders, params: dashboardFilters });
      const { target_user: updatedTargetUser, ...stats } = response.data;
      accountStats.value = stats;
      targetUser.value = updatedTargetUser;
    };

    const loadCustomersPage = async (page = 1) => {
      requireContext();
      const response = await loadPage(`${contextBase.value}/clientes`, customerFilters, page);
      customers.value = response.items;
      Object.assign(pagination.customers, response.pagination);
    };

    const loadServicesPage = async (page = 1) => {
      requireContext();
      const response = await loadPage(`${contextBase.value}/servicos`, serviceFilters, page);
      services.value = response.items;
      Object.assign(pagination.services, response.pagination);
    };

    const loadProposalsPage = async (page = 1) => {
      requireContext();
      const response = await loadPage(`${contextBase.value}/propostas`, proposalFilters, page);
      proposals.value = response.items;
      Object.assign(pagination.proposals, response.pagination);
    };

    const loadAuditPage = async (page = 1) => {
      requireContext();
      const response = await loadPage(`${contextBase.value}/auditoria`, {}, page);
      auditLogs.value = response.items;
      Object.assign(pagination.audit, response.pagination);
    };

    const loadContext = async () => {
      requireContext();
      const [profileResponse, allCustomerOptions, allServiceOptions] = await Promise.all([
        axios.get(`${contextBase.value}/perfil`, { headers: jsonHeaders }),
        loadPaginated(`${contextBase.value}/clientes?per_page=50`),
        loadPaginated(`${contextBase.value}/servicos?active=1&per_page=50`),
      ]);
      targetUser.value = profileResponse.data;
      customerOptions.value = allCustomerOptions;
      serviceOptions.value = allServiceOptions;
      Object.assign(profileForm, {
        business_name: targetUser.value?.business_name ?? '',
        contact_details: targetUser.value?.contact_details ?? '',
        default_footer_text: targetUser.value?.default_footer_text ?? '',
        primary_color: targetUser.value?.primary_color ?? '#2563eb',
        secondary_color: targetUser.value?.secondary_color ?? '#0f172a',
        logo: null,
      });
      logoPreviewUrl.value = targetUser.value?.logo_path ? `/storage/${targetUser.value.logo_path}` : null;

      await Promise.all([
        loadAccountDashboard(),
        loadCustomersPage(pagination.customers.current_page),
        loadServicesPage(pagination.services.current_page),
        loadProposalsPage(pagination.proposals.current_page),
        loadAuditPage(pagination.audit.current_page),
      ]);
    };

    const load = async () => {
      loading.value = true;
      try {
        await loadOverview();
        if (targetUser.value) {
          await loadContext();
        }
      } catch (exception) {
        setError(exception);
      } finally {
        loading.value = false;
      }
    };

    const selectUser = async (user: RecordData) => {
      targetUser.value = user;
      active.value = 'account';
      Object.assign(pagination.customers, defaultPagination());
      Object.assign(pagination.services, defaultPagination());
      Object.assign(pagination.proposals, defaultPagination());
      Object.assign(pagination.audit, defaultPagination());
      resetCustomer();
      resetService();
      resetProposal();
      await loadContext();
      setMessage(`Conta selecionada: ${user.name}.`);
    };

    const selectUserFromForm = () => {
      const selected = users.value.find((item) => item.id == userForm.id);
      if (selected) {
        Object.assign(userForm, selected, { plan_id: selected.plan_id ?? '' });
      }
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
        await loadOverview();
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
        setMessage('Usuario atualizado com sucesso.');
        await loadOverview();
        if (targetUser.value) {
          const updatedTarget = users.value.find((item) => item.id === targetUser.value?.id);
          if (updatedTarget) targetUser.value = updatedTarget;
        }
      } catch (exception) { setError(exception); }
    };

    const saveSettings = async () => {
      try {
        await axios.put('/admin/configuracoes', { settings: settings.value });
        setMessage('Configuracoes atualizadas com sucesso.');
        await loadOverview();
      } catch (exception) { setError(exception); }
    };

    const resetCustomer = () => Object.assign(customerForm, { id: null, name: '', email: '', phone: '', document: '', address: '', notes: '' });
    const resetService = () => Object.assign(serviceForm, { id: null, name: '', description: '', unit_price: 0, is_active: true });
    const resetProposal = () => Object.assign(proposalForm, { id: null, customer_id: '', title: '', description: '', valid_until: '', discount: 0, notes: '', commercial_terms: '', items: [{ service_id: '', description: '', quantity: 1, unit_price: 0 }] });

    const editCustomer = (customer: RecordData) => {
      Object.assign(customerForm, customer);
      active.value = 'customers';
    };

    const editService = (service: RecordData) => {
      Object.assign(serviceForm, service);
      active.value = 'services';
    };

    const editProposal = async (proposal: RecordData) => {
      try {
        requireContext();
        const response = await axios.get(`${contextBase.value}/propostas/${proposal.id}`, { headers: jsonHeaders });
        Object.assign(proposalForm, response.data, {
          customer_id: response.data.customer_id,
          valid_until: response.data.valid_until?.slice(0, 10) ?? '',
          items: response.data.items.map((item: RecordData) => ({ service_id: '', description: item.description, quantity: item.quantity, unit_price: item.unit_price })),
        });
        active.value = 'proposals';
      } catch (exception) { setError(exception); }
    };

    const applyServiceToItem = (item: RecordData) => {
      const service = serviceOptions.value.find((service) => service.id == item.service_id);
      if (! service) return;

      item.description = service.name;
      item.unit_price = Number(service.unit_price ?? 0);
      item.quantity = Number(item.quantity || 1);
    };

    const saveCustomer = async () => {
      try {
        requireContext();
        if (customerForm.id) {
          await axios.put(`${contextBase.value}/clientes/${customerForm.id}`, customerForm);
          setMessage('Cliente atualizado na conta selecionada.');
        } else {
          await axios.post(`${contextBase.value}/clientes`, customerForm);
          setMessage('Cliente criado na conta selecionada.');
        }
        resetCustomer();
        await loadContext();
      } catch (exception) { setError(exception); }
    };

    const saveService = async () => {
      try {
        requireContext();
        if (serviceForm.id) {
          await axios.put(`${contextBase.value}/servicos/${serviceForm.id}`, serviceForm);
          setMessage('Servico atualizado na conta selecionada.');
        } else {
          await axios.post(`${contextBase.value}/servicos`, serviceForm);
          setMessage('Servico criado na conta selecionada.');
        }
        resetService();
        await loadContext();
      } catch (exception) { setError(exception); }
    };

    const saveProposal = async () => {
      try {
        requireContext();
        const payload = { ...proposalForm, discount: Number(proposalForm.discount || 0) };
        if (proposalForm.id) {
          await axios.put(`${contextBase.value}/propostas/${proposalForm.id}`, payload);
          setMessage('Proposta atualizada na conta selecionada.');
        } else {
          await axios.post(`${contextBase.value}/propostas`, payload);
          setMessage('Proposta criada na conta selecionada.');
        }
        resetProposal();
        await loadContext();
      } catch (exception) { setError(exception); }
    };

    const sendProposal = async (proposal: RecordData) => {
      try {
        requireContext();
        await axios.post(`${contextBase.value}/propostas/${proposal.id}/enviar`);
        setMessage('Proposta enviada a partir do contexto administrativo.');
        await loadContext();
      } catch (exception) { setError(exception); }
    };

    const saveProfile = async () => {
      try {
        requireContext();
        const payload = new FormData();
        payload.append('_method', 'PUT');
        ['business_name', 'contact_details', 'default_footer_text', 'primary_color', 'secondary_color'].forEach((key) => {
          payload.append(key, profileForm[key] ?? '');
        });
        if (profileForm.logo instanceof File) {
          payload.append('logo', profileForm.logo);
        }

        const response = await axios.post(`${contextBase.value}/perfil`, payload, { headers: { Accept: 'application/json', 'Content-Type': 'multipart/form-data' } });
        targetUser.value = response.data;
        logoPreviewUrl.value = response.data.logo_path ? `/storage/${response.data.logo_path}` : null;
        setMessage('Perfil da marca atualizado para a conta selecionada.');
        await loadContext();
      } catch (exception) { setError(exception); }
    };

    const selectLogo = (event: Event) => {
      const input = event.target as HTMLInputElement;
      const file = input.files?.[0] ?? null;
      profileForm.logo = file;
      logoPreviewUrl.value = file ? URL.createObjectURL(file) : (targetUser.value?.logo_path ? `/storage/${targetUser.value.logo_path}` : null);
    };

    const destroy = async (url: string, confirmation = 'Confirma a exclusao?') => {
      if (! confirm(confirmation)) return;
      try {
        await axios.delete(url);
        setMessage('Registro removido com auditoria.');
        await load();
      } catch (exception) { setError(exception); }
    };

    const goToPage = async (resource: 'customers' | 'services' | 'proposals' | 'audit', page: number) => {
      if (page < 1 || page > pagination[resource].last_page) return;

      loading.value = true;
      try {
        if (resource === 'customers') await loadCustomersPage(page);
        if (resource === 'services') await loadServicesPage(page);
        if (resource === 'proposals') await loadProposalsPage(page);
        if (resource === 'audit') await loadAuditPage(page);
      } catch (exception) {
        setError(exception);
      } finally {
        loading.value = false;
      }
    };

    const logout = () => {
      const form = document.createElement('form');
      const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

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
      accountStats, active, adminUser, applyServiceToItem, auditLogs, contextBase, customerFilters, customerForm, customerOptions, customerSelectQuery, customers, dashboardFilters, destroy, editCustomer, editPlan, editProposal, editService, error, filteredCustomerOptions, formatDate, formatDateTime, formatStatLabel, formatStatValue, goToPage, isFinalProposal, loadAccountDashboard, loadAuditPage, loadCustomersPage, loadProposalsPage, loadServicesPage, loading, logoPreviewUrl, logout, message, money, pagination, paginationSummary, planForm, plans, platformStats, profileForm, proposalFilters, proposalForm, proposalStatuses, proposalSubtotal, proposalTotal, proposals, resetCustomer, resetProposal, resetService, saveCustomer, savePlan, saveProfile, saveProposal, saveService, saveSettings, saveUser, selectLogo, selectUser, selectUserFromForm, sendProposal, serviceFilters, serviceForm, serviceOptions, services, settingInputType, settingLabel, settings, statusLabel, targetLabel, targetUser, userForm, users,
    };
  },
});
</script>

<template>
  <div class="min-h-screen">
    <aside class="fixed inset-y-0 left-0 hidden w-80 flex-col bg-slate-950 p-6 text-white lg:flex">
      <a href="/admin" class="text-2xl font-black">Admin · Proposta Facil</a>
      <p class="mt-2 text-sm text-slate-300">{{ adminUser.name }}</p>
      <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-400">Conta em contexto</p>
        <strong class="mt-2 block break-words text-sm">{{ targetLabel }}</strong>
      </div>
      <nav class="mt-6 grid gap-2 text-sm">
        <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" @click="active = 'overview'">Plataforma</button>
        <button :disabled="!targetUser" class="rounded-xl px-4 py-3 text-left hover:bg-white/10 disabled:opacity-40" @click="active = 'account'">Dashboard da conta</button>
        <button :disabled="!targetUser" class="rounded-xl px-4 py-3 text-left hover:bg-white/10 disabled:opacity-40" @click="active = 'proposals'">Propostas da conta</button>
        <button :disabled="!targetUser" class="rounded-xl px-4 py-3 text-left hover:bg-white/10 disabled:opacity-40" @click="active = 'customers'">Clientes da conta</button>
        <button :disabled="!targetUser" class="rounded-xl px-4 py-3 text-left hover:bg-white/10 disabled:opacity-40" @click="active = 'services'">Servicos da conta</button>
        <button :disabled="!targetUser" class="rounded-xl px-4 py-3 text-left hover:bg-white/10 disabled:opacity-40" @click="active = 'profile'">Perfil da marca</button>
        <button :disabled="!targetUser" class="rounded-xl px-4 py-3 text-left hover:bg-white/10 disabled:opacity-40" @click="active = 'audit'">Auditoria</button>
        <a href="/dashboard" class="rounded-xl px-4 py-3 text-left hover:bg-white/10">Minha area</a>
        <button class="rounded-xl px-4 py-3 text-left hover:bg-white/10" type="button" @click="logout">Sair</button>
      </nav>
    </aside>

    <main class="lg:ml-80">
      <header class="sticky top-0 z-10 border-b bg-white/90 px-6 py-4 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
          <div>
            <p class="text-sm text-slate-500">Painel administrativo</p>
            <h1 class="text-2xl font-bold">{{ active === 'overview' ? 'Gestao da plataforma' : targetLabel }}</h1>
          </div>
          <div class="flex flex-wrap gap-2 text-sm lg:hidden">
            <button class="rounded bg-slate-900 px-3 py-2 text-white" @click="active='overview'">Admin</button>
            <button :disabled="!targetUser" class="rounded bg-slate-900 px-3 py-2 text-white disabled:opacity-40" @click="active='proposals'">Propostas</button>
            <button :disabled="!targetUser" class="rounded bg-slate-900 px-3 py-2 text-white disabled:opacity-40" @click="active='customers'">Clientes</button>
            <a href="/dashboard" class="rounded bg-slate-900 px-3 py-2 text-white">Minha area</a>
          </div>
        </div>
      </header>

      <section class="mx-auto grid max-w-7xl gap-6 p-6">
        <div v-if="message" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">{{ message }}</div>
        <div v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-700">{{ error }}</div>
        <div v-if="loading" class="rounded-2xl bg-white p-4 shadow-sm">Carregando dados...</div>

        <section v-if="active === 'overview'" class="grid gap-6">
          <div class="grid gap-4 md:grid-cols-4">
            <article v-for="(value, key) in platformStats" :key="key" class="rounded-2xl bg-slate-950 p-5 text-white">
              <p class="text-xs uppercase text-slate-400">{{ formatStatLabel(key) }}</p>
              <strong class="mt-2 block break-words text-xl">{{ formatStatValue(value, key) }}</strong>
            </article>
          </div>

          <div class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
            <article class="rounded-2xl bg-white p-6 shadow-sm">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold">Contas de usuarios</h2>
                <span class="text-sm text-slate-500">{{ users.length }} usuario(s)</span>
              </div>
              <div class="mt-4 grid gap-3">
                <div v-for="item in users" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4">
                  <div class="min-w-0">
                    <strong class="block break-words text-slate-950">{{ item.name }}</strong>
                    <p class="mt-1 break-words text-sm text-slate-500">{{ item.email }} · {{ item.role }} · {{ item.plan?.name ?? 'Sem plano' }}</p>
                  </div>
                  <div class="flex flex-wrap gap-2 text-sm">
                    <button type="button" class="rounded-xl border px-3 py-2 font-semibold text-blue-600" @click="selectUser(item)">Gerenciar conta</button>
                    <button type="button" class="rounded-xl border px-3 py-2 font-semibold text-slate-700" @click="Object.assign(userForm, item, { plan_id: item.plan_id ?? '' }); active = 'overview'">Editar usuario</button>
                  </div>
                </div>
              </div>
            </article>

            <div class="grid gap-6">
              <form class="rounded-2xl bg-white p-6 shadow-sm" @submit.prevent="saveUser">
                <h2 class="text-xl font-bold">Usuario</h2>
                <select v-model="userForm.id" class="mt-4 w-full rounded-xl border p-3" @change="selectUserFromForm">
                  <option value="">Selecione</option>
                  <option v-for="item in users" :key="item.id" :value="item.id">{{ item.name }}</option>
                </select>
                <input v-model="userForm.name" class="mt-3 w-full rounded-xl border p-3" placeholder="Nome" required>
                <input v-model="userForm.email" class="mt-3 w-full rounded-xl border p-3" placeholder="E-mail" required>
                <select v-model="userForm.plan_id" class="mt-3 w-full rounded-xl border p-3">
                  <option value="">Sem plano</option>
                  <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                </select>
                <select v-model="userForm.role" class="mt-3 w-full rounded-xl border p-3">
                  <option value="user">Usuario</option>
                  <option value="admin">Admin</option>
                </select>
                <label class="mt-3 flex gap-2"><input v-model="userForm.is_active" type="checkbox"> Ativo</label>
                <div class="mt-4 flex flex-wrap gap-3">
                  <button class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white" :disabled="!userForm.id">Atualizar usuario</button>
                  <button type="button" class="rounded-xl border border-rose-200 px-5 py-3 font-semibold text-rose-600" :disabled="!userForm.id" @click="destroy('/admin/usuarios/' + userForm.id, 'Confirma a desativacao deste usuario?')">Desativar</button>
                </div>
              </form>

              <form class="rounded-2xl bg-white p-6 shadow-sm" @submit.prevent="savePlan">
                <h2 class="text-xl font-bold">Planos</h2>
                <input v-model="planForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required>
                <input v-model="planForm.slug" class="mt-3 w-full rounded-xl border p-3" placeholder="Slug" required>
                <input v-model.number="planForm.monthly_price_cents" type="number" class="mt-3 w-full rounded-xl border p-3" placeholder="Preco em centavos" required>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <input v-model="planForm.monthly_proposal_limit" type="number" class="rounded-xl border p-3" placeholder="Limite propostas">
                  <input v-model="planForm.customer_limit" type="number" class="rounded-xl border p-3" placeholder="Limite clientes">
                </div>
                <label class="mt-3 flex gap-2"><input v-model="planForm.allows_pdf" type="checkbox"> PDF</label>
                <label class="mt-2 flex gap-2"><input v-model="planForm.allows_custom_logo" type="checkbox"> Logo customizada</label>
                <label class="mt-2 flex gap-2"><input v-model="planForm.is_active" type="checkbox"> Ativo</label>
                <button class="mt-4 rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">{{ planForm.id ? 'Atualizar plano' : 'Salvar plano' }}</button>
                <div v-for="plan in plans" :key="plan.id" class="mt-3 flex justify-between gap-3 rounded-xl border p-3">
                  <span>{{ plan.name }} · {{ money(plan.monthly_price_cents / 100) }}</span>
                  <div class="flex gap-3 text-sm">
                    <button type="button" class="text-blue-600" @click="editPlan(plan)">Editar</button>
                    <button type="button" class="text-rose-600" @click="destroy('/admin/planos/' + plan.id)">Excluir</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <form class="rounded-2xl bg-white p-6 shadow-sm" @submit.prevent="saveSettings">
            <h2 class="text-xl font-bold">Configuracoes globais</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <label v-for="(_, key) in settings" :key="key" class="grid gap-1 text-sm">
                <span>{{ settingLabel(key) }}</span>
                <input v-model="settings[key]" :type="settingInputType(key)" class="rounded-xl border p-3">
              </label>
            </div>
            <button class="mt-4 rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">Salvar configuracoes</button>
          </form>
        </section>

        <section v-if="active === 'account' && targetUser" class="grid gap-6">
          <div class="grid gap-4 md:grid-cols-4">
            <article v-for="(value, key) in accountStats" :key="key" class="rounded-2xl bg-white p-5 shadow-sm">
              <p class="text-xs uppercase text-slate-400">{{ formatStatLabel(key) }}</p>
              <strong class="mt-2 block break-words text-xl">{{ formatStatValue(value, key) }}</strong>
            </article>
          </div>
        </section>

        <section v-if="active === 'customers' && targetUser" class="grid gap-6 lg:grid-cols-[1fr_2fr]">
          <form class="rounded-2xl bg-white p-6 shadow-sm" @submit.prevent="saveCustomer">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold">{{ customerForm.id ? 'Editar cliente' : 'Novo cliente' }}</h2>
              <button v-if="customerForm.id" type="button" class="text-sm text-blue-600" @click="resetCustomer">Novo</button>
            </div>
            <input v-model="customerForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required>
            <input v-model="customerForm.email" type="email" class="mt-3 w-full rounded-xl border p-3" placeholder="E-mail">
            <input v-model="customerForm.phone" class="mt-3 w-full rounded-xl border p-3" placeholder="Telefone">
            <input v-model="customerForm.document" class="mt-3 w-full rounded-xl border p-3" placeholder="Documento">
            <textarea v-model="customerForm.address" class="mt-3 w-full rounded-xl border p-3" placeholder="Endereco"></textarea>
            <textarea v-model="customerForm.notes" class="mt-3 w-full rounded-xl border p-3" placeholder="Notas"></textarea>
            <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ customerForm.id ? 'Atualizar cliente' : 'Salvar cliente' }}</button>
          </form>
          <article class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-xl font-bold">Clientes da conta</h2>
              <form class="flex flex-wrap gap-2" @submit.prevent="loadCustomersPage(1)">
                <input v-model="customerFilters.q" class="rounded-xl border p-3 text-sm" placeholder="Buscar cliente">
                <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Filtrar</button>
              </form>
            </div>
            <p class="mt-3 text-sm text-slate-500">{{ paginationSummary(pagination.customers) }}</p>
            <div v-for="customer in customers" :key="customer.id" class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border p-4">
              <div class="min-w-0">
                <strong class="break-words">{{ customer.name }}</strong>
                <p class="break-words text-sm text-slate-500">{{ customer.email || 'Sem e-mail' }}</p>
              </div>
              <div class="flex gap-3 text-sm">
                <button class="text-blue-600" @click="editCustomer(customer)">Editar</button>
                <button class="text-rose-600" @click="destroy(contextBase + '/clientes/' + customer.id)">Excluir</button>
              </div>
            </div>
          </article>
        </section>

        <section v-if="active === 'services' && targetUser" class="grid gap-6 lg:grid-cols-[1fr_2fr]">
          <form class="rounded-2xl bg-white p-6 shadow-sm" @submit.prevent="saveService">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold">{{ serviceForm.id ? 'Editar servico' : 'Novo servico' }}</h2>
              <button v-if="serviceForm.id" type="button" class="text-sm text-blue-600" @click="resetService">Novo</button>
            </div>
            <input v-model="serviceForm.name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome" required>
            <textarea v-model="serviceForm.description" class="mt-3 w-full rounded-xl border p-3" placeholder="Descricao"></textarea>
            <input v-model.number="serviceForm.unit_price" type="number" step="0.01" class="mt-3 w-full rounded-xl border p-3" placeholder="Preco" required>
            <label class="mt-3 flex gap-2"><input v-model="serviceForm.is_active" type="checkbox"> Ativo</label>
            <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ serviceForm.id ? 'Atualizar servico' : 'Salvar servico' }}</button>
          </form>
          <article class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-xl font-bold">Catalogo da conta</h2>
              <form class="flex flex-wrap gap-2" @submit.prevent="loadServicesPage(1)">
                <input v-model="serviceFilters.q" class="rounded-xl border p-3 text-sm" placeholder="Buscar servico">
                <select v-model="serviceFilters.active" class="rounded-xl border p-3 text-sm">
                  <option value="">Todos</option>
                  <option value="1">Ativos</option>
                  <option value="0">Inativos</option>
                </select>
                <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Filtrar</button>
              </form>
            </div>
            <p class="mt-3 text-sm text-slate-500">{{ paginationSummary(pagination.services) }}</p>
            <div v-for="service in services" :key="service.id" class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border p-4">
              <div><strong>{{ service.name }}</strong><p class="text-sm text-slate-500">{{ money(service.unit_price) }}</p></div>
              <div class="flex gap-3 text-sm">
                <button class="text-blue-600" @click="editService(service)">Editar</button>
                <button class="text-rose-600" @click="destroy(contextBase + '/servicos/' + service.id)">Excluir</button>
              </div>
            </div>
          </article>
        </section>

        <section v-if="active === 'proposals' && targetUser" class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
          <form class="rounded-2xl bg-white p-6 shadow-sm" @submit.prevent="saveProposal">
            <div class="flex items-center justify-between"><h2 class="text-xl font-bold">Editor de proposta</h2><button type="button" class="text-sm text-blue-600" @click="resetProposal">Nova</button></div>
            <input v-model="customerSelectQuery" class="mt-4 w-full rounded-xl border p-3" placeholder="Pesquisar cliente">
            <select v-model="proposalForm.customer_id" class="mt-3 w-full rounded-xl border p-3" required>
              <option value="">Selecione um cliente</option>
              <option v-for="customer in filteredCustomerOptions" :key="customer.id" :value="customer.id">{{ customer.name }}</option>
            </select>
            <input v-model="proposalForm.title" class="mt-3 w-full rounded-xl border p-3" placeholder="Titulo" required>
            <textarea v-model="proposalForm.description" class="mt-3 w-full rounded-xl border p-3" placeholder="Descricao"></textarea>
            <div class="mt-3 grid gap-3 md:grid-cols-2"><input v-model="proposalForm.valid_until" type="date" class="rounded-xl border p-3"><input v-model.number="proposalForm.discount" type="number" step="0.01" class="rounded-xl border p-3" placeholder="Desconto"></div>
            <div class="mt-5 rounded-2xl border p-4">
              <div class="flex items-center justify-between"><h3 class="font-semibold">Itens</h3><button type="button" class="text-blue-600" @click="proposalForm.items.push({ service_id: '', description: '', quantity: 1, unit_price: 0 })">+ Item</button></div>
              <div v-for="(item, index) in proposalForm.items" :key="index" class="mt-3 grid gap-3 md:grid-cols-[150px_1fr_90px_120px_70px]">
                <select v-model="item.service_id" class="rounded-xl border p-3" @change="applyServiceToItem(item)">
                  <option value="">Servico</option>
                  <option v-for="service in serviceOptions" :key="service.id" :value="service.id">{{ service.name }}</option>
                </select>
                <input v-model="item.description" class="rounded-xl border p-3" placeholder="Descricao" required>
                <input v-model.number="item.quantity" type="number" step="0.01" class="rounded-xl border p-3" placeholder="Qtd." required>
                <input v-model.number="item.unit_price" type="number" step="0.01" class="rounded-xl border p-3" placeholder="Unitario" required>
                <button type="button" class="text-rose-600" @click="proposalForm.items.splice(index, 1)">Remover</button>
              </div>
            </div>
            <textarea v-model="proposalForm.commercial_terms" class="mt-3 w-full rounded-xl border p-3" placeholder="Condicoes comerciais"></textarea>
            <textarea v-model="proposalForm.notes" class="mt-3 w-full rounded-xl border p-3" placeholder="Observacoes"></textarea>
            <div class="mt-4 rounded-2xl border p-4">
              <p class="flex justify-between text-sm"><span>Subtotal</span><strong>{{ money(proposalSubtotal) }}</strong></p>
              <p class="mt-2 flex justify-between text-sm"><span>Total</span><strong>{{ money(proposalTotal) }}</strong></p>
            </div>
            <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">{{ proposalForm.id ? 'Atualizar proposta' : 'Criar proposta' }}</button>
          </form>

          <article class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-xl font-bold">Propostas da conta</h2>
              <form class="flex flex-wrap gap-2" @submit.prevent="loadProposalsPage(1)">
                <input v-model="proposalFilters.q" class="rounded-xl border p-3 text-sm" placeholder="Buscar proposta">
                <select v-model="proposalFilters.status" class="rounded-xl border p-3 text-sm">
                  <option value="">Todos os status</option>
                  <option v-for="status in proposalStatuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                </select>
                <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Filtrar</button>
              </form>
            </div>
            <p class="mt-3 text-sm text-slate-500">{{ paginationSummary(pagination.proposals) }}</p>
            <div v-for="proposal in proposals" :key="proposal.id" class="mt-4 rounded-2xl border border-slate-200 p-4">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                  <strong class="break-words text-slate-950">{{ proposal.title }}</strong>
                  <p class="mt-2 text-sm text-slate-500">{{ proposal.customer?.name || 'Sem cliente' }} · {{ money(proposal.total) }} · {{ statusLabel(proposal.status) }}</p>
                </div>
                <button class="rounded-xl border px-3 py-2 text-sm font-semibold text-blue-600" @click="editProposal(proposal)">Editar</button>
              </div>
              <div class="mt-3 flex flex-wrap gap-3 text-sm">
                <a v-if="proposal.public_token" class="text-blue-600" :href="'/p/' + proposal.public_token.token" target="_blank">Link publico</a>
                <a class="text-slate-700" :href="contextBase + '/propostas/' + proposal.id + '/pdf'" target="_blank">PDF</a>
                <button class="text-emerald-600" @click="sendProposal(proposal)">Enviar e-mail</button>
                <button class="text-rose-600" @click="destroy(contextBase + '/propostas/' + proposal.id)">Excluir</button>
                <span v-if="isFinalProposal(proposal)" class="text-amber-600">Finalizada; admin ainda pode editar.</span>
              </div>
            </div>
          </article>
        </section>

        <section v-if="active === 'profile' && targetUser" class="grid gap-6 lg:grid-cols-[1fr_.9fr]">
          <form class="rounded-2xl bg-white p-6 shadow-sm" @submit.prevent="saveProfile">
            <h2 class="text-xl font-bold">Perfil da marca da conta</h2>
            <input v-model="profileForm.business_name" class="mt-4 w-full rounded-xl border p-3" placeholder="Nome da empresa">
            <textarea v-model="profileForm.contact_details" class="mt-3 w-full rounded-xl border p-3" placeholder="Dados de contato"></textarea>
            <textarea v-model="profileForm.default_footer_text" class="mt-3 w-full rounded-xl border p-3" placeholder="Texto padrao de rodape"></textarea>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <label class="grid gap-1 text-sm"><span>Cor primaria</span><input v-model="profileForm.primary_color" type="color" class="h-12 rounded-xl border p-1"></label>
              <label class="grid gap-1 text-sm"><span>Cor secundaria</span><input v-model="profileForm.secondary_color" type="color" class="h-12 rounded-xl border p-1"></label>
            </div>
            <label class="mt-3 grid gap-1 text-sm"><span>Logo</span><input type="file" accept="image/*" class="rounded-xl border p-3" @change="selectLogo"></label>
            <button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">Salvar perfil</button>
          </form>
          <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center gap-3 p-5">
              <img v-if="logoPreviewUrl" :src="logoPreviewUrl" alt="Logo" class="h-10 max-w-32 rounded object-contain">
              <strong class="text-lg" :style="{ color: profileForm.primary_color }">{{ profileForm.business_name || targetUser.name }}</strong>
            </div>
            <div class="px-6 py-6 text-white" :style="{ backgroundColor: profileForm.secondary_color }">
              <p class="text-xs font-semibold uppercase tracking-wide text-white/70">Previa da proposta publica</p>
              <h2 class="mt-2 text-2xl font-black">Proposta comercial</h2>
              <p class="mt-2 text-sm text-white/80">As cores, nome da marca, contato e rodape aparecem no link enviado ao cliente.</p>
            </div>
            <p class="border-t border-slate-200 p-5 text-sm text-slate-500">{{ profileForm.default_footer_text || 'Rodape padrao das propostas.' }}</p>
          </article>
        </section>

        <section v-if="active === 'audit' && targetUser" class="rounded-2xl bg-white p-6 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold">Auditoria da conta</h2>
            <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white" @click="loadAuditPage(1)">Atualizar</button>
          </div>
          <p class="mt-3 text-sm text-slate-500">{{ paginationSummary(pagination.audit) }}</p>
          <div v-for="log in auditLogs" :key="log.id" class="mt-4 rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <strong class="text-slate-950">{{ log.action }}</strong>
                <p class="mt-1 text-sm text-slate-500">{{ log.admin?.name || 'Admin removido' }} · {{ log.resource_type || 'Conta' }} #{{ log.resource_id || '-' }}</p>
              </div>
              <span class="text-sm text-slate-500">{{ formatDateTime(log.created_at) }}</span>
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between gap-3 text-sm">
            <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.audit.current_page <= 1" @click="goToPage('audit', pagination.audit.current_page - 1)">Anterior</button>
            <span>Pagina {{ pagination.audit.current_page }} de {{ pagination.audit.last_page }}</span>
            <button class="rounded-xl border px-4 py-2 disabled:opacity-40" :disabled="pagination.audit.current_page >= pagination.audit.last_page" @click="goToPage('audit', pagination.audit.current_page + 1)">Proxima</button>
          </div>
        </section>
      </section>
    </main>
  </div>
</template>
