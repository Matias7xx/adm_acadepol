<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, useForm, Head, usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

const props = defineProps({
  tiposOrgao: { type: Object, default: () => ({}) },
  condicoes: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
});

const page = usePage();
const { toast } = useToast();

const loading = ref(false);
const buscandoCpf = ref(false);
const cpfBusca = ref('');
const mensagemBusca = ref('');
const dadosEncontrados = ref(false);
const termoVisivel = ref(false);
const isSubmitting = ref(false);

const estadosBrasileiros = [
  'AC',
  'AL',
  'AP',
  'AM',
  'BA',
  'CE',
  'DF',
  'ES',
  'GO',
  'MA',
  'MT',
  'MS',
  'MG',
  'PA',
  'PB',
  'PR',
  'PE',
  'PI',
  'RJ',
  'RN',
  'RS',
  'RO',
  'RR',
  'SC',
  'SP',
  'SE',
  'TO',
];

const form = useForm({
  nome: '',
  cpf: '',
  rg: '',
  orgao_expedidor_rg: '',
  data_nascimento: '',
  sexo: '',
  telefone: '',
  email: '',
  endereco: { rua: '', numero: '', bairro: '', cidade: '', uf: '', cep: '' },
  orgao_trabalho: '',
  cargo: '',
  matricula_funcional: '',
  tipo_orgao: '',
  data_inicial: '',
  data_final: '',
  motivo: '',
  condicao: '',
  aceita_termos: false,
  documento_identidade: null,
  documento_funcional: null,
  documento_comprobatorio: null,
});

const hoje = computed(() => new Date().toISOString().split('T')[0]);
const dataMinimaSaida = computed(() => form.data_inicial || hoje.value);

const formatarCpf = v =>
  v
    .replace(/\D/g, '')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})/, '$1-$2')
    .replace(/(-\d{2})\d+?$/, '$1');
const formatarTelefone = v =>
  v
    .replace(/\D/g, '')
    .replace(/(\d{2})(\d)/, '($1) $2')
    .replace(/(\d{4})(\d)/, '$1-$2')
    .replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3')
    .replace(/(-\d{4})\d+?$/, '$1');
const formatarCep = v =>
  v
    .replace(/\D/g, '')
    .replace(/(\d{5})(\d)/, '$1-$2')
    .replace(/(-\d{3})\d+?$/, '$1');

const handleCpfInput = e => {
  form.cpf = formatarCpf(e.target.value);
};
const handleTelefoneInput = e => {
  form.telefone = formatarTelefone(e.target.value);
};
const handleCepInput = e => {
  form.endereco.cep = formatarCep(e.target.value);
};

const handleFileChange = (field, event) => {
  const file = event.target.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    toast.error('Arquivo muito grande. O tamanho máximo é 5MB.');
    event.target.value = '';
    return;
  }
  if (file.type !== 'application/pdf') {
    toast.error('Tipo de arquivo não permitido. Use PDF.');
    event.target.value = '';
    return;
  }
  form[field] = file;
};

const buscarPorCpf = async () => {
  if (!cpfBusca.value) return;
  buscandoCpf.value = true;
  mensagemBusca.value = '';
  dadosEncontrados.value = false;
  try {
    const response = await fetch('/api/visitante/buscar-cpf', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document
          .querySelector('meta[name="csrf-token"]')
          .getAttribute('content'),
      },
      body: JSON.stringify({ cpf: cpfBusca.value }),
    });
    const data = await response.json();
    if (data.success) {
      const v = data.visitante;
      Object.assign(form, {
        nome: v.nome || '',
        cpf: cpfBusca.value,
        rg: v.rg || '',
        orgao_expedidor_rg: v.orgao_expedidor_rg || '',
        data_nascimento: v.data_nascimento || '',
        sexo: v.sexo || '',
        telefone: v.telefone || '',
        email: v.email || '',
        orgao_trabalho: v.orgao_trabalho || '',
        cargo: v.cargo || '',
        matricula_funcional: v.matricula_funcional || '',
        tipo_orgao: v.tipo_orgao || '',
      });
      if (v.endereco)
        Object.assign(form.endereco, {
          rua: v.endereco.rua || '',
          numero: v.endereco.numero || '',
          bairro: v.endereco.bairro || '',
          cidade: v.endereco.cidade || '',
          uf: v.endereco.uf || '',
          cep: v.endereco.cep || '',
        });
      mensagemBusca.value = 'Dados encontrados e preenchidos automaticamente!';
      dadosEncontrados.value = true;
      toast.success('Dados encontrados e preenchidos automaticamente!');
    } else {
      mensagemBusca.value =
        'CPF não encontrado. Preencha o formulário manualmente.';
      form.cpf = cpfBusca.value;
      toast.info('CPF não encontrado. Preencha o formulário manualmente.');
    }
  } catch (error) {
    mensagemBusca.value =
      'Erro ao buscar dados. Preencha o formulário manualmente.';
    form.cpf = cpfBusca.value;
    toast.error('Erro ao buscar dados. Preencha o formulário manualmente.');
  } finally {
    buscandoCpf.value = false;
  }
};

