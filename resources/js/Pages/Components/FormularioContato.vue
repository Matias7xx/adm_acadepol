<script setup>
import { ref } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

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

const { toast } = useToast();
const isSubmitting = ref(false);

const formData = ref({
  nome: props.user?.name || '',
  email: props.user?.email || '',
  telefone: props.user?.telefone || '',
  assunto: '',
  mensagem: '',
});

const submeterContato = () => {
  for (const campo of ['nome', 'email', 'assunto', 'mensagem']) {
    if (!formData.value[campo]) {
      toast.error(`Por favor, preencha o campo ${campo.replace('_', ' ')}`);
      return;
    }
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(formData.value.email)) {
    toast.error('Por favor, informe um email válido');
    return;
  }
  isSubmitting.value = true;
  const form = useForm({ ...formData.value });
  form.post(route('contato.store'), {
    preserveScroll: false,
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: errors => {
      isSubmitting.value = false;
      toast.error(
        errors.message ||
          'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente.'
      );
    },
  });
};
</script>

<template>
  <Head title="Fale Conosco" />
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
          <h1 class="page-header-title">Fale Conosco</h1>
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
        <!-- Card header -->
        <div class="form-card-header">
          <div class="form-card-header-left">
            <span class="form-card-accent"></span>
            <h2 class="form-card-title">Entre em Contato</h2>
          </div>
          <span class="form-required-note">* Campos obrigatórios</span>
        </div>

        <!-- Aviso -->
        <div class="warning-box mx-6 mt-5">
          <svg
            class="warning-icon h-5 w-5 flex-shrink-0"
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
              Preencha corretamente todos os campos para que possamos responder
              sua solicitação. Nossa equipe responderá o mais breve possível.
            </p>
          </div>
        </div>

        <form @submit.prevent="submeterContato" class="form-body">
          <!-- Seus dados -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Seus Dados</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="form-field">
                <label for="nome" class="form-label">Nome Completo *</label>
                <input
                  id="nome"
                  v-model="formData.nome"
                  type="text"
                  required
                  class="form-input"
                  :class="{ 'form-input-readonly': !!user }"
                  :disabled="!!user"
                />
              </div>
              <div class="form-field">
                <label for="email" class="form-label">E-mail *</label>
                <input
                  id="email"
                  v-model="formData.email"
                  type="email"
                  required
                  class="form-input"
                  :class="{ 'form-input-readonly': !!user }"
                  :disabled="!!user"
                />
              </div>
              <div class="form-field">
                <label for="telefone" class="form-label"
                  >Telefone de Contato</label
                >
                <input
                  id="telefone"
                  v-model="formData.telefone"
                  type="tel"
                  placeholder="(00) 00000-0000"
                  class="form-input"
                />
              </div>
              <div class="form-field">
                <label for="assunto" class="form-label">Assunto *</label>
                <select
                  id="assunto"
                  v-model="formData.assunto"
                  required
                  class="form-input"
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
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Sua Mensagem</h3>
            </div>
            <div class="form-field">
              <label for="mensagem" class="form-label">Mensagem *</label>
              <textarea
                id="mensagem"
                v-model="formData.mensagem"
                rows="5"
                required
                class="form-input"
                placeholder="Escreva sua mensagem aqui..."
              ></textarea>
              <p class="form-hint">
                Seja o mais específico possível para que possamos melhor atender
                sua solicitação.
              </p>
            </div>
          </div>

          <!-- Política -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Política de Privacidade</h3>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed">
              Ao enviar este formulário, você concorda com nossa política de
              privacidade. Seus dados serão utilizados exclusivamente para
              responder à sua solicitação e não serão compartilhados com
              terceiros.
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
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z"
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
              {{ isSubmitting ? 'Enviando...' : 'Enviar Mensagem' }}
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
  @apply flex gap-3 rounded-lg p-4 mb-0 border;
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
  @apply bg-gray-50 text-gray-500;
}
.form-hint {
  @apply text-xs text-gray-400 mt-0.5;
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
@media (prefers-reduced-motion: reduce) {
  .form-input,
  .btn-submit,
  .btn-cancel {
    @apply transition-none;
  }
}
</style>
