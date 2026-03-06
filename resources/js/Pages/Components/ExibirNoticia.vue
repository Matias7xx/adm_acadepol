<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SiteNavbar from './SiteNavbar.vue';
import Footer from './Footer.vue';
import Header from './Header.vue';
import ImageCarousel from './ImageCarousel.vue';

const props = defineProps({
  noticia: { type: Object, required: true },
  proximaNoticia: { type: Object, default: null },
  noticiaAnterior: { type: Object, default: null },
});

const linkCopiado = ref(false);

const extractImages = htmlContent => {
  if (!htmlContent) return [];
  const div = document.createElement('div');
  div.innerHTML = htmlContent;
  return [...div.querySelectorAll('img')]
    .map(img => img.getAttribute('src'))
    .filter(src => src && !src.startsWith('data:'));
};

const removeImagesFromContent = htmlContent => {
  if (!htmlContent) return '';
  const div = document.createElement('div');
  div.innerHTML = htmlContent;
  div.querySelectorAll('img').forEach(img => img.remove());
  return div.innerHTML;
};

const imageUrls = computed(() => {
  if (props.noticia.carousel_images?.length)
    return props.noticia.carousel_images.map(img => img.url);
  return extractImages(props.noticia.conteudo);
});

const conteudoSemImagens = computed(() =>
  removeImagesFromContent(props.noticia.conteudo)
);

const urlAtual = computed(() => window.location.href);

const urlsCompartilhamento = computed(() => ({
  facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(urlAtual.value)}`,
  x: `https://twitter.com/intent/tweet?url=${encodeURIComponent(urlAtual.value)}&text=${encodeURIComponent(props.noticia.titulo)}`,
  whatsapp: `https://wa.me/?text=${encodeURIComponent(props.noticia.titulo + ' - ' + urlAtual.value)}`,
  telegram: `https://t.me/share/url?url=${encodeURIComponent(urlAtual.value)}&text=${encodeURIComponent(props.noticia.titulo)}`,
}));

const dataPublicacaoFormatada = computed(() => {
  try {
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    }).format(new Date(props.noticia.data_publicacao_iso));
  } catch {
    return props.noticia.data_publicacao;
  }
});

const dataAtualizacaoFormatada = computed(() => {
  if (!props.noticia.updated_at_iso || !props.noticia.data_publicacao_iso)
    return null;
  try {
    const pub = new Date(props.noticia.data_publicacao_iso);
    const upd = new Date(props.noticia.updated_at_iso);
    if (Math.abs(upd - pub) / (1000 * 60) < 1) return null;
    const diffMs = Date.now() - upd;
    const diffHoras = Math.floor(diffMs / (1000 * 60 * 60));
    if (diffHoras < 1) {
      const min = Math.floor(diffMs / (1000 * 60));
      return `há ${min} ${min === 1 ? 'minuto' : 'minutos'}`;
    }
    if (diffHoras < 24)
      return `há ${diffHoras} ${diffHoras === 1 ? 'hora' : 'horas'}`;
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(upd);
  } catch {
    return null;
  }
});

const tempoLeitura = computed(() => {
  if (!props.noticia.conteudo) return 1;
  const palavras = props.noticia.conteudo
    .replace(/<[^>]*>/g, '')
    .split(/\s+/).length;
  return Math.ceil(palavras / 200);
});

const compartilhar = plataforma =>
  window.open(
    urlsCompartilhamento.value[plataforma],
    '_blank',
    'width=600,height=400'
  );
const copiarLink = () => {
  navigator.clipboard.writeText(urlAtual.value).then(() => {
    linkCopiado.value = true;
    setTimeout(() => {
      linkCopiado.value = false;
    }, 3000);
  });
};
const voltarParaLista = () =>
  router.visit('/noticias', { preserveScroll: true, method: 'get' });
const navegarComScroll = url =>
  router.visit(url, {
    onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
  });
</script>