const validarDatas = () => {
  if (!form.data_inicial || !form.data_final) return false;
  const ini = new Date(form.data_inicial),
    fim = new Date(form.data_final),
    hj = new Date();
  hj.setHours(0, 0, 0, 0);
  if (ini < hj) {
    toast.error('A data inicial não pode ser anterior à data atual');
    return false;
  }
  if (fim < ini) {
    toast.error('A data final deve ser igual ou posterior à data inicial');
    return false;
  }
  return true;
};

const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value;
};

const submeterFormulario = () => {
  if (!form.aceita_termos) {
    toast.error('Você precisa aceitar os termos para continuar');
    return;
  }
  if (!validarDatas()) return;
  const campos = [
    'nome',
    'cpf',
    'rg',
    'orgao_expedidor_rg',
    'data_nascimento',
    'sexo',
    'telefone',
    'email',
    'orgao_trabalho',
    'cargo',
    'tipo_orgao',
    'data_inicial',
    'data_final',
    'motivo',
    'condicao',
  ];
  for (const campo of campos) {
    if (!form[campo]) {
      toast.error(`Por favor, preencha o campo ${campo.replace(/_/g, ' ')}`);
      return;
    }
  }
  if (!form.documento_identidade) {
    toast.error('Por favor, anexe o documento de identidade');
    return;
  }
  isSubmitting.value = true;
  form.post(route('visitante.store'), {
    preserveScroll: false,
    forceFormData: true,
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: errors => {
      isSubmitting.value = false;
      if (errors.message) {
        toast.error(errors.message);
        return;
      }
      if (errors.reserva_pendente) {
        toast.error(errors.reserva_pendente);
        return;
      }
      if (errors.cpf) {
        toast.error(Array.isArray(errors.cpf) ? errors.cpf[0] : errors.cpf);
        return;
      }
      const primeiro = Object.values(errors)[0];
      toast.error(
        Array.isArray(primeiro)
          ? primeiro[0]
          : typeof primeiro === 'string'
            ? primeiro
            : 'Ocorreu um erro ao enviar a solicitação. Por favor, tente novamente.'
      );
    },
  });
};

onMounted(() => {
  document.getElementById('cpf_busca')?.focus();
  if (page.props.flash) {
    if (page.props.flash.error) toast.error(page.props.flash.error);
    if (page.props.flash.message) toast.success(page.props.flash.message);
    if (page.props.flash.warning) toast.warning(page.props.flash.warning);
  }
});
</script>

