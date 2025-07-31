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

// Debounce para busca
const searchTimeout = ref(null);

// Estados de animação
const showGrid = ref(false);

// Filtros e ordenações
const filtrosDisponiveis = [
  { 
    key: 'todos', 
    label: 'Todos', 
    icon: 'users',
    count: computed(() => diretores.value.length)
  },
  { 
    key: 'atual', 
    label: 'Atual', 
    icon: 'star',
    count: computed(() => diretores.value.filter(d => d.atual || d.periodo?.includes('ATUALMENTE')).length)
  },
  { 
    key: 'anteriores', 
    label: 'Ex-Diretores', 
    icon: 'clock',
    count: computed(() => diretores.value.filter(d => !d.atual && !d.periodo?.includes('ATUALMENTE')).length)
  }
];

const ordenacoesDisponiveis = [
  { key: 'cronologica', label: 'Mais Recentes', icon: 'calendar' },
  { key: 'alfabetica', label: 'A-Z', icon: 'sort-alpha' }
];

// SVG Icons
const icons = {
  users: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  star: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
  clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  'sort-alpha': 'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12'
};

// ===== COMPUTED =====
const diretoresFiltrados = computed(() => {
  let resultado = [...diretores.value];
  
  // Filtrar por período
  if (filtroAtivo.value === 'atual') {
    resultado = resultado.filter(diretor => diretor.periodo?.includes('ATUALMENTE') || diretor.atual);
  } else if (filtroAtivo.value === 'anteriores') {
    resultado = resultado.filter(diretor => !diretor.periodo?.includes('ATUALMENTE') && !diretor.atual);
  }
  
  // Filtrar por busca
  if (termoBusca.value.trim()) {
    const termo = termoBusca.value.toLowerCase().trim();
    resultado = resultado.filter(diretor => 
      diretor.nome?.toLowerCase().includes(termo) ||
      diretor.periodo?.toLowerCase().includes(termo) ||
      diretor.historico?.toLowerCase().includes(termo)
    );
  }
  
  // Ordenar
  switch (ordenacaoAtiva.value) {
    case 'alfabetica':
      resultado.sort((a, b) => (a.nome || '').localeCompare(b.nome || '', 'pt-BR'));
      break;
    case 'mandato':
      resultado.sort((a, b) => calcularDuracaoMandato(b) - calcularDuracaoMandato(a));
      break;
    case 'cronologica':
    default:
      resultado.sort((a, b) => {
        // Diretores atuais primeiro
        if (a.atual && !b.atual) return -1;
        if (!a.atual && b.atual) return 1;
        
        // Depois por data de início (mais recente primeiro)
        const dataA = new Date(a.data_inicio || 0);
        const dataB = new Date(b.data_inicio || 0);
        return dataB - dataA;
      });
      break;
  }
  
  return resultado;
});

// ===== FUNÇÕES =====
const calcularDuracaoMandato = (diretor) => {
  if (!diretor.data_inicio) return 0;
  
  const inicio = new Date(diretor.data_inicio);
  const fim = diretor.data_fim ? new Date(diretor.data_fim) : new Date();
  
  return Math.floor((fim - inicio) / (1000 * 60 * 60 * 24)); // dias
};

const formatarDuracao = (dias) => {
  const anos = Math.floor(dias / 365);
  const meses = Math.floor((dias % 365) / 30);
  
  if (anos > 0) {
    return meses > 0 ? `${anos}a ${meses}m` : `${anos} ${anos === 1 ? 'ano' : 'anos'}`;
  }
  return meses > 0 ? `${meses} ${meses === 1 ? 'mês' : 'meses'}` : 'Menos de 1 mês';
};

