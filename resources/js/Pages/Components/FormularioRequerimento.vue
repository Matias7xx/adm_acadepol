<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  tiposRequerimento: {
    type: Array,
    default: () => [],
  },
});

// Toast notification
const { toast } = useToast();

// Estado do formulário
const isSubmitting = ref(false);
const documentoSelecionado = ref(null);
const termoVisivel = ref(false);

// Dados para o formulário de requerimento
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

// Handler para seleção de documento
const handleDocumentoChange = event => {
  documentoSelecionado.value = event.target.files[0];
};

// Função para enviar o formulário
const submeterRequerimento = () => {
  // Validar aceite de termos
  if (!formData.value.aceita_termos) {
    toast.error('Você precisa aceitar os termos para continuar');
    return;
  }

  // Validar campos obrigatórios
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
      toast.error(`Por favor, preencha o campo ${campo.replace('_', ' ')}`);
      return;
    }
  }

  isSubmitting.value = true;

  // Configurar o FormData para enviar arquivos
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
    forceFormData: true, // Importante para enviar arquivos
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: errors => {
      isSubmitting.value = false;
      console.error(errors);
      if (errors.message) {
        toast.error(errors.message);
      } else {
        toast.error(
          'Ocorreu um erro ao enviar o requerimento. Por favor, tente novamente.'
        );
      }
    },
  });
};

// Exibir/ocultar os termos
const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value;
};

// Verificar se CPF tem formato válido
const validarCPF = cpf => {
  const regex = /^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/;
  return regex.test(cpf);
};
</script>