<template>
  <Head title="Reserva de Alojamento - Visitantes" />
  <div class="min-h-screen bg-gray-50">
    <Header />
    <SiteNavbar />

    <!-- Header da página -->
    <div class="page-header">
      <div
        class="container mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3">
          <span class="page-header-accent"></span>
          <h1 class="page-header-title">
            Pré-Reserva de Alojamento — Visitantes
          </h1>
        </div>
        <Link href="/alojamento/tipo-usuario" class="back-link">
          <svg
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Voltar
        </Link>
      </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-header-left">
            <span class="form-card-accent"></span>
            <h2 class="form-card-title">Formulário de Pré-Reserva</h2>
          </div>
          <span class="form-required-note">* Campos obrigatórios</span>
        </div>

        <!-- Aviso -->
        <div class="warning-box mx-6 mt-5">
          <svg
            class="warning-icon h-5 w-5 flex-shrink-0 mt-0.5"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <p class="warning-title">Informações Importantes</p>
            <p class="warning-text">
              A ACADEPOL <strong>NÃO FORNECE</strong> materiais de higiene
              pessoal, lençóis e toalhas. Os quartos são compartilhados
              (beliches) e separados por sexo.
            </p>
          </div>
        </div>

        <form @submit.prevent="submeterFormulario" class="form-body">
          <!-- Busca por CPF -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">
                Verificação de Dados Existentes
              </h3>
            </div>
            <p class="text-sm text-gray-500 mb-4 -mt-1">
              Se você já fez uma reserva anteriormente, insira seu CPF para
              pré-preencher os dados.
            </p>
            <div class="flex gap-3">
              <div class="form-field flex-1">
                <label for="cpf_busca" class="form-label">CPF para busca</label>
                <input
                  id="cpf_busca"
                  v-model="cpfBusca"
                  type="text"
                  class="form-input"
                  placeholder="000.000.000-00"
                  maxlength="14"
                  @input="cpfBusca = formatarCpf($event.target.value)"
                />
              </div>
              <div class="flex items-end pb-0.5">
                <button
                  type="button"
                  @click="buscarPorCpf"
                  :disabled="buscandoCpf || !cpfBusca"
                  class="search-btn"
                >
                  <svg
                    v-if="buscandoCpf"
                    class="animate-spin h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                  </svg>
                  <svg
                    v-else
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                  </svg>
                  {{ buscandoCpf ? 'Buscando...' : 'Buscar' }}
                </button>
              </div>
            </div>
            <div
              v-if="mensagemBusca"
              class="mt-3 px-3 py-2.5 rounded-md text-sm"
              :class="
                dadosEncontrados
                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                  : 'bg-amber-50 text-amber-700 border border-amber-100'
              "
            >
              {{ mensagemBusca }}
            </div>
          </div>

          <!-- Dados Pessoais -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Dados Pessoais</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field md:col-span-2">
                <label for="nome" class="form-label">Nome Completo *</label>
                <input
                  id="nome"
                  v-model="form.nome"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.nome }"
                />
                <p v-if="form.errors?.nome" class="form-error">
                  {{ form.errors.nome }}
                </p>
              </div>
              <div class="form-field">
                <label for="cpf" class="form-label">CPF *</label>
                <input
                  id="cpf"
                  v-model="form.cpf"
                  type="text"
                  required
                  maxlength="14"
                  class="form-input"
                  :class="{ 'field-error': form.errors?.cpf }"
                  @input="handleCpfInput"
                />
                <p v-if="form.errors?.cpf" class="form-error">
                  {{ form.errors.cpf }}
                </p>
              </div>
              <div class="form-field">
                <label for="rg" class="form-label">RG/CNH *</label>
                <input
                  id="rg"
                  v-model="form.rg"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.rg }"
                />
                <p v-if="form.errors?.rg" class="form-error">
                  {{ form.errors.rg }}
                </p>
              </div>
              <div class="form-field">
                <label for="orgao_expedidor_rg" class="form-label"
                  >Órgão Expedidor *</label
                >
                <input
                  id="orgao_expedidor_rg"
                  v-model="form.orgao_expedidor_rg"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.orgao_expedidor_rg }"
                  placeholder="Ex: SSP-PB"
                />
                <p v-if="form.errors?.orgao_expedidor_rg" class="form-error">
                  {{ form.errors.orgao_expedidor_rg }}
                </p>
              </div>
              <div class="form-field">
                <label for="data_nascimento" class="form-label"
                  >Data de Nascimento *</label
                >
                <input
                  id="data_nascimento"
                  v-model="form.data_nascimento"
                  type="date"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.data_nascimento }"
                />
                <p v-if="form.errors?.data_nascimento" class="form-error">
                  {{ form.errors.data_nascimento }}
                </p>
              </div>
              <div class="form-field">
                <label for="sexo" class="form-label">Sexo *</label>
                <select
                  id="sexo"
                  v-model="form.sexo"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.sexo }"
                >
                  <option value="">Selecione...</option>
                  <option value="masculino">Masculino</option>
                  <option value="feminino">Feminino</option>
                </select>
                <p v-if="form.errors?.sexo" class="form-error">
                  {{ form.errors.sexo }}
                </p>
              </div>
              <div class="form-field">
                <label for="telefone" class="form-label">Telefone *</label>
                <input
                  id="telefone"
                  v-model="form.telefone"
                  type="text"
                  required
                  maxlength="15"
                  class="form-input"
                  :class="{ 'field-error': form.errors?.telefone }"
                  placeholder="(83) 99999-9999"
                  @input="handleTelefoneInput"
                />
                <p v-if="form.errors?.telefone" class="form-error">
                  {{ form.errors.telefone }}
                </p>
              </div>
              <div class="form-field">
                <label for="email" class="form-label">E-mail *</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.email }"
                />
                <p v-if="form.errors?.email" class="form-error">
                  {{ form.errors.email }}
                </p>
              </div>
            </div>
          </div>

          <!-- Endereço -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Endereço</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field md:col-span-2">
                <label for="rua" class="form-label">Rua/Avenida *</label>
                <input
                  id="rua"
                  v-model="form.endereco.rua"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.['endereco.rua'] }"
                />
                <p v-if="form.errors?.['endereco.rua']" class="form-error">
                  {{ form.errors['endereco.rua'] }}
                </p>
              </div>
              <div class="form-field">
                <label for="numero" class="form-label">Número</label>
                <input
                  id="numero"
                  v-model="form.endereco.numero"
                  type="text"
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="bairro" class="form-label">Bairro *</label>
                <input
                  id="bairro"
                  v-model="form.endereco.bairro"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.['endereco.bairro'] }"
                />
                <p v-if="form.errors?.['endereco.bairro']" class="form-error">
                  {{ form.errors['endereco.bairro'] }}
                </p>
              </div>
              <div class="form-field">
                <label for="cidade" class="form-label">Cidade *</label>
                <input
                  id="cidade"
                  v-model="form.endereco.cidade"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.['endereco.cidade'] }"
                />
                <p v-if="form.errors?.['endereco.cidade']" class="form-error">
                  {{ form.errors['endereco.cidade'] }}
                </p>
              </div>
              <div class="form-field">
                <label for="uf" class="form-label">UF *</label>
                <select
                  id="uf"
                  v-model="form.endereco.uf"
                  required
                  class="form-input"
                >
                  <option value="">Selecione...</option>
                  <option
                    v-for="uf in estadosBrasileiros"
                    :key="uf"
                    :value="uf"
                  >
                    {{ uf }}
                  </option>
                </select>
              </div>
              <div class="form-field">
                <label for="cep" class="form-label">CEP</label>
                <input
                  id="cep"
                  v-model="form.endereco.cep"
                  type="text"
                  maxlength="9"
                  class="form-input"
                  placeholder="00000-000"
                  @input="handleCepInput"
                />
              </div>
            </div>
          </div>

          <!-- Dados Profissionais -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Dados Profissionais</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field md:col-span-2">
                <label for="orgao_trabalho" class="form-label"
                  >Órgão/Local de Trabalho *</label
                >
                <input
                  id="orgao_trabalho"
                  v-model="form.orgao_trabalho"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.orgao_trabalho }"
                />
                <p v-if="form.errors?.orgao_trabalho" class="form-error">
                  {{ form.errors.orgao_trabalho }}
                </p>
              </div>
              <div class="form-field">
                <label for="cargo" class="form-label">Cargo/Função *</label>
                <input
                  id="cargo"
                  v-model="form.cargo"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.cargo }"
                />
                <p v-if="form.errors?.cargo" class="form-error">
                  {{ form.errors.cargo }}
                </p>
              </div>
              <div class="form-field">
                <label for="matricula_funcional" class="form-label"
                  >Matrícula Funcional</label
                >
                <input
                  id="matricula_funcional"
                  v-model="form.matricula_funcional"
                  type="text"
                  class="form-input"
                />
              </div>
              <div class="form-field md:col-span-2">
                <label for="tipo_orgao" class="form-label"
                  >Tipo de Órgão *</label
                >
                <select
                  id="tipo_orgao"
                  v-model="form.tipo_orgao"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.tipo_orgao }"
                >
                  <option value="">Selecione o tipo de órgão</option>
                  <option
                    v-for="(label, value) in props.tiposOrgao"
                    :key="value"
                    :value="value"
                  >
                    {{ label }}
                  </option>
                </select>
                <p v-if="form.errors?.tipo_orgao" class="form-error">
                  {{ form.errors.tipo_orgao }}
                </p>
              </div>
            </div>
          </div>

          <!-- Dados da Reserva -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Dados da Reserva</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field">
                <label for="data_inicial" class="form-label"
                  >Data de Entrada *</label
                >
                <input
                  id="data_inicial"
                  v-model="form.data_inicial"
                  type="date"
                  required
                  :min="hoje"
                  class="form-input"
                  :class="{ 'field-error': form.errors?.data_inicial }"
                />
                <p v-if="form.errors?.data_inicial" class="form-error">
                  {{ form.errors.data_inicial }}
                </p>
              </div>
              <div class="form-field">
                <label for="data_final" class="form-label"
                  >Data de Saída *</label
                >
                <input
                  id="data_final"
                  v-model="form.data_final"
                  type="date"
                  required
                  :min="dataMinimaSaida"
                  class="form-input"
                  :class="{ 'field-error': form.errors?.data_final }"
                />
                <p v-if="form.errors?.data_final" class="form-error">
                  {{ form.errors.data_final }}
                </p>
              </div>
              <div class="form-field md:col-span-2">
                <label for="condicao" class="form-label"
                  >Condição da Reserva *</label
                >
                <select
                  id="condicao"
                  v-model="form.condicao"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.condicao }"
                >
                  <option value="">Selecione a condição</option>
                  <option
                    v-for="(label, value) in props.condicoes"
                    :key="value"
                    :value="value"
                  >
                    {{ label }}
                  </option>
                </select>
                <p v-if="form.errors?.condicao" class="form-error">
                  {{ form.errors.condicao }}
                </p>
              </div>
              <div class="form-field md:col-span-2">
                <label for="motivo" class="form-label"
                  >Motivo da Reserva *</label
                >
                <textarea
                  id="motivo"
                  v-model="form.motivo"
                  rows="4"
                  required
                  class="form-input"
                  :class="{ 'field-error': form.errors?.motivo }"
                  placeholder="Descreva detalhadamente o motivo da sua estadia..."
                ></textarea>
                <p v-if="form.errors?.motivo" class="form-error">
                  {{ form.errors.motivo }}
                </p>
              </div>
            </div>
          </div>

          <!-- Documentos -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Documentos Obrigatórios</h3>
            </div>
            <div class="space-y-4">
              <div class="form-field">
                <label for="documento_identidade" class="form-label"
                  >Documento de Identidade (RG/CNH) *</label
                >
                <input
                  id="documento_identidade"
                  type="file"
                  accept=".pdf"
                  required
                  @change="handleFileChange('documento_identidade', $event)"
                  class="file-input"
                  :class="{ 'field-error': form.errors?.documento_identidade }"
                />
                <p class="form-hint">Formato aceito: PDF (máx. 5MB)</p>
                <p v-if="form.errors?.documento_identidade" class="form-error">
                  {{ form.errors.documento_identidade }}
                </p>
              </div>
              <div class="form-field">
                <label for="documento_funcional" class="form-label"
                  >Carteira Funcional
                  <span class="text-gray-400 font-normal"
                    >(se possuir)</span
                  ></label
                >
                <input
                  id="documento_funcional"
                  type="file"
                  accept=".pdf"
                  @change="handleFileChange('documento_funcional', $event)"
                  class="file-input"
                />
                <p class="form-hint">Formato aceito: PDF (máx. 5MB)</p>
                <p v-if="form.errors?.documento_funcional" class="form-error">
                  {{ form.errors.documento_funcional }}
                </p>
              </div>
              <div class="form-field">
                <label for="documento_comprobatorio" class="form-label"
                  >Documento Comprobatório
                  <span class="text-gray-400 font-normal"
                    >(se possuir)</span
                  ></label
                >
                <input
                  id="documento_comprobatorio"
                  type="file"
                  accept=".pdf"
                  @change="handleFileChange('documento_comprobatorio', $event)"
                  class="file-input"
                />
                <p class="form-hint">
                  Convite, ofício, comprovante de curso, etc. Formato: PDF (máx.
                  5MB)
                </p>
                <p
                  v-if="form.errors?.documento_comprobatorio"
                  class="form-error"
                >
                  {{ form.errors.documento_comprobatorio }}
                </p>
              </div>
            </div>
          </div>

          <!-- Termos -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Termos e Condições</h3>
              <button
                type="button"
                @click="toggleTermos"
                class="toggle-terms-btn"
              >
                {{ termoVisivel ? 'Ocultar' : 'Ver termos completos' }}
              </button>
            </div>
            <div
              class="terms-box"
              :class="termoVisivel ? 'terms-expanded' : 'terms-collapsed'"
            >
              <h4 class="font-semibold text-gray-700 text-sm mb-3">
                TERMO DE UTILIZAÇÃO DO ALOJAMENTO
              </h4>
              <p class="text-sm text-gray-600 mb-3">
                Ao solicitar a reserva, o visitante declara estar ciente e
                concordar com as seguintes condições:
              </p>
              <ol
                class="list-decimal ml-5 space-y-1.5 mb-4 text-sm text-gray-600"
              >
                <li>
                  O uso do alojamento é exclusivo para servidores e pessoas
                  autorizadas pela administração da ACADEPOL;
                </li>
                <li>
                  A ACADEPOL NÃO FORNECE materiais de higiene pessoal, lençóis,
                  cobertores, travesseiros e toalhas;
                </li>
                <li>
                  Os quartos são compartilhados (beliches) e separados por sexo.
                  Não há possibilidade de reserva de quartos individuais;
                </li>
                <li>
                  O ocupante é responsável pela conservação das instalações e
                  equipamentos utilizados;
                </li>
                <li>
                  É proibido consumir bebidas alcoólicas ou substâncias ilícitas
                  nas dependências do alojamento;
                </li>
                <li>
                  É proibido realizar festas, reuniões ou eventos que perturbem
                  o silêncio ou a tranquilidade dos demais ocupantes;
                </li>
                <li>Deve-se respeitar o horário de silêncio entre 22h e 6h;</li>
                <li>
                  A ACADEPOL não se responsabiliza por objetos pessoais deixados
                  nos quartos;
                </li>
                <li>
                  O check-in deve ser realizado entre 8h e 18h, mediante
                  apresentação de documento de identificação;
                </li>
                <li>
                  O check-out deve ser realizado até as 12h do dia de saída;
                </li>
                <li>
                  A pré-reserva está sujeita à disponibilidade e aprovação pela
                  administração da ACADEPOL;
                </li>
                <li>
                  A administração poderá cancelar a reserva em caso de
                  descumprimento destas normas.
                </li>
              </ol>
              <p class="text-sm text-gray-500">
                Este termo poderá ser atualizado a qualquer momento.
              </p>
            </div>
            <label class="accept-terms-label mt-3">
              <input
                id="aceita_termos"
                v-model="form.aceita_termos"
                type="checkbox"
                required
                class="form-checkbox"
                :class="{ 'border-red-300': form.errors?.aceita_termos }"
              />
              <span class="text-sm text-gray-700"
                >Declaro que li e aceito os termos e condições para hospedagem
                no alojamento da ACADEPOL *</span
              >
            </label>
            <p v-if="form.errors?.aceita_termos" class="form-error mt-1">
              {{ form.errors.aceita_termos }}
            </p>
          </div>

          <!-- Ações -->
          <div class="form-actions">
            <button type="submit" class="btn-submit" :disabled="isSubmitting">
              <svg
                v-if="!isSubmitting"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
              <svg
                v-else
                class="animate-spin h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
              {{
                isSubmitting ? 'Enviando...' : 'Enviar Solicitação de Reserva'
              }}
            </button>
            <Link href="/" class="btn-cancel">Cancelar</Link>
          </div>
        </form>
      </div>
    </div>
    <Footer />
  </div>
