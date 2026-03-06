<script setup>
import SideCard from './SideCard.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const noticias = ref([]);
const loading = ref(true);
const error = ref(null);
const retryCount = ref(0);
const mounted = ref(false);

const MAX_RETRIES = 1;
const RETRY_DELAY = 2000;
const MAX_PREVIEW_ITEMS = 5;

let abortController = null;

const formatDate = dateString => {
  if (!dateString) return 'Data não informada';
  try {
    let date;
    if (dateString.includes('/')) {
      const [day, month, year] = dateString.split('/');
      date = new Date(year, month - 1, day);
    } else {
      date = new Date(dateString);
    }
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  } catch (e) {
    return dateString;
  }
};

const truncateText = (text, length = 80) => {
  if (!text || typeof text !== 'string') return '';
  if (text.length <= length) return text;
  const truncated = text.substring(0, length);
  const lastSpace = truncated.lastIndexOf(' ');
  return (
    (lastSpace > length * 0.7
      ? truncated.substring(0, lastSpace)
      : truncated
    ).trim() + '...'
  );
};

const fetchNoticias = async (isRetry = false) => {
  try {
    if (abortController) abortController.abort();
    abortController = new AbortController();
    if (!isRetry) loading.value = true;
    error.value = null;

    const response = await fetch('/api/noticias-home', {
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      signal: abortController.signal,
      cache: 'no-cache',
    });

    if (!response.ok) {
      throw new Error(
        response.status === 404
          ? 'Serviço de notícias indisponível'
          : `Erro ${response.status}: ${response.statusText || 'Não foi possível carregar as notícias'}`
      );
    }

    const data = await response.json();
    if (!Array.isArray(data))
      throw new Error('Formato de dados inválido recebido do servidor');

    noticias.value = data
      .slice(0, MAX_PREVIEW_ITEMS)
      .map(n => ({
        ...n,
        id: n.id || Math.random().toString(36).substr(2, 9),
        titulo: n.titulo || 'Título não disponível',
        descricao_curta: n.descricao_curta,
        data_publicacao: formatDate(n.data_publicacao),
        visualizacoes: parseInt(n.visualizacoes) || 0,
        destaque: Boolean(n.destaque),
      }))
      .filter(n => n.titulo !== 'Título não disponível');

    loading.value = false;
    retryCount.value = 0;
  } catch (err) {
    if (err.name === 'AbortError') return;
    if (retryCount.value < MAX_RETRIES && mounted.value) {
      retryCount.value++;
      setTimeout(() => {
        if (mounted.value) fetchNoticias(true);
      }, RETRY_DELAY);
    } else {
      error.value = err.message;
      loading.value = false;
    }
  }
};

onMounted(() => {
  mounted.value = true;
  fetchNoticias();
});
onUnmounted(() => {
  mounted.value = false;
  if (abortController) abortController.abort();
});
</script>

