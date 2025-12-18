<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SiteNavbar from './SiteNavbar.vue';
import Footer from './Footer.vue';
import Header from './Header.vue';
import ImageCarousel from './ImageCarousel.vue';

const props = defineProps({
  noticia: {
    type: Object,
    required: true,
  },
  proximaNoticia: {
    type: Object,
    default: null,
  },
  noticiaAnterior: {
    type: Object,
    default: null,
  },
});

const imagemCarregada = ref(true);
const linkCopiado = ref(false);

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

// Computed properties para imagens e conteúdo
const imageUrls = computed(() => {
  // Priorizar imagens do carrossel se existirem
  if (
    props.noticia.carousel_images &&
    props.noticia.carousel_images.length > 0
  ) {
    return props.noticia.carousel_images.map(img => img.url);
  }

  // Fallback: extrair do conteúdo (para notícias antigas)
  return extractImages(props.noticia.conteudo);
});

const conteudoSemImagens = computed(() =>
  removeImagesFromContent(props.noticia.conteudo)
);

// URLs de compartilhamento
const urlAtual = computed(() => window.location.href);

const urlsCompartilhamento = computed(() => ({
  facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(urlAtual.value)}`,
  x: `https://twitter.com/intent/tweet?url=${encodeURIComponent(urlAtual.value)}&text=${encodeURIComponent(props.noticia.titulo)}`,
  whatsapp: `https://wa.me/?text=${encodeURIComponent(props.noticia.titulo + ' - ' + urlAtual.value)}`,
  telegram: `https://t.me/share/url?url=${encodeURIComponent(urlAtual.value)}&text=${encodeURIComponent(props.noticia.titulo)}`,
}));

// Formatar data de publicação
const dataPublicacaoFormatada = computed(() => {
  try {
    const data = new Date(props.noticia.data_publicacao);
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    }).format(data);
  } catch (e) {
    return props.noticia.data_publicacao;
  }
});

// Data de atualização formatada (apenas se for diferente da publicação)
const dataAtualizacaoFormatada = computed(() => {
  if (!props.noticia.updated_at || !props.noticia.data_publicacao) return null;

  try {
    const dataPublicacao = new Date(props.noticia.data_publicacao);
    const dataAtualizacao = new Date(props.noticia.updated_at);

    // Se a diferença for menor que 1 minuto, não mostrar
    const diffMinutes =
      Math.abs(dataAtualizacao - dataPublicacao) / (1000 * 60);
    if (diffMinutes < 1) return null;

    const agora = new Date();
    const diffMs = agora - dataAtualizacao;
    const diffHoras = Math.floor(diffMs / (1000 * 60 * 60));

    if (diffHoras < 24) {
      if (diffHoras < 1) {
        const minutos = Math.floor(diffMs / (1000 * 60));
        return `há ${minutos} ${minutos === 1 ? 'minuto' : 'minutos'}`;
      }
      return `há ${diffHoras} ${diffHoras === 1 ? 'hora' : 'horas'}`;
    }

    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(dataAtualizacao);
  } catch (e) {
    return null;
  }
});

// Tempo de leitura estimado
const tempoLeitura = computed(() => {
  if (!props.noticia.conteudo) return 1;

  const textoSemTags = props.noticia.conteudo.replace(/<[^>]*>/g, '');
  const palavras = textoSemTags.split(/\s+/).length;
  const minutos = Math.ceil(palavras / 200);
  return minutos;
});

// Métodos
const compartilhar = plataforma => {
  window.open(
    urlsCompartilhamento.value[plataforma],
    '_blank',
    'width=600,height=400'
  );
};

const copiarLink = () => {
  navigator.clipboard.writeText(urlAtual.value).then(() => {
    linkCopiado.value = true;
    setTimeout(() => {
      linkCopiado.value = false;
    }, 3000);
  });
};

const handleImageError = event => {
  imagemCarregada.value = false;
  event.target.src = '';
};

const voltarParaLista = () => {
  router.visit('/noticias', {
    preserveScroll: true,
    method: 'get',
  });
};

