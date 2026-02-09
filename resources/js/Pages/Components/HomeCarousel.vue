<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';

const props = defineProps({
  lazy: {
    type: Boolean,
    default: true,
  },
});

const newsItems = ref([]);
const loading = ref(true);
const error = ref(null);
const mounted = ref(false);
const isMobile = ref(false);

const imageStates = ref(new Map());
const preloadedImages = ref(new Set());

const PLACEHOLDER_LOCAL = '/images/placeholder-news2.png';

const showSkeleton = computed(() => loading.value && mounted.value);
const showNews = computed(
  () => !loading.value && !error.value && newsItems.value.length > 0
);
const showEmpty = computed(
  () => !loading.value && !error.value && newsItems.value.length === 0
);

// Detectar mobile
const checkMobile = () => {
  isMobile.value = window.innerWidth < 768;
};

const isImageReady = (imageUrl, itemId) => {
  if (!imageUrl || imageUrl === PLACEHOLDER_LOCAL) return true;

  const cacheKey = `${itemId}-${imageUrl}`;
  return (
    preloadedImages.value.has(cacheKey) ||
    imageStates.value.get(cacheKey)?.loaded
  );
};

const preloadImage = (imageUrl, itemId) => {
  return new Promise((resolve, reject) => {
    if (!imageUrl || imageUrl === PLACEHOLDER_LOCAL) {
      resolve();
      return;
    }

    const cacheKey = `${itemId}-${imageUrl}`;

    if (preloadedImages.value.has(cacheKey)) {
      resolve();
      return;
    }

    const img = new Image();

    img.onload = () => {
      preloadedImages.value.add(cacheKey);
      imageStates.value.set(cacheKey, { loaded: true, error: false });
      resolve();
    };

    img.onerror = () => {
      imageStates.value.set(cacheKey, { loaded: false, error: true });
      reject();
    };

    img.src = imageUrl;
  });
};

const fetchDestacadas = async () => {
  try {
    loading.value = true;
    error.value = null;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    const response = await fetch('/api/ultimas-noticias', {
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
        'Cache-Control': 'no-cache',
      },
    });

    clearTimeout(timeoutId);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    if (!Array.isArray(data)) {
      throw new Error('Formato de dados inválido recebido do servidor');
    }

    // Filtra apenas as notícias destacadas e limita a 2
    const destacadas = data
      .filter(item => item?.destaque && item?.id)
      .slice(0, 2);

    const formattedItems = destacadas
      .map((noticia, index) => ({
        id: noticia.id || `item-${index}`,
        title: noticia.titulo || 'Título não disponível',
        excerpt: noticia.descricao_curta || '',
        image: noticia.imagem || PLACEHOLDER_LOCAL,
        originalImage: noticia.imagem,
        link: `/noticias/${noticia.id}`,
      }))
      .filter(item => item.title && item.id);

    newsItems.value = formattedItems;

    // Precarregar imagens
    if (formattedItems.length > 0) {
      for (const item of formattedItems) {
        if (item.originalImage && item.originalImage !== PLACEHOLDER_LOCAL) {
          try {
            await preloadImage(item.originalImage, item.id);
            item.image = item.originalImage;
          } catch (e) {
            console.warn('Falha ao carregar imagem, usando placeholder local');
            item.image = PLACEHOLDER_LOCAL;
          }
        }
      }
    }

    await nextTick();
    loading.value = false;
  } catch (err) {
    console.error('Erro ao carregar notícias:', err);
    error.value =
      err.name === 'AbortError'
        ? 'Tempo limite excedido. Verifique sua conexão.'
        : `Erro ao carregar notícias: ${err.message}`;
    loading.value = false;
  }
};

const handleImageLoad = index => {
  const item = newsItems.value[index];
  if (item) {
    const cacheKey = `${item.id}-${item.image}`;
    imageStates.value.set(cacheKey, { loaded: true, error: false });
    preloadedImages.value.add(cacheKey);
  }
};

const handleImageError = (event, index) => {
  console.warn(`Erro ao carregar imagem do item ${index}`);
  const item = newsItems.value[index];

  if (item) {
    const currentSrc = event.target.src;

    if (currentSrc !== PLACEHOLDER_LOCAL) {
      console.log('Tentando placeholder local');
      item.image = PLACEHOLDER_LOCAL;
      const cacheKey = `${item.id}-${PLACEHOLDER_LOCAL}`;
      imageStates.value.set(cacheKey, { loaded: false, error: true });
    }
  }
};

onMounted(async () => {
  mounted.value = true;
  checkMobile();
  window.addEventListener('resize', checkMobile);

  await fetchDestacadas();
});
</script>

