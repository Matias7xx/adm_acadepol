<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
  mdiStar,
  mdiSwapVertical,
  mdiClose,
  mdiEye,
  mdiAlertCircle,
} from '@mdi/js';

const page = usePage();
const destaques = ref([]);
const loading = ref(true);
const error = ref(null);
const isReordering = ref(false);

const hasDestaques = computed(() => destaques.value.length > 0);
const destaquesOrdenados = computed(() =>
  [...destaques.value].sort((a, b) => a.ordem - b.ordem)
);

const loadDestaques = async (silent = false) => {
  try {
    if (!silent) loading.value = true;  // ← spinner só no carregamento inicial
    const response = await fetch(route('admin.noticias.destaques-atuais'));
    if (!response.ok) throw new Error('Erro ao carregar destaques');
    destaques.value = await response.json();
  } catch (err) {
    error.value = err.message;
  } finally {
    if (!silent) loading.value = false;
  }
};

const removerDestaque = noticiaId => {
  router.patch(
    route('admin.noticias.toggle-destaque', noticiaId),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        loadDestaques(true);
      },
    }
  );
};

const trocarOrdem = async () => {
  if (destaques.value.length !== 2) return;

  const temp = destaques.value[0].ordem;
  destaques.value[0].ordem = destaques.value[1].ordem;
  destaques.value[1].ordem = temp;

  isReordering.value = true;

  try {
    const response = await fetch(route('admin.noticias.atualizar-ordem-destaques'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        destaques: destaques.value.map(d => ({ id: d.id, ordem: d.ordem })),
      }),
    });

    if (!response.ok) throw new Error('Erro ao atualizar ordem');
    await loadDestaques(true);
  } catch (err) {
    error.value = err.message;
  } finally {
    isReordering.value = false;
  }
};

const visualizarNoSite = noticiaId => {
  window.open(`/noticias/${noticiaId}`, '_blank');
};

onMounted(() => {
  loadDestaques();
});

watch(
  () => page.props.flash,
  () => {
    loadDestaques();
  },
  { deep: true }
);
</script>

