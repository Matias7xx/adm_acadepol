<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import Header from './Header.vue';
import SiteNavbar from './SiteNavbar.vue';
import Footer from './Footer.vue';

const noticias = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);
const itemsPerPage = ref(6);
const retryCount = ref(0);
const mounted = ref(false);

const MAX_RETRIES = 2;
const RETRY_DELAY = 2000;

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

const truncateText = (text, length = 100) => {
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

const updateUrlParams = () => {
  if (typeof window === 'undefined') return;
  const params = new URLSearchParams(window.location.search);
  currentPage.value !== 1
    ? params.set('page', currentPage.value)
    : params.delete('page');
  searchQuery.value.trim()
    ? params.set('search', searchQuery.value.trim())
    : params.delete('search');
  const url =
    window.location.pathname +
    (params.toString() ? `?${params.toString()}` : '');
  window.history.pushState({}, '', url);
};

const fetchNoticias = async (isRetry = false) => {
  try {
    if (abortController) abortController.abort();
    abortController = new AbortController();
    if (!isRetry) loading.value = true;
    error.value = null;

    let url = `/api/noticias?page=${currentPage.value}`;
    if (searchQuery.value)
      url += `&search=${encodeURIComponent(searchQuery.value)}`;

    const response = await fetch(url, {
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
    if (!data || !Array.isArray(data.data))
      throw new Error('Formato de dados inválido recebido do servidor');

    noticias.value = data.data
      .map(n => ({
        ...n,
        id: n.id || Math.random().toString(36).substr(2, 9),
        titulo: n.titulo || 'Título não disponível',
        descricao_curta: n.descricao_curta || 'Descrição não disponível',
        data_publicacao: formatDate(n.data_publicacao),
        visualizacoes: parseInt(n.visualizacoes) || 0,
        destaque: Boolean(n.destaque),
      }))
      .filter(n => n.titulo !== 'Título não disponível');

    currentPage.value = data.current_page || 1;
    totalPages.value = data.last_page || 1;
    totalItems.value = data.total || 0;
    itemsPerPage.value = data.per_page || 6;

    loading.value = false;
    retryCount.value = 0;
    updateUrlParams();
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

const handleSearch = () => {
  currentPage.value = 1;
  fetchNoticias();
};
const clearSearch = () => {
  searchQuery.value = '';
  handleSearch();
};

const changePage = page => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    currentPage.value = page;
    fetchNoticias();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const debounce = (fn, delay) => {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), delay);
  };
};
const debouncedSearch = debounce(() => {
  if (mounted.value) handleSearch();
}, 500);

watch(searchQuery, (nv, ov) => {
  if (mounted.value && nv !== ov) debouncedSearch();
});

const getPaginationPages = () => {
  const pages = [];
  const current = currentPage.value;
  const total = totalPages.value;

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i);
  } else {
    pages.push(1);
    if (current > 3) pages.push('...');
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    if (current < total - 2) pages.push('...');
    if (total > 1) pages.push(total);
  }
  return pages;
};

const initFromUrl = () => {
  if (typeof window === 'undefined') return;
  const params = new URLSearchParams(window.location.search);
  const pageParam = params.get('page');
  if (pageParam && !isNaN(parseInt(pageParam)))
    currentPage.value = Math.max(1, parseInt(pageParam));
  const searchParam = params.get('search');
  if (searchParam) searchQuery.value = searchParam.trim();
  fetchNoticias();
};

onMounted(() => {
  mounted.value = true;
  initFromUrl();
});
onUnmounted(() => {
  mounted.value = false;
  if (abortController) abortController.abort();
});
</script>