<template>
  <section
    class="w-full bg-gray-50 py-8 lg:py-12"
    aria-labelledby="noticias-titulo"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col-reverse lg:flex-row gap-8 lg:gap-12">
        <!-- Coluna principal -->
        <main class="w-full lg:w-2/3">
          <!-- Título da seção -->
          <div class="flex items-center gap-2.5 mb-6">
            <span class="section-accent"></span>
            <h2 id="noticias-titulo" class="section-title">Últimas Notícias</h2>
          </div>

          <!-- Carregando -->
          <div
            v-if="loading"
            class="flex flex-col items-center py-16"
            aria-live="polite"
            aria-busy="true"
          >
            <div
              class="animate-spin rounded-full h-10 w-10 border-2 border-gray-200 border-t-[#bea55a]"
              role="status"
            ></div>
            <p class="mt-3 text-sm text-gray-500">Carregando notícias...</p>
            <span class="sr-only">Carregando notícias...</span>
          </div>

          <!-- Erro -->
          <div
            v-else-if="error"
            class="flex gap-3 bg-red-50 border border-red-100 rounded-lg p-5"
            aria-live="assertive"
          >
            <svg
              class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <div class="flex-1">
              <p class="text-sm font-semibold text-red-700 mb-1">
                Erro ao carregar notícias
              </p>
              <p class="text-sm text-red-600 mb-3">{{ error }}</p>
              <div class="flex gap-2">
                <button
                  @click="fetchNoticias"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 transition-colors"
                >
                  <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                  </svg>
                  Tentar novamente
                </button>
                <Link
                  href="/noticias"
                  class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50 transition-colors"
                >
                  Ver todas as notícias
                </Link>
              </div>
            </div>
          </div>

          <!-- Vazio -->
          <div
            v-else-if="noticias.length === 0"
            class="flex flex-col items-center text-center py-14 bg-white rounded-lg border border-gray-200"
            style="box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06)"
          >
            <div
              class="w-14 h-14 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center mb-4"
            >
              <svg
                class="h-7 w-7 text-gray-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
                />
              </svg>
            </div>
            <p class="text-sm font-semibold text-gray-700 mb-1">
              Nenhuma notícia encontrada
            </p>
            <p class="text-sm text-gray-400 mb-5">
              Não há notícias publicadas no momento. Volte em breve!
            </p>
            <Link href="/noticias" class="btn-primary"
              >Explorar arquivo de notícias</Link
            >
          </div>

          <!-- Lista de notícias -->
          <div v-else class="space-y-3">
            <article
              v-for="noticia in noticias"
              :key="noticia.id"
              class="news-card group"
            >
              <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-2">
                <svg
                  class="h-3 w-3 flex-shrink-0"
                  style="color: #bea55a"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                <time :datetime="noticia.data_publicacao">{{
                  noticia.data_publicacao
                }}</time>
              </div>

              <Link :href="`/noticias/${noticia.id}`">
                <h3 class="news-title">{{ noticia.titulo }}</h3>
              </Link>

              <p
                v-if="
                  noticia.descricao_curta &&
                  noticia.descricao_curta !== 'Descrição não disponível'
                "
                class="news-desc"
              >
                <span class="sm:hidden">{{
                  truncateText(noticia.descricao_curta, 100)
                }}</span>
                <span class="hidden sm:inline">{{
                  truncateText(noticia.descricao_curta, 200)
                }}</span>
              </p>

              <Link :href="`/noticias/${noticia.id}`" class="read-more-link">
                Leia mais
                <svg
                  class="h-3 w-3 group-hover:translate-x-0.5 transition-transform duration-150"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 8l4 4m0 0l-4 4m4-4H3"
                  />
                </svg>
              </Link>
            </article>
          </div>

          <!-- Ver mais -->
          <div v-if="noticias.length > 0" class="mt-6 flex justify-center">
            <Link href="/noticias" class="btn-secondary"
              >Ver todas as notícias</Link
            >
          </div>
        </main>

        <!-- Sidebar -->
        <aside class="w-full lg:w-1/3">
          <div class="sticky top-8">
            <SideCard />
          </div>
        </aside>
      </div>
    </div>
  </section>
</template>

<style scoped>
.section-accent {
  @apply block w-1 h-5 rounded-full flex-shrink-0;
  background-color: #bea55a;
}
.section-title {
  @apply text-base font-semibold text-gray-700 uppercase tracking-wide;
}

.news-card {
  @apply bg-white rounded-lg border border-gray-200 p-4 transition-colors duration-150;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.news-card:hover {
  @apply border-gray-300;
}

.news-title {
  @apply text-sm font-semibold text-gray-800 leading-snug line-clamp-2 mb-2;
  @apply transition-colors duration-150;
}
.group:hover .news-title {
  color: #bea55a;
}

.news-desc {
  @apply text-xs text-gray-500 leading-relaxed line-clamp-2 mb-3;
}

.read-more-link {
  @apply inline-flex items-center gap-1 text-xs font-medium transition-colors duration-150;
  color: #bea55a;
}
.read-more-link:hover {
  color: #a38e4d;
}

.btn-primary {
  @apply inline-flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-semibold text-white transition-colors duration-150;
  background-color: #bea55a;
}
.btn-primary:hover {
  background-color: #a38e4d;
}

.btn-secondary {
  @apply inline-flex items-center px-5 py-2 rounded-md text-sm font-medium text-gray-600;
  @apply bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-colors duration-150;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

a:focus-visible,
button:focus-visible {
  outline: 2px solid #bea55a;
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .news-card,
  .read-more-link,
  .btn-primary,
  .btn-secondary {
    @apply transition-none;
  }
}
</style>