</template>

<style scoped>
.page-header {
  @apply py-4 bg-white border-b border-gray-200;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.page-header-accent {
  @apply block w-1 h-6 rounded-full flex-shrink-0;
  background-color: #bea55a;
}
.page-header-title {
  @apply text-base font-semibold text-gray-800;
}
.back-link {
  @apply inline-flex items-center gap-1.5 text-sm font-medium transition-colors duration-150;
  color: #bea55a;
}
.back-link:hover {
  color: #a38e4d;
}
.form-card {
  @apply bg-white rounded-lg border border-gray-200 overflow-hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.form-card-header {
  @apply flex items-center justify-between px-6 py-4 border-b border-gray-100;
}
.form-card-header-left {
  @apply flex items-center gap-3;
}
.form-card-accent {
  @apply block w-1 h-5 rounded-full flex-shrink-0;
  background-color: #bea55a;
}
.form-card-title {
  @apply text-sm font-semibold text-gray-800 uppercase tracking-wide;
}
.form-required-note {
  @apply text-xs text-gray-400;
}
.warning-box {
  @apply flex gap-3 rounded-lg p-4 border;
  background-color: #fdfbf2;
  border-color: #e5d5a0;
}
.warning-icon {
  color: #bea55a;
}
.warning-title {
  @apply text-sm font-semibold mb-1;
  color: #92740e;
}
.warning-text {
  @apply text-sm;
  color: #a38e4d;
}
.form-body {
  @apply p-6 space-y-6;
}
.form-section {
  @apply pb-6 border-b border-gray-100 last:border-0 last:pb-0;
}
.form-section-header {
  @apply flex items-center gap-2.5 mb-4;
}
.form-section-accent {
  @apply block w-1 h-4 rounded-full flex-shrink-0;
  background-color: #bea55a;
}
.form-section-title {
  @apply text-sm font-semibold text-gray-700 uppercase tracking-wide;
}
.form-field {
  @apply flex flex-col gap-1;
}
.form-label {
  @apply text-sm font-medium text-gray-600;
}
.form-input {
  @apply w-full text-sm border border-gray-200 rounded-md px-3 py-2.5 bg-white placeholder-gray-400 focus:outline-none focus:border-transparent transition-colors duration-150;
}
.form-input:focus {
  box-shadow: 0 0 0 2px #bea55a40;
  border-color: #bea55a;
}
.field-error {
  @apply border-red-300 bg-red-50;
}
.form-error {
  @apply text-xs text-red-500 mt-0.5;
}
.form-hint {
  @apply text-xs text-gray-400 mt-0.5;
}
.file-input {
  @apply w-full text-sm text-gray-500;
}
.file-input::file-selector-button {
  @apply mr-3 py-1.5 px-3 rounded-md border-0 text-xs font-semibold cursor-pointer;
  background-color: #faf5e8;
  color: #bea55a;
}
.file-input::file-selector-button:hover {
  background-color: #f0e8d0;
}
.terms-box {
  @apply bg-gray-50 border border-gray-200 rounded-md p-4 overflow-y-auto transition-all duration-200;
}
.terms-collapsed {
  max-height: 10rem;
}
.terms-expanded {
  max-height: none;
}
.toggle-terms-btn {
  @apply text-xs font-medium ml-auto transition-colors duration-150;
  color: #bea55a;
}
.toggle-terms-btn:hover {
  color: #a38e4d;
}
.form-checkbox {
  @apply w-4 h-4 rounded border-gray-300 flex-shrink-0 mt-0.5;
  accent-color: #bea55a;
}
.accept-terms-label {
  @apply flex items-start gap-2 cursor-pointer;
}
.form-actions {
  @apply flex flex-col sm:flex-row gap-3 pt-5 border-t border-gray-100;
}
.btn-submit {
  @apply inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-md text-sm font-semibold text-white transition-colors duration-150;
  background-color: #bea55a;
}
.btn-submit:hover:not(:disabled) {
  background-color: #a38e4d;
}
.btn-submit:disabled {
  @apply opacity-60 cursor-not-allowed;
}
.btn-cancel {
  @apply inline-flex items-center justify-center px-6 py-2.5 rounded-md text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors duration-150;
}
.search-btn {
  @apply inline-flex items-center gap-2 px-4 py-2.5 rounded-md text-sm font-medium text-white transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed;
  background-color: #3b82f6;
}
.search-btn:hover:not(:disabled) {
  background-color: #2563eb;
}
@media (prefers-reduced-motion: reduce) {
  .form-input,
  .btn-submit,
  .btn-cancel,
  .search-btn {
    @apply transition-none;
  }
}
</style>
