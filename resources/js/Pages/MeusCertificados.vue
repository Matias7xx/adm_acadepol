<script setup>
import { computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import SiteNavbar from './Components/SiteNavbar.vue';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';

const props = defineProps({
  certificados: {
    type: Object,
    required: true,
  },
});

const { toast } = useToast();

const formatDate = dateString => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('pt-BR');
};

const baixarCertificado = certificado => {
  try {
    window.open(route('certificados.download', certificado.id), '_blank');
    toast.success('Download iniciado!');
  } catch (error) {
    console.error('Erro no download:', error);
    toast.error('Erro ao iniciar download');
  }
};

const estatisticas = computed(() => {
  const certificados = props.certificados.data || [];
  return {
    total: certificados.length,
    horasTotais: certificados.reduce(
      (total, c) => total + (parseInt(c.carga_horaria) || 0),
      0
    ),
  };
});
</script>

<template>
  <Head title="Meus Certificados" />
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
          <h1 class="page-header-title">Meus Certificados</h1>
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
          Início
        </Link>
      </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Estatísticas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-7">
        <!-- Total -->
        <div class="stat-card">
          <div class="stat-icon-wrap">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="stat-icon"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.75"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
          </div>
          <div>
            <p class="stat-label">Total de Certificados</p>
            <p class="stat-value">{{ estatisticas.total }}</p>
          </div>
        </div>

        <!-- Carga Horária -->
        <div class="stat-card">
          <div class="stat-icon-wrap">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="stat-icon"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.75"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
          <div>
            <p class="stat-label">Carga Horária Total</p>
            <p class="stat-value">
              {{ estatisticas.horasTotais }}<span class="stat-unit">h</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Aviso sobre o sistema -->
      <div class="notice-box mb-7">
        <div class="notice-icon-wrap">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <div class="notice-body">
          <h3 class="notice-title">Informação Importante sobre Certificados</h3>
          <p class="notice-text">
            No momento, serão disponibilizados
            <strong>majoritariamente</strong> os certificados emitidos a partir
            da implementação do novo sistema. Certificados de cursos anteriores
            podem não estar disponíveis aqui.
          </p>
          <p class="notice-text mt-1.5">
            <strong>Precisa de um certificado anterior?</strong>
            Entre em contato pelo
            <Link :href="route('contato.create')" class="notice-link"
              >Fale Conosco</Link
            >
            ou solicite uma
            <Link :href="route('requerimentos.create')" class="notice-link"
              >2ª via do certificado</Link
            >.
          </p>
        </div>
      </div>

      <!-- Card principal -->
      <div class="main-card">
        <!-- Header do card -->
        <div class="main-card-header">
          <span class="main-card-accent"></span>
          <h2 class="main-card-title">Seus Certificados</h2>
        </div>

        <!-- Lista -->
        <div
          v-if="certificados.data && certificados.data.length > 0"
          class="cert-list"
        >
          <div
            v-for="certificado in certificados.data"
            :key="certificado.id"
            class="cert-item group"
          >
            <!-- Ícone + nome -->
            <div class="cert-main">
              <div class="cert-icon-wrap">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="cert-icon"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.75"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
              </div>
              <div class="cert-info">
                <h3 class="cert-name">{{ certificado.nome_curso }}</h3>

                <!-- Metadados -->
                <div class="cert-meta">
                  <div class="meta-chip">
                    <svg
                      class="w-3.5 h-3.5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 0v1h6V7m-6 0H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-3"
                      />
                    </svg>
                    <span
                      >Emissão: {{ formatDate(certificado.data_emissao) }}</span
                    >
                  </div>
                  <div class="meta-chip">
                    <svg
                      class="w-3.5 h-3.5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                    <span>{{ certificado.carga_horaria }}h</span>
                  </div>
                  <div class="meta-chip">
                    <svg
                      class="w-3.5 h-3.5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 0v1h6V7m-6 0H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-3"
                      />
                    </svg>
                    <span
                      >Conclusão:
                      {{ formatDate(certificado.data_conclusao_curso) }}</span
                    >
                  </div>
                </div>
              </div>
            </div>

            <!-- Botão download -->
            <button
              @click="baixarCertificado(certificado)"
              class="download-btn"
              :title="`Baixar certificado ${certificado.numero_certificado}`"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              <span>Baixar PDF</span>
            </button>
          </div>
        </div>

        <!-- Estado vazio -->
        <div v-else class="empty-state">
          <div class="empty-icon-wrap">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-10 w-10 text-gray-300"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-gray-700 mb-1">
            Nenhum certificado encontrado
          </h3>
          <p class="text-sm text-gray-400 mb-5">
            Complete um curso para receber seu certificado.
          </p>
          <Link :href="route('cursos')" class="btn-primary">
            Ver Cursos Disponíveis
          </Link>
        </div>

        <!-- Paginação -->
        <div
          v-if="
            certificados.data &&
            certificados.data.length > 0 &&
            certificados.links &&
            certificados.links.length > 3
          "
          class="pagination-wrap"
        >
          <p class="text-sm text-gray-500">
            Mostrando
            <span class="font-medium text-gray-700">{{
              certificados.from || 0
            }}</span
            >–<span class="font-medium text-gray-700">{{
              certificados.to || 0
            }}</span>
            de
            <span class="font-medium text-gray-700">{{
              certificados.total || 0
            }}</span>
            certificados
          </p>
          <nav class="flex gap-1">
            <template v-for="(link, index) in certificados.links" :key="index">
              <Link
                v-if="link.url"
                :href="link.url"
                class="page-btn"
                :class="link.active ? 'page-btn-active' : ''"
                v-html="link.label"
              />
              <span
                v-else
                class="page-btn page-btn-disabled"
                v-html="link.label"
              />
            </template>
          </nav>
        </div>
      </div>
    </div>
    <Footer />
  </div>