const navegarComScroll = url => {
  router.visit(url, {
    onSuccess: () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
  });
};
</script>

<template>
  <Head :title="noticia.titulo" />
  <Header />
  <SiteNavbar />

  <main class="bg-gray-50 py-4 sm:py-8">
    <div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6">
      <!-- Breadcrumbs -->
      <nav
        class="mb-4 sm:mb-6 text-xs sm:text-sm text-gray-600"
        aria-label="Breadcrumb"
      >
        <ol class="flex flex-wrap items-center">
          <li class="flex items-center">
            <Link href="/" class="hover:text-blue-600 hover:underline"
              >Home</Link
            >
            <svg
              class="h-3 w-3 sm:h-4 sm:w-4 mx-1 sm:mx-2 text-gray-400"
              viewBox="0 0 16 16"
              fill="currentColor"
            >
              <path d="M6.6 13.4L5.2 12l4-4-4-4 1.4-1.4L12 8z" />
            </svg>
          </li>
          <li class="flex items-center">
            <Link href="/noticias" class="hover:text-blue-600 hover:underline"
              >Notícias</Link
            >
            <svg
              class="h-3 w-3 sm:h-4 sm:w-4 mx-1 sm:mx-2 text-gray-400"
              viewBox="0 0 16 16"
              fill="currentColor"
            >
              <path d="M6.6 13.4L5.2 12l4-4-4-4 1.4-1.4L12 8z" />
            </svg>
          </li>
          <li class="text-gray-800 truncate text-xs sm:text-sm">
            {{ noticia.titulo }}
          </li>
        </ol>
      </nav>

      <!-- Cartão principal da notícia -->
      <article
        class="bg-white rounded-lg sm:rounded-xl shadow-sm overflow-hidden"
      >
        <!-- Título e meta -->
        <div class="p-4 sm:p-6 pb-4 sm:pb-0">
          <h1
            class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 mb-3 sm:mb-4 leading-tight"
          >
            {{ noticia.titulo }}
          </h1>

          <!-- Metadados da notícia -->
          <div
            class="flex flex-wrap items-center text-gray-600 gap-x-2 sm:gap-x-4 gap-y-2 mb-3 sm:mb-4 text-xs sm:text-sm"
          >
            <!-- Data de publicação -->
            <div class="flex items-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 sm:h-5 sm:w-5 mr-1 sm:mr-1.5"
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
              {{ dataPublicacaoFormatada }}
            </div>

            <!-- Última atualização (se disponível) -->
            <div v-if="dataAtualizacaoFormatada" class="flex items-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 sm:h-5 sm:w-5 mr-1 sm:mr-1.5"
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
              <span class="hidden sm:inline mr-1">Atualizado</span>
              {{ dataAtualizacaoFormatada }}
            </div>

            <!-- Tempo de leitura -->
            <div class="flex items-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 sm:h-5 sm:w-5 mr-1 sm:mr-1.5"
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
              {{ tempoLeitura }} {{ tempoLeitura === 1 ? 'minuto' : 'minutos' }}
              de leitura
            </div>
          </div>

          <!-- Descrição curta -->
          <p
            v-if="noticia.descricao_curta"
            class="text-base sm:text-lg text-gray-700 mb-4 sm:mb-6 leading-relaxed"
          >
            {{ noticia.descricao_curta }}
          </p>
        </div>

        <!-- Carrossel de Imagens -->
        <ImageCarousel v-if="imageUrls.length > 0" :images="imageUrls" />

        <!-- Conteúdo da notícia (sem imagens) -->
        <div class="p-4 sm:p-6 pt-2 sm:pt-4">
          <div
            v-if="conteudoSemImagens"
            class="prose prose-sm sm:prose max-w-none"
            v-html="conteudoSemImagens"
          ></div>
          <div v-else class="text-gray-500 italic text-center py-8">
            Sem conteúdo detalhado disponível.
          </div>
        </div>

        <!-- Compartilhamento -->
        <div class="p-4 sm:p-6 pt-2 sm:pt-0 border-t border-gray-100">
          <div
            class="mt-2 flex flex-col sm:flex-row sm:items-center sm:justify-center gap-3 sm:gap-4"
          >
            <span class="font-semibold">Compartilhar Notícia:</span>
            <div class="items-center flex flex-wrap gap-2">
              <!-- Facebook -->
              <button
                @click="compartilhar('facebook')"
                class="flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 bg-blue-600 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                aria-label="Compartilhar no Facebook"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                  />
                </svg>
                <span>Facebook</span>
              </button>

              <!-- X (Twitter) -->
              <button
                @click="compartilhar('x')"
                class="flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 bg-black text-white text-xs sm:text-sm rounded-lg hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-1"
                aria-label="Compartilhar no X"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                  />
                </svg>
                <span>X</span>
              </button>

              <!-- WhatsApp -->
              <button
                @click="compartilhar('whatsapp')"
                class="flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 bg-green-500 text-white text-xs sm:text-sm rounded-lg hover:bg-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-1"
                aria-label="Compartilhar no WhatsApp"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                  />
                </svg>
                <span>WhatsApp</span>
              </button>

              <!-- Telegram -->
              <button
                @click="compartilhar('telegram')"
                class="flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 bg-blue-500 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1"
                aria-label="Compartilhar no Telegram"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                  />
                </svg>
                <span>Telegram</span>
              </button>

              <!-- Copiar link -->
              <button
                @click="copiarLink"
                class="flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-200 text-gray-700 text-xs sm:text-sm rounded-lg hover:bg-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-1"
                :aria-label="
                  linkCopiado ? 'Link copiado' : 'Copiar link da notícia'
                "
              >
                <svg
                  v-if="!linkCopiado"
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                <span>{{ linkCopiado ? 'Copiado!' : 'Copiar link' }}</span>
              </button>
            </div>
          </div>
        </div>
      </article>

      <!-- Navegação entre notícias -->
      <div
        v-if="noticiaAnterior || proximaNoticia"
        class="mt-6 sm:mt-8 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4"
      >
        <!-- Notícia anterior -->
        <Link
          v-if="noticiaAnterior"
          :href="`/noticias/${noticiaAnterior.id}`"
          class="flex items-center gap-3 p-3 sm:p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow group"
          @click.prevent="navegarComScroll(`/noticias/${noticiaAnterior.id}`)"
        >
          <svg
            class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-[#bea55a] transition-colors flex-shrink-0"
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
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-500 mb-1">Notícia anterior</p>
            <p
              class="text-sm sm:text-base font-medium text-gray-900 line-clamp-2 group-hover:text-[#bea55a] transition-colors"
            >
              {{ noticiaAnterior.titulo }}
            </p>
          </div>
        </Link>

        <!-- Espaço vazio se não houver notícia anterior -->
        <div v-else></div>

        <!-- Próxima notícia -->
        <Link
          v-if="proximaNoticia"
          :href="`/noticias/${proximaNoticia.id}`"
          class="flex items-center gap-3 p-3 sm:p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow group"
          @click.prevent="navegarComScroll(`/noticias/${proximaNoticia.id}`)"
        >
          <div class="flex-1 min-w-0 text-right">
            <p class="text-xs text-gray-500 mb-1">Próxima notícia</p>
            <p
              class="text-sm sm:text-base font-medium text-gray-900 line-clamp-2 group-hover:text-[#bea55a] transition-colors"
            >
              {{ proximaNoticia.titulo }}
            </p>
          </div>
          <svg
            class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-[#bea55a] transition-colors flex-shrink-0"
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
        </Link>
      </div>

      <!-- Botão voltar para lista -->
      <div class="mt-6 sm:mt-8 text-center">
        <button
          @click="voltarParaLista"
          class="inline-flex items-center gap-2 px-5 py-2.5 sm:px-6 sm:py-3 bg-gray-100 text-gray-800 font-bold rounded-full hover:from-yellow-600 hover:to-[#bea55a] border border-gray-800 focus:outline-none transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
        >
          <svg
            class="w-4 h-4 sm:w-5 sm:h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          <span>Voltar para todas as notícias</span>
        </button>
      </div>
    </div>
  </main>

  <Footer />
</template>

<style scoped>
/* Estilos para o conteúdo da notícia */
:deep(.prose) {
  color: #374151;
  line-height: 1.75;
  max-width: 100%;
}

:deep(.prose h2) {
  color: #1f2937;
  font-weight: 700;
  font-size: 1.5rem;
  margin-top: 2rem;
  margin-bottom: 1rem;
  line-height: 1.3;
}

:deep(.prose h3) {
  color: #1f2937;
  font-weight: 600;
  font-size: 1.25rem;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  line-height: 1.4;
}

:deep(.prose h4) {
  color: #1f2937;
  font-weight: 600;
  font-size: 1.125rem;
  margin-top: 1.25rem;
  margin-bottom: 0.5rem;
}

:deep(.prose p) {
  /* margin-bottom: 1.25rem; */ /* Tamanho do espaçamento */
  line-height: 1.75;
}

:deep(.prose strong) {
  color: #1f2937;
  font-weight: 600;
}

:deep(.prose a) {
  color: #2563eb;
  text-decoration: underline;
  font-weight: 500;
}

:deep(.prose a:hover) {
  color: #1d4ed8;
}

:deep(.prose ul),
:deep(.prose ol) {
  margin: 1.25rem 0;
  padding-left: 1.5rem;
}

:deep(.prose ul) {
  list-style-type: disc;
}

:deep(.prose ol) {
  list-style-type: decimal;
}

:deep(.prose li) {
  margin-bottom: 0.5rem;
  line-height: 1.75;
}

:deep(.prose blockquote) {
  border-left: 4px solid #bea55a;
  padding-left: 1rem;
  margin: 1.5rem 0;
  font-style: italic;
  color: #4b5563;
}

:deep(.prose code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
  font-family: 'Courier New', monospace;
}

:deep(.prose pre) {
  background-color: #1f2937;
  color: #f3f4f6;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1.5rem 0;
}

:deep(.prose table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1.5rem 0;
}

:deep(.prose th) {
  background-color: #f9fafb;
  font-weight: 600;
  text-align: left;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
}

:deep(.prose td) {
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
}

:deep(.prose hr) {
  border: none;
  border-top: 2px solid #e5e7eb;
  margin: 2rem 0;
}

:deep(.prose iframe),
:deep(.prose video) {
  width: 100%;
  max-width: 100%;
  height: 200px;
  margin: 1.5rem 0;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

@media (min-width: 480px) {
  :deep(.prose iframe),
  :deep(.prose video) {
    height: 250px;
  }
}

@media (min-width: 640px) {
  :deep(.prose iframe),
  :deep(.prose video) {
    height: 300px;
    max-width: 560px;
  }
}

@media (min-width: 1024px) {
  :deep(.prose iframe),
  :deep(.prose video) {
    height: 315px;
  }
}

:deep(.prose iframe[src*='youtube']) {
  aspect-ratio: 16 / 9;
  height: auto;
}

/* Transições */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

.transition-shadow {
  transition-property: box-shadow;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

.transition-transform {
  transition-property: transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out forwards;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
  .transition-colors,
  .transition-shadow,
  .transition-transform,
  .animate-fade-in {
    transition: none;
    animation: none;
  }
}

@media (max-width: 479px) {
  :deep(.prose) {
    font-size: 0.9rem;
  }

  :deep(.prose h1) {
    font-size: 1.375rem;
    line-height: 1.2;
  }

  :deep(.prose h2) {
    font-size: 1.125rem;
  }

  :deep(.prose h3) {
    font-size: 1rem;
  }
}

@media (max-width: 639px) {
  :deep(.prose table) {
    font-size: 0.8rem;
  }

  :deep(.prose table th),
  :deep(.prose table td) {
    padding: 0.375rem;
  }
}

@media (hover: hover) {
  button:hover {
    transform: translateY(-1px);
  }

  :deep(.prose a:hover) {
    text-decoration: none;
    background-color: rgba(37, 99, 235, 0.1);
    padding: 0 0.125rem;
    border-radius: 0.25rem;
  }
}
</style>
