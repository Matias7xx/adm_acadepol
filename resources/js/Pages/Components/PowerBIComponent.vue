<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  title: {
    type: String,
    default: 'Relatório Power BI',
  },
  src: {
    type: String,
    required: true,
  },
  height: {
    type: String,
    default: '600px',
  },
});

// Estado
const isMobile = ref(false);
const isSmallMobile = ref(false);
const isLoading = ref(true);
const loadingProgress = ref(0);
const isFullscreen = ref(false);

// Verificar se está em dispositivo móvel
onMounted(() => {
  const checkDevice = () => {
    const isMobileDevice =
      /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent
      );
    const windowWidth = window.innerWidth;

    isMobile.value = isMobileDevice || windowWidth < 768;
    isSmallMobile.value = windowWidth < 640; // Telas muito pequenas
  };

  checkDevice();

  // Listener para mudanças de tamanho (rotação, redimensionamento)
  window.addEventListener('resize', checkDevice);

  // Simular carregamento apenas se não for mobile pequeno
  if (!isSmallMobile.value) {
    const interval = setInterval(() => {
      loadingProgress.value += 10;
      if (loadingProgress.value >= 100) {
        clearInterval(interval);
        isLoading.value = false;
      }
    }, 200);
  } else {
    isLoading.value = false; // Não carregar iframe em mobile pequeno
  }
});

// Altura responsiva
const responsiveHeight = computed(() => {
  if (isMobile.value && !isSmallMobile.value) {
    return '400px';
  }
  return props.height;
});

// Alternar tela cheia
const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value;
};

// Abrir em nova aba
const openInNewTab = () => {
  window.open(props.src, '_blank');
};
</script>