<template>
  <Head title="Todas as Notícias — ACADEPOL" />
  <SiteNavbar />
  <Header />

  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Voltar -->
      <Link href="/" class="back-link mb-6 inline-flex">
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
        Voltar para Home
      </Link>

      <!-- Cabeçalho + busca -->
      <div class="header-card mb-6">
        <div
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
          <div class="flex items-center gap-3">
            <span class="section-accent"></span>
            <div>
              <h1 class="section-title">Todas as Notícias</h1>
              <p class="text-sm text-gray-400 mt-0.5">
                Fique atualizado sobre as atividades da ACADEPOL
              </p>
            </div>
          </div>

          <!-- Busca -->
          <div class="relative w-full sm:w-72">
            <div
              class="absolute inset-y-0 left-3 flex items-center pointer-events-none"
            >
              <svg
                class="h-4 w-4 text-gray-400"
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
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar notícias..."
              class="search-input"
            />
            <button
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute inset-y-0 right-2 flex items-center p-1 text-gray-400 hover:text-gray-600 transition-colors"
              aria-label="Limpar busca"
            >
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
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Carregando -->
      <div
        v-if="loading"
        class="flex flex-col items-center py-20"
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
        class="flex gap-3 bg-red-50 border border-red-100 rounded-lg p-5 mb-6"
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
        </div>
      </div>

      <template v-else>
        <!-- Banner de busca ativa -->
        <div v-if="searchQuery" class="search-banner mb-5">
          <p class="text-sm text-gray-600">
            <span v-if="totalItems === 0">Nenhum resultado para </span>
            <span v-else
              >{{ totalItems }} resultado{{ totalItems !== 1 ? 's' : '' }} para
            </span>
            <strong class="text-gray-800">"{{ searchQuery }}"</strong>
          </p>
          <button @click="clearSearch" class="clear-search-btn">
            <svg
              class="h-3.5 w-3.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            Limpar
          </button>
        </div>

        <!-- Vazio -->
        <div
          v-if="noticias.length === 0"
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
            {{
              searchQuery
                ? `Não encontramos resultados para "${searchQuery}". Tente uma busca diferente.`
                : 'Não há notícias publicadas no momento.'
            }}
          </p>
          <button v-if="searchQuery" @click="clearSearch" class="btn-primary">
            Limpar busca
          </button>
        </div>

        <!-- Lista -->
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
              <h2 class="news-title">{{ noticia.titulo }}</h2>
            </Link>

            <!-- <p class="news-desc">
              <span class="sm:hidden">{{ truncateText(noticia.descricao_curta, 100) }}</span>
              <span class="hidden sm:inline">{{ truncateText(noticia.descricao_curta, 200) }}</span>
            </p> -->

            <Link
              :href="`/noticias/${noticia.id}`"
              class="read-more-link"
              :aria-label="`Leia a notícia completa: ${noticia.titulo}`"
            >
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

        <!-- Paginação -->
        <div
          v-if="totalPages > 1"
          class="flex flex-col sm:flex-row items-center justify-between mt-8 gap-4"
        >
          <p class="text-sm text-gray-500 order-2 sm:order-1">
            Mostrando
            <span class="font-medium text-gray-700">{{
              (currentPage - 1) * itemsPerPage + 1
            }}</span
            >–<span class="font-medium text-gray-700">{{
              Math.min(currentPage * itemsPerPage, totalItems)
            }}</span>
            de
            <span class="font-medium text-gray-700">{{ totalItems }}</span>
            resultado{{ totalItems !== 1 ? 's' : '' }}
          </p>

          <nav class="flex gap-1 order-1 sm:order-2" aria-label="Paginação">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="page-btn"
              :class="currentPage === 1 ? 'page-btn-disabled' : ''"
            >
              <span class="sr-only">Anterior</span>
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
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>

            <template
              v-for="(page, index) in getPaginationPages()"
              :key="`page-${index}`"
            >
              <button
                v-if="typeof page === 'number'"
                @click="changePage(page)"
                :aria-current="page === currentPage ? 'page' : undefined"
                class="page-btn"
                :class="page === currentPage ? 'page-btn-active' : ''"
              >
                {{ page }}
              </button>
              <span v-else class="page-btn page-btn-disabled">…</span>
            </template>

            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="page-btn"
              :class="currentPage === totalPages ? 'page-btn-disabled' : ''"
            >
              <span class="sr-only">Próximo</span>
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
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </nav>
        </div>
      </template>
    </div>
  </div>

  <Footer />
</template>

<style scoped>
.back-link {
  @apply inline-flex items-center gap-1.5 text-sm font-medium transition-colors duration-150 text-gray-500 hover:text-gray-700;
}

.header-card {
  @apply bg-white rounded-lg border border-gray-200 p-5;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.section-accent {
  @apply block w-1 h-6 rounded-full flex-shrink-0;
  background-color: #bea55a;
}
.section-title {
  @apply text-sm font-semibold text-gray-800 uppercase tracking-wide;
}

.search-input {
  @apply w-full pl-9 pr-9 py-2.5 text-sm border border-gray-200 rounded-md bg-white placeholder-gray-400;
  @apply focus:outline-none focus:border-transparent transition-colors duration-150;
}
.search-input:focus {
  box-shadow: 0 0 0 2px #bea55a40;
  border-color: #bea55a;
}

.search-banner {
  @apply flex items-center justify-between px-4 py-3 bg-white border border-gray-200 rounded-lg;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.clear-search-btn {
  @apply inline-flex items-center gap-1.5 text-xs font-medium transition-colors duration-150;
  color: #bea55a;
}
.clear-search-btn:hover {
  color: #a38e4d;
}

.news-card {
  @apply bg-white rounded-lg border border-gray-200 p-4 transition-colors duration-150;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.news-card:hover {
  @apply border-gray-300;
}

.news-title {
  @apply text-sm font-semibold text-gray-800 leading-snug line-clamp-2 mb-2 transition-colors duration-150;
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

.page-btn {
  @apply inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-md text-sm font-medium;
  @apply border border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-800 transition-colors duration-150;
}
.page-btn-active {
  @apply text-white border-transparent;
  background-color: #bea55a;
}
.page-btn-disabled {
  @apply bg-gray-50 text-gray-300 cursor-not-allowed pointer-events-none;
}

a:focus-visible,
button:focus-visible {
  outline: 2px solid #bea55a;
  outline-offset: 2px;
}

::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: #f5f5f5;
  border-radius: 3px;
}
::-webkit-scrollbar-thumb {
  background: #d4bc84;
  border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
  background: #bea55a;
}

@media (prefers-reduced-motion: reduce) {
  .news-card,
  .read-more-link,
  .btn-primary,
  .page-btn {
    @apply transition-none;
  }
}
</style>
