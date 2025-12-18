<script setup>
import {
  ref,
  onMounted,
  onBeforeUnmount,
  computed,
  watch,
  nextTick,
} from 'vue';

const props = defineProps({
  autoPlayDuration: {
    type: Number,
    default: 5000,
  },
  maxItems: {
    type: Number,
    default: 3,
  },
  lazy: {
    type: Boolean,
    default: true,
  },
});

const currentIndex = ref(0);
const autoPlayInterval = ref(null);
const newsItems = ref([]);
const loading = ref(true);
const error = ref(null);
const isPlaying = ref(true);
const touchStartX = ref(0);
const touchEndX = ref(0);
const retryCount = ref(0);
const maxRetries = 1;
const mounted = ref(false);

const imageStates = ref(new Map()); // Controla estado de cada imagem
const preloadedImages = ref(new Set()); // Cache de imagens carregadas

const PLACEHOLDER_LOCAL = '/images/placeholder-news2.png';
const PLACEHOLDER_CDN =
  'https://images.unsplash.com/photo-1586339949916-3e9457bef6d3?w=800&h=600&fit=crop&crop=center&auto=format&q=80&cs=tinysrgb';

const showSkeleton = computed(() => loading.value && mounted.value);
const showCarousel = computed(
  () => !loading.value && !error.value && newsItems.value.length > 0
);
const showEmpty = computed(
  () => !loading.value && !error.value && newsItems.value.length === 0
);
const hasMultipleItems = computed(() => newsItems.value.length > 1);
const showControls = computed(() => hasMultipleItems.value && !loading.value);

// verificar se imagem já foi carregada
const isImageReady = (imageUrl, itemId) => {
  if (
    !imageUrl ||
    imageUrl === PLACEHOLDER_LOCAL ||
    imageUrl === PLACEHOLDER_CDN
  )
    return true;

  const cacheKey = `${itemId}-${imageUrl}`;
  return (
    preloadedImages.value.has(cacheKey) ||
    imageStates.value.get(cacheKey)?.loaded
  );
};

