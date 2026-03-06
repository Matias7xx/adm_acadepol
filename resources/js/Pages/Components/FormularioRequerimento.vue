<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

const props = defineProps({
  user: { type: Object, required: true },
  tiposRequerimento: { type: Array, default: () => [] },
});

const { toast } = useToast();
const isSubmitting = ref(false);
const documentoSelecionado = ref(null);
const termoVisivel = ref(false);

const formData = ref({
  nome: props.user?.name || '',
  cargo: props.user?.cargo || '',
  matricula: props.user?.matricula || '',
  orgao: props.user?.orgao || '',
  cpf: props.user?.cpf || '',
  email: props.user?.email || '',
  telefone: props.user?.telefone || '',
  tipo_requerimento: '',
  descricao: '',
  aceita_termos: false,
});

const handleDocumentoChange = e => {
  documentoSelecionado.value = e.target.files[0];
};
const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value;
};

const validarCPF = cpf => /^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/.test(cpf);

const submeterRequerimento = () => {
  if (!formData.value.aceita_termos) {
    toast.error('Você precisa aceitar os termos para continuar');
    return;
  }
  for (const campo of [
    'nome',
    'cargo',
    'matricula',
    'cpf',
    'email',
    'telefone',
    'tipo_requerimento',
    'descricao',
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
    email: formData.value.email,
    telefone: formData.value.telefone,
    tipo: formData.value.tipo_requerimento,
    conteudo: formData.value.descricao,
    aceita_termos: formData.value.aceita_termos,
    documento: documentoSelecionado.value,
  });
  form.post(route('requerimentos.store'), {
    preserveScroll: false,
    forceFormData: true,
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: errors => {
      isSubmitting.value = false;
      toast.error(
        errors.message ||
          'Ocorreu um erro ao enviar o requerimento. Por favor, tente novamente.'
      );
    },
  });
};
</script>

<template>
  <Head title="Envio de Requerimento" />
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
          <h1 class="page-header-title">Formulário de Requerimento</h1>
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
            <h2 class="form-card-title">Novo Requerimento</h2>
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
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <p class="warning-title">Informações Importantes</p>
            <p class="warning-text">
              Preencha todos os campos corretamente. Sua solicitação será
              analisada pela equipe da ACADEPOL e você será notificado por
              e-mail sobre o status do requerimento.
            </p>
          </div>
        </div>

        <form @submit.prevent="submeterRequerimento" class="form-body">
          <!-- Dados do Requerente -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Dados do Requerente</h3>
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
                />
                <p
                  v-if="formData.cpf && !validarCPF(formData.cpf)"
                  class="form-error"
                >
                  CPF inválido. Use o formato 000.000.000-00
                </p>
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
                <label for="orgao" class="form-label">Órgão/Instituição</label>
                <input
                  id="orgao"
                  v-model="formData.orgao"
                  type="text"
                  readonly
                  class="form-input form-input-readonly"
                />
              </div>
              <div class="form-field">
                <label for="email" class="form-label">E-mail *</label>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  required
                  readonly
                  class="form-input form-input-readonly"
                />
              </div>
              <div class="form-field">
                <label for="telefone" class="form-label">Telefone *</label>
                <input
                  id="telefone"
                  v-model="formData.telefone"
                  type="tel"
                  required
                  readonly
                  class="form-input form-input-readonly"
                />
              </div>
            </div>
          </div>

          <!-- Detalhes do Requerimento -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Detalhes do Requerimento</h3>
            </div>
            <div class="grid grid-cols-1 gap-4">
              <div class="form-field">
                <label for="tipo_requerimento" class="form-label"
                  >Tipo de Requerimento *</label
                >
                <select
                  id="tipo_requerimento"
                  v-model="formData.tipo_requerimento"
                  required
                  class="form-input"
                >
                  <option value="">Selecione o tipo de requerimento</option>
                  <option
                    v-for="tipo in tiposRequerimento"
                    :key="tipo.id || tipo"
                    :value="tipo.id || tipo"
                  >
                    {{ tipo.nome || tipo }}
                  </option>
                </select>
              </div>
              <div class="form-field">
                <label for="descricao" class="form-label"
                  >Descrição Detalhada *</label
                >
                <textarea
                  id="descricao"
                  v-model="formData.descricao"
                  rows="5"
                  required
                  class="form-input"
                  placeholder="Descreva detalhadamente o seu requerimento, incluindo todas as informações relevantes"
                ></textarea>
              </div>
              <div class="form-field">
                <label for="documento_anexo" class="form-label"
                  >Documento Anexo
                  <span class="text-gray-400 font-normal"
                    >(opcional)</span
                  ></label
                >
                <input
                  id="documento_anexo"
                  type="file"
                  accept=".pdf"
                  @change="handleDocumentoChange"
                  class="file-input"
                />
                <p class="form-hint">
                  Formato aceito: PDF. Tamanho máximo: 10MB
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
                TERMO DE RESPONSABILIDADE E ACORDO PARA ENVIO DE REQUERIMENTOS
              </h4>
              <p class="text-sm text-gray-600 mb-3">
                Ao submeter este requerimento, o usuário declara estar ciente e
                concordar com as seguintes condições:
              </p>
              <ol
                class="list-decimal ml-5 space-y-1.5 mb-4 text-sm text-gray-600"
              >
                <li>
                  As informações fornecidas neste requerimento são verdadeiras e
                  de minha inteira responsabilidade;
                </li>
                <li>
                  Estou ciente de que o fornecimento de informações falsas pode
                  configurar crime de falsidade ideológica, conforme previsto no
                  Art. 299 do Código Penal;
                </li>
                <li>
                  A ACADEPOL analisará o requerimento de acordo com as normas e
                  procedimentos internos vigentes;
                </li>
                <li>
                  Os prazos para análise e resposta podem variar de acordo com a
                  complexidade do requerimento;
                </li>
                <li>
                  Serei notificado sobre o andamento e resultado do requerimento
                  através do e-mail informado;
                </li>
                <li>
                  Documentos anexados devem estar legíveis e em formatos aceitos
                  pelo sistema;
                </li>
                <li>
                  A ACADEPOL se reserva o direito de solicitar documentos
                  adicionais caso necessário;
                </li>
                <li>
                  Todos os dados pessoais fornecidos serão tratados de acordo
                  com a LGPD;
                </li>
                <li>
                  Estou ciente de que o requerimento pode ser deferido ou
                  indeferido, conforme análise técnica e administrativa;
                </li>
                <li>
                  Em caso de dúvidas, deverei entrar em contato através dos
                  canais oficiais da ACADEPOL.
                </li>
              </ol>
              <p class="text-sm text-gray-500">
                Este termo poderá ser atualizado a qualquer momento.
              </p>
            </div>
            <label class="accept-terms-label mt-3">
              <input
                id="aceita_termos"
                v-model="formData.aceita_termos"
                type="checkbox"
                required
                class="form-checkbox"
              />
              <span class="text-sm text-gray-700"
                >Declaro que li e aceito os termos e condições para envio de
                requerimentos</span
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
              {{ isSubmitting ? 'Enviando...' : 'Enviar Requerimento' }}
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
  @apply mr-3 py-1.5 px-3 rounded-md border-0 text-xs font-semibold cursor-pointer transition-colors duration-150;
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