<template>
  <Head :title="noticia.titulo" />
  <Header />
  <SiteNavbar />

  <main class="bg-gray-50 py-6 sm:py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
      <!-- Breadcrumb -->
      <nav
        class="flex items-center gap-1.5 text-xs text-gray-400 mb-5"
        aria-label="Breadcrumb"
      >
        <Link href="/" class="hover:text-gray-600 transition-colors">Home</Link>
        <svg class="h-3 w-3" viewBox="0 0 16 16" fill="currentColor">
          <path d="M6.6 13.4L5.2 12l4-4-4-4 1.4-1.4L12 8z" />
        </svg>
        <Link href="/noticias" class="hover:text-gray-600 transition-colors"
          >Notícias</Link
        >
        <svg class="h-3 w-3" viewBox="0 0 16 16" fill="currentColor">
          <path d="M6.6 13.4L5.2 12l4-4-4-4 1.4-1.4L12 8z" />
        </svg>
        <span class="text-gray-500 truncate">{{ noticia.titulo }}</span>
      </nav>

      <!-- Artigo -->
      <article
        class="bg-white rounded-xl border border-gray-200 overflow-hidden"
        style="box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06)"
      >
        <!-- Título + metadados -->
        <div class="px-5 sm:px-8 pt-6 sm:pt-8 pb-4">
          <h1
            class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 leading-tight mb-4"
          >
            {{ noticia.titulo }}
          </h1>

          <!-- Meta -->
          <div
            class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-400"
          >
            <div class="flex items-center gap-1.5">
              <svg
                class="h-3.5 w-3.5 flex-shrink-0"
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
              {{ dataPublicacaoFormatada }}
            </div>

            <div
              v-if="dataAtualizacaoFormatada"
              class="flex items-center gap-1.5"
            >
              <svg
                class="h-3.5 w-3.5 flex-shrink-0"
                style="color: #bea55a"
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
              <span class="hidden sm:inline">Atualizado</span>
              {{ dataAtualizacaoFormatada }}
            </div>

            <div class="flex items-center gap-1.5">
              <svg
                class="h-3.5 w-3.5 flex-shrink-0"
                style="color: #bea55a"
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
              {{ tempoLeitura }}
              {{ tempoLeitura === 1 ? 'minuto' : 'minutos' }} de leitura
            </div>
          </div>
        </div>

        <!-- Carrossel -->
        <div v-if="imageUrls.length > 0" class="px-0">
          <ImageCarousel :images="imageUrls" />
        </div>

        <!-- Conteúdo -->
        <div class="px-5 sm:px-8 py-6">
          <div
            v-if="conteudoSemImagens"
            class="prose prose-sm sm:prose max-w-none"
            v-html="conteudoSemImagens"
          ></div>
          <p v-else class="text-gray-400 italic text-center py-8 text-sm">
            Sem conteúdo detalhado disponível.
          </p>
        </div>

        <!-- Compartilhar -->
        <div class="px-5 sm:px-8 pb-6 pt-2 border-t border-gray-100">
          <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <span
              class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
              >Compartilhar:</span
            >
            <div class="flex flex-wrap gap-2">
              <!-- Facebook -->
              <button
                @click="compartilhar('facebook')"
                class="share-btn share-btn-facebook"
                aria-label="Compartilhar no Facebook"
              >
                <svg
                  class="w-3.5 h-3.5"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                  />
                </svg>
                Facebook
              </button>
              <!-- X -->
              <button
                @click="compartilhar('x')"
                class="share-btn share-btn-x"
                aria-label="Compartilhar no X"
              >
                <svg
                  class="w-3.5 h-3.5"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                  />
                </svg>
                X
              </button>
              <!-- WhatsApp -->
              <button
                @click="compartilhar('whatsapp')"
                class="share-btn share-btn-whatsapp"
                aria-label="Compartilhar no WhatsApp"
              >
                <svg
                  class="w-3.5 h-3.5"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                  />
                </svg>
                WhatsApp
              </button>
              <!-- Telegram -->
              <button
                @click="compartilhar('telegram')"
                class="share-btn share-btn-telegram"
                aria-label="Compartilhar no Telegram"
              >
                <svg
                  class="w-3.5 h-3.5"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                  />
                </svg>
                Telegram
              </button>
              <!-- Copiar link -->
              <button
                @click="copiarLink"
                class="share-btn share-btn-copy"
                :aria-label="
                  linkCopiado ? 'Link copiado' : 'Copiar link da notícia'
                "
              >
                <svg
                  v-if="!linkCopiado"
                  class="w-3.5 h-3.5"
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
                  class="w-3.5 h-3.5"
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
                {{ linkCopiado ? 'Copiado!' : 'Copiar link' }}
              </button>
            </div>
          </div>
        </div>
      </article>

      <!-- Navegação entre notícias -->
      <div
        v-if="noticiaAnterior || proximaNoticia"
        class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3"
      >
        <!-- Anterior -->
        <Link
          v-if="noticiaAnterior"
          :href="`/noticias/${noticiaAnterior.id}`"
          class="nav-noticia-card group"
          @click.prevent="navegarComScroll(`/noticias/${noticiaAnterior.id}`)"
        >
          <svg
            class="nav-noticia-arrow"
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
            <p class="text-xs text-gray-400 mb-0.5">Notícia anterior</p>
            <p
              class="text-sm font-medium text-gray-700 line-clamp-2 group-hover:text-[#bea55a] transition-colors duration-150"
            >
              {{ noticiaAnterior.titulo }}
            </p>
          </div>
        </Link>
        <div v-else></div>

        <!-- Próxima -->
        <Link
          v-if="proximaNoticia"
          :href="`/noticias/${proximaNoticia.id}`"
          class="nav-noticia-card group text-right"
          @click.prevent="navegarComScroll(`/noticias/${proximaNoticia.id}`)"
        >
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-400 mb-0.5">Próxima notícia</p>
            <p
              class="text-sm font-medium text-gray-700 line-clamp-2 group-hover:text-[#bea55a] transition-colors duration-150"
            >
              {{ proximaNoticia.titulo }}
            </p>
          </div>
          <svg
            class="nav-noticia-arrow"
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

      <!-- Voltar -->
      <div class="mt-6 flex justify-center">
        <button @click="voltarParaLista" class="btn-secondary">
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
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Voltar para todas as notícias
        </button>
      </div>
    </div>
  </main>

  <Footer />
