<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

const props = defineProps({
  curso: { type: Object, required: true },
  user: { type: Object, required: true },
});

const { toast } = useToast();
const termoVisivel = ref(false);
const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value;
};

const equipamentos = computed(() => {
  if (!props.curso.enxoval) return [];
  try {
    return Array.isArray(props.curso.enxoval)
      ? props.curso.enxoval
      : JSON.parse(props.curso.enxoval);
  } catch {
    return [];
  }
});

const formData = ref({
  aceitaTermos: false,
  experiencia: '',
  cursosAnteriores: '',
  expectativas: '',
  restricoesSaude: '',
  equipamentosConfirmados: computed(() =>
    Array(equipamentos.value.length).fill(false)
  ),
  observacoes: '',
});

const isSubmitting = ref(false);

const formatarData = dataString =>
  new Intl.DateTimeFormat('pt-BR').format(new Date(dataString));

const submeterInscricao = () => {
  if (!formData.value.aceitaTermos) {
    toast.error('Você precisa aceitar os termos para continuar');
    return;
  }
  if (!formData.value.expectativas) {
    toast.error('Por favor, preencha suas expectativas para o curso');
    return;
  }
  isSubmitting.value = true;

  const dadosAdicionais = {
    experiencia: formData.value.experiencia,
    cursosAnteriores: formData.value.cursosAnteriores,
    expectativas: formData.value.expectativas,
    restricoesSaude: formData.value.restricoesSaude,
    equipamentosConfirmados: equipamentos.value.filter(
      (_, i) => formData.value.equipamentosConfirmados[i]
    ),
    observacoes: formData.value.observacoes,
    dataInscricao: new Date().toISOString(),
  };

  const form = useForm({
    curso_id: props.curso.id,
    dados_adicionais: dadosAdicionais,
  });
  form.post(route('matricula.store'), {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: errors => {
      isSubmitting.value = false;
      toast.error(
        errors.message ||
          'Ocorreu um erro ao enviar a inscrição. Por favor, tente novamente.'
      );
    },
  });
};
</script>