// ===== MÉTODOS =====
const carregarDiretores = async (tentativa = 1) => {
  if (tentativa === 1) {
    carregando.value = true;
    erro.value = null;
    showGrid.value = false;
  }
  
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout
    
    const response = await axios.get('/api/directors', {
      signal: controller.signal,
      timeout: 15000,
      headers: {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache'
      }
    });
    
    clearTimeout(timeoutId);
    
    // Processar dados
    diretores.value = response.data.map((diretor, index) => ({
      ...diretor,
      id: diretor.id || `diretor-${index}`,
      periodo: diretor.periodo || formatarPeriodo(diretor),
      // Adicionar dados computados
      duracaoMandato: calcularDuracaoMandato(diretor),
      isAtual: diretor.atual || diretor.periodo?.includes('ATUALMENTE')
    }));
    
    // Pré-carregar imagens (primeiras 4)
    const imagensCriticas = diretores.value.slice(0, 4);
    imagensCriticas.forEach(diretor => {
      if (diretor.imagem) {
        const img = new Image();
        img.src = diretor.imagem;
      }
    });
    
    carregando.value = false;
    tentativasReconexao.value = 0;
    
    // Animação
    setTimeout(() => {
      showGrid.value = true;
    }, 100);
    
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
  
  const inicio = new Date(diretor.data_inicio).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit', 
    year: 'numeric'
  });
  
  if (diretor.atual || !diretor.data_fim) {
    return `${inicio} - ATUALMENTE`;
  }
  
  const fim = new Date(diretor.data_fim).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit', 
    year: 'numeric'
  });
  
  return `${inicio} - ${fim}`;
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

// Filtros e busca
const filtrarPeriodo = (filtro) => {
  if (filtroAtivo.value !== filtro) {
    filtroAtivo.value = filtro;
  }
};

const alternarVisualizacao = () => {
  tipoVisualizacao.value = tipoVisualizacao.value === 'grid' ? 'list' : 'grid';
};

// Busca com debounce
const buscarDiretores = (termo) => {
  clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => {
    termoBusca.value = termo;
  }, 300);
};

const limparBusca = () => {
  termoBusca.value = '';
};

// Imagem otimizada com lazy loading
const onImageLoad = (diretorId) => {
  imagensCarregando.value[diretorId] = false;
};

const onImageError = (diretorId, event) => {
  console.warn(`Erro ao carregar imagem do diretor ${diretorId}`);
  event.target.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 240' fill='%23f3f4f6'%3E%3Crect width='240' height='240'/%3E%3Ccircle cx='120' cy='80' r='30' fill='%23d1d5db'/%3E%3Cpath d='M60 180c0-24.853 20.147-45 45-45h30c24.853 0 45 20.147 45 45v20H60v-20z' fill='%23d1d5db'/%3E%3Ctext x='120' y='220' fill='%239ca3af' font-family='system-ui' font-size='14' text-anchor='middle'%3EDiretor%3C/text%3E%3C/svg%3E";
  imagensCarregando.value[diretorId] = false;
};

// Compartilhamento
const compartilharDiretor = async (diretor) => {
  const dadosCompartilhamento = {
    title: `${diretor.nome} - ACADEPOL`,
    text: `Conheça ${diretor.nome}, ${diretor.isAtual ? 'atual diretor' : 'ex-diretor'} da ACADEPOL (${diretor.periodo})`,
    url: window.location.href
  };

  if (navigator.share && navigator.canShare(dadosCompartilhamento)) {
    try {
      await navigator.share(dadosCompartilhamento);
    } catch (error) {
      if (error.name !== 'AbortError') {
        console.log('Erro ao compartilhar:', error);
        copiarParaClipboard(diretor);
      }
    }
  } else {
    copiarParaClipboard(diretor);
  }
};

const copiarParaClipboard = async (diretor) => {
  const texto = `${diretor.nome} - ACADEPOL\n${diretor.periodo}\n${window.location.href}`;
  
  try {
    await navigator.clipboard.writeText(texto);
    console.log('Copiado para área de transferência!');
  } catch (error) {
    // Fallback para navegadores antigos
    const textArea = document.createElement('textarea');
    textArea.value = texto;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
  }
};

// Navegação no modal
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
  const filtrados = diretoresFiltrados.value;
  const indiceAtual = filtrados.findIndex(d => d.id === diretorSelecionado.value.id);
  const novoIndice = indiceAtual + direcao;
  
  if (novoIndice >= 0 && novoIndice < filtrados.length) {
    diretorSelecionado.value = filtrados[novoIndice];
  }
};

// Detectar conectividade
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
  clearTimeout(searchTimeout.value);
});

// ===== WATCHERS =====
watch(offline, (novoValor) => {
  if (!novoValor && erro.value) {
    setTimeout(() => carregarDiretores(), 1000);
  }
});
</script>

