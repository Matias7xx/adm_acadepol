<template>
  <div class="flex flex-col items-center space-y-2 w-full max-w-lg mx-auto">
    <div class="relative w-full">
      <input
        type="text"
        v-model="searchQuery"
        @keyup.enter="searchOnPage"
        placeholder="Pesquisar na página..."
        class="pl-10 pr-10 py-2 border rounded-lg focus:ring-2 focus:ring-[#bea55a] focus:border-[#bea55a] w-full transition-all bg-white text-gray-800 border-gray-300 shadow-sm"
      />
      <div
        class="absolute left-3 top-2.5 text-gray-500 cursor-pointer hover:text-yellow-500"
        @click="searchOnPage"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>
      <button
        v-if="searchQuery"
        @click="clearSearch"
        class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700"
      >
        ✕
      </button>
    </div>
    <!-- <div v-if="matchCount > 0" class="text-sm text-gray-600">
      {{ matchCount }} resultado{{ matchCount > 1 ? 's' : '' }} encontrado{{ matchCount > 1 ? 's' : '' }}
    </div>
    <div v-else-if="searchQuery && hasSearched && matchCount === 0" class="text-sm text-red-500">
      Nenhum resultado encontrado.
    </div> -->
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue';

const searchQuery = ref('');
const matchCount = ref(0);
const hasSearched = ref(false);

function searchOnPage() {
  // Remover destaques anteriores
  clearHighlights();

  if (!searchQuery.value.trim()) {
    matchCount.value = 0;
    hasSearched.value = true;
    return;
  }

  // Buscar no conteúdo da página (exclui elementos de script, style, etc.)
  const searchText = searchQuery.value.toLowerCase();
  const elements = document.querySelectorAll(
    'body *:not(script):not(style):not(noscript):not(iframe)'
  );

  let count = 0;

  elements.forEach(element => {
    if (element.childNodes.length > 0) {
      element.childNodes.forEach(node => {
        // Procurar apenas em nós de texto
        if (node.nodeType === Node.TEXT_NODE) {
          const text = node.textContent.toLowerCase();
          if (text.includes(searchText)) {
            // Criar um elemento temporário para substituir o texto
            const tempElement = document.createElement('span');
            const regex = new RegExp(`(${escapeRegExp(searchText)})`, 'gi');
            tempElement.innerHTML = node.textContent.replace(
              regex,
              '<span class="highlight-search bg-yellow-200">$1</span>'
            );

            // Substituir o nó de texto pelos elementos criados
            while (tempElement.firstChild) {
              node.parentNode.insertBefore(tempElement.firstChild, node);
            }
            node.parentNode.removeChild(node);
            count++;
          }
        }
      });
    }
  });

  matchCount.value = count;
  hasSearched.value = true;

  // Se houver resultados, role até o primeiro resultado
  const firstMatch = document.querySelector('.highlight-search');
  if (firstMatch) {
    firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function clearSearch() {
  searchQuery.value = '';
  clearHighlights();
  matchCount.value = 0;
  hasSearched.value = false;
}

function clearHighlights() {
  // Remover todos os destaques anteriores
  document.querySelectorAll('.highlight-search').forEach(el => {
    const parent = el.parentNode;
    parent.replaceChild(document.createTextNode(el.textContent), el);
    parent.normalize(); // Juntar nós de texto adjacentes
  });
}

// Função auxiliar para escapar caracteres especiais em regex
function escapeRegExp(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Limpar destaques quando o componente for desmontado
onBeforeUnmount(() => {
  clearHighlights();
});
</script>

<style>
.highlight-search {
  background-color: rgba(255, 222, 0, 0.4);
  font-weight: bold;
  border-radius: 2px;
  padding: 0 2px;
}
</style>
