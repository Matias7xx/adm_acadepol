<script setup>
import { Link } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Obter breadcrumbs das props de navegação
const breadcrumbs = computed(() => usePage().props.navigation?.breadcrumbs || [])

// Função para limpar títulos (remover objetos stringificados)
const cleanTitle = (title) => {
  if (!title) return '';
  
  // Verifica se é um objeto JSON stringificado
  if (typeof title === 'string' && title.startsWith('{') && title.endsWith('}')) {
    try {
      // Se for um objeto, tenta extrair o nome
      const obj = JSON.parse(title);
      return obj.nome || obj.name || 'Item';
    } catch (e) {
      // Em caso de erro, retorna o título original
      return title;
    }
  }
  
  // Se o título não precisar de limpeza, retorna como está
  return title;
}
</script>

<template>
  <nav v-if="breadcrumbs && breadcrumbs.length > 0" 
       class="flex items-center py-3 px-4 md:px-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm mb-4 rounded-md shadow-sm">
    <ol class="inline-flex items-center flex-wrap gap-1 text-sm">
      <li v-for="(item, index) in breadcrumbs" :key="index" class="inline-flex items-center">
        <!-- Item com link (todos menos o último) -->
        <Link v-if="index !== breadcrumbs.length - 1 && item.url" 
              :href="item.url" 
              class="inline-flex items-center text-gray-600 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
          <!-- Home icon para o primeiro item -->
          <svg v-if="index === 0" class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
          </svg>
          <span class="text-sm font-medium">{{ cleanTitle(item.title) }}</span>
        </Link>

        <!-- Separador (apenas se não for o último) -->
        <span v-if="index !== breadcrumbs.length - 1" class="mx-2 text-gray-400 dark:text-gray-600">
          <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </span>

        <!-- Item atual (último item) -->
        <span v-if="index === breadcrumbs.length - 1" 
              class="text-sm font-medium text-blue-600 dark:text-blue-400">
          {{ cleanTitle(item.title) }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
/* Adicione estilos específicos aqui se necessário */
/* Por exemplo, você pode adicionar um efeito de hover diferente */
</style>