<template>
  <Head title="Galeria de Diretores e Ex-Diretores" />
  
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="max-w-7xl mx-auto px-4 py-12" role="main">
      <!-- Cabeçalho -->
      <header class="mb-12 text-center">
        <h1 class="text-5xl font-bold text-gray-900 mb-4 tracking-tight">
          Galeria de Diretores
        </h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
          Conheça os profissionais que lideraram a ACADEPOL ao longo de sua história.
        </p>
      </header>

      <!-- Alert de conectividade -->
      <Transition name="slide-down">
        <div v-if="offline" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl shadow-sm">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-amber-800">
                Você está offline. Algumas funcionalidades podem estar limitadas.
              </p>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Controles otimizados -->
      <div class="mb-8 space-y-6">
        <!-- Barra de busca -->
        <div class="relative max-w-md mx-auto">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input 
            :value="termoBusca"
            @input="buscarDiretores($event.target.value)"
            type="text"
            placeholder="Buscar por nome ou período..."
            class="w-full pl-10 pr-10 py-4 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
          />
          <button 
            v-if="termoBusca"
            @click="limparBusca"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
            aria-label="Limpar busca"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- Filtros e controles -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <!-- Filtros -->
          <nav aria-label="Filtro de diretores">
            <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
              <button 
                v-for="filtro in filtrosDisponiveis" 
                :key="filtro.key"
                @click="filtrarPeriodo(filtro.key)" 
                :aria-pressed="filtroAtivo === filtro.key"
                class="group inline-flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 focus:ring-2 focus:ring-blue-500 focus:outline-none transform hover:scale-105"
                :class="filtroAtivo === filtro.key 
                  ? 'bg-blue-600 text-white shadow-lg' 
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm'"
              >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons[filtro.icon]" />
                </svg>
                {{ filtro.label }}
                <span 
                  class="ml-2 px-2 py-0.5 text-xs rounded-full"
                  :class="filtroAtivo === filtro.key 
                    ? 'bg-blue-500 text-blue-100' 
                    : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200'"
                >
                  {{ filtro.count }}
                </span>
              </button>
            </div>
          </nav>

          <!-- Controles de visualização -->
          <div class="flex items-center gap-4 justify-center lg:justify-end">
            <!-- Ordenação -->
            <div class="flex items-center gap-2">
              <label class="text-sm font-medium text-gray-700">Ordenar:</label>
              <select 
                v-model="ordenacaoAtiva"
                class="px-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm"
              >
                <option v-for="ordenacao in ordenacoesDisponiveis" :key="ordenacao.key" :value="ordenacao.key">
                  {{ ordenacao.label }}
                </option>
              </select>
            </div>

            <!-- Toggle visualização -->
            <button 
              @click="alternarVisualizacao"
              class="p-3 rounded-xl text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-all border border-gray-200 shadow-sm"
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
      </div>
      
      <!-- Estados de loading otimizados -->
      <Transition name="fade" mode="out-in">
        <!-- Loading state -->
        <div v-if="carregando" class="text-center py-16">
          <div class="relative inline-block">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600"></div>
            <div class="absolute inset-0 rounded-full h-16 w-16 border-2 border-blue-200"></div>
          </div>
          <p class="mt-6 text-gray-600 text-lg">
            Carregando diretores...
            <span v-if="tentativasReconexao > 0" class="block text-sm text-gray-500 mt-2">
              Tentativa {{ tentativasReconexao + 1 }} de {{ maxTentativas }}
            </span>
          </p>
        </div>
        
        <!-- Erro -->
        <div v-else-if="erro" class="text-center py-16">
          <div class="mx-auto w-20 h-20 mb-6 text-red-500 bg-red-50 rounded-full flex items-center justify-center">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-10 h-10">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="text-xl font-medium text-gray-900 mb-3">Erro ao carregar dados</h3>
          <p class="text-red-600 mb-6 max-w-md mx-auto">{{ erro }}</p>
          <button 
            @click="carregarDiretores" 
            class="px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 focus:ring-2 focus:ring-blue-300 transition-all shadow-lg transform hover:scale-105"
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
        <div v-else-if="diretoresFiltrados.length === 0" class="text-center py-16">
          <div class="mx-auto w-20 h-20 mb-6 text-gray-400 bg-gray-50 rounded-full flex items-center justify-center">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-10 h-10">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons.users" />
            </svg>
          </div>
          <h3 class="text-xl font-medium text-gray-900 mb-3">
            {{ termoBusca ? 'Nenhum diretor encontrado' : 'Nenhum diretor disponível' }}
          </h3>
          <p class="text-gray-500 mb-6 max-w-md mx-auto">
            {{ termoBusca 
              ? `Não encontramos resultados para "${termoBusca}". Tente ajustar sua busca ou alterar os filtros.` 
              : 'Não há diretores disponíveis para o filtro selecionado.' 
            }}
          </p>
          <div class="flex gap-3 justify-center">
            <button 
              v-if="termoBusca"
              @click="limparBusca"
              class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
            >
              Limpar busca
            </button>
            <button 
              v-if="filtroAtivo !== 'todos'"
              @click="filtroAtivo = 'todos'"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Ver todos
            </button>
          </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div v-else>
          <Transition name="fade">
            <!-- Grid  -->
            <div 
              v-if="tipoVisualizacao === 'grid' && showGrid"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8"
            >
              <TransitionGroup name="stagger" appear>
                <article 
                  v-for="(diretor, index) in diretoresFiltrados" 
                  :key="`grid-${diretor.id}`" 
                  :style="{ '--delay': `${index * 0.1}s` }"
                  class="group relative bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 cursor-pointer stagger-item"
                  role="button"
                  tabindex="0"
                  @click="selecionarDiretor(diretor)"
                  @keydown.enter="selecionarDiretor(diretor)"
                  @keydown.space.prevent="selecionarDiretor(diretor)"
                  :aria-label="`Ver detalhes de ${diretor.nome}`"
                >
                  <div class="relative h-80 overflow-hidden">
                    <!-- Skeleton loading -->
                    <div 
                      v-show="imagensCarregando[diretor.id]"
                      class="absolute inset-0 bg-gradient-to-br from-gray-200 via-gray-100 to-gray-200 animate-pulse"
                    >
                      <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 animate-shimmer"></div>
                    </div>

                    <!-- Imagem -->
                    <img 
                      v-show="!imagensCarregando[diretor.id]"
                      :src="diretor.imagem" 
                      :alt="`Foto de ${diretor.nome}`" 
                      class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" 
                      loading="lazy"
                      @load="onImageLoad(diretor.id)"
                      @error="onImageError(diretor.id, $event)"
                    />
                    
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent opacity-80 group-hover:opacity-95 transition-opacity duration-300"></div>
                    
                    <!-- Conteúdo sobreposto -->
                    <div class="absolute inset-0 p-6 flex flex-col justify-end text-white">
                      <div class="transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                        <p class="text-sm font-medium text-blue-200 mb-2 opacity-90">
                          {{ diretor.periodo }}
                        </p>
                        <h2 class="text-xl font-bold mb-2 line-clamp-2">
                          {{ diretor.nome }}
                        </h2>
                        <div v-if="diretor.duracaoMandato" class="text-sm text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
                          Mandato: {{ formatarDuracao(diretor.duracaoMandato) }}
                        </div>
                      </div>
                    </div>
                    
                    <!-- Badge diretor atual -->
                    <div class="absolute top-4 right-4">
                      <Transition name="bounce">
                        <span 
                          v-if="diretor.isAtual" 
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-500 text-white shadow-lg"
                        >
                          <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path :d="icons.star" />
                          </svg>
                          ATUAL
                        </span>
                      </Transition>
                    </div>

                    <!-- Botão de compartilhar -->
                    <button 
                      @click.stop="compartilharDiretor(diretor)"
                      class="absolute top-4 left-4 w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-all duration-300 hover:bg-white/30 hover:scale-110"
                      aria-label="Compartilhar informações do diretor"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                      </svg>
                    </button>

                    <!-- Indicador de mais informações -->
                    <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                      <div class="w-8 h-8 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                      </div>
                    </div>
                  </div>
                </article>
              </TransitionGroup>
            </div>

            <!-- Lista -->
            <div v-else-if="tipoVisualizacao === 'list'" class="space-y-6">
              <TransitionGroup name="slide-up" appear>
                <article 
                  v-for="(diretor, index) in diretoresFiltrados" 
                  :key="`list-${diretor.id}`"
                  :style="{ '--delay': `${index * 0.05}s` }"
                  class="group bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-500 hover:shadow-2xl cursor-pointer slide-up-item"
                  role="button"
                  tabindex="0"
                  @click="selecionarDiretor(diretor)"
                  @keydown.enter="selecionarDiretor(diretor)"
                  @keydown.space.prevent="selecionarDiretor(diretor)"
                  :aria-label="`Ver detalhes de ${diretor.nome}`"
                >
                  <div class="flex">
                    <div class="w-40 h-40 flex-shrink-0 relative overflow-hidden">
                      <img 
                        :src="diretor.imagem" 
                        :alt="`Foto de ${diretor.nome}`" 
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                        loading="lazy"
                        @error="onImageError(diretor.id, $event)"
                      />
                    </div>
                    <div class="flex-1 p-6">
                      <div class="flex items-start justify-between h-full">
                        <div class="flex-1">
                          <div class="flex items-center gap-3 mb-3">
                            <h3 class="text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                              {{ diretor.nome }}
                            </h3>
                            <Transition name="bounce">
                              <span 
                                v-if="diretor.isAtual"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800"
                              >
                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                  <path :d="icons.star" />
                                </svg>
                                ATUAL
                              </span>
                            </Transition>
                          </div>
                          <p class="text-gray-600 mb-3 font-medium text-lg">{{ diretor.periodo }}</p>
                          <p v-if="diretor.duracaoMandato" class="text-gray-500 mb-3 text-sm">
                            Duração: {{ formatarDuracao(diretor.duracaoMandato) }}
                          </p>
                          <p v-if="diretor.historico" class="text-gray-700 text-sm line-clamp-3 leading-relaxed">
                            {{ diretor.historico }}
                          </p>
                        </div>
                        <div class="ml-6 flex-shrink-0 flex items-center gap-3">
                          <button 
                            @click.stop="compartilharDiretor(diretor)"
                            class="p-3 rounded-full hover:bg-gray-100 transition-colors group/share"
                            aria-label="Compartilhar informações do diretor"
                          >
                            <svg class="h-5 w-5 text-gray-400 group-hover/share:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                            </svg>
                          </button>
                          <svg class="h-6 w-6 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                        </div>
                      </div>
                    </div>
                  </div>
                </article>
              </TransitionGroup>
            </div>
          </Transition>
        </div>
      </Transition>

      <!-- Modal -->
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
                  <p v-if="diretorSelecionado.duracaoMandato" class="text-gray-500 mt-2 text-sm">
                    Duração: {{ formatarDuracao(diretorSelecionado.duracaoMandato) }}
                  </p>
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
@keyframes shimmer {
  0% { transform: translateX(-100%) skewX(-12deg); }
  100% { transform: translateX(200%) skewX(-12deg); }
}

