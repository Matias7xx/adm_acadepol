<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  mdiInformationOutline,
  mdiArrowLeftBoldOutline,
  mdiPencilOutline,
  mdiStar,
  mdiStarOutline,
  mdiTrashCan,
} from "@mdi/js";
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue";
import SectionMain from "@/Components/SectionMain.vue";
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue";
import CardBox from "@/Components/CardBox.vue";
import BaseButton from "@/Components/BaseButton.vue";
import BaseButtons from "@/Components/BaseButtons.vue";
import NotificationBar from "@/Components/NotificationBar.vue";
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
  if (confirm("Tem certeza de que deseja remover esta notícia?")) {
    isDeleting.value = true;
    deleteMessage.value = null;
    formDelete.delete(route("admin.noticias.destroy", props.noticia.id), {
      preserveScroll: true,
      onSuccess: () => {
        deleteMessage.value = { type: 'success', text: 'Notícia excluída com sucesso!' };
        isDeleting.value = false;
      },
      onError: () => {
        deleteMessage.value = { type: 'danger', text: 'Erro ao excluir a notícia.' };
        isDeleting.value = false;
      },
    });
  }
}

// Formulário para alterar destaque
const formDestaque = useForm({});
const isTogglingDestaque = ref(false);
const destaqueMessage = ref(null);
function toggleDestaque() {
  isTogglingDestaque.value = true;
  destaqueMessage.value = null;
  formDestaque.patch(route("admin.noticias.toggle-destaque", props.noticia.id), {
    preserveScroll: true,
    onSuccess: () => {
      destaqueMessage.value = { type: 'success', text: `Notícia ${props.noticia.destaque ? 'removida do' : 'marcada como'} destaque!` };
      isTogglingDestaque.value = false;
    },
    onError: () => {
      destaqueMessage.value = { type: 'danger', text: 'Erro ao alterar o status de destaque.' };
      isTogglingDestaque.value = false;
    },
  });
}

// Função auxiliar para obter URL da imagem
const getImageUrl = (imagePath) => {
  if (!imagePath) return null;
  if (imagePath.startsWith('/') || imagePath.startsWith('http')) {
    return imagePath;
  }
  return `/storage/${imagePath}`;
};

// Helper para obter a classe CSS do status
function getStatusClass(status) {
  switch (status) {
    case 'publicado': return 'bg-green-100 text-green-800';
    case 'rascunho': return 'bg-gray-100 text-gray-800';
    case 'arquivado': return 'bg-orange-100 text-orange-800';
    default: return 'bg-gray-100 text-gray-800';
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

// Computed para a URL da imagem
const imagemUrl = computed(() => getImageUrl(props.noticia.imagem));
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
      <NotificationBar
        v-if="destaqueMessage"
        :color="destaqueMessage.type"
        :icon="mdiInformationOutline"
      >
        {{ destaqueMessage.text }}
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
          <span v-if="noticia.destaque" class="flex items-center text-yellow-600">
            <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            Destaque
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
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              Publicado em: {{ dataFormatada }}
            </span>
            <span class="mx-2">•</span>
            <span class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
              </svg>
              {{ noticia.visualizacoes }} visualizações
            </span>
          </div>
        </div>
        
        <!-- Imagem da notícia -->
        <div v-if="imagemUrl" class="mb-6">
          <img 
            :src="imagemUrl"
            :alt="noticia.titulo"
            class="rounded-lg max-h-48 object-contain w-full"
          />
        </div>
        <div v-else class="mb-6 flex items-center justify-center border rounded-lg p-4 bg-gray-50 border-dashed text-gray-400">
          <div class="text-center">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="mt-1">Nenhuma imagem disponível</p>
          </div>
        </div>
        
        <!-- Descrição curta -->
        <div class="mb-6">
          <h3 class="font-semibold text-lg mb-2 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M14 17H7V15H14V17M17 13H7V11H17V13M17 9H7V7H17V9M19 3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3Z" />
              </svg>
            </span>
            Descrição Curta
          </h3>
          <div class="p-4 rounded-lg border border-gray-200">
            {{ noticia.descricao_curta || 'Nenhuma descrição curta disponível.' }}
          </div>
        </div>
        
        <!-- Conteúdo completo -->
        <div>
          <h3 class="font-semibold text-lg mb-2 flex items-center">
            <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.03 6.03L20 7L18.15 8.85L19.03 9.03L19.4 12.41L18.21 13.6L19 15L16.3 17.7L15 17L13.6 18.21L13.03 19.97L10.03 19.97L8.3 18.3L6 19L5 16.5L3.41 14.91L6.83 12.3H7.33L9 13L10.03 13.03V15L12.5 13.5L14 12L12.5 10.5L11 9.1H9.03L5.83 7.5L7.5 5.8L10 5L11 7L14 7L15.56 5.44L19.03 6.03Z" />
              </svg>
            </span>
            Conteúdo Completo
          </h3>
          <div class="prose max-w-none">
            <div v-if="noticia.conteudo" v-html="noticia.conteudo"></div>
            <div v-else class="text-gray-500 italic">Sem conteúdo detalhado.</div>
          </div>
        </div>
      </CardBox>
      
      <!-- Metadados -->
      <CardBox class="mb-6">
        <h3 class="font-semibold text-lg mb-4 flex items-center">
          <span class="icon w-6 h-6 mr-2 text-[#bea55a]">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
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
            <span class="ml-2 font-medium">{{ new Date(noticia.created_at).toLocaleString('pt-BR') }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Última Atualização:</span>
            <span class="ml-2 font-medium">{{ new Date(noticia.updated_at).toLocaleString('pt-BR') }}</span>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Destaque:</span>
            <span class="ml-2 font-medium">{{ noticia.destaque ? 'Sim' : 'Não' }}</span>
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
            v-if="noticia.destaque"
            @click="toggleDestaque"
            :icon="mdiStarOutline"
            label="Remover Destaque"
            color="warning"
            :disabled="isTogglingDestaque"
            aria-label="Remover notícia do destaque"
          />
          <BaseButton
            v-else
            @click="toggleDestaque"
            :icon="mdiStar"
            label="Marcar como Destaque"
            color="warning"
            :disabled="isTogglingDestaque"
            aria-label="Marcar notícia como destaque"
          />
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
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
</style>