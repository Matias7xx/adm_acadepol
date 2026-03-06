<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

const props = defineProps({
  user: { type: Object, required: true },
});

const { toast } = useToast();
const termoVisivel = ref(false);
const isSubmitting = ref(false);
const documentoSelecionado = ref(null);

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

const formatarDataParaInput = data => {
  if (!data) return '';
  if (typeof data === 'string' && data.match(/^\d{4}-\d{2}-\d{2}/)) {
    const d = new Date(data);
    if (!isNaN(d.getTime())) return d.toISOString().split('T')[0];
  }
  return '';
};

const formData = ref({
  aceitaTermos: false,
  nome: props.user?.name || '',
  cargo: props.user?.cargo || '',
  matricula: props.user?.matricula || '',
  orgao: props.user?.orgao || '',
  cpf: props.user?.cpf || '',
  data_nascimento: formatarDataParaInput(props.user?.data_nascimento),
  rg: props.user?.rg || '',
  orgao_expedidor: props.user?.orgao_expedidor || '',
  sexo: props.user?.sexo || '',
  uf: props.user?.uf || '',
  motivo: '',
  condicao: '',
  email: props.user?.email || '',
  telefone: props.user?.telefone || '',
  endereco_rua: props.user?.endereco?.rua || '',
  endereco_bairro: props.user?.endereco?.bairro || '',
  endereco_cidade: props.user?.endereco?.cidade || '',
  endereco_numero: props.user?.endereco?.numero || '',
  endereco_cep: props.user?.endereco?.cep || '',
  data_inicial: '',
  data_final: '',
});

const dataMinima = new Date().toISOString().split('T')[0];
const handleDocumentoChange = e => {
  documentoSelecionado.value = e.target.files[0];
};
const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value;
};
const validarCPF = cpf => /^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/.test(cpf);

const formatarCep = valor =>
  valor
    .replace(/\D/g, '')
    .replace(/(\d{5})(\d)/, '$1-$2')
    .replace(/(-\d{3})\d+?$/, '$1');
const handleCepInput = e => {
  formData.value.endereco_cep = formatarCep(e.target.value);
};

const validarDatas = () => {
  if (!formData.value.data_inicial || !formData.value.data_final) return false;
  const ini = new Date(formData.value.data_inicial);
  const fim = new Date(formData.value.data_final);
  const hoje = new Date();
  hoje.setHours(0, 0, 0, 0);
  if (ini < hoje) {
    toast.error('A data inicial não pode ser anterior à data atual');
    return false;
  }
  if (fim < ini) {
    toast.error('A data final deve ser igual ou posterior à data inicial');
    return false;
  }
  return true;
};