// Preload de imagens
const preloadImage = (imageUrl, itemId) => {
  return new Promise((resolve, reject) => {
    if (
      !imageUrl ||
      imageUrl === PLACEHOLDER_LOCAL ||
      imageUrl === PLACEHOLDER_CDN
    ) {
      resolve();
      return;
    }

    const cacheKey = `${itemId}-${imageUrl}`;

    // Se já foi carregada, resolve
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

// Buscar notícias com preload
const fetchDestacadas = async (attempt = 1) => {
  try {
    if (attempt === 1 && !mounted.value) {
      loading.value = true;
    } else if (attempt > 1) {
      loading.value = true;
    }

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

    const destacadas = data.filter(item => item?.destaque && item?.id);
    const finalItems =
      destacadas.length > 0 ? destacadas : data.filter(item => item?.id);

    // Formatar dados com hierarquia: Original > Local > CDN
    const formattedItems = finalItems
      .slice(0, props.maxItems)
      .map((noticia, index) => ({
        id: noticia.id || `item-${index}`,
        title: noticia.titulo || 'Título não disponível',
        excerpt: noticia.descricao_curta || '',
        image: noticia.imagem || PLACEHOLDER_LOCAL,
        originalImage: noticia.imagem, // Guardar URL original
        link: `/noticias/${noticia.id}`,
      }))
      .filter(item => item.title && item.id);

    // Se não há notícias válidas, define array vazio e exibe estado "sem notícias"
    newsItems.value = formattedItems;

    // Preload das imagens apenas se houver notícias
    if (formattedItems.length > 0) {
      // Preload da primeira imagem (slide atual)
      const firstItem = formattedItems[0];
      if (
        firstItem.originalImage &&
        firstItem.originalImage !== PLACEHOLDER_LOCAL
      ) {
        try {
          await preloadImage(firstItem.originalImage, firstItem.id);
          firstItem.image = firstItem.originalImage;
        } catch (e) {
          console.warn(
            'Falha ao carregar primeira imagem, usando placeholder local'
          );
          // Se falhar, verifica se o local existe, senão usa CDN
          firstItem.image = PLACEHOLDER_LOCAL;
        }
      }

      // Preload das demais imagens em background
      formattedItems.slice(1).forEach(async item => {
        if (item.originalImage && item.originalImage !== PLACEHOLDER_LOCAL) {
          try {
            await preloadImage(item.originalImage, item.id);
            // Atualizar a imagem quando carregada
            const itemIndex = newsItems.value.findIndex(
              newsItem => newsItem.id === item.id
            );
            if (itemIndex !== -1) {
              newsItems.value[itemIndex].image = item.originalImage;
            }
          } catch (e) {
            console.warn(
              `Falha ao carregar imagem do item ${item.id}, mantendo placeholder local`
            );
          }
        }
      });
    }

    if (currentIndex.value >= newsItems.value.length) {
      currentIndex.value = 0;
    }

    await nextTick();
    loading.value = false;
    retryCount.value = 0;
  } catch (err) {
    console.error(`Erro ao carregar notícias (tentativa ${attempt}):`, err);

    if (attempt < maxRetries && err.name !== 'AbortError') {
      retryCount.value = attempt;
      setTimeout(() => fetchDestacadas(attempt + 1), 2000 * attempt);
    } else {
      error.value =
        err.name === 'AbortError'
          ? 'Tempo limite excedido. Verifique sua conexão.'
          : `Erro ao carregar notícias: ${err.message}`;
      loading.value = false;
    }
  }
};

// Navegação
const nextSlide = () => {
  if (!hasMultipleItems.value) return;
  currentIndex.value = (currentIndex.value + 1) % newsItems.value.length;
};

const prevSlide = () => {
  if (!hasMultipleItems.value) return;
  currentIndex.value =
    (currentIndex.value - 1 + newsItems.value.length) % newsItems.value.length;
};

const goToSlide = index => {
  if (index >= 0 && index < newsItems.value.length) {
    currentIndex.value = index;
  }
};

// Auto-play
const startAutoPlay = () => {
  if (!hasMultipleItems.value || !isPlaying.value) return;

  stopAutoPlay();
  autoPlayInterval.value = setInterval(() => {
    if (isPlaying.value && hasMultipleItems.value) {
      nextSlide();
    }
  }, props.autoPlayDuration);
};

const stopAutoPlay = () => {
  if (autoPlayInterval.value) {
    clearInterval(autoPlayInterval.value);
    autoPlayInterval.value = null;
  }
};

const toggleAutoPlay = () => {
  isPlaying.value = !isPlaying.value;
  if (isPlaying.value) {
    startAutoPlay();
  } else {
    stopAutoPlay();
  }
};

// Touch/Swipe support
const handleTouchStart = e => {
  touchStartX.value = e.touches[0].clientX;
  stopAutoPlay();
};

const handleTouchEnd = e => {
  touchEndX.value = e.changedTouches[0].clientX;
  handleSwipe();
  if (isPlaying.value) {
    startAutoPlay();
  }
};

const handleSwipe = () => {
  const swipeThreshold = 50;
  const diff = touchStartX.value - touchEndX.value;

  if (Math.abs(diff) > swipeThreshold) {
    if (diff > 0) {
      nextSlide();
    } else {
      prevSlide();
    }
  }
};

// Keyboard navigation
const handleKeyDown = e => {
  if (!mounted.value) return;

  switch (e.key) {
    case 'ArrowLeft':
      e.preventDefault();
      prevSlide();
      break;
    case 'ArrowRight':
      e.preventDefault();
      nextSlide();
      break;
    case ' ':
      e.preventDefault();
      toggleAutoPlay();
      break;
    case 'Home':
      e.preventDefault();
      goToSlide(0);
      break;
    case 'End':
      e.preventDefault();
      goToSlide(newsItems.value.length - 1);
      break;
  }
};

// imagens com hierarquia correta
const handleImageLoad = index => {
  const item = newsItems.value[index];
  if (item) {
    const cacheKey = `${item.id}-${item.image}`;
    imageStates.value.set(cacheKey, { loaded: true, error: false });
    preloadedImages.value.add(cacheKey);
  }
};

const handleImageError = (event, index) => {
  console.warn(`Erro ao carregar imagem do slide ${index}`);
  const item = newsItems.value[index];

  if (item) {
    const currentSrc = event.target.src;

    // Hierarquia: Original > Placeholder Local > Placeholder CDN
    if (currentSrc === item.originalImage) {
      // Se falhou a original, tenta o placeholder local
      console.log('Tentando placeholder local...');
      event.target.src = PLACEHOLDER_LOCAL;
    } else if (currentSrc === PLACEHOLDER_LOCAL) {
      // Se falhou o local, usa o CDN
      console.log('Tentando placeholder CDN...');
      event.target.src = PLACEHOLDER_CDN;
    }
    // Se chegou aqui e ainda falhou, mantém o CDN

    const cacheKey = `${item.id}-${item.image}`;
    imageStates.value.set(cacheKey, { loaded: true, error: true });
  }
};

// Preload inteligente da próxima imagem
const preloadNextImage = async () => {
  if (!props.lazy) return;

  const nextIndex = (currentIndex.value + 1) % newsItems.value.length;
  const nextItem = newsItems.value[nextIndex];

  if (nextItem?.originalImage && nextItem.image === PLACEHOLDER_LOCAL) {
    try {
      await preloadImage(nextItem.originalImage, nextItem.id);
      nextItem.image = nextItem.originalImage;
    } catch (e) {
      console.warn(
        `Falha ao preload da próxima imagem: ${nextItem.id}, mantendo placeholder local`
      );
    }
  }
};

// Watchers
watch(currentIndex, () => {
  preloadNextImage();
});

watch(
  () => newsItems.value.length,
  newLength => {
    if (newLength > 0 && isPlaying.value) {
      startAutoPlay();
    }
  }
);

// Observer
let intersectionObserver = null;

const setupIntersectionObserver = () => {
  if ('IntersectionObserver' in window) {
    intersectionObserver = new IntersectionObserver(
      entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            if (isPlaying.value) startAutoPlay();
          } else {
            stopAutoPlay();
          }
        });
      },
      { threshold: 0.5 }
    );
  }
};

