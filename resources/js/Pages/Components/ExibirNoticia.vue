<script setup>
import { ref, computed } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import Header from './Header.vue';
import SiteNavbar from './SiteNavbar.vue';
import Footer from './Footer.vue';

// Propriedades do componente
const props = defineProps({
  noticia: {
    type: Object,
    required: true,
  },
  proximaNoticia: {
    type: Object,
    default: () => null,
  },
  noticiaAnterior: {
    type: Object,
    default: () => null,
  },
});

// Estado
const linkCopiado = ref(false);
const imagemCarregada = ref(true);

// URLs para compartilhamento
const urlAtual = computed(() => {
  return window.location.href;
});

const urlsCompartilhamento = computed(() => {
  const url = encodeURIComponent(urlAtual.value);
  const titulo = encodeURIComponent(props.noticia.titulo);

  return {
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
    twitter: `https://twitter.com/intent/tweet?url=${url}&text=${titulo}`,
    whatsapp: `https://api.whatsapp.com/send?text=${titulo}%20${url}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${url}`,
  };
});

// Data formatada com última atualização
const dataPublicacaoFormatada = computed(() => {
  if (!props.noticia.data_publicacao_iso) return props.noticia.data_publicacao;

  try {
    const data = new Date(props.noticia.data_publicacao_iso);
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }).format(data);
  } catch (e) {
    return props.noticia.data_publicacao;
  }
});

