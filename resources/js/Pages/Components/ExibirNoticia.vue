<template>
   <Header />
   <SiteNavbar />
  <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6">
    <!-- Breadcrumbs -->
    <div class="mb-6 text-sm text-gray-600">
      <Link href="/" class="hover:text-blue-600 hover:underline">Home</Link>
      <span class="mx-2">/</span>
      <Link href="/noticias" class="hover:text-blue-600 hover:underline">Notícias</Link>
      <span class="mx-2">/</span>
      <span class="text-gray-800">{{ noticia.titulo }}</span>
    </div>
    
    <!-- Título e meta -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ noticia.titulo }}</h1>
      
      <div class="flex flex-wrap items-center text-gray-600 gap-x-4 gap-y-2">
        <!-- Data -->
        <div class="flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          {{ noticia.data_publicacao }}
        </div>
        
        <!-- Visualizações -->
        <div class="flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
          </svg>
          {{ noticia.visualizacoes }} visualizações
        </div>
        
        <!-- Badge de destaque -->
        <div v-if="noticia.destaque" class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-1 rounded-full">
          Destaque
        </div>
        
        <!-- Compartilhar -->
        <div class="flex items-center ml-auto">
          <button 
            @click="compartilhar('facebook')" 
            class="text-blue-600 hover:text-blue-800 p-1"
            title="Compartilhar no Facebook"
          >
            <svg fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
              <path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96C15.9 21.58 18.03 20.39 19.63 18.64C21.22 16.89 22.2 14.64 22.2 12.29C22.2 6.62 17.7 2.04 12 2.04Z" />
            </svg>
          </button>
          
          <button 
            @click="compartilhar('twitter')" 
            class="text-black hover:text-gray-700 p-1"
            title="Compartilhar no Twitter"
          >
            <svg fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
            </svg>
          </button>
          
          <button 
            @click="compartilhar('whatsapp')" 
            class="text-green-600 hover:text-green-800 p-1"
            title="Compartilhar no WhatsApp"
          >
            <svg fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
            </svg>
          </button>
          
          <button 
            @click="copiarLink" 
            class="text-gray-600 hover:text-gray-800 p-1"
            title="Copiar link"
          >
            <svg fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
              <path d="M7.5 3.75a1.5 1.5 0 00-1.5 1.5v1.5h13.5V6.75A2.25 2.25 0 0017.25 4.5h-5.5A1.5 1.5 0 0010 3.75h-2.5zM15 5.25a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v.75h-3v-.75zM6 8.25h12v11.25A2.25 2.25 0 0115.75 21h-7.5A2.25 2.25 0 016 18.75V8.25z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Imagem principal -->
    <div v-if="noticia.imagem" class="mb-8 rounded-lg overflow-hidden shadow-lg">
      <img 
        :src="noticia.imagem" 
        :alt="noticia.titulo" 
        class="w-full h-auto"
        @error="handleImageError"
      />
    </div>
    
    <!-- Descrição curta destacada -->
    <div class="mb-8 text-lg text-gray-700 font-medium border-l-4 border-[#bea55a] pl-4 py-2 bg-yellow-50">
      {{ noticia.descricao_curta }}
    </div>
    
    <!-- Conteúdo principal -->
    <div class="prose prose-lg max-w-none mb-12" v-html="noticia.conteudo"></div>
    
    <!-- Navegação entre notícias -->
    <div v-if="proximaNoticia || noticiaAnterior" class="border-t border-gray-200 pt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
      <Link 
        v-if="noticiaAnterior" 
        :href="`/noticias/${noticiaAnterior.id}`" 
        class="flex flex-col items-start hover:text-blue-600 group"
      >
        <span class="text-sm text-gray-500 mb-1 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 group-hover:translate-x-[-4px] transition-transform" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
          Notícia Anterior
        </span>
        <span class="font-medium line-clamp-1">{{ noticiaAnterior.titulo }}</span>
      </Link>
      
      <Link 
        v-if="proximaNoticia" 
        :href="`/noticias/${proximaNoticia.id}`" 
        class="flex flex-col items-end ml-auto text-right hover:text-blue-600 group"
      >
        <span class="text-sm text-gray-500 mb-1 flex items-center">
          Próxima Notícia
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 group-hover:translate-x-[4px] transition-transform" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </span>
        <span class="font-medium line-clamp-1">{{ proximaNoticia.titulo }}</span>
      </Link>
    </div>
    
    <!-- Mensagem ao copiar link -->
    <div 
      v-if="linkCopiado" 
      class="fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg flex items-center transition-opacity duration-300"
      :class="linkCopiado ? 'opacity-100' : 'opacity-0'"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
      </svg>
      Link copiado para a área de transferência!
    </div>
  </div>
  <Footer />
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Header from './Header.vue';
import SiteNavbar from './SiteNavbar.vue';
import Footer from './Footer.vue';

// Propriedades do componente
const props = defineProps({
  noticia: {
    type: Object,
    required: true
  },
  proximaNoticia: {
    type: Object,
    default: null
  },
  noticiaAnterior: {
    type: Object,
    default: null
  }
});

// Estado
const linkCopiado = ref(false);

// Métodos
const compartilhar = (plataforma) => {
  const url = window.location.href;
  const titulo = props.noticia.titulo;
  
  let shareUrl = '';
  
  switch (plataforma) {
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
      break;
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(titulo)}`;
      break;
    case 'whatsapp':
      shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(titulo + ' ' + url)}`;
      break;
  }
  
  if (shareUrl) {
    window.open(shareUrl, '_blank', 'width=600,height=400');
  }
};

const copiarLink = () => {
  const url = window.location.href;
  navigator.clipboard.writeText(url).then(() => {
    linkCopiado.value = true;
    setTimeout(() => {
      linkCopiado.value = false;
    }, 3000);
  });
};

const handleImageError = (event) => {
  event.target.src = '/images/placeholder-news.jpg';
};
</script>

<style>
/* Estilos para o conteúdo gerado pelo HTML */
.prose img {
  border-radius: 0.375rem;
  margin: 2rem 0;
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