<template>
  <div class="news-section">
    <!-- Skeleton Loading -->
    <div v-if="showSkeleton" class="news-skeleton">
        <div class="skeleton-grid single-item">
            <div class="skeleton-card-simple">
                <div class="skeleton-shimmer"></div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div v-else-if="showNews" class="news-container">
      <!-- <div class="mb-6 md:mb-8 lg:mb-8">
        <div class="flex items-center gap-4">
          <hr class="flex-1 border-t border-gray-300" />
          <h2
            id="noticias-titulo"
            class="text-gray-700 font-semibold text-xl sm:text-2xl tracking-wide uppercase whitespace-nowrap"
          >
            Notícias em Destaque
          </h2>
          <hr class="flex-1 border-t border-gray-300" />
        </div>
      </div> -->
      <div
        class="news-grid"
        :class="{ 'single-item': newsItems.length === 1 }"
      >
        <a
          v-for="(item, index) in newsItems"
          :key="item.id"
          :href="item.link"
          class="news-card"
        >
          <div class="card-image-container">
            <img
              :src="item.image"
              :alt="item.title"
              class="card-image"
              :class="{ 'image-loading': !isImageReady(item.image, item.id) }"
              loading="lazy"
              @load="handleImageLoad(index)"
              @error="handleImageError($event, index)"
            />
            <div class="card-overlay"></div>
            <div class="card-content">
              <h3 class="card-title">{{ item.title }}</h3>
              <span class="card-link">
                Leia mais
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="link-arrow"
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
              </span>
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="news-error">
      <div class="error-content">
        <svg
          class="error-icon"
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
        <p class="error-title">Erro ao carregar notícias</p>
        <p class="error-message">{{ error }}</p>
        <button @click="fetchDestacadas" class="error-retry-btn">
          Tentar Novamente
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="showEmpty" class="news-empty">
      <div class="empty-content">
        <svg
          class="empty-icon"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
          />
        </svg>
        <p class="empty-title">Não há notícias em destaque no momento.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.news-section {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative mb-6;
}

.news-container {
  @apply relative w-full;
}

.news-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
  margin: 0;
  padding: 0;
}

.news-grid.single-item {
  @apply md:grid-cols-1 md:max-w-xl md:mx-auto;
}

.news-card {
  @apply relative overflow-hidden rounded-lg shadow-lg;
  border: none;
  outline: none;
}

.card-image-container {
  @apply relative w-full;
  aspect-ratio: 3 / 4;
  max-height: 400px;
}

@media (max-width: 768px) {
  .card-image-container {
    max-height: 250px; /* 350 ou 250 */
  }
}

.card-image {
  @apply w-full object-cover transition-opacity duration-300; /* h-full (pega mais embaixo) ou h-fit (pega mais em cima) */
  opacity: 1;
}

.image-loading {
  filter: brightness(0.98) contrast(1.02);
  transition: filter 0.5s ease-in-out;
}

.card-overlay {
  @apply absolute inset-0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.85) 0%,
    rgba(0, 0, 0, 0.85) 25%,
    rgba(0, 0, 0, 0.2) 30%,
    transparent 100%
  );
}

.card-content {
  @apply absolute bottom-0 left-0 right-0 p-3 sm:p-4 text-white z-10;
}

.card-title {
  @apply text-sm sm:text-base md:text-lg font-bold mb-2 line-clamp-2 leading-tight;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.card-link {
  @apply inline-flex items-center text-[#f0cf6c] hover:text-[#b99e4d] transition-colors text-sm font-medium;
}

.link-arrow {
  @apply w-4 h-4 ml-1 transition-transform;
}

/* Skeleton Loading */
.news-skeleton {
  @apply relative overflow-hidden w-full rounded-lg;
}

.skeleton-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.skeleton-grid.single-item {
  @apply md:grid-cols-1 md:max-w-xl md:mx-auto;
}

.skeleton-card-simple {
  @apply relative bg-gray-200 rounded-lg overflow-hidden;
  aspect-ratio: 3 / 4;
  max-height: 350px;
}

@media (max-width: 768px) {
  .skeleton-card-simple {
    max-height: 300px;
  }
}

.skeleton-shimmer {
  @apply absolute inset-0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.6) 50%,
    transparent 100%
  );
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

/* Estados de erro/vazio */
.news-error,
.news-empty {
  @apply relative shadow-xl overflow-hidden w-full h-64 sm:h-80 bg-gray-50 flex items-center justify-center rounded-lg border-2 border-dashed border-gray-200;
}

.error-content,
.empty-content {
  @apply text-center p-6 max-w-md;
}

.error-icon,
.empty-icon {
  @apply h-12 w-12 mx-auto mb-3 text-gray-400;
}

.error-title,
.empty-title {
  @apply text-lg font-semibold text-gray-700 mb-2;
}

.error-message,
.empty-message {
  @apply text-gray-500 mb-4;
}

.error-retry-btn {
  @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (max-width: 768px) {
  .card-content {
    @apply p-2;
  }

  .card-title {
    @apply text-xs line-clamp-2;
  }

  .card-link {
    @apply text-xs;
  }

  .link-arrow {
    @apply w-3 h-3;
  }
}

@media (hover: hover) {
  .card-link:hover .link-arrow {
    @apply translate-x-1;
  }

  .news-card:hover .card-image {
    @apply scale-105;
    transition: transform 0.6s ease-out;
  }
}

@media (prefers-reduced-motion: reduce) {
  .card-image,
  .link-arrow {
    @apply transition-none;
    animation: none !important;
  }

  .skeleton-shimmer {
    animation: none;
  }
}

.card-image {
  will-change: opacity, transform;
  backface-visibility: hidden;
  transform: translateZ(0);
}

.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}
</style>