</template>

<style scoped>
/* Header da página */
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

/* Estatísticas */
.stat-card {
  @apply flex items-center gap-4 bg-white rounded-lg border border-gray-200 p-5;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.stat-icon-wrap {
  @apply flex-shrink-0 w-11 h-11 rounded-lg flex items-center justify-center;
  background-color: #faf5e8;
}

.stat-icon {
  @apply w-5 h-5;
  color: #bea55a;
}

.stat-label {
  @apply text-xs font-medium text-gray-500 uppercase tracking-wide;
}

.stat-value {
  @apply text-2xl font-bold text-gray-900 mt-0.5;
}

.stat-unit {
  @apply text-base font-medium text-gray-500 ml-0.5;
}

/* Aviso */
.notice-box {
  @apply flex gap-3 bg-blue-50 border border-blue-100 rounded-lg p-5;
}

.notice-icon-wrap {
  @apply flex-shrink-0 text-blue-500 mt-0.5;
}

.notice-body {
  @apply flex-1;
}

.notice-title {
  @apply text-sm font-semibold text-blue-800 mb-1.5;
}

.notice-text {
  @apply text-sm text-blue-700;
}

.notice-link {
  @apply font-medium underline underline-offset-2 transition-colors duration-150;
  color: #1d4ed8;
}
.notice-link:hover {
  color: #1e3a8a;
}

/* Card principal */
.main-card {
  @apply bg-white rounded-lg overflow-hidden border border-gray-200;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.main-card-header {
  @apply flex items-center gap-3 px-6 py-4 border-b border-gray-100;
}

.main-card-accent {
  @apply block w-1 h-5 rounded-full flex-shrink-0;
  background-color: #bea55a;
}

.main-card-title {
  @apply text-sm font-semibold text-gray-800 uppercase tracking-wide;
}

/* Lista de certificados */
.cert-list {
  @apply divide-y divide-gray-50;
}

.cert-item {
  @apply flex items-center justify-between gap-4 px-6 py-5;
  @apply transition-colors duration-150;
}

.cert-item:hover {
  @apply bg-gray-50;
}

.cert-main {
  @apply flex items-start gap-4 flex-1 min-w-0;
}

.cert-icon-wrap {
  @apply flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center;
  background-color: #faf5e8;
}

.cert-icon {
  @apply w-5 h-5;
  color: #bea55a;
}

.cert-info {
  @apply flex-1 min-w-0;
}

.cert-name {
  @apply text-sm font-semibold text-gray-800 mb-2 leading-snug;
}

.cert-meta {
  @apply flex flex-wrap gap-2;
}

.meta-chip {
  @apply inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded;
}

/* Botão download */
.download-btn {
  @apply flex-shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-md;
  @apply text-sm font-medium text-white transition-colors duration-150;
  background-color: #bea55a;
}

.download-btn:hover {
  background-color: #a38e4d;
}

/* Estado vazio */
.empty-state {
  @apply flex flex-col items-center text-center py-14 px-6;
}

.empty-icon-wrap {
  @apply w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-4 border border-gray-100;
}

/* Botão primário */
.btn-primary {
  @apply inline-flex items-center gap-2 px-5 py-2.5 rounded-md;
  @apply text-sm font-semibold text-white transition-colors duration-150;
  background-color: #bea55a;
}
.btn-primary:hover {
  background-color: #a38e4d;
}

/* Paginação */
.pagination-wrap {
  @apply flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-gray-100;
}

.page-btn {
  @apply inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm font-medium border border-gray-200 bg-white text-gray-600;
  @apply hover:border-gray-300 hover:text-gray-800 transition-colors duration-150;
  min-width: 2rem;
}

.page-btn-active {
  @apply text-white border-transparent;
  background-color: #bea55a;
}

.page-btn-disabled {
  @apply bg-gray-50 text-gray-400 cursor-default;
}

@media (prefers-reduced-motion: reduce) {
  .cert-item,
  .download-btn,
  .page-btn {
    @apply transition-none;
  }
}
</style>
