<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import Header from './Components/Header.vue';
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';
import CardSection from './CursoDetalhesComponents/CardSection.vue';
import InfoItem from './CursoDetalhesComponents/InfoItem.vue';
import CourseHeader from './CursoDetalhesComponents/CourseHeader.vue';
import EnrollmentCard from './CursoDetalhesComponents/EnrollmentCard.vue';
import SocialShareCard from './CursoDetalhesComponents/SocialShareCard.vue';

const props = defineProps({
  curso: { type: Object, required: true },
});

const courseUrl = computed(
  () => `${window.location.origin}/cursos/${props.curso.id}`
);

const formatarData = data => {
  if (!data) return 'Não definido';
  return new Date(data).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatarTextoHtml = texto => {
  if (!texto) return 'Nenhuma informação disponível';
  try {
    let lista = Array.isArray(texto)
      ? texto
      : typeof texto === 'string'
        ? JSON.parse(texto)
        : [];
    if (Array.isArray(lista) && lista.length > 0) {
      return `<ul class="list-disc list-inside space-y-2 text-gray-700">${lista.map(item => `<li class="leading-relaxed">${item}</li>`).join('')}</ul>`;
    }
  } catch {}
  if (typeof texto === 'string') {
    return texto
      .replace(/\n/g, '<br>')
      .replace(/- (.*?)(?=<br|$)/g, '<li class="ml-4">$1</li>')
      .replace(
        /(<li.*?<\/li>)/g,
        '<ul class="list-disc list-inside space-y-1 text-gray-700">$1</ul>'
      );
  }
  return 'Nenhuma informação disponível';
};

const temPreRequisitos = computed(() => {
  if (!props.curso.pre_requisitos) return false;
  try {
    const p = Array.isArray(props.curso.pre_requisitos)
      ? props.curso.pre_requisitos
      : JSON.parse(props.curso.pre_requisitos);
    return Array.isArray(p) && p.length > 0;
  } catch {
    return props.curso.pre_requisitos?.length > 0;
  }
});

const temEnxoval = computed(() => {
  if (!props.curso.enxoval) return false;
  try {
    const p = Array.isArray(props.curso.enxoval)
      ? props.curso.enxoval
      : JSON.parse(props.curso.enxoval);
    return Array.isArray(p) && p.length > 0;
  } catch {
    return props.curso.enxoval?.length > 0;
  }
});
</script>

<template>
  <Head :title="'Curso - ' + curso.nome" />
  <div class="bg-gray-50 min-h-screen pb-16">
    <Header />
    <SiteNavbar />

    <CourseHeader
      :imagem="curso.imagem"
      :nome="curso.nome"
      :modalidade="curso.modalidade"
      :status="curso.status"
    />

    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Botão de retorno -->
      <div class="relative z-10 mt-4 mb-6">
        <Link :href="route('cursos')" class="back-btn">
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Voltar para Cursos
        </Link>
      </div>

      <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        <!-- Conteúdo principal -->
        <div class="w-full lg:w-2/3 space-y-5">
          <!-- Sobre o curso -->
          <CardSection title="Sobre o curso">
            <p class="text-sm text-gray-600 leading-relaxed mb-6">
              {{ curso.descricao || 'Descrição do curso não disponível.' }}
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <InfoItem
                icon="clock"
                label="Carga Horária"
                :value="`${curso.carga_horaria} horas`"
              />
              <InfoItem
                icon="calendar"
                label="Período"
                :value="`${formatarData(curso.data_inicio)} a ${formatarData(curso.data_fim)}`"
              />
              <InfoItem
                icon="location"
                label="Localização"
                :value="curso.localizacao || 'Local a definir'"
              />
              <InfoItem
                icon="users"
                label="Capacidade"
                :value="`${curso.capacidade_maxima} participantes`"
              />
            </div>
          </CardSection>

          <!-- Pré-requisitos -->
          <CardSection title="Pré-requisitos">
            <div class="prose-sm text-gray-600">
              <div
                v-if="temPreRequisitos"
                v-html="formatarTextoHtml(curso.pre_requisitos)"
              />
              <p v-else class="text-gray-400 italic text-sm">
                Não há pré-requisitos específicos para este curso.
              </p>
            </div>
          </CardSection>

          <!-- Material necessário -->
          <CardSection title="Material necessário">
            <div class="prose-sm text-gray-600">
              <div
                v-if="temEnxoval"
                v-html="formatarTextoHtml(curso.enxoval)"
              />
              <p v-else class="text-gray-400 italic text-sm">
                Todos os materiais serão fornecidos durante o curso.
              </p>
            </div>
          </CardSection>

          <!-- Certificação -->
          <CardSection v-if="curso.certificacao" title="Certificação">
            <div class="flex items-center gap-2">
              <svg
                class="w-5 h-5 text-emerald-500 flex-shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="text-sm font-medium text-gray-700"
                >Este curso oferece certificado de conclusão</span
              >
            </div>
            <p
              v-if="curso.certificacao_modelo"
              class="text-sm text-gray-500 mt-2"
            >
              {{ curso.certificacao_modelo }}
            </p>
          </CardSection>
        </div>

        <!-- Sidebar -->
        <div class="w-full lg:w-1/3 space-y-5">
          <div class="sticky top-4 space-y-5">
            <EnrollmentCard
              :status="curso.status"
              :capacidade="curso.capacidade_maxima"
              :dataInicio="curso.data_inicio"
              :curso="curso"
            />
            <SocialShareCard
              :courseUrl="courseUrl"
              :courseTitle="`Confira o curso ${curso.nome} na Acadepol!`"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
  <Footer />
</template>

<style scoped>
.back-btn {
  @apply inline-flex items-center gap-2 px-4 py-2 rounded-md;
  @apply bg-white border border-gray-200 text-sm font-medium text-gray-600;
  @apply hover:text-gray-900 hover:border-gray-300 transition-colors duration-150;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.prose-sm ul {
  @apply list-disc list-inside space-y-1;
}

.prose-sm li {
  @apply leading-relaxed;
}
</style>