</template>

<style scoped>
/* ─── Share buttons ─── */
.share-btn {
  @apply inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-colors duration-150;
}
.share-btn-facebook {
  @apply bg-blue-600   text-white hover:bg-blue-700;
}
.share-btn-x {
  @apply bg-black      text-white hover:bg-gray-800;
}
.share-btn-whatsapp {
  @apply bg-green-500  text-white hover:bg-green-600;
}
.share-btn-telegram {
  @apply bg-sky-500    text-white hover:bg-sky-600;
}
.share-btn-copy {
  @apply bg-gray-100   text-gray-700 hover:bg-gray-200;
}

/* ─── Nav notícias ─── */
.nav-noticia-card {
  @apply flex items-center gap-3 bg-white border border-gray-200 rounded-lg p-4 transition-colors duration-150;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.nav-noticia-card:hover {
  @apply border-gray-300;
}

.nav-noticia-arrow {
  @apply w-4 h-4 text-gray-300 flex-shrink-0 transition-colors duration-150;
}
.group:hover .nav-noticia-arrow {
  color: #bea55a;
}

/* ─── Botão voltar ─── */
.btn-secondary {
  @apply inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-600;
  @apply bg-white border border-gray-200 rounded-md hover:bg-gray-50 hover:border-gray-300 transition-colors duration-150;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* ─── Prose (conteúdo rico) ─── */
:deep(.prose) {
  color: #374151;
  line-height: 1.75;
  max-width: 100%;
}
:deep(.prose h2) {
  color: #1f2937;
  font-weight: 700;
  font-size: 1.4rem;
  margin-top: 1.75rem;
  margin-bottom: 0.875rem;
}
:deep(.prose h3) {
  color: #1f2937;
  font-weight: 600;
  font-size: 1.2rem;
  margin-top: 1.4rem;
  margin-bottom: 0.625rem;
}
:deep(.prose h4) {
  color: #1f2937;
  font-weight: 600;
  font-size: 1.05rem;
  margin-top: 1.2rem;
  margin-bottom: 0.5rem;
}
:deep(.prose p) {
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
  border-left: 3px solid #bea55a;
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
  border-top: 1px solid #e5e7eb;
  margin: 2rem 0;
}
:deep(.prose iframe),
:deep(.prose video) {
  width: 100%;
  height: 200px;
  margin: 1.5rem 0;
  border-radius: 0.5rem;
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
  aspect-ratio: 16/9;
  height: auto;
}

/* Mobile prose */
@media (max-width: 479px) {
  :deep(.prose) {
    font-size: 0.9rem;
  }
  :deep(.prose h2) {
    font-size: 1.1rem;
  }
  :deep(.prose h3) {
    font-size: 1rem;
  }
}
@media (max-width: 639px) {
  :deep(.prose table) {
    font-size: 0.8rem;
  }
  :deep(.prose th),
  :deep(.prose td) {
    padding: 0.375rem;
  }
}

a:focus-visible,
button:focus-visible {
  outline: 2px solid #bea55a;
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .nav-noticia-card,
  .btn-secondary,
  .share-btn {
    @apply transition-none;
  }
}
</style>