// Lifecycle
onMounted(async () => {
  mounted.value = true;
  await nextTick();

  fetchDestacadas();
  setupIntersectionObserver();

  window.addEventListener('keydown', handleKeyDown);

  await nextTick();
  if (intersectionObserver) {
    const carousel = document.querySelector('.carousel-container');
    if (carousel) {
      intersectionObserver.observe(carousel);
    }
  }
});

onBeforeUnmount(() => {
  mounted.value = false;
  stopAutoPlay();
  window.removeEventListener('keydown', handleKeyDown);

  if (intersectionObserver) {
    intersectionObserver.disconnect();
  }

  // Limpar caches
  imageStates.value.clear();
  preloadedImages.value.clear();
});

const retry = () => {
  retryCount.value = 0;
  imageStates.value.clear();
  preloadedImages.value.clear();
  fetchDestacadas();
};
</script>

<template>
  <div class="carousel-wrapper max-w-screen-xl mx-auto px-4 mt-1">
    <!-- Estado de carregamento -->
    <div v-if="showSkeleton" class="carousel-skeleton">
      <div class="skeleton-image">
        <div class="skeleton-spinner">
          <svg
            class="animate-spin h-12 w-12 text-gray-400"
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
              d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
        </div>
        <div class="skeleton-text">
          <div class="skeleton-line skeleton-title"></div>
          <div class="skeleton-line skeleton-excerpt"></div>
          <div class="skeleton-line skeleton-link"></div>
        </div>
      </div>
      <p class="text-center text-gray-500 mt-2 text-sm">
        Carregando notícias...
        <span v-if="retryCount > 0">(tentativa {{ retryCount + 1 }})</span>
      </p>
    </div>

    <!-- Estado sem notícias -->
    <div v-else-if="showEmpty" class="carousel-empty">
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
        <h3 class="empty-title">Nenhuma notícia em destaque</h3>
        <p class="empty-message">Não há notícias em destaque no momento.</p>
        <a href="/noticias" class="empty-link">Ver todas as notícias</a>
      </div>
    </div>

    <!-- Estado de erro -->
    <div v-else-if="error" class="carousel-error">
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
        <h3 class="error-title">Ops! Algo deu errado</h3>
        <p class="error-message">{{ error }}</p>
        <button @click="retry" class="error-retry-btn">
          <svg
            class="w-4 h-4 mr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
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

    <!-- Carrossel -->
    <div
      v-if="showCarousel"
      class="carousel-container"
      @mouseenter="stopAutoPlay"
      @mouseleave="isPlaying && startAutoPlay()"
      @touchstart="handleTouchStart"
      @touchend="handleTouchEnd"
      @focusin="stopAutoPlay"
      @focusout="isPlaying && startAutoPlay()"
      tabindex="0"
      role="region"
      aria-label="Carrossel de notícias em destaque"
      :aria-live="isPlaying ? 'off' : 'polite'"
    >
      <!-- Slides Container -->
      <div
        class="slides-container"
        :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
        role="tabpanel"
        :aria-label="`Slide ${currentIndex + 1} de ${newsItems.length}`"
      >
        <article
          v-for="(news, index) in newsItems"
          :key="news.id"
          class="slide-item"
          :class="{ 'slide-active': index === currentIndex }"
          :aria-hidden="index !== currentIndex"
        >
          <!-- Container de imagem -->
          <div class="slide-image-container">
            <img
              :src="news.image"
              :alt="news.title"
              class="slide-image"
              :class="{
                'image-loading':
                  news.image === PLACEHOLDER_LOCAL && news.originalImage,
              }"
              :loading="index === currentIndex ? 'eager' : 'lazy'"
              @load="handleImageLoad(index)"
              @error="e => handleImageError(e, index)"
            />
            <!-- Overlay gradiente -->
            <div class="slide-overlay"></div>
          </div>

          <!-- Conteúdo -->
          <div class="slide-content">
            <h2 class="slide-title">{{ news.title }}</h2>
            <p v-if="news.excerpt" class="slide-excerpt">{{ news.excerpt }}</p>
            <a
              :href="news.link"
              class="slide-link"
              :tabindex="index === currentIndex ? 0 : -1"
            >
              Leia mais
              <svg
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
            </a>
          </div>
        </article>
      </div>

      <!-- Controles de Navegação -->
      <template v-if="showControls">
        <button
          @click="prevSlide"
          class="nav-button nav-button-prev"
          aria-label="Slide anterior"
          :disabled="!hasMultipleItems"
        >
          <svg
            class="nav-icon"
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

        <button
          @click="nextSlide"
          class="nav-button nav-button-next"
          aria-label="Próximo slide"
          :disabled="!hasMultipleItems"
        >
          <svg
            class="nav-icon"
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

        <!-- Botão play/pause -->
        <button
          @click="toggleAutoPlay"
          class="play-pause-button"
          :aria-label="
            isPlaying ? 'Pausar apresentação' : 'Iniciar apresentação'
          "
        >
          <svg
            v-if="isPlaying"
            class="play-pause-icon"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 9v6m4-6v6"
            />
          </svg>
          <svg
            v-else
            class="play-pause-icon"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8"
            />
          </svg>
        </button>
      </template>

      <!-- Indicadores -->
      <div v-if="showControls" class="indicators-container" role="tablist">
        <button
          v-for="(_, index) in newsItems"
          :key="`indicator-${index}`"
          @click="goToSlide(index)"
          class="indicator"
          :class="{ 'indicator-active': index === currentIndex }"
          :aria-label="`Ir para slide ${index + 1}: ${newsItems[index]?.title}`"
          :aria-selected="index === currentIndex"
          role="tab"
        >
          <span class="sr-only">Slide {{ index + 1 }}</span>
        </button>
      </div>

      <!-- Progress bar -->
      <div v-if="isPlaying && hasMultipleItems" class="progress-bar">
        <div
          class="progress-fill"
          :style="{ animationDuration: `${autoPlayDuration}ms` }"
          :key="`progress-${currentIndex}`"
        ></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Wrapper para garantir espaço reservado */
