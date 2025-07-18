<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

// ===== ESTADOS REATIVOS =====
const diretores = ref([]);
const carregando = ref(true);
const erro = ref(null);
const diretorSelecionado = ref(null);
const filtroAtivo = ref('todos');
const ordenacaoAtiva = ref('cronologica');
const tipoVisualizacao = ref('grid');
const termoBusca = ref('');
const tentativasReconexao = ref(0);
const maxTentativas = 3;
const imagensCarregando = ref({});
const offline = ref(!navigator.onLine);

// Placeholder para imagens com erro
const placeholderProfile = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQwIiBoZWlnaHQ9IjI0MCIgdmlld0JveD0iMCAwIDI0MCAyNDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyNDAiIGhlaWdodD0iMjQwIiBmaWxsPSIjRjNGNEY2Ii8+CjxjaXJjbGUgY3g9IjEyMCIgY3k9IjgwIiByPSIzMCIgZmlsbD0iI0Q5RDlEOSIvPgo8cGF0aCBkPSJNNjAgMTgwQzYwIDE1NS4xNDcgODAuMTQ3IDEzNSAxMDUgMTM1SDEzNUMxNTkuODUzIDEzNSAxODAgMTU1LjE0NyAxODAgMTgwVjIwMEg2MFYxODBaIiBmaWxsPSIjRDlEOUQ5Ii8+Cjx0ZXh0IHg9IjEyMCIgeT0iMjIwIiBmaWxsPSIjOUM5QzlDIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkRpcmV0b3I8L3RleHQ+Cjwvc3ZnPg==';

