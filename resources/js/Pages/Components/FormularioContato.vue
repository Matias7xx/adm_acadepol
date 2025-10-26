<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

// Props
const props = defineProps({
  user: Object,
  assuntos: {
    type: Array,
    default: () => [
      'Informações sobre cursos',
      'Dúvidas sobre matrícula',
      'Problemas no sistema',
      'Alojamento',
      'Certificados',
      'Outros assuntos',
    ],
  },
});

// Toast notification
const { toast } = useToast();

// Estado do formulário
const isSubmitting = ref(false);

const formData = ref({
  nome: props.user?.name || '',
  email: props.user?.email || '',
  telefone: '',
  assunto: '',
  mensagem: '',
});

// Função para enviar o formulário
const submeterContato = () => {
  // Validar campos obrigatórios
  for (const campo of ['nome', 'email', 'assunto', 'mensagem']) {
    if (!formData.value[campo]) {
      toast.error(`Por favor, preencha o campo ${campo.replace('_', ' ')}`);
      return;
    }
  }

  // Validar email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(formData.value.email)) {
    toast.error('Por favor, informe um email válido');
    return;
  }

  isSubmitting.value = true;

  // Usar o Inertia form helper
  const form = useForm({
    nome: formData.value.nome,
    email: formData.value.email,
    telefone: formData.value.telefone,
    assunto: formData.value.assunto,
    mensagem: formData.value.mensagem,
  });

  form.post(route('contato.store'), {
    preserveScroll: false,
    onSuccess: () => {
      isSubmitting.value = false;
      // Não precisa exibir toast aqui pois será redirecionado para página de confirmação
    },
    onError: errors => {
      isSubmitting.value = false;
      console.error(errors);
      if (errors.message) {
        toast.error(errors.message);
      } else {
        toast.error(
          'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente.'
        );
      }
    },
  });
};
</script>

<template>
  <Head title="Fale Conosco" />
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
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            />
          </svg>
          <h1 class="text-2xl font-bold">Fale Conosco</h1>
        </div>
        <Link
          :href="route('home')"
          class="flex items-center text-amber-400 hover:text-amber-300 transition"
        >
          <span>Voltar</span>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 ml-1"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
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
                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Entre em Contato</h2>
          </div>
          <div class="text-sm text-gray-500">* Campos obrigatórios</div>
        </div>

        <!-- Aviso Importante -->
        <div
          class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-amber-500 p-4 mb-6 shadow-sm"
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
                Preencha corretamente todos os campos para que possamos
                responder sua solicitação adequadamente. Nossa equipe responderá
                sua mensagem o mais breve possível.
              </p>
            </div>
          </div>
        </div>

        <form @submit.prevent="submeterContato" class="space-y-8">
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
              Seus Dados
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
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  :class="{ 'bg-slate-100': user }"
                  required
                  :disabled="!!user"
                />
              </div>

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
                  :class="{ 'bg-slate-100': user }"
                  required
                  :disabled="!!user"
                />
              </div>

              <!-- Telefone -->
              <div>
                <label
                  for="telefone"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Telefone de Contato
                </label>
                <input
                  id="telefone"
                  v-model="formData.telefone"
                  type="tel"
                  placeholder="(00) 00000-0000"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                />
              </div>

              <!-- Assunto -->
              <div>
                <label
                  for="assunto"
                  class="block text-sm font-medium text-gray-700 mb-1"
                >
                  Assunto *
                </label>
                <select
                  id="assunto"
                  v-model="formData.assunto"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  required
                >
                  <option value="">Selecione um assunto</option>
                  <option
                    v-for="assunto in assuntos"
                    :key="assunto"
                    :value="assunto"
                  >
                    {{ assunto }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Mensagem -->
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
                  d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                  clip-rule="evenodd"
                />
              </svg>
              Sua Mensagem
            </h3>

            <div>
              <label
                for="mensagem"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Mensagem *
              </label>
              <textarea
                id="mensagem"
                v-model="formData.mensagem"
                rows="6"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                placeholder="Escreva sua mensagem aqui..."
                required
              ></textarea>
              <p class="mt-1 text-sm text-gray-500">
                Por favor, seja o mais específico possível para que possamos
                melhor atender sua solicitação.
              </p>
            </div>
          </div>

          <!-- Política de Privacidade -->
          <div class="bg-gray-50 p-4 rounded-lg mb-6 text-sm border">
            <h4 class="font-bold mb-2">Política de Privacidade</h4>
            <p class="mb-3">
              Ao enviar este formulário, você concorda com nossa política de
              privacidade. Seus dados serão utilizados exclusivamente para
              responder à sua solicitação e não serão compartilhados com
              terceiros.
            </p>
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
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z"
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
              <span v-else>Enviar Mensagem</span>
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