.animate-shimmer {
  animation: shimmer 1.5s infinite;
}

/* Transições de página */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Animações de itens */
.stagger-item {
  animation: staggerIn 0.6s ease-out forwards;
  animation-delay: var(--delay);
  opacity: 0;
  transform: translateY(20px);
}

.slide-up-item {
  animation: slideUpIn 0.4s ease-out forwards;
  animation-delay: var(--delay);
  opacity: 0;
  transform: translateY(10px);
}

@keyframes staggerIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideUpIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Transições de grupo */
.stagger-enter-active {
  transition: all 0.6s ease;
  transition-delay: calc(var(--i) * 0.1s);
}
.stagger-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.slide-up-enter-active {
  transition: all 0.4s ease;
  transition-delay: calc(var(--i) * 0.05s);
}
.slide-up-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

/* Animação do modal */
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

/* Bounce para badges */
.bounce-enter-active {
  animation: bounceIn 0.5s ease;
}

@keyframes bounceIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

/* CSS utilitários */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Estados de foco */
button:focus-visible,
[tabindex]:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
  border-radius: 0.5rem;
}

/* Responsividade */
@media (max-width: 640px) {
  .stagger-item {
    animation-delay: calc(var(--delay) * 0.5);
  }
}

/* Redução de movimento */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  
  .stagger-item,
  .slide-up-item {
    animation: none;
    opacity: 1;
    transform: none;
  }
}

/* Scroll suave */
html {
  scroll-behavior: smooth;
}

/* Backdrop blur fallback */
@supports not (backdrop-filter: blur(4px)) {
  .backdrop-blur-sm {
    background-color: rgba(0, 0, 0, 0.8);
  }
  .backdrop-blur-md {
    background-color: rgba(255, 255, 255, 0.8);
  }
}
</style>