.carousel-wrapper {
  min-height: 12rem;
}

@media (min-width: 640px) {
  .carousel-wrapper {
    min-height: 16rem;
  }
}

@media (min-width: 768px) {
  .carousel-wrapper {
    min-height: 20rem;
  }
}

@media (min-width: 1024px) {
  .carousel-wrapper {
    min-height: 24rem;
  }
}

/* Container principal */
.carousel-container {
  @apply relative shadow-xl overflow-hidden w-full rounded-lg focus:outline-none;
  animation: fadeInUp 0.6s ease-out;
}

/* Estados de loading/error/empty */
.carousel-skeleton {
  @apply relative shadow-xl overflow-hidden w-full h-48 sm:h-64 md:h-80 lg:h-96 bg-gray-100 rounded-lg;
}

.skeleton-image {
  @apply w-full h-full bg-gray-200 animate-pulse relative;
}

.skeleton-spinner {
  @apply absolute inset-0 flex items-center justify-center;
}

.skeleton-text {
  @apply absolute bottom-0 left-0 right-0 p-4 sm:p-6 space-y-2;
}

.skeleton-line {
  @apply bg-gray-300 rounded animate-pulse;
}

.skeleton-title {
  @apply h-6 w-3/4;
}

.skeleton-excerpt {
  @apply h-4 w-1/2;
}

.skeleton-link {
  @apply h-4 w-1/4;
}