// Filtros e ordenações disponíveis
const filtrosDisponiveis = [
  { key: 'todos', label: 'Todos', icon: 'M19 7h-3v-2a4 4 0 00-8 0v2h-3a1 1 0 00-1 1v11a3 3 0 003 3h10a3 3 0 003-3v-11a1 1 0 00-1-1z' },
  { key: 'atual', label: 'Atual', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
  { key: 'anteriores', label: 'Anteriores', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' }
];

const ordenacoesDisponiveis = [
  { key: 'cronologica', label: 'Cronológica', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
  { key: 'alfabetica', label: 'Alfabética', icon: 'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12' },
  { key: 'tempo_mandato', label: 'Tempo de Mandato', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' }
];

// ===== COMPUTED =====
const diretoresFiltrados = computed(() => {
  let resultado = diretores.value;
  
  // Filtrar por período
  if (filtroAtivo.value === 'atual') {
    resultado = resultado.filter(diretor => diretor.periodo?.includes('ATUALMENTE') || diretor.atual);
  } else if (filtroAtivo.value === 'anteriores') {
    resultado = resultado.filter(diretor => !diretor.periodo?.includes('ATUALMENTE') && !diretor.atual);
  }
  
  // Filtrar por busca
  if (termoBusca.value) {
    const termo = termoBusca.value.toLowerCase();
    resultado = resultado.filter(diretor =>
      diretor.nome?.toLowerCase().includes(termo) ||
      diretor.periodo?.toLowerCase().includes(termo) ||
      diretor.historico?.toLowerCase().includes(termo)
    );
  }
  
  // Ordenar
  if (ordenacaoAtiva.value === 'alfabetica') {
    resultado = [...resultado].sort((a, b) => a.nome?.localeCompare(b.nome) || 0);
  } else if (ordenacaoAtiva.value === 'cronologica') {
    resultado = [...resultado].sort((a, b) => {
      const ordemA = a.ordem || 0;
      const ordemB = b.ordem || 0;
      return ordemB - ordemA; // Mais recentes primeiro
    });
  } else if (ordenacaoAtiva.value === 'tempo_mandato') {
    resultado = [...resultado].sort((a, b) => {
      const tempoA = calcularTempoMandato(a);
      const tempoB = calcularTempoMandato(b);
      return tempoB - tempoA;
    });
  }
  
  return resultado;
});

// ===== MÉTODOS =====
const carregarDiretores = async (tentativa = 1) => {
  if (tentativa === 1) {
    carregando.value = true;
    erro.value = null;
  }
  
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout
    
    const response = await axios.get('/api/directors', {
      signal: controller.signal,
      timeout: 10000
    });
    
    clearTimeout(timeoutId);
    
    // Processar dados e adicionar delays de animação
    diretores.value = response.data.map((diretor, index) => ({
      ...diretor,
      animationDelay: `${index * 0.1}s`,
      id: diretor.id || `diretor-${index}`,
      periodo: diretor.periodo || formatarPeriodo(diretor)
    }));
    
    // Inicializar estados de carregamento das imagens
    diretores.value.forEach(diretor => {
      imagensCarregando.value[diretor.id] = true;
    });
    
    carregando.value = false;
    tentativasReconexao.value = 0;
    
  } catch (error) {
    console.error(`Erro ao carregar diretores (tentativa ${tentativa}):`, error);
    
    if (tentativa < maxTentativas && error.name !== 'AbortError') {
      tentativasReconexao.value = tentativa;
      setTimeout(() => carregarDiretores(tentativa + 1), 2000 * tentativa);
    } else {
      erro.value = error.name === 'AbortError' 
        ? 'Tempo limite excedido. Verifique sua conexão.'
        : error.response?.data?.error || error.message || 'Erro ao carregar os dados';
      carregando.value = false;
    }
  }
};

const formatarPeriodo = (diretor) => {
  if (!diretor.data_inicio) return 'Período não informado';
  
  const inicio = new Date(diretor.data_inicio).toLocaleDateString('pt-BR');
  if (diretor.atual || !diretor.data_fim) {
    return `${inicio} - ATUALMENTE`;
  }
  
  const fim = new Date(diretor.data_fim).toLocaleDateString('pt-BR');
  return `${inicio} - ${fim}`;
};

const calcularTempoMandato = (diretor) => {
  if (!diretor.data_inicio) return 0;
  
  const inicio = new Date(diretor.data_inicio);
  const fim = diretor.data_fim ? new Date(diretor.data_fim) : new Date();
  return fim.getTime() - inicio.getTime();
};

const formatarTempoMandato = (diretor) => {
  const tempoMs = calcularTempoMandato(diretor);
  if (tempoMs === 0) return 'Não informado';
  
  const anos = Math.floor(tempoMs / (1000 * 60 * 60 * 24 * 365));
  const meses = Math.floor((tempoMs % (1000 * 60 * 60 * 24 * 365)) / (1000 * 60 * 60 * 24 * 30));
  
  if (anos > 0 && meses > 0) {
    return `${anos} ano${anos > 1 ? 's' : ''} e ${meses} mês${meses > 1 ? 'es' : ''}`;
  } else if (anos > 0) {
    return `${anos} ano${anos > 1 ? 's' : ''}`;
  } else if (meses > 0) {
    return `${meses} mês${meses > 1 ? 'es' : ''}`;
  } else {
    const dias = Math.floor(tempoMs / (1000 * 60 * 60 * 24));
    return `${dias} dia${dias > 1 ? 's' : ''}`;
  }
};

const selecionarDiretor = (diretor) => {
  diretorSelecionado.value = diretor;
  document.body.style.overflow = 'hidden';
  
  // Acessibilidade: Foco no modal
  nextTick(() => {
    const modal = document.querySelector('[role="dialog"]');
    modal?.focus();
  });
};

const fecharModal = () => {
  diretorSelecionado.value = null;
  document.body.style.overflow = '';
};

const filtrarPeriodo = (filtro) => {
  filtroAtivo.value = filtro;
};

const alternarOrdenacao = (ordenacao) => {
  ordenacaoAtiva.value = ordenacao;
};

const alternarVisualizacao = () => {
  tipoVisualizacao.value = tipoVisualizacao.value === 'grid' ? 'list' : 'grid';
};

const limparBusca = () => {
  termoBusca.value = '';
};

const onImageLoad = (diretorId) => {
  imagensCarregando.value[diretorId] = false;
};

const onImageError = (diretorId, event) => {
  console.warn(`Erro ao carregar imagem do diretor ${diretorId}`);
  event.target.src = placeholderProfile;
  imagensCarregando.value[diretorId] = false;
};

const compartilharDiretor = async (diretor) => {
  if (navigator.share) {
    try {
      await navigator.share({
        title: `${diretor.nome} - ACADEPOL`,
        text: `Conheça mais sobre ${diretor.nome}, diretor da ACADEPOL no período: ${diretor.periodo}`,
        url: window.location.href
      });
    } catch (error) {
      console.log('Erro ao compartilhar:', error);
    }
  } else {
    const texto = `${diretor.nome} - ACADEPOL\n${diretor.periodo}\n${window.location.href}`;
    await navigator.clipboard.writeText(texto);
    alert('Informações copiadas para a área de transferência!');
  }
};

// Navegação por teclado no modal
const handleModalKeydown = (event) => {
  if (!diretorSelecionado.value) return;
  
  switch (event.key) {
    case 'Escape':
      event.preventDefault();
      fecharModal();
      break;
    case 'ArrowLeft':
      event.preventDefault();
      navegarDiretor(-1);
      break;
    case 'ArrowRight':
      event.preventDefault();
      navegarDiretor(1);
      break;
  }
};

const navegarDiretor = (direcao) => {
  const indiceAtual = diretoresFiltrados.value.findIndex(d => d.id === diretorSelecionado.value.id);
  const novoIndice = indiceAtual + direcao;
  
  if (novoIndice >= 0 && novoIndice < diretoresFiltrados.value.length) {
    diretorSelecionado.value = diretoresFiltrados.value[novoIndice];
  }
};

// Detectar mudanças de conectividade
const handleOnline = () => {
  offline.value = false;
  if (erro.value && tentativasReconexao.value > 0) {
    carregarDiretores();
  }
};

const handleOffline = () => {
  offline.value = true;
};

// ===== Ciclo de VIDA =====
onMounted(() => {
  carregarDiretores();
  document.addEventListener('keydown', handleModalKeydown);
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleModalKeydown);
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
  document.body.style.overflow = '';
});

// ===== WATCHERS =====
watch(offline, (novoValor) => {
  if (!novoValor && erro.value) {
    // Reconectar automaticamente quando voltar online
    setTimeout(() => carregarDiretores(), 1000);
  }
});
</script>

<template>
  <Head title="Galeria de Diretores e Ex-Diretores" />
  
  <div class="max-w-7xl mx-auto px-4 py-12" role="main">
    <!-- Cabeçalho -->
    <header class="mb-10 text-center">
      <h1 class="text-4xl font-bold text-gray-800 mb-4">
        Galeria de Diretores e Ex-Diretores
      </h1>
      <p class="text-gray-600 max-w-3xl mx-auto text-lg leading-relaxed">
        Conheça os profissionais que estiveram à frente da direção da instituição ao longo da sua história, 
        contribuindo para o desenvolvimento e crescimento da ACADEPOL.
      </p>
    </header>

    <!-- Alert de conectividade -->
    <div v-if="offline" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span class="text-yellow-800">Você está offline. Algumas funcionalidades podem estar limitadas.</span>
      </div>
    </div>

    <!-- Barra de busca -->
    <div class="mb-6">
      <div class="relative max-w-md mx-auto">
        <input 
          v-model="termoBusca"
          type="text"
          placeholder="Buscar diretores..."
          class="w-full px-4 py-3 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
        />
        <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <button 
          v-if="termoBusca"
          @click="limparBusca"
          class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600"
          aria-label="Limpar busca"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Controles -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
      <!-- Filtros por período -->
      <nav aria-label="Filtro de diretores">
        <div class="flex flex-wrap gap-2">
          <button 
            v-for="filtro in filtrosDisponiveis" 
            :key="filtro.key"
            @click="filtrarPeriodo(filtro.key)" 
            :aria-pressed="filtroAtivo === filtro.key"
            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            :class="filtroAtivo === filtro.key 
              ? 'bg-blue-600 text-white shadow-md' 
              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'"
          >
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="filtro.icon" />
            </svg>
            {{ filtro.label }}
          </button>
        </div>
      </nav>

      <!-- Controles de visualização e ordenação -->
      <div class="flex items-center gap-4">
        <!-- Ordenação -->
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Ordenar por:</label>
          <select 
            v-model="ordenacaoAtiva"
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option v-for="ordenacao in ordenacoesDisponiveis" :key="ordenacao.key" :value="ordenacao.key">
              {{ ordenacao.label }}
            </option>
          </select>
        </div>

        <!-- Tipo de visualização -->
        <button 
          @click="alternarVisualizacao"
          class="p-2 rounded-lg text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors"
          :title="tipoVisualizacao === 'grid' ? 'Visualizar como lista' : 'Visualizar como grade'"
        >
          <svg v-if="tipoVisualizacao === 'grid'" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/>
          </svg>
          <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm0 11h7v7h-7v-7z"/>
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Loading state -->
    <div v-if="carregando" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      <p class="mt-4 text-gray-600">
        Carregando diretores...
        <span v-if="tentativasReconexao > 0" class="block text-sm text-gray-500 mt-1">
          Tentativa {{ tentativasReconexao + 1 }} de {{ maxTentativas }}
        </span>
      </p>
    </div>
    
    <!-- Erro -->
    <div v-else-if="erro" class="text-center py-12">
      <div class="mx-auto w-16 h-16 mb-4 text-red-500">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Erro ao carregar dados</h3>
      <p class="text-red-600 mb-4">{{ erro }}</p>
      <button 
        @click="carregarDiretores" 
        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-300 transition-colors"
        :disabled="carregando"
      >
        <svg v-if="carregando" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Tentar novamente
      </button>
    </div>

    <!-- Estado vazio -->
    <div v-else-if="diretoresFiltrados.length === 0" class="text-center py-12">
      <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum diretor encontrado</h3>
      <p class="text-gray-500 mb-4">
        {{ termoBusca ? 'Tente ajustar sua busca ou alterar os filtros.' : 'Não há diretores disponíveis para o filtro selecionado.' }}
      </p>
      <button 
        v-if="termoBusca || filtroAtivo !== 'todos'"
        @click="termoBusca = ''; filtroAtivo = 'todos'"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Limpar filtros
      </button>
    </div>
    
    <!-- Grid de diretores -->
    <div 
      v-else-if="tipoVisualizacao === 'grid'"
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"
    >
      <article 
        v-for="diretor in diretoresFiltrados" 
        :key="`grid-${diretor.id}`" 
        :style="{ animationDelay: diretor.animationDelay }"
        class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl group fade-in"
        role="button"
        tabindex="0"
        @click="selecionarDiretor(diretor)"
        @keydown.enter="selecionarDiretor(diretor)"
        @keydown.space.prevent="selecionarDiretor(diretor)"
        :aria-label="`Ver detalhes de ${diretor.nome}`"
      >
        <div class="relative h-64">
          <!-- Skeleton loading -->
          <div 
            v-show="imagensCarregando[diretor.id]"
            class="w-full h-full bg-gray-200 animate-pulse"
          ></div>

          <!-- Imagem -->
          <img 
            v-show="!imagensCarregando[diretor.id]"
            :src="diretor.imagem" 
            :alt="`Foto de ${diretor.nome}`" 
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
            loading="lazy"
            @load="onImageLoad(diretor.id)"
            @error="onImageError(diretor.id, $event)"
          />
          
          <!-- Overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-70 group-hover:opacity-90 transition-opacity"></div>
          
          <!-- Informações -->
          <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
            <p class="text-xs font-medium text-blue-200 mb-1">{{ diretor.periodo }}</p>
            <h2 class="text-lg font-bold truncate">{{ diretor.nome }}</h2>
          </div>
          
          <!-- Badge atual -->
          <div class="absolute top-3 right-3">
            <span 
              v-if="diretor.periodo?.includes('ATUALMENTE') || diretor.atual" 
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Atual
            </span>
          </div>

          <!-- Botão de compartilhar -->
          <button 
            @click.stop="compartilharDiretor(diretor)"
            class="absolute top-3 left-3 w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all hover:bg-white/30"
            aria-label="Compartilhar informações do diretor"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
            </svg>
          </button>
        </div>
      </article>
    </div>

    <!-- Lista de diretores -->
    <div v-else class="space-y-4">
      <article 
        v-for="diretor in diretoresFiltrados" 
        :key="`list-${diretor.id}`"
        class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl group fade-in"
        role="button"
        tabindex="0"
        @click="selecionarDiretor(diretor)"
        @keydown.enter="selecionarDiretor(diretor)"
        @keydown.space.prevent="selecionarDiretor(diretor)"
        :aria-label="`Ver detalhes de ${diretor.nome}`"
      >
        <div class="flex">
          <div class="w-32 h-32 flex-shrink-0 relative overflow-hidden">
            <img 
              :src="diretor.imagem" 
              :alt="`Foto de ${diretor.nome}`" 
              class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
              loading="lazy"
              @error="onImageError(diretor.id, $event)"
            />
          </div>
          <div class="flex-1 p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <h3 class="text-xl font-semibold text-gray-900">{{ diretor.nome }}</h3>
                  <span 
                    v-if="diretor.periodo?.includes('ATUALMENTE') || diretor.atual"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Atual
                  </span>
                </div>
                <p class="text-gray-600 mb-3 font-medium">{{ diretor.periodo }}</p>
                <p v-if="diretor.historico" class="text-gray-700 text-sm line-clamp-2">{{ diretor.historico }}</p>
              </div>
              <div class="ml-4 flex-shrink-0 flex items-center gap-2">
                <button 
                  @click.stop="compartilharDiretor(diretor)"
                  class="p-2 rounded-full hover:bg-gray-100 transition-colors"
                  aria-label="Compartilhar informações do diretor"
                >
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                  </svg>
                </button>
                <svg class="h-6 w-6 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>

    <!-- Modal de detalhes -->
    <div 
      v-if="diretorSelecionado" 
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto"
      @click.self="fecharModal"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="`diretor-nome-${diretorSelecionado.id}`"
      tabindex="-1"
    >
      <div 
        class="bg-white rounded-lg shadow-xl max-w-4xl w-full relative overflow-hidden my-8 animate-modal-enter"
        @click.stop
      >
        <!-- Cabeçalho do modal -->
        <div class="flex items-center justify-between p-4 border-b bg-gray-50">
          <h2 :id="`diretor-nome-${diretorSelecionado.id}`" class="text-xl font-semibold text-gray-900">
            Detalhes do Diretor
          </h2>
          <div class="flex items-center gap-2">
            <!-- Navegação entre diretores -->
            <div class="flex items-center gap-1 mr-4">
              <button 
                @click="navegarDiretor(-1)"
                :disabled="diretoresFiltrados.findIndex(d => d.id === diretorSelecionado.id) === 0"
                class="p-2 rounded-full hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Diretor anterior"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <span class="text-sm text-gray-500 px-2">
                {{ diretoresFiltrados.findIndex(d => d.id === diretorSelecionado.id) + 1 }} de {{ diretoresFiltrados.length }}
              </span>
              <button 
                @click="navegarDiretor(1)"
                :disabled="diretoresFiltrados.findIndex(d => d.id === diretorSelecionado.id) === diretoresFiltrados.length - 1"
                class="p-2 rounded-full hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Próximo diretor"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            <!-- Botão de compartilhar -->
            <button 
              @click="compartilharDiretor(diretorSelecionado)"
              class="p-2 rounded-full hover:bg-gray-200 transition-colors"
              title="Compartilhar informações do diretor"
            >
              <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
              </svg>
            </button>
            
            <!-- Botão de fechar -->
            <button 
              @click="fecharModal" 
              class="p-2 rounded-full hover:bg-gray-200 transition-colors"
              aria-label="Fechar modal"
            >
              <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <div class="flex flex-col lg:flex-row">
          <!-- Imagem -->
          <div class="lg:w-2/5 relative">
            <img 
              :src="diretorSelecionado.imagem" 
              :alt="`Foto de ${diretorSelecionado.nome}`"
              class="w-full h-64 lg:h-full object-cover" 
              loading="lazy"
              @error="onImageError(diretorSelecionado.id, $event)"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent lg:hidden"></div>
          </div>
          
          <!-- Conteúdo -->
          <div class="lg:w-3/5 p-6">
            <div class="space-y-4">
              <!-- Nome e período -->
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span 
                    v-if="diretorSelecionado.periodo?.includes('ATUALMENTE') || diretorSelecionado.atual" 
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                  >
                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Diretor Atual
                  </span>
                </div>
                <h2 class="text-3xl font-bold text-gray-800">{{ diretorSelecionado.nome }}</h2>
                <p class="text-gray-600 mt-1 font-medium">{{ diretorSelecionado.periodo }}</p>
              </div>
              
              <!-- Histórico -->
              <div v-if="diretorSelecionado.historico" class="space-y-2">
                <h3 class="text-lg font-semibold text-gray-800">Histórico</h3>
                <p class="text-gray-700 leading-relaxed">{{ diretorSelecionado.historico }}</p>
              </div>
              
              <!-- Realizações -->
              <div v-if="diretorSelecionado.realizacoes?.length" class="space-y-2">
                <h3 class="text-lg font-semibold text-gray-800">Principais Realizações</h3>
                <ul class="list-disc pl-5 space-y-2">
                  <li v-for="(realizacao, index) in diretorSelecionado.realizacoes" :key="index" class="text-gray-700">
                    {{ realizacao }}
                  </li>
                </ul>
              </div>

              <!-- Informações adicionais -->
              <div v-if="diretorSelecionado.ordem || calcularTempoMandato(diretorSelecionado)" class="pt-4 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Informações Adicionais</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                  <div v-if="diretorSelecionado.ordem">
                    <span class="font-medium text-gray-600">Ordem cronológica:</span>
                    <span class="ml-2 text-gray-800">{{ diretorSelecionado.ordem }}º diretor</span>
                  </div>
                  <div v-if="calcularTempoMandato(diretorSelecionado)">
                    <span class="font-medium text-gray-600">Tempo de mandato:</span>
                    <span class="ml-2 text-gray-800">{{ formatarTempoMandato(diretorSelecionado) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ===== ANIMAÇÕES ===== */
.fade-in {
  animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-modal-enter {
  animation: modalEnter 0.3s ease-out forwards;
}

@keyframes modalEnter {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* ===== TRANSIÇÕES DE LISTA ===== */
.fade-list-enter-active,
.fade-list-leave-active {
  transition: all 0.5s ease;
}

.fade-list-enter-from,
.fade-list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.fade-list-move {
  transition: transform 0.5s ease;
}

/* ===== CSS ===== */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* ===== ACESSIBILIDADE ===== */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* ===== ESTADOS DE FOCO ===== */
button:focus-visible,
[tabindex]:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

/* ===== BACKDROP BLUR FALLBACK ===== */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

@supports not (backdrop-filter: blur(4px)) {
  .backdrop-blur-sm {
    background-color: rgba(0, 0, 0, 0.8);
  }
}

/* ===== HOVER EFFECTS ===== */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:opacity-100 {
  opacity: 1;
}

.group:hover .group-hover\:text-gray-600 {
  color: #4b5563;
}

/* ===== LOADING STATES ===== */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

/* ===== SCROLLBAR PERSONALIZADO ===== */
.overflow-y-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>