<template>
  <div
    class="bg-gray-100 rounded-lg shadow-lg p-6 mb-6 border-2 border-gray-200"
  >
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div>
          <h3 class="text-lg font-bold text-gray-800">
            Notícias em Destaque na Home
          </h3>
          <p class="text-sm text-gray-600">
            {{ destaques.length }} de 2 slots ocupados
          </p>
        </div>
      </div>

      <!-- Botão de trocar ordem (só aparece se tiver 2) -->
      <button
        v-if="destaques.length === 2"
        @click="trocarOrdem"
        :disabled="isReordering"
        class="flex items-center space-x-2 px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        title="Trocar ordem dos destaques"
      >
        <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
          <path :d="mdiSwapVertical" />
        </svg>
        <span class="text-sm font-medium text-gray-700">Inverter Ordem</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-500"
      ></div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-4"
    >
      <div class="flex items-start">
        <svg
          class="w-5 h-5 text-red-500 mt-0.5"
          fill="currentColor"
          viewBox="0 0 24 24"
        >
          <path :d="mdiAlertCircle" />
        </svg>
        <div class="ml-3">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Cards dos Destaques -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Slot 1 -->
      <div
        class="relative bg-white rounded-lg shadow-md overflow-hidden border-2 transition-all"
        :class="
          destaquesOrdenados[0]
            ? 'border-gray-300 hover:shadow-lg'
            : 'border-dashed border-gray-300'
        "
      >
        <!-- Badge de posição -->
        <div
          class="absolute top-2 left-2 z-10 px-3 py-1 rounded-full text-xs font-bold"
          :class="
            destaquesOrdenados[0]
              ? 'bg-yellow-600 text-white'
              : 'bg-gray-200 text-gray-500'
          "
        >
          1º Destaque
        </div>

        <div v-if="destaquesOrdenados[0]" class="relative">
          <!-- Imagem -->
          <div class="aspect-video relative">
            <img
              v-if="destaquesOrdenados[0].imagem"
              :src="destaquesOrdenados[0].imagem"
              :alt="destaquesOrdenados[0].titulo"
              class="w-full h-full object-cover"
            />
            <div
              v-else
              class="w-full h-full bg-gray-200 flex items-center justify-center"
            >
              <span class="text-gray-400 text-sm">Sem imagem</span>
            </div>

            <!-- Overlay com ações -->
            <div
              class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 hover:opacity-100"
            >
              <button
                @click="visualizarNoSite(destaquesOrdenados[0].id)"
                class="bg-white text-gray-800 px-4 py-2 rounded-lg mx-1 hover:bg-gray-100 transition-colors flex items-center space-x-2"
                title="Visualizar no site"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path :d="mdiEye" />
                </svg>
                <span class="text-sm">Ver</span>
              </button>
              <button
                @click="removerDestaque(destaquesOrdenados[0].id)"
                class="bg-red-500 text-white px-4 py-2 rounded-lg mx-1 hover:bg-red-600 transition-colors flex items-center space-x-2"
                title="Remover destaque"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path :d="mdiClose" />
                </svg>
                <span class="text-sm">Remover</span>
              </button>
            </div>
          </div>

          <!-- Conteúdo -->
          <div class="p-4">
            <h4 class="font-semibold text-gray-800 text-sm mb-2 line-clamp-2">
              {{ destaquesOrdenados[0].titulo }}
            </h4>
            <p class="text-xs text-gray-500">
              {{ destaquesOrdenados[0].data_publicacao }}
            </p>
          </div>
        </div>

        <!-- Slot vazio -->
        <div v-else class="p-8 text-center">
          <svg
            class="w-12 h-12 text-gray-300 mx-auto mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"
            />
          </svg>
          <p class="text-sm text-gray-500">Slot disponível</p>
          <p class="text-xs text-gray-400 mt-1">
            Marque uma notícia como destaque
          </p>
        </div>
      </div>

      <!-- Slot 2 -->
      <div
        class="relative bg-white rounded-lg shadow-md overflow-hidden border-2 transition-all"
        :class="
          destaquesOrdenados[1]
            ? 'border-gray-300 hover:shadow-lg'
            : 'border-dashed border-gray-300'
        "
      >
        <!-- Badge de posição -->
        <div
          class="absolute top-2 left-2 z-10 px-3 py-1 rounded-full text-xs font-bold"
          :class="
            destaquesOrdenados[1]
              ? 'bg-yellow-600 text-white'
              : 'bg-gray-200 text-gray-500'
          "
        >
          2º Destaque
        </div>

        <div v-if="destaquesOrdenados[1]" class="relative">
          <!-- Imagem -->
          <div class="aspect-video relative">
            <img
              v-if="destaquesOrdenados[1].imagem"
              :src="destaquesOrdenados[1].imagem"
              :alt="destaquesOrdenados[1].titulo"
              class="w-full h-full object-cover"
            />
            <div
              v-else
              class="w-full h-full bg-gray-200 flex items-center justify-center"
            >
              <span class="text-gray-400 text-sm">Sem imagem</span>
            </div>

            <!-- Overlay com ações -->
            <div
              class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 hover:opacity-100"
            >
              <button
                @click="visualizarNoSite(destaquesOrdenados[1].id)"
                class="bg-white text-gray-800 px-4 py-2 rounded-lg mx-1 hover:bg-gray-100 transition-colors flex items-center space-x-2"
                title="Visualizar no site"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path :d="mdiEye" />
                </svg>
                <span class="text-sm">Ver</span>
              </button>
              <button
                @click="removerDestaque(destaquesOrdenados[1].id)"
                class="bg-red-500 text-white px-4 py-2 rounded-lg mx-1 hover:bg-red-600 transition-colors flex items-center space-x-2"
                title="Remover destaque"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path :d="mdiClose" />
                </svg>
                <span class="text-sm">Remover</span>
              </button>
            </div>
          </div>

          <!-- Conteúdo -->
          <div class="p-4">
            <h4 class="font-semibold text-gray-800 text-sm mb-2 line-clamp-2">
              {{ destaquesOrdenados[1].titulo }}
            </h4>
            <p class="text-xs text-gray-500">
              {{ destaquesOrdenados[1].data_publicacao }}
            </p>
          </div>
        </div>

        <!-- Slot vazio -->
        <div v-else class="p-8 text-center">
          <svg
            class="w-12 h-12 text-gray-300 mx-auto mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"
            />
          </svg>
          <p class="text-sm text-gray-500">Slot disponível</p>
          <p class="text-xs text-gray-400 mt-1">
            Marque uma notícia como destaque
          </p>
        </div>
      </div>
    </div>

    <!-- Aviso se não houver destaques -->
    <div
      v-if="!loading && !error && destaques.length === 0"
      class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4"
    >
      <div class="flex items-start">
        <svg
          class="w-5 h-5 text-blue-500 mt-0.5"
          fill="currentColor"
          viewBox="0 0 24 24"
        >
          <path :d="mdiAlertCircle" />
        </svg>
        <div class="ml-3">
          <p class="text-sm text-blue-800 font-medium">
            Nenhuma notícia em destaque
          </p>
          <p class="text-xs text-blue-700 mt-1">
            Marque até 2 notícias como destaque para aparecerem na página
            inicial do site.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.aspect-video {
  aspect-ratio: 16 / 9;
}
</style>