<template>
  <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
    <!-- Cabeçalho do componente -->
    <div class="bg-gray-100 border p-4">
      <div class="flex justify-between items-center">
        <h2
          class="text-xl font-bold text-gray-800"
          :class="isSmallMobile ? 'text-lg' : ''"
        >
          {{ title }}
        </h2>

        <!-- Botões apenas se não for mobile pequeno -->
        <div v-if="!isSmallMobile" class="flex space-x-2">
          <!-- Botão de tela cheia -->
          <button
            @click="toggleFullscreen"
            class="bg-white/20 hover:bg-white/30 text-gray-800 p-2 rounded-lg transition-colors"
            :title="isFullscreen ? 'Sair da tela cheia' : 'Tela cheia'"
          >
            <svg
              v-if="!isFullscreen"
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
              />
            </svg>
            <svg
              v-else
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 9V4.5M9 9H4.5M9 9L3.5 3.5M15 9v-4.5M15 9h4.5M15 9l5.5-5.5M9 15v4.5M9 15H4.5M9 15l-5.5 5.5M15 15v4.5M15 15h4.5m0 0l5.5 5.5"
              />
            </svg>
          </button>

          <!-- Botão para abrir em nova aba -->
          <button
            @click="openInNewTab"
            class="bg-white/20 hover:bg-white/30 text-gray-800 p-2 rounded-lg transition-colors"
            title="Abrir em nova aba"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Versão Mobile (telas < 640px) -->
    <div v-if="isSmallMobile" class="p-8 text-center">
      <div class="max-w-sm mx-auto">
        <!-- Ícone do Power BI -->
        <div class="mb-6">
          <div class="w-20 h-20 mx-auto rounded-2xl shadow-lg overflow-hidden">
            <svg viewBox="0 0 240 240" class="w-full h-full">
              <!-- Fundo amarelo -->
              <rect width="240" height="240" rx="28" fill="#F2C811" />
              <rect
                x="60"
                y="180"
                width="25"
                height="40"
                rx="3"
                fill="#323130"
              />
              <rect
                x="95"
                y="140"
                width="25"
                height="80"
                rx="3"
                fill="#323130"
              />
              <rect
                x="130"
                y="100"
                width="25"
                height="120"
                rx="3"
                fill="#323130"
              />
              <rect
                x="165"
                y="80"
                width="25"
                height="140"
                rx="3"
                fill="#323130"
              />
              <circle cx="200" cy="90" r="8" fill="#FF6600" />
              <path
                d="M 55 185 Q 120 120 205 85"
                stroke="#323130"
                stroke-width="2"
                fill="none"
                opacity="0.3"
              />

              <!-- Texto "BI" -->
              <text
                x="40"
                y="70"
                font-family="Segoe UI, Arial, sans-serif"
                font-size="32"
                font-weight="bold"
                fill="#323130"
              >
                BI
              </text>
            </svg>
          </div>
        </div>

        <p class="text-gray-600 mb-2 text-sm leading-relaxed">
          Este relatório contém gráficos e dados interativos otimizados para
          desktop.
        </p>

        <p class="text-gray-500 mb-6 text-xs">
          Para melhor experiência, acesse em um computador ou tablet em modo
          paisagem.
        </p>

        <!-- Botão principal -->
        <Link
          @click="openInNewTab"
          class="inline-flex items-center px-6 py-3 bg-[#bea55a] text-white font-medium rounded-lg hover:bg-yellow-600 focus:ring-4 focus:ring-yellow-200 focus:outline-none transition-all duration-200 shadow-md"
        >
          <div class="flex items-center justify-center space-x-2">
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
              />
            </svg>
            <span>Abrir Relatório Completo</span>
          </div>
        </Link>
      </div>
    </div>

    <!-- Versão Desktop/Tablet (telas >= 640px) -->
    <div
      v-else
      class="relative"
      :class="isFullscreen ? 'fixed inset-0 z-50 bg-white' : ''"
    >
      <!-- Estado de carregamento -->
      <div
        v-if="isLoading"
        class="flex flex-col justify-center items-center p-8"
        :style="{ height: responsiveHeight }"
      >
        <div class="w-64 h-2 bg-gray-200 rounded-full overflow-hidden mb-4">
          <div
            class="h-full bg-gray-300 transition-all duration-200 ease-out"
            :style="{ width: `${loadingProgress}%` }"
          ></div>
        </div>
        <p class="text-center text-gray-600">
          Carregando relatório... {{ loadingProgress }}%
        </p>
      </div>

      <!-- Iframe do Power BI -->
      <div v-else class="relative">
        <!-- Botão de fechar tela cheia (quando em fullscreen) -->
        <button
          v-if="isFullscreen"
          @click="toggleFullscreen"
          class="absolute top-4 right-4 z-10 bg-black/50 hover:bg-black/70 text-white p-2 rounded-lg transition-colors"
          title="Fechar tela cheia"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

        <!-- Container responsivo do iframe -->
        <div
          class="w-full bg-gray-50 overflow-hidden"
          :class="isFullscreen ? 'h-screen' : 'rounded-b-lg'"
          :style="!isFullscreen ? { height: responsiveHeight } : {}"
        >
          <iframe
            :src="props.src"
            :title="props.title"
            class="w-full h-full border-0"
            frameborder="0"
            allowfullscreen="true"
            loading="lazy"
          ></iframe>
        </div>
      </div>

      <!-- Aviso para tablets -->
      <div
        v-if="isMobile && !isLoading"
        class="bg-blue-50 border-l-4 border-blue-400 p-4 m-4"
        role="alert"
      >
        <div class="flex">
          <div class="flex-shrink-0">
            <svg
              class="h-5 w-5 text-blue-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-blue-700">
              <strong>💡 Dica:</strong> Para melhor experiência, use o modo
              paisagem ou abra em nova aba.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animações e estilos */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Estilo para fullscreen */
.fixed.inset-0 {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

/* Animação hover para o botão principal */
@media (hover: hover) {
  .transform:hover {
    transform: translateY(-2px) scale(1.02);
  }
}

/* Garantir boa legibilidade em telas pequenas */
@media (max-width: 480px) {
  .text-xl {
    font-size: 1.125rem;
  }

  .p-8 {
    padding: 1.5rem;
  }
}

/* visualização em tablets */
@media (min-width: 768px) and (max-width: 1024px) {
  iframe {
    min-height: 500px;
  }
}
</style>
