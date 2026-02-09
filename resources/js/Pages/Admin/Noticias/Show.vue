<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  mdiInformationOutline,
  mdiArrowLeftBoldOutline,
  mdiPencilOutline,
  mdiStar,
  mdiStarOutline,
  mdiTrashCan,
} from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseButtons from '@/Components/BaseButtons.vue';
import NotificationBar from '@/Components/NotificationBar.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  noticia: {
    type: Object,
    default: () => ({}),
  },
  can: {
    type: Object,
    default: () => ({}),
  },
});

// Formulário para exclusão
const formDelete = useForm({});
const isDeleting = ref(false);
const deleteMessage = ref(null);
function destroy() {
  if (confirm('Tem certeza de que deseja remover esta notícia?')) {
    isDeleting.value = true;
    deleteMessage.value = null;
    formDelete.delete(route('admin.noticias.destroy', props.noticia.id), {
      preserveScroll: true,
      onSuccess: () => {
        deleteMessage.value = {
          type: 'success',
          text: 'Notícia excluída com sucesso!',
        };
        isDeleting.value = false;
      },
      onError: () => {
        deleteMessage.value = {
          type: 'danger',
          text: 'Erro ao excluir a notícia.',
        };
        isDeleting.value = false;
      },
    });
  }
}

// Função auxiliar para obter URL da imagem
const getImageUrl = imagePath => {
  if (!imagePath) return null;
  if (imagePath.startsWith('/') || imagePath.startsWith('http')) {
    return imagePath;
  }
  return `/storage/${imagePath}`;
};