.carousel-error,
.carousel-empty {
  @apply relative shadow-xl overflow-hidden w-full h-48 sm:h-64 bg-gray-50 flex items-center justify-center rounded-lg border-2 border-dashed border-gray-200;
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

.empty-link {
  @apply inline-block text-blue-600 hover:text-blue-700 font-medium;
}

/* Slides */
.slides-container {
  @apply flex transition-transform duration-500 ease-in-out;
}

.slide-item {
  @apply min-w-full relative;
}

.slide-image-container {
  @apply relative w-full;
}

/* Imagem sem flicker */
.slide-image {
  @apply w-full h-48 sm:h-64 md:h-80 lg:h-96 object-cover transition-opacity duration-300;
  opacity: 1;
}

.image-loading {
  filter: brightness(0.98) contrast(1.02);
  transition: filter 0.5s ease-in-out;
}

.slide-overlay {
  @apply absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent;
}

.slide-content {
  @apply absolute bottom-0 left-0 right-0 p-4 sm:p-6 text-white;
}

.slide-title {
  @apply text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold mb-2 line-clamp-2 leading-tight;
}

.slide-excerpt {
  @apply text-sm sm:text-base mb-3 line-clamp-2 text-gray-200 leading-relaxed;
}

.slide-link {
  @apply inline-flex items-center text-yellow-400 hover:text-yellow-300 transition-colors text-sm sm:text-base font-medium focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 focus:ring-offset-black rounded px-1;
}

.link-arrow {
  @apply w-4 h-4 ml-1 transition-transform;
}

/* Controles de navegação */
.nav-button {
  @apply absolute top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 sm:p-3 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-black disabled:opacity-50 disabled:cursor-not-allowed;
}

.nav-button-prev {
  @apply left-2 sm:left-4;
}

.nav-button-next {
  @apply right-2 sm:right-4;
}

.nav-icon {
  @apply w-5 h-5 sm:w-6 sm:h-6;
}

/* Botão play/pause */
.play-pause-button {
  @apply absolute top-4 right-4 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-black;
}

.play-pause-icon {
  @apply w-4 h-4;
}

/* Indicadores */
.indicators-container {
  @apply absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2;
}

.indicator {
  @apply w-2 h-2 sm:w-3 sm:h-3 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-black;
}

.indicator-active {
  @apply bg-white scale-110;
}

.indicator:not(.indicator-active) {
  @apply bg-white/50 hover:bg-white/75 hover:scale-105;
}

/* Progress bar */
.progress-bar {
  @apply absolute bottom-0 left-0 right-0 h-1 bg-black/20;
}

.progress-fill {
  @apply h-full bg-yellow-400 w-full transform origin-left scale-x-0;
  animation: progress linear forwards;
}

/* Animações */
@keyframes progress {
  from {
    transform: scaleX(0);
  }
  to {
    transform: scaleX(1);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.transition-transform {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Utilities */
.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}

/* Line clamp utilities */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsividade */
@media (max-width: 640px) {
  .slide-title {
    @apply text-base leading-snug;
  }

  .slide-excerpt {
    @apply text-xs line-clamp-1;
  }

  .slide-content {
    @apply p-3;
  }

  .nav-button {
    @apply p-2;
  }

  .nav-icon {
    @apply w-4 h-4;
  }
}

/* Estados de hover e focus */
@media (hover: hover) {
  .slide-link:hover .link-arrow {
    @apply translate-x-1;
  }

  .nav-button:hover {
    @apply scale-110;
  }

  .indicator:hover {
    @apply scale-110;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .slides-container,
  .nav-button,
  .indicator,
  .slide-image,
  .link-arrow,
  .carousel-container {
    @apply transition-none;
    animation: none !important;
  }

  .progress-fill {
    animation: none;
  }
}

/* controle de altura mínima */
.carousel-skeleton,
.carousel-error,
.carousel-empty,
.carousel-container {
  min-height: 12rem;
}

@media (min-width: 640px) {
  .carousel-skeleton,
  .carousel-error,
  .carousel-empty,
  .carousel-container {
    min-height: 16rem;
  }
}

@media (min-width: 768px) {
  .carousel-skeleton,
  .carousel-error,
  .carousel-empty,
  .carousel-container {
    min-height: 20rem;
  }
}

@media (min-width: 1024px) {
  .carousel-skeleton,
  .carousel-error,
  .carousel-empty,
  .carousel-container {
    min-height: 24rem;
  }
}

/* Smooth loading transitions */
.carousel-wrapper {
  opacity: 1;
  transition: opacity 0.3s ease-in-out;
}

/* Performance otimizada para imagens */
.slide-image {
  will-change: opacity;
  backface-visibility: hidden;
  transform: translateZ(0);
}

/* Preload hint visual para placeholders locais */
.image-loading::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.05),
    transparent
  );
  animation: shimmer 3s infinite;
  pointer-events: none;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>