const dataAtualizacaoFormatada = computed(() => {
  if (!props.noticia.updated_at_iso) return null;

  try {
    const dataAtualizacao = new Date(props.noticia.updated_at_iso);
    const agora = new Date();
    const diff = agora - dataAtualizacao;

    // Se for menos de 24 horas, mostra "há X horas"
    if (diff < 86400000) {
      // 24h em milissegundos
      const horas = Math.floor(diff / 3600000);
      if (horas === 0) {
        const minutos = Math.floor(diff / 60000);
        return `há ${minutos} ${minutos === 1 ? 'minuto' : 'minutos'}`;
      }
      return `há ${horas} ${horas === 1 ? 'hora' : 'horas'}`;
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

  // Conta o número de palavras no conteúdo (remove tags HTML)
  const textoSemTags = props.noticia.conteudo.replace(/<[^>]*>/g, '');
  const palavras = textoSemTags.split(/\s+/).length;

  // Média de 200 palavras por minuto
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
  event.target.src = ''; // Imagem padrão em caso de erro
};

const voltarParaLista = () => {
  // Use o router do Inertia para navegação
  router.visit('/noticias', {
    preserveScroll: true,
    method: 'get',
  });
};

// Scroll suave ao navegar entre notícias
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
              <span class="sm:hidden mr-1">Atualizado</span>
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
              {{ tempoLeitura }} min
            </div>

            <!-- Visualizações -->
            <!-- <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-1 sm:mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
              </svg>
              {{ noticia.visualizacoes }}
            </div> -->

            <!-- Badge de destaque -->
            <div
              v-if="noticia.destaque"
              class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded-full"
            >
              Destaque
            </div>
          </div>
        </div>

        <!-- Imagem principal -->
        <!-- <div v-if="noticia.imagem && imagemCarregada" class="relative">
          <div class="aspect-[4/3] sm:aspect-[16/10] lg:aspect-[16/9] overflow-hidden">
            <img 
              :src="noticia.imagem" 
              :alt="noticia.titulo" 
              class="w-full h-full object-cover"
              @error="handleImageError"
            />
            <div class="absolute bottom-0 right-0 bg-white/80 text-xs text-gray-600 py-1 px-2 rounded-tl-md">
              Foto: ACADEPOL/PB
            </div>
          </div>
        </div> -->

        <div class="p-4 sm:p-6">
          <!-- Descrição curta destacada -->
          <div
            class="mb-6 sm:mb-8 text-base sm:text-lg text-gray-700 font-medium border-l-4 border-[#bea55a] pl-3 sm:pl-4 py-2 bg-yellow-50 rounded-r-md"
          >
            {{ noticia.descricao_curta }}
          </div>

          <!-- Conteúdo principal -->
          <div
            class="prose prose-sm sm:prose-base lg:prose-lg max-w-none mb-8 sm:mb-12"
            v-html="noticia.conteudo"
          ></div>

          <!-- Barra de compartilhamento -->
          <div
            class="flex flex-col sm:flex-row sm:justify-between sm:items-center border-t border-gray-200 pt-4 sm:pt-6 mt-6 sm:mt-8 gap-4"
          >
            <button
              @click="voltarParaLista"
              class="flex items-center justify-center sm:justify-start text-gray-600 hover:text-[#a38e4d] transition-colors py-2 px-4 rounded-lg hover:bg-gray-50"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                  clip-rule="evenodd"
                />
              </svg>
              Voltar para notícias
            </button>

            <div
              class="flex items-center justify-center sm:justify-end space-x-2"
            >
              <span class="text-sm text-gray-500 mr-2 hidden sm:inline"
                >Compartilhar:</span
              >
              <span class="text-xs text-gray-500 mr-2 sm:hidden"
                >Compartilhar:</span
              >

              <div class="flex space-x-1 sm:space-x-2">
                <button
                  @click="compartilhar('facebook')"
                  class="text-blue-600 hover:text-blue-800 hover:bg-blue-50 p-2 rounded-full transition-colors"
                  aria-label="Compartilhar no Facebook"
                >
                  <svg
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    class="h-4 w-4 sm:h-5 sm:w-5"
                  >
                    <path
                      d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96C15.9 21.58 18.03 20.39 19.63 18.64C21.22 16.89 22.2 14.64 22.2 12.29C22.2 6.62 17.7 2.04 12 2.04Z"
                    />
                  </svg>
                </button>

                <button
                  @click="compartilhar('twitter')"
                  class="text-black hover:text-gray-700 hover:bg-gray-50 p-2 rounded-full transition-colors"
                  aria-label="Compartilhar no Twitter"
                >
                  <svg
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    class="h-4 w-4 sm:h-5 sm:w-5"
                  >
                    <path
                      d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                    />
                  </svg>
                </button>

                <button
                  @click="compartilhar('whatsapp')"
                  class="text-green-600 hover:text-green-800 hover:bg-green-50 p-2 rounded-full transition-colors"
                  aria-label="Compartilhar no WhatsApp"
                >
                  <svg
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    class="h-4 w-4 sm:h-5 sm:w-5"
                  >
                    <path
                      d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                    />
                  </svg>
                </button>

                <button
                  @click="compartilhar('linkedin')"
                  class="text-blue-700 hover:text-blue-900 hover:bg-blue-50 p-2 rounded-full transition-colors"
                  aria-label="Compartilhar no LinkedIn"
                >
                  <svg
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    class="h-4 w-4 sm:h-5 sm:w-5"
                  >
                    <path
                      d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"
                    />
                  </svg>
                </button>

                <button
                  @click="copiarLink"
                  class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 p-2 rounded-full transition-colors"
                  aria-label="Copiar link"
                >
                  <svg
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    class="h-4 w-4 sm:h-5 sm:w-5"
                  >
                    <path
                      d="M7.5 3.75a1.5 1.5 0 00-1.5 1.5v1.5h13.5V6.75A2.25 2.25 0 0017.25 4.5h-5.5A1.5 1.5 0 0010 3.75h-2.5zM15 5.25a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v.75h-3v-.75zM6 8.25h12v11.25A2.25 2.25 0 0115.75 21h-7.5A2.25 2.25 0 016 18.75V8.25z"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </article>

      <!-- Navegação entre notícias -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4 mt-6 sm:mt-8">
        <!-- Notícia anterior -->
        <div
          class="bg-white rounded-lg shadow-sm p-3 sm:p-4 hover:shadow-md transition-shadow"
        >
          <Link
            v-if="noticiaAnterior"
            :href="`/noticias/${noticiaAnterior.id}`"
            class="flex flex-col h-full"
            @click.prevent="navegarComScroll(`/noticias/${noticiaAnterior.id}`)"
          >
            <span
              class="text-xs sm:text-sm text-gray-500 mb-1 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-3 w-3 sm:h-4 sm:w-4 mr-1"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z"
                  clip-rule="evenodd"
                />
              </svg>
              Notícia Anterior
            </span>
            <span
              class="font-medium text-[#bea55a] hover:text-[#a38e4d] transition-colors line-clamp-2 text-sm sm:text-base"
            >
              {{ noticiaAnterior.titulo }}
            </span>
          </Link>
          <div v-else class="flex flex-col h-full opacity-50">
            <span
              class="text-xs sm:text-sm text-gray-500 mb-1 flex items-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-3 w-3 sm:h-4 sm:w-4 mr-1"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z"
                  clip-rule="evenodd"
                />
              </svg>
              Notícia Anterior
            </span>
            <span class="font-medium text-gray-400 text-sm sm:text-base">
              Não há notícia anterior
            </span>
          </div>
        </div>

        <!-- Próxima notícia -->
        <div
          class="bg-white rounded-lg shadow-sm p-3 sm:p-4 hover:shadow-md transition-shadow"
        >
          <Link
            v-if="proximaNoticia"
            :href="`/noticias/${proximaNoticia.id}`"
            class="flex flex-col h-full items-end text-right"
            @click.prevent="navegarComScroll(`/noticias/${proximaNoticia.id}`)"
          >
            <span
              class="text-xs sm:text-sm text-gray-500 mb-1 flex items-center"
            >
              Próxima Notícia
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-3 w-3 sm:h-4 sm:w-4 ml-1"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </span>
            <span
              class="font-medium text-[#bea55a] hover:text-[#a38e4d] transition-colors line-clamp-2 text-sm sm:text-base"
            >
              {{ proximaNoticia.titulo }}
            </span>
          </Link>
          <div
            v-else
            class="flex flex-col h-full items-end text-right opacity-50"
          >
            <span
              class="text-xs sm:text-sm text-gray-500 mb-1 flex items-center"
            >
              Próxima Notícia
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-3 w-3 sm:h-4 sm:w-4 ml-1"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </span>
            <span class="font-medium text-gray-400 text-sm sm:text-base">
              Não há próxima notícia
            </span>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Mensagem ao copiar link -->
  <div
    v-if="linkCopiado"
    class="fixed bottom-4 right-4 bg-gray-800 text-white px-3 sm:px-4 py-2 rounded-lg shadow-lg flex items-center z-50 animate-fade-in text-sm"
  >
    <svg
      xmlns="http://www.w3.org/2000/svg"
      class="h-4 w-4 sm:h-5 sm:w-5 mr-2 text-green-400"
      viewBox="0 0 20 20"
      fill="currentColor"
    >
      <path
        fill-rule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
        clip-rule="evenodd"
      />
    </svg>
    <span class="hidden sm:inline"
      >Link copiado para a área de transferência!</span
    >
    <span class="sm:hidden">Link copiado!</span>
  </div>

  <Footer />
</template>

<style>
/* Estilos para o conteúdo gerado pelo HTML - Responsivos */
.prose {
  color: #374151;
  line-height: 1.6;
}

.prose img {
  border-radius: 0.5rem;
  margin: 1.5rem auto;
  max-width: 100%;
  height: auto;
  display: block;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Imagens do conteúdo - tamanhos responsivos */
.prose img {
  max-height: 300px;
  width: auto;
  object-fit: cover;
}

@media (min-width: 640px) {
  .prose img {
    max-height: 400px;
  }
}

@media (min-width: 1024px) {
  .prose img {
    max-height: 500px;
  }
}

.prose h1 {
  font-size: 1.5rem;
  font-weight: 800;
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #111827;
  line-height: 1.3;
}

.prose h2 {
  font-size: 1.25rem;
  font-weight: 700;
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #1f2937;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.prose h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #1f2937;
}

.prose h4 {
  font-size: 1rem;
  font-weight: 600;
  margin-top: 1.25rem;
  margin-bottom: 0.5rem;
  color: #374151;
}

@media (min-width: 640px) {
  .prose h1 {
    font-size: 1.875rem;
  }

  .prose h2 {
    font-size: 1.5rem;
  }

  .prose h3 {
    font-size: 1.25rem;
  }
}

@media (min-width: 1024px) {
  .prose h1 {
    font-size: 2.25rem;
  }

  .prose h2 {
    font-size: 1.75rem;
  }

  .prose h3 {
    font-size: 1.375rem;
  }
}

.prose p {
  margin-bottom: 1.25rem;
  line-height: 1.7;
  font-size: 0.95rem;
}

@media (min-width: 640px) {
  .prose p {
    font-size: 1rem;
  }
}

.prose ul,
.prose ol {
  list-style-position: inside;
  padding-left: 1rem;
  margin: 1.25rem 0;
  font-size: 0.95rem;
}

@media (min-width: 640px) {
  .prose ul,
  .prose ol {
    font-size: 1rem;
    padding-left: 1.5rem;
  }
}

.prose ul {
  list-style-type: disc;
}

.prose ol {
  list-style-type: decimal;
}

.prose li {
  margin-bottom: 0.5rem;
}

.prose a {
  color: #2563eb;
  text-decoration: underline;
  transition: color 0.2s;
  word-break: break-word;
}

.prose a:hover {
  color: #1d4ed8;
}

.prose blockquote {
  border-left: 4px solid #bea55a;
  padding-left: 1rem;
  font-style: italic;
  color: #4b5563;
  margin: 1.5rem 0;
  background-color: #f9fafb;
  padding: 1rem;
  border-radius: 0.375rem;
  font-size: 0.95rem;
}

@media (min-width: 640px) {
  .prose blockquote {
    font-size: 1rem;
    padding: 1.25rem;
  }
}

.prose table {
  width: 100%;
  border-collapse: collapse;
  margin: 2rem 0;
  font-size: 0.875rem;
  overflow-x: auto;
  display: block;
  white-space: nowrap;
}

@media (min-width: 640px) {
  .prose table {
    font-size: 0.95rem;
    display: table;
    white-space: normal;
  }
}

.prose table th {
  background-color: #f3f4f6;
  font-weight: 600;
  text-align: left;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
}

@media (min-width: 640px) {
  .prose table th {
    padding: 0.75rem;
  }
}

.prose table td {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
}

@media (min-width: 640px) {
  .prose table td {
    padding: 0.75rem;
  }
}

.prose table tr:nth-child(even) {
  background-color: #f9fafb;
}

/* Media - responsiva */
.prose iframe,
.prose video {
  width: 100%;
  max-width: 100%;
  height: 200px;
  margin: 1.5rem auto;
  display: block;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

@media (min-width: 480px) {
  .prose iframe,
  .prose video {
    height: 250px;
  }
}

@media (min-width: 640px) {
  .prose iframe,
  .prose video {
    height: 300px;
    max-width: 560px;
  }
}

@media (min-width: 1024px) {
  .prose iframe,
  .prose video {
    height: 315px;
  }
}

/* Estilos para vídeos do YouTube */
.prose iframe[src*='youtube'] {
  aspect-ratio: 16 / 9;
  height: auto;
}

/* Animações */
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

/* Linha de corte para texto longo */
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

/* Aspect ratio para imagens de capa */
.aspect-\[4\/3\] {
  aspect-ratio: 4 / 3;
}

.aspect-\[16\/10\] {
  aspect-ratio: 16 / 10;
}

.aspect-\[16\/9\] {
  aspect-ratio: 16 / 9;
}

/* Melhorias para acessibilidade e UX */
@media (prefers-reduced-motion: reduce) {
  .transition-colors,
  .transition-shadow,
  .transition-transform,
  .animate-fade-in {
    transition: none;
    animation: none;
  }
}

/* Melhor legibilidade em dispositivos pequenos */
@media (max-width: 479px) {
  .prose {
    font-size: 0.9rem;
  }

  .prose h1 {
    font-size: 1.375rem;
    line-height: 1.2;
  }

  .prose h2 {
    font-size: 1.125rem;
  }

  .prose h3 {
    font-size: 1rem;
  }
}

/* Estilo para tabelas em dispositivos pequenos */
@media (max-width: 639px) {
  .prose table {
    font-size: 0.8rem;
  }

  .prose table th,
  .prose table td {
    padding: 0.375rem;
  }
}

/* Hover states melhorados */
@media (hover: hover) {
  button:hover {
    transform: translateY(-1px);
  }

  .prose a:hover {
    text-decoration: none;
    background-color: rgba(37, 99, 235, 0.1);
    padding: 0 0.125rem;
    border-radius: 0.25rem;
  }
}
</style>