const submeterReserva = () => {
  if (!formData.value.aceitaTermos) {
    toast.error('Você precisa aceitar os termos para continuar');
    return;
  }
  if (!validarDatas()) return;
  for (const campo of [
    'nome',
    'cargo',
    'matricula',
    'orgao',
    'cpf',
    'motivo',
    'condicao',
    'email',
    'telefone',
    'endereco_rua',
    'endereco_bairro',
    'endereco_cidade',
  ]) {
    if (!formData.value[campo]) {
      toast.error(`Por favor, preencha o campo ${campo.replace(/_/g, ' ')}`);
      return;
    }
  }
  isSubmitting.value = true;
  const form = useForm({
    nome: formData.value.nome,
    cargo: formData.value.cargo,
    matricula: formData.value.matricula,
    orgao: formData.value.orgao,
    cpf: formData.value.cpf,
    data_nascimento: formData.value.data_nascimento,
    rg: formData.value.rg,
    orgao_expedidor: formData.value.orgao_expedidor,
    sexo: formData.value.sexo,
    uf: formData.value.uf,
    motivo: formData.value.motivo,
    condicao: formData.value.condicao,
    email: formData.value.email,
    telefone: formData.value.telefone,
    endereco: {
      rua: formData.value.endereco_rua,
      bairro: formData.value.endereco_bairro,
      cidade: formData.value.endereco_cidade,
      numero: formData.value.endereco_numero,
      cep: formData.value.endereco_cep,
    },
    data_inicial: formData.value.data_inicial,
    data_final: formData.value.data_final,
    aceita_termos: formData.value.aceitaTermos,
    documento_comprobatorio: documentoSelecionado.value,
  });
  form.post(route('alojamento.reserva.store'), {
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
</script>

<template>
  <Head title="Pré-Reserva de Alojamento" />
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
          <h1 class="page-header-title">Pré-Reserva de Alojamento</h1>
        </div>
        <Link :href="route('home')" class="back-link">
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

        <!-- Aviso Importante -->
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

        <form @submit.prevent="submeterReserva" class="form-body">
          <!-- Informações Pessoais -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Informações Pessoais</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field md:col-span-2">
                <label for="nome" class="form-label">Nome Completo *</label>
                <input
                  id="nome"
                  v-model="formData.nome"
                  type="text"
                  required
                  readonly
                  class="form-input form-input-readonly"
                />
              </div>
              <div class="form-field">
                <label for="cpf" class="form-label">CPF *</label>
                <input
                  id="cpf"
                  v-model="formData.cpf"
                  type="text"
                  required
                  readonly
                  class="form-input form-input-readonly"
                  placeholder="000.000.000-00"
                />
                <p
                  v-if="formData.cpf && !validarCPF(formData.cpf)"
                  class="form-error"
                >
                  CPF inválido. Use o formato 000.000.000-00
                </p>
              </div>
              <div class="form-field">
                <label for="data_nascimento" class="form-label"
                  >Data de Nascimento *</label
                >
                <input
                  id="data_nascimento"
                  v-model="formData.data_nascimento"
                  type="date"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="rg" class="form-label">RG *</label>
                <input
                  id="rg"
                  v-model="formData.rg"
                  type="text"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="orgao_expedidor" class="form-label"
                  >Órgão Expedidor *</label
                >
                <input
                  id="orgao_expedidor"
                  v-model="formData.orgao_expedidor"
                  type="text"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="sexo" class="form-label">Sexo *</label>
                <select
                  id="sexo"
                  v-model="formData.sexo"
                  required
                  class="form-input"
                >
                  <option value="">Selecione...</option>
                  <option value="masculino">Masculino</option>
                  <option value="feminino">Feminino</option>
                </select>
              </div>
              <div class="form-field">
                <label for="cargo" class="form-label">Cargo/Função *</label>
                <input
                  id="cargo"
                  v-model="formData.cargo"
                  type="text"
                  required
                  readonly
                  class="form-input form-input-readonly"
                />
              </div>
              <div class="form-field">
                <label for="matricula" class="form-label">Matrícula *</label>
                <input
                  id="matricula"
                  v-model="formData.matricula"
                  type="text"
                  required
                  readonly
                  class="form-input form-input-readonly"
                />
              </div>
              <div class="form-field">
                <label for="orgao" class="form-label"
                  >Órgão/Instituição *</label
                >
                <input
                  id="orgao"
                  v-model="formData.orgao"
                  type="text"
                  required
                  readonly
                  class="form-input form-input-readonly"
                />
              </div>
              <div class="form-field">
                <label for="condicao" class="form-label"
                  >Condição do Alojado *</label
                >
                <select
                  id="condicao"
                  v-model="formData.condicao"
                  required
                  class="form-input"
                >
                  <option value="">Selecione...</option>
                  <option value="Professor">Professor</option>
                  <option value="Aluno">Aluno</option>
                  <option value="Visitante">Visitante</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>
              <div class="form-field md:col-span-2">
                <label for="motivo" class="form-label"
                  >Motivo da Reserva *</label
                >
                <textarea
                  id="motivo"
                  v-model="formData.motivo"
                  rows="2"
                  required
                  class="form-input"
                  placeholder="Explique o motivo da sua solicitação de alojamento"
                ></textarea>
              </div>
              <div class="form-field md:col-span-2">
                <label for="documento_comprobatorio" class="form-label"
                  >Documento Comprobatório (PDF)
                  <span class="text-gray-400 font-normal"
                    >— opcional</span
                  ></label
                >
                <input
                  id="documento_comprobatorio"
                  type="file"
                  accept=".pdf"
                  @change="handleDocumentoChange"
                  class="file-input"
                />
                <p class="form-hint">
                  Licença para curso, documento oficial, etc. Tamanho máximo:
                  10MB
                </p>
              </div>
            </div>
          </div>

          <!-- Informações de Contato -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Informações de Contato</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field">
                <label for="email" class="form-label">E-mail *</label>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="telefone" class="form-label"
                  >Telefone de Contato *</label
                >
                <input
                  id="telefone"
                  v-model="formData.telefone"
                  type="tel"
                  required
                  class="form-input"
                  placeholder="(00) 00000-0000"
                />
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
                <label for="endereco_rua" class="form-label"
                  >Rua/Avenida *</label
                >
                <input
                  id="endereco_rua"
                  v-model="formData.endereco_rua"
                  type="text"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="endereco_numero" class="form-label">Número</label>
                <input
                  id="endereco_numero"
                  v-model="formData.endereco_numero"
                  type="text"
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="endereco_bairro" class="form-label">Bairro *</label>
                <input
                  id="endereco_bairro"
                  v-model="formData.endereco_bairro"
                  type="text"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="endereco_cidade" class="form-label">Cidade *</label>
                <input
                  id="endereco_cidade"
                  v-model="formData.endereco_cidade"
                  type="text"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="uf" class="form-label">UF *</label>
                <select
                  id="uf"
                  v-model="formData.uf"
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
                <label for="endereco_cep" class="form-label">CEP *</label>
                <input
                  id="endereco_cep"
                  v-model="formData.endereco_cep"
                  type="text"
                  maxlength="9"
                  required
                  placeholder="00000-000"
                  class="form-input"
                  @input="handleCepInput"
                />
              </div>
            </div>
          </div>

          <!-- Período de Reserva -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Período de Reserva</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field">
                <label for="data_inicial" class="form-label"
                  >Data Inicial *</label
                >
                <input
                  id="data_inicial"
                  v-model="formData.data_inicial"
                  type="date"
                  :min="dataMinima"
                  required
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="data_final" class="form-label">Data Final *</label>
                <input
                  id="data_final"
                  v-model="formData.data_final"
                  type="date"
                  :min="formData.data_inicial || dataMinima"
                  required
                  class="form-input"
                />
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
                Ao solicitar a pré-reserva, o usuário declara estar ciente e
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
                id="aceitaTermos"
                v-model="formData.aceitaTermos"
                type="checkbox"
                required
                class="form-checkbox"
              />
              <span class="text-sm text-gray-700"
                >Declaro que li e aceito os termos e condições para utilização
                do alojamento</span
              >
            </label>
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
            <Link :href="route('home')" class="btn-cancel">Cancelar</Link>
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
.form-input-readonly {
  @apply bg-gray-50 text-gray-500 cursor-default;
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
</style>