// Helper para obter a classe CSS do status
function getStatusClass(status) {
  switch (status) {
    case 'publicado':
      return 'bg-green-100 text-green-800';
    case 'rascunho':
      return 'bg-gray-100 text-gray-800';
    case 'arquivado':
      return 'bg-orange-100 text-orange-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

// Formatar data para exibição
const dataFormatada = computed(() => {
  if (!props.noticia.data_publicacao) return '';
  return new Date(props.noticia.data_publicacao).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
});

const formatDateTime = isoString => {
  if (!isoString) return 'Data não disponível';

  try {
    return new Date(isoString).toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch (error) {
    console.warn('Erro ao formatar data:', error);
    return 'Data inválida';
  }
};

// Computed para a URL da imagem de capa
const imagemUrl = computed(() => getImageUrl(props.noticia.imagem));

// ====== NOVO: Funções para extrair e gerenciar imagens do carrossel ======

// Extrair imagens do conteúdo HTML
const extractImages = htmlContent => {
  if (!htmlContent) return [];

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = htmlContent;

  const images = tempDiv.querySelectorAll('img');
  const imageUrls = [];

  images.forEach(img => {
    const src = img.getAttribute('src');
    if (src && !src.startsWith('data:')) {
      imageUrls.push(src);
    }
  });

  return imageUrls;
};

// Remover imagens do conteúdo HTML
const removeImagesFromContent = htmlContent => {
  if (!htmlContent) return '';

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = htmlContent;

  const images = tempDiv.querySelectorAll('img');
  images.forEach(img => img.remove());

  return tempDiv.innerHTML;
};

// Computed para as URLs das imagens do carrossel
const carouselImages = computed(() => {
  // Priorizar imagens do campo carousel_images se existir (SOLUÇÃO 2)
  if (
    props.noticia.carousel_images &&
    props.noticia.carousel_images.length > 0
  ) {
    return props.noticia.carousel_images.map(img => img.url);
  }

  // Fallback: extrair do conteúdo (SOLUÇÃO 1 / notícias antigas)
  return extractImages(props.noticia.conteudo);
});

// Conteúdo sem imagens
const conteudoSemImagens = computed(() =>
  removeImagesFromContent(props.noticia.conteudo)
);

// Estado do carrossel
const currentImageIndex = ref(0);

// Navegar para imagem anterior
const previousImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--;
  } else {
    currentImageIndex.value = carouselImages.value.length - 1;
  }
};

// Navegar para próxima imagem
const nextImage = () => {
  if (currentImageIndex.value < carouselImages.value.length - 1) {
    currentImageIndex.value++;
  } else {
    currentImageIndex.value = 0;
  }
};

// Ir para imagem específica
const goToImage = index => {
  currentImageIndex.value = index;
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes da Notícia" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiInformationOutline"
        title="Detalhes da Notícia"
        main
      >
        <BaseButton
          :route-name="route('admin.noticias.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
          aria-label="Voltar para a lista de notícias"
        />
      </SectionTitleLineWithButton>

      <!-- Mensagens de feedback -->
      <NotificationBar
        v-if="deleteMessage"
        :color="deleteMessage.type"
        :icon="mdiInformationOutline"
      >
        {{ deleteMessage.text }}
      </NotificationBar>

      <!-- Cabeçalho com ações -->
      <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-2">
          <span
            class="px-2 py-1 text-xs font-medium rounded"
            :class="getStatusClass(noticia.status)"
          >
            {{ noticia.status }}
          </span>
        </div>
        <BaseButtons>
          <BaseButton
            v-if="can.edit"
            :route-name="route('admin.noticias.edit', noticia.id)"
            :icon="mdiPencilOutline"
            label="Editar"
            color="info"
            small
            aria-label="Editar notícia"
          />
          <BaseButton
            v-if="can.delete"
            :icon="mdiTrashCan"
            label="Excluir"
            color="danger"
            small
            :disabled="isDeleting"
            @click="destroy"
            aria-label="Excluir notícia"
          />
        </BaseButtons>
      </div>

      <!-- Conteúdo principal -->
      <CardBox class="mb-6">
        <!-- Título e data -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold mb-2">{{ noticia.titulo }}</h1>
          <div class="flex items-center text-gray-600 text-sm">
            <span class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                  clip-rule="evenodd"
                />
              </svg>
              Publicado em: {{ dataFormatada }}
            </span>
            <span class="mx-2">•</span>
            <span class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path
                  fill-rule="evenodd"
                  d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                  clip-rule="evenodd"
                />
              </svg>
              {{ noticia.visualizacoes }} visualizações
            </span>
          </div>
        </div>

        <!-- Imagem de capa (se existir) -->
        <div v-if="imagemUrl" class="mb-6">
          <div class="flex items-center mb-2">
            <span class="icon w-5 h-5 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"
                />
              </svg>
            </span>
            <span class="text-sm font-medium text-gray-700"
              >Imagem de Capa</span
            >
          </div>
          <img
            :src="imagemUrl"
            :alt="noticia.titulo"
            class="rounded-lg max-h-64 object-cover w-full border border-gray-200"
          />
        </div>

        <!-- NOVO: Carrossel de Imagens -->
        <div v-if="carouselImages.length > 0" class="mb-6">
          <div class="flex items-center mb-3">
            <span class="icon w-5 h-5 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M13,19C13,19.7 13.13,20.37 13.35,21H5C3.89,21 3,20.1 3,19V5C3,3.89 3.89,3 5,3H19C20.1,3 21,3.89 21,5V13.35C20.37,13.13 19.7,13 19,13V5H5V19H13M13.96,12.29L11.21,15.83L9.25,13.47L6.5,17H13.35C13.75,15.88 14.47,14.91 15.4,14.21L13.96,12.29M20,18V15H18V18H15V20H18V23H20V20H23V18H20Z"
                />
              </svg>
            </span>
            <span class="text-sm font-medium text-gray-700">
              Imagens da Notícia ({{ carouselImages.length }})
            </span>
          </div>

          <!-- Carrossel -->
          <div
            class="relative bg-gray-100 rounded-lg overflow-hidden border border-gray-200"
          >
            <!-- Imagem principal -->
            <div class="relative aspect-video bg-gray-900">
              <img
                :src="carouselImages[currentImageIndex]"
                :alt="`Imagem ${currentImageIndex + 1}`"
                class="w-full h-full object-contain"
              />

              <!-- Contador -->
              <div
                class="absolute top-3 right-3 bg-black/70 text-white px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ currentImageIndex + 1 }} / {{ carouselImages.length }}
              </div>

              <!-- Botões de navegação (apenas se houver mais de 1 imagem) -->
              <template v-if="carouselImages.length > 1">
                <!-- Anterior -->
                <button
                  @click="previousImage"
                  class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white p-2 rounded-full shadow-lg transition-all"
                  aria-label="Imagem anterior"
                >
                  <svg
                    class="w-6 h-6 text-gray-800"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 19l-7-7 7-7"
                    />
                  </svg>
                </button>

                <!-- Próxima -->
                <button
                  @click="nextImage"
                  class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white p-2 rounded-full shadow-lg transition-all"
                  aria-label="Próxima imagem"
                >
                  <svg
                    class="w-6 h-6 text-gray-800"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </button>
              </template>
            </div>

            <!-- Miniaturas (apenas se houver mais de 1 imagem) -->
            <div
              v-if="carouselImages.length > 1"
              class="p-3 bg-white border-t border-gray-200"
            >
              <div class="flex gap-2 overflow-x-auto pb-2">
                <button
                  v-for="(image, index) in carouselImages"
                  :key="index"
                  @click="goToImage(index)"
                  :class="[
                    'flex-shrink-0 w-20 h-16 rounded-md border-2 overflow-hidden transition-all',
                    currentImageIndex === index
                      ? 'border-[#bea55a] ring-2 ring-[#bea55a]/30'
                      : 'border-gray-300 hover:border-gray-400',
                  ]"
                >
                  <img
                    :src="image"
                    :alt="`Miniatura ${index + 1}`"
                    class="w-full h-full object-cover"
                  />
                </button>
              </div>
            </div>
          </div>

          <!-- Info sobre as imagens -->
          <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
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
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span>
              {{
                carouselImages.length === 1
                  ? '1 imagem encontrada'
                  : `${carouselImages.length} imagens encontradas`
              }}
              no conteúdo da notícia
            </span>
          </div>
        </div>

        <!-- Descrição curta -->
        <div class="mb-6">
          <h3 class="font-semibold text-lg mb-2 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M14 17H7V15H14V17M17 13H7V11H17V13M17 9H7V7H17V9M19 3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3Z"
                />
              </svg>
            </span>
            Descrição Curta
          </h3>
          <div class="p-4 rounded-lg border border-gray-200 bg-gray-50">
            {{
              noticia.descricao_curta || 'Nenhuma descrição curta disponível.'
            }}
          </div>
        </div>

        <!-- Conteúdo completo (SEM imagens) -->
        <div>
          <h3 class="font-semibold text-lg mb-2 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M19.03 6.03L20 7L18.15 8.85L19.03 9.03L19.4 12.41L18.21 13.6L19 15L16.3 17.7L15 17L13.6 18.21L13.03 19.97L10.03 19.97L8.3 18.3L6 19L5 16.5L3.41 14.91L6.83 12.3H7.33L9 13L10.03 13.03V15L12.5 13.5L14 12L12.5 10.5L11 9.1H9.03L5.83 7.5L7.5 5.8L10 5L11 7L14 7L15.56 5.44L19.03 6.03Z"
                />
              </svg>
            </span>
            Conteúdo Completo
            <span
              v-if="carouselImages.length > 0"
              class="ml-2 text-xs text-gray-500 font-normal"
            >
              (imagens exibidas no carrossel acima)
            </span>
          </h3>
          <div class="prose max-w-none">
            <div v-if="conteudoSemImagens" v-html="conteudoSemImagens"></div>
            <div v-else class="text-gray-500 italic">
              Sem conteúdo detalhado.
            </div>
          </div>
        </div>
      </CardBox>

      <!-- Metadados -->
      <CardBox class="mb-6">
        <h3 class="font-semibold text-lg mb-4 flex items-center">
          <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"
              />
            </svg>
          </span>
          Informações Adicionais
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <span class="text-gray-600 text-sm">ID:</span>
            <span class="ml-2 font-medium">{{ noticia.id }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Status:</span>
            <span
              class="ml-2 px-2 py-1 text-xs font-medium rounded"
              :class="getStatusClass(noticia.status)"
            >
              {{ noticia.status }}
            </span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Data de Criação:</span>
            <span class="ml-2 font-medium">{{
              formatDateTime(noticia.created_at)
            }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Última Atualização:</span>
            <span class="ml-2 font-medium">{{
              formatDateTime(noticia.updated_at)
            }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Visualizações:</span>
            <span class="ml-2 font-medium">{{ noticia.visualizacoes }}</span>
          </div>
        </div>
      </CardBox>

      <!-- Botões de ação -->
      <div class="flex justify-between">
        <BaseButton
          :route-name="route('admin.noticias.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar para Notícias"
          color="white"
          aria-label="Voltar para a lista de notícias"
        />
        <BaseButtons v-if="can.edit">
          <BaseButton
            :route-name="route('admin.noticias.edit', noticia.id)"
            :icon="mdiPencilOutline"
            label="Editar Notícia"
            color="info"
            aria-label="Editar notícia"
          />
        </BaseButtons>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style>
/* Estilos para o conteúdo gerado pelo HTML */
.prose img {
  border-radius: 0.375rem;
  margin: 2rem 0;
  max-height: 28rem;
  object-fit: contain;
  width: 100%;
}

.prose h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #1f2937;
}

.prose h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #1f2937;
}

.prose p {
  margin-bottom: 1.25rem;
  line-height: 1.7;
}

.prose ul {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin: 1.25rem 0;
}

.prose ol {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin: 1.25rem 0;
}

.prose a {
  color: #2563eb;
  text-decoration: underline;
}

.prose a:hover {
  color: #1d4ed8;
}

.prose iframe,
.prose video {
  width: 100%;
  max-width: 560px;
  height: 315px;
  margin: 2rem auto;
  display: block;
  border-radius: 0.375rem;
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Animações */
.transition-opacity {
  transition-property: opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

.transition-transform {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Scroll suave para miniaturas */
.overflow-x-auto {
  scrollbar-width: thin;
  scrollbar-color: #bea55a #f3f4f6;
}

.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #bea55a;
  border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #a08d47;
}
</style>