<template>
  <Head :title="'Matrícula - ' + curso.nome" />
  <div class="min-h-screen bg-gray-50">
    <Header />
    <SiteNavbar />

    <!-- Cabeçalho da página -->
    <div class="form-page-header">
      <div
        class="container mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3 min-w-0">
          <span class="form-header-accent"></span>
          <h1 class="form-header-title truncate">{{ curso.nome }}</h1>
          <span class="form-header-subtitle hidden sm:inline"
            >— Formulário de Inscrição</span
          >
        </div>
        <Link :href="route('cursos')" class="form-back-link flex-shrink-0">
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
          Voltar aos Cursos
        </Link>
      </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Card principal -->
      <div class="form-card">
        <!-- Info do curso e aluno -->
        <div class="info-banner">
          <div class="info-banner-grid">
            <div>
              <h3 class="info-banner-title">Informações do Curso</h3>
              <div class="info-banner-rows">
                <div class="info-row">
                  <span>Curso</span><span>{{ curso.nome }}</span>
                </div>
                <div class="info-row">
                  <span>Período</span
                  ><span
                    >{{ formatarData(curso.data_inicio) }} a
                    {{ formatarData(curso.data_fim) }}</span
                  >
                </div>
                <div class="info-row">
                  <span>Carga Horária</span
                  ><span>{{ curso.carga_horaria }}h</span>
                </div>
                <div class="info-row" v-if="curso.localizacao">
                  <span>Localização</span><span>{{ curso.localizacao }}</span>
                </div>
              </div>
            </div>
            <div>
              <h3 class="info-banner-title">Informações do Aluno</h3>
              <div class="info-banner-rows">
                <div class="info-row">
                  <span>Nome</span><span>{{ user.name }}</span>
                </div>
                <div class="info-row">
                  <span>Matrícula</span><span>{{ user.matricula }}</span>
                </div>
                <div class="info-row">
                  <span>Lotação</span><span>{{ user.lotacao }}</span>
                </div>
                <div class="info-row">
                  <span>Email</span><span>{{ user.email }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <form @submit.prevent="submeterInscricao" class="form-body">
          <!-- Termos -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Termos e Condições</h3>
              <button
                type="button"
                @click="toggleTermos"
                class="toggle-terms-btn ml-auto"
              >
                {{ termoVisivel ? 'Ocultar' : 'Ver termos completos' }}
              </button>
            </div>

            <div
              class="terms-box"
              :class="termoVisivel ? 'terms-expanded' : 'terms-collapsed'"
            >
              <h4 class="font-semibold text-gray-700 mb-3 text-sm">
                TERMO DE PARTICIPAÇÃO NO CURSO
              </h4>
              <p class="mb-3 text-sm text-gray-600">
                Ao se inscrever neste curso, o aluno compromete-se a:
              </p>
              <ol
                class="list-decimal ml-5 space-y-1.5 mb-4 text-sm text-gray-600"
              >
                <li>Comparecer a todas as aulas nos horários estabelecidos;</li>
                <li>Cumprir com todas as atividades e avaliações propostas;</li>
                <li>
                  Trazer todos os materiais necessários listados nos
                  pré-requisitos;
                </li>
                <li>
                  Seguir as normas de segurança durante as atividades práticas;
                </li>
                <li>
                  Manter conduta compatível com os princípios éticos da
                  instituição;
                </li>
                <li>
                  Comunicar com antecedência qualquer impossibilidade de
                  comparecimento;
                </li>
                <li>
                  Estar ciente que a matrícula está sujeita à aprovação pela
                  coordenação do curso.
                </li>
              </ol>
              <p class="text-sm text-gray-600">
                A ACADEPOL se reserva o direito de cancelar a matrícula em caso
                de descumprimento das normas estabelecidas.
              </p>
            </div>

            <label class="accept-terms-label">
              <input
                id="aceitaTermos"
                v-model="formData.aceitaTermos"
                type="checkbox"
                class="form-checkbox"
                required
              />
              <span class="text-sm text-gray-700"
                >Declaro que li e aceito os termos e condições para participação
                no curso</span
              >
            </label>
          </div>

          <!-- Informações Adicionais -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Informações Adicionais</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-4">
              <!-- Experiência -->
              <div class="form-field">
                <label for="experiencia" class="form-label"
                  >Experiência prévia com o tema</label
                >
                <select
                  id="experiencia"
                  v-model="formData.experiencia"
                  class="form-select"
                >
                  <option value="">Selecione uma opção</option>
                  <option value="nenhuma">Nenhuma experiência</option>
                  <option value="basica">Experiência básica</option>
                  <option value="intermediaria">
                    Experiência intermediária
                  </option>
                  <option value="avancada">Experiência avançada</option>
                </select>
              </div>

              <!-- Restrições de Saúde -->
              <div class="form-field">
                <label for="restricoesSaude" class="form-label"
                  >Restrições de saúde *</label
                >
                <input
                  id="restricoesSaude"
                  v-model="formData.restricoesSaude"
                  type="text"
                  class="form-input"
                  placeholder="Informe se possui alguma restrição médica"
                />
              </div>

              <!-- Cursos Anteriores -->
              <div class="form-field md:col-span-2">
                <label for="cursosAnteriores" class="form-label"
                  >Cursos relacionados que já participou (opcional)</label
                >
                <textarea
                  id="cursosAnteriores"
                  v-model="formData.cursosAnteriores"
                  rows="2"
                  class="form-input"
                  placeholder="Liste os cursos relacionados que você já participou"
                ></textarea>
              </div>

              <!-- Expectativas -->
              <div class="form-field md:col-span-2">
                <label for="expectativas" class="form-label"
                  >Expectativas para este curso *</label
                >
                <textarea
                  id="expectativas"
                  v-model="formData.expectativas"
                  rows="3"
                  class="form-input"
                  placeholder="Descreva o que você espera aprender ou desenvolver com este curso"
                  required
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Equipamentos -->
          <div v-if="equipamentos.length > 0" class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Equipamentos Necessários</h3>
            </div>

            <div class="equipment-box mt-4">
              <p class="text-sm text-gray-500 mb-3">
                Confirme que você possui ou tem acesso aos seguintes itens:
              </p>
              <div class="space-y-2.5">
                <label
                  v-for="(item, index) in equipamentos"
                  :key="index"
                  class="equipment-item"
                >
                  <input
                    :id="'equipamento-' + index"
                    type="checkbox"
                    v-model="formData.equipamentosConfirmados[index]"
                    class="form-checkbox"
                  />
                  <span class="text-sm text-gray-700">{{ item }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Observações -->
          <div class="form-section">
            <div class="form-section-header">
              <span class="form-section-accent"></span>
              <h3 class="form-section-title">Observações Adicionais</h3>
            </div>
            <div class="form-field mt-4">
              <label for="observacoes" class="form-label"
                >Outras informações relevantes (opcional)</label
              >
              <textarea
                id="observacoes"
                v-model="formData.observacoes"
                rows="3"
                class="form-input"
                placeholder="Informe se há alguma outra informação importante para sua participação no curso"
              ></textarea>
            </div>
          </div>

          <!-- Ações -->
          <div class="form-actions">
            <button
              type="submit"
              class="btn-submit"
              :disabled="isSubmitting"
              :class="{ 'opacity-60 cursor-not-allowed': isSubmitting }"
            >
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
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
              <span>{{
                isSubmitting ? 'Enviando...' : 'Enviar Inscrição'
              }}</span>
            </button>

            <Link :href="route('cursos')" class="btn-cancel"> Cancelar </Link>
          </div>
        </form>
      </div>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
/* Header da página */
.form-page-header {
  @apply py-4 border-b border-gray-200 bg-white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.form-header-accent {
  @apply block w-1 h-6 rounded-full flex-shrink-0;
  background-color: #bea55a;
}

.form-header-title {
  @apply text-base font-semibold text-gray-800;
}

.form-header-subtitle {
  @apply text-sm text-gray-400;
}

.form-back-link {
  @apply inline-flex items-center gap-1.5 text-sm font-medium transition-colors duration-150;
  color: #bea55a;
}

.form-back-link:hover {
  color: #a38e4d;
}

/* Card */
.form-card {
  @apply bg-white rounded-lg border border-gray-200 overflow-hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

/* Banner de info */
.info-banner {
  @apply px-6 py-5 border-b border-gray-100;
  background-color: #fdfbf5;
}

.info-banner-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-6;
}

.info-banner-title {
  @apply text-xs font-semibold uppercase tracking-wide mb-3;
  color: #bea55a;
}

.info-banner-rows {
  @apply space-y-1.5;
}

.info-row {
  @apply flex gap-2 text-sm;
}

.info-row span:first-child {
  @apply font-medium text-gray-600 w-24 flex-shrink-0;
}

.info-row span:last-child {
  @apply text-gray-700;
}

/* Corpo do form */
.form-body {
  @apply p-6 space-y-6;
}

/* Seções */
.form-section {
  @apply pb-6 border-b border-gray-100 last:border-0 last:pb-0;
}

.form-section-header {
  @apply flex items-center gap-2.5;
}

.form-section-accent {
  @apply block w-1 h-4 rounded-full flex-shrink-0;
  background-color: #bea55a;
}

.form-section-title {
  @apply text-sm font-semibold text-gray-700 uppercase tracking-wide;
}

.toggle-terms-btn {
  @apply text-xs font-medium transition-colors duration-150;
  color: #bea55a;
}

.toggle-terms-btn:hover {
  color: #a38e4d;
}

/* Termos */
.terms-box {
  @apply bg-gray-50 border border-gray-200 rounded-md p-4 my-4 overflow-y-auto transition-all duration-200;
}

.terms-collapsed {
  max-height: 10rem;
}
.terms-expanded {
  max-height: none;
}

.accept-terms-label {
  @apply flex items-start gap-2 cursor-pointer;
}

/* Campos */
.form-field {
  @apply flex flex-col gap-1;
}

.form-label {
  @apply text-sm font-medium text-gray-600;
}

.form-input {
  @apply w-full text-sm border border-gray-200 rounded-md px-3 py-2.5 bg-white;
  @apply placeholder-gray-400 transition-colors duration-150;
  @apply focus:outline-none focus:border-transparent;
}

.form-input:focus {
  box-shadow: 0 0 0 2px #bea55a40;
  border-color: #bea55a;
}

.form-select {
  @apply form-input;
}

.form-checkbox {
  @apply w-4 h-4 rounded border-gray-300 flex-shrink-0 mt-0.5;
  accent-color: #bea55a;
}

/* Equipamentos */
.equipment-box {
  @apply bg-gray-50 border border-gray-100 rounded-md p-4;
}

.equipment-item {
  @apply flex items-start gap-2 cursor-pointer;
}

/* Ações */
.form-actions {
  @apply flex flex-col sm:flex-row gap-3 pt-5 border-t border-gray-100;
}

.btn-submit {
  @apply inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-md;
  @apply text-sm font-semibold text-white transition-colors duration-150;
  background-color: #bea55a;
}

.btn-submit:hover:not(:disabled) {
  background-color: #a38e4d;
}

.btn-cancel {
  @apply inline-flex items-center justify-center px-6 py-2.5 rounded-md;
  @apply text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors duration-150;
}

@media (prefers-reduced-motion: reduce) {
  .form-input,
  .btn-submit,
  .btn-cancel,
  .terms-box {
    @apply transition-none;
  }
}
</style>