<template>
  <Head title="Envio de Requerimento" />
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <Header />
    <SiteNavbar />

    <!-- Cabeçalho -->
    <div
      class="bg-gradient-to-r from-black to-gray-900 text-white py-5 shadow-md"
    >
      <div class="container mx-auto flex justify-between items-center px-4">
        <div class="flex items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 text-[#bea55a] mr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <h1 class="text-2xl font-bold">Formulário de Requerimento</h1>
        </div>
        <Link
          :href="route('home')"
          class="flex items-center text-amber-400 hover:text-amber-300 transition"
        >
          <svg
            class="h-4 w-4 mr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          <span>Voltar</span>
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      <div
        class="bg-white rounded-lg shadow-lg p-6 mb-6 border border-gray-200"
      >
        <div
          class="flex items-center justify-between mb-6 pb-3 border-b border-amber-200"
        >
          <div class="flex items-center">
            <div class="bg-amber-100 p-3 rounded-full mr-3">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6 text-amber-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Novo Requerimento</h2>
          </div>
          <div class="text-sm text-gray-500">* Campos obrigatórios</div>
        </div>

        <!-- Aviso Importante -->
        <div
          class="bg-gradient-to-r from-amber-50 to-amber-50 border-l-4 border-amber-500 p-4 mb-6 shadow-sm"
        >
          <div class="flex">
            <div class="flex-shrink-0">
              <svg
                class="h-6 w-6 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-amber-800">
                Informações Importantes
              </h3>
              <p class="mt-1 text-sm text-amber-700">
                Preencha todos os campos corretamente. Sua solicitação será
                analisada pela equipe da ACADEPOL e você será notificado por
                e-mail sobre o status do requerimento.
              </p>
            </div>
          </div>
        </div>

        <form @submit.prevent="submeterRequerimento" class="space-y-8">
          <!-- Informações Pessoais -->
          <div
            class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm"
          >
            <h3
              class="text-lg font-semibold text-gray-700 mb-4 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                  clip-rule="evenodd"
                />
              </svg>
              Informações Pessoais
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Nome Completo -->
              <div>
                <label
                  for="nome"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Nome Completo *
                </label>
                <input
                  id="nome"
                  v-model="formData.nome"
                  type="text"
                  class="w-full bg-slate-100 border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>

              <!-- CPF -->
              <div>
                <label
                  for="cpf"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  CPF *
                </label>
                <input
                  id="cpf"
                  v-model="formData.cpf"
                  type="text"
                  placeholder="000.000.000-00"
                  class="w-full border-gray-300 rounded-md shadow-sm bg-slate-100 focus:border-amber-500 focus:ring focus:ring-amber-200"
                  readonly
                  required
                />
                <p
                  v-if="formData.cpf && !validarCPF(formData.cpf)"
                  class="mt-1 text-sm text-red-600"
                >
                  CPF inválido. Use o formato 000.000.000-00
                </p>
              </div>

              <!-- Cargo/Função -->
              <div>
                <label
                  for="cargo"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Cargo/Função *
                </label>
                <input
                  id="cargo"
                  v-model="formData.cargo"
                  type="text"
                  class="w-full border-gray-300 bg-slate-100 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>

              <!-- Matrícula -->
              <div>
                <label
                  for="matricula"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Matrícula *
                </label>
                <input
                  id="matricula"
                  v-model="formData.matricula"
                  type="text"
                  class="w-full bg-slate-100 border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>

              <!-- Órgão/Instituição -->
              <div>
                <label
                  for="orgao"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Órgão/Instituição *
                </label>
                <input
                  id="orgao"
                  v-model="formData.orgao"
                  type="text"
                  class="w-full border-gray-300 bg-slate-100 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                  readonly
                />
              </div>
            </div>
          </div>

          <!-- Informações de Contato -->
          <div
            class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm"
          >
            <h3
              class="text-lg font-semibold text-gray-700 mb-4 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"
                />
              </svg>
              Informações de Contato
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Email -->
              <div>
                <label
                  for="email"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  E-mail *
                </label>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>

              <!-- Telefone -->
              <div>
                <label
                  for="telefone"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Telefone de Contato *
                </label>
                <input
                  id="telefone"
                  v-model="formData.telefone"
                  type="tel"
                  placeholder="(00) 00000-0000"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Detalhes do Requerimento -->
          <div
            class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm"
          >
            <h3
              class="text-lg font-semibold text-gray-700 mb-4 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                  clip-rule="evenodd"
                />
              </svg>
              Detalhes do Requerimento
            </h3>

            <div class="space-y-4">
              <!-- Tipo de Requerimento -->
              <div>
                <label
                  for="tipo_requerimento"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Tipo de Requerimento *
                </label>
                <select
                  id="tipo_requerimento"
                  v-model="formData.tipo_requerimento"
                  class="border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                >
                  <option value="">Selecione um tipo...</option>
                  <option
                    v-for="tipo in tiposRequerimento"
                    :key="tipo.id"
                    :value="tipo.id"
                  >
                    {{ tipo.nome }}
                  </option>
                </select>
              </div>

              <!-- Descrição -->
              <div>
                <label
                  for="descricao"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Descrição Detalhada *
                </label>
                <textarea
                  id="descricao"
                  v-model="formData.descricao"
                  rows="5"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="Descreva detalhadamente o seu requerimento, incluindo todas as informações relevantes"
                  required
                ></textarea>
              </div>

              <!-- Documento Anexo -->
              <div>
                <label
                  for="documento_anexo"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Documento Anexo (opcional)
                </label>
                <div class="mt-1 flex items-center">
                  <input
                    id="documento_anexo"
                    type="file"
                    @change="handleDocumentoChange"
                    accept=".pdf"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100"
                  />
                </div>
                <p class="mt-1 text-xs text-gray-500">
                  Formato aceito: PDF. Tamanho máximo: 10MB
                </p>
              </div>
            </div>
          </div>

          <!-- Termos e Condições -->
          <div class="mb-6">
            <h3
              class="text-lg font-semibold text-gray-700 mb-3 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 text-amber-500"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd"
                />
              </svg>
              Termos e Condições
            </h3>

            <div class="flex justify-end mb-2">
              <button
                type="button"
                @click="toggleTermos"
                class="text-amber-600 hover:text-amber-800 text-sm font-medium"
              >
                {{ termoVisivel ? 'Ocultar Termos' : 'Ver Termos Completos' }}
              </button>
            </div>

            <div
              :class="{ 'h-40': !termoVisivel, 'h-auto': termoVisivel }"
              class="bg-gray-50 p-4 rounded-lg mb-4 text-sm overflow-y-auto border transition-all duration-300"
            >
              <h4 class="font-bold mb-2">
                TERMO DE RESPONSABILIDADE E ACORDO PARA ENVIO DE REQUERIMENTOS
              </h4>

              <p class="mb-3">
                Ao submeter este requerimento, o usuário declara estar ciente e
                concordar com as seguintes condições:
              </p>

              <ol class="list-decimal ml-5 space-y-2 mb-4">
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
                  através do e-mail informado neste formulário;
                </li>
                <li>
                  Documentos anexados devem estar legíveis e em formatos aceitos
                  pelo sistema;
                </li>
                <li>
                  A ACADEPOL se reserva o direito de solicitar documentos
                  adicionais caso necessário para a análise do requerimento;
                </li>
                <li>
                  Todos os dados pessoais fornecidos serão tratados de acordo
                  com a Lei Geral de Proteção de Dados Pessoais (LGPD);
                </li>
                <li>
                  Estou ciente de que o requerimento pode ser deferido ou
                  indeferido, conforme análise técnica e administrativa;
                </li>
                <li>
                  Em caso de dúvidas sobre o andamento do requerimento, deverei
                  entrar em contato através dos canais oficiais da ACADEPOL.
                </li>
              </ol>

              <p>
                Este termo poderá ser atualizado a qualquer momento, sendo
                responsabilidade do usuário manter-se informado sobre as normas
                vigentes.
              </p>
            </div>

            <div class="flex items-start mb-4">
              <div class="flex items-center h-5">
                <input
                  id="aceita_termos"
                  v-model="formData.aceita_termos"
                  type="checkbox"
                  class="w-4 h-4 border-gray-300 rounded text-amber-600 focus:ring-amber-500"
                  required
                />
              </div>
              <label
                for="aceita_termos"
                class="ml-2 text-sm font-medium text-gray-700"
              >
                Declaro que li e aceito os termos e condições para envio de
                requerimentos
              </label>
            </div>
          </div>

          <!-- Botões de Ação -->
          <div
            class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 mt-8 pt-6 border-t border-gray-200"
          >
            <button
              type="submit"
              class="bg-[#bea55a] text-white py-3 px-8 rounded-md font-medium transition-all duration-300 shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-300 flex items-center justify-center"
              :disabled="isSubmitting"
            >
              <svg
                v-if="!isSubmitting"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2"
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
                v-if="isSubmitting"
                class="animate-spin h-5 w-5 mr-2"
                xmlns="http://www.w3.org/2000/svg"
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
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              <span v-if="isSubmitting">Enviando...</span>
              <span v-else>Enviar Requerimento</span>
            </button>
            <Link
              :href="route('home')"
              class="text-center border border-gray-300 bg-white text-gray-700 py-3 px-8 rounded-md font-medium transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 flex items-center justify-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
              Cancelar
            </Link>
          </div>
        </form>
      </div>
    </div>
    <Footer />
  </div>
</template>
