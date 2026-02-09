<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { mdiClose, mdiAlertCircle } from '@mdi/js';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  noticiaNova: {
    type: Object,
    required: true,
  },
  destaquesAtuais: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(['close']);

const confirmarSubstituicao = noticiaSubstituirId => {
  router.post(
    route('admin.noticias.confirmar-substituicao-destaque'),
    {
      noticia_nova_id: props.noticiaNova.id,
      noticia_substituir_id: noticiaSubstituirId,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        emit('close');
      },
    }
  );
};

const cancelar = () => {
  emit('close');
};
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="cancelar"
      >
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="show"
            class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
          >
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="bg-amber-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                    <path :d="mdiAlertCircle" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900">
                    Limite de Destaques Atingido
                  </h3>
                  <p class="text-sm text-gray-600">
                    Você já tem 2 notícias em destaque. Escolha qual deseja substituir.
                  </p>
                </div>
              </div>
              <button
                @click="cancelar"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path :d="mdiClose" />
                </svg>
              </button>
            </div>

            <!-- Content -->
            <div class="p-6">
              <!-- Nova Notícia -->
              <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                  Nova notícia para destaque:
                </h4>
                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4 flex items-start space-x-4">
                  <div class="flex-shrink-0">
                    <img
                      v-if="noticiaNova.imagem"
                      :src="noticiaNova.imagem"
                      :alt="noticiaNova.titulo"
                      class="w-24 h-24 object-cover rounded-lg"
                    />
                    <div
                      v-else
                      class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center"
                    >
                      <span class="text-gray-400 text-xs">Sem imagem</span>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h5 class="text-base font-semibold text-gray-900 mb-1">
                      {{ noticiaNova.titulo }}
                    </h5>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      Nova
                    </span>
                  </div>
                </div>
              </div>

              <!-- Destaques Atuais -->
              <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                  Escolha qual notícia deseja remover do destaque:
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <button
                    v-for="destaque in destaquesAtuais"
                    :key="destaque.id"
                    @click="confirmarSubstituicao(destaque.id)"
                    class="bg-white border-2 border-gray-200 hover:border-red-400 rounded-lg p-4 transition-all hover:shadow-lg text-left group"
                  >
                    <div class="flex items-start space-x-4">
                      <div class="flex-shrink-0">
                        <img
                          v-if="destaque.imagem"
                          :src="destaque.imagem"
                          :alt="destaque.titulo"
                          class="w-24 h-24 object-cover rounded-lg"
                        />
                        <div
                          v-else
                          class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center"
                        >
                          <span class="text-gray-400 text-xs">Sem imagem</span>
                        </div>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between mb-2">
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-amber-500 text-white">
                            {{ destaque.ordem }}º Destaque
                          </span>
                        </div>
                        <h5 class="text-sm font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-red-600 transition-colors">
                          {{ destaque.titulo }}
                        </h5>
                        <p class="text-xs text-gray-500">
                          {{ destaque.data_publicacao }}
                        </p>
                        <div class="mt-3 text-xs text-red-600 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                          Clique para substituir →
                        </div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Informação -->
              <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                  <svg
                    class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path :d="mdiAlertCircle" />
                  </svg>
                  <div class="ml-3">
                    <p class="text-sm text-blue-800">
                      A notícia removida do destaque permanecerá publicada no site,
                      apenas não será exibida na área de destaque.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
              <button
                @click="cancelar"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors font-medium"
              >
                Cancelar
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>