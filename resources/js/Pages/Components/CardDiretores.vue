<template>
    <Head title="Galeria de Diretores e Ex-Diretores" />
    
    <div class="max-w-7xl mx-auto px-4 py-12">
      <div class="mb-10 text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">
          Galeria de Diretores e Ex-Diretores
        </h2>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Conheça os profissionais que estiveram à frente da direção da instituição ao longo da sua história.
        </p>
      </div>
      
      <!-- Filtro por período (opcional) -->
      <div class="mb-8 flex justify-center">
        <div class="inline-flex rounded-md shadow-sm" role="group">
          <button 
            @click="filtrarPeriodo('todos')" 
            :class="[
              'px-4 py-2 text-sm font-medium border rounded-l-lg',
              filtroAtivo === 'todos' 
                ? 'bg-blue-700 text-white border-blue-700' 
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
            ]"
          >
            Todos
          </button>
          <button 
            @click="filtrarPeriodo('atual')" 
            :class="[
              'px-4 py-2 text-sm font-medium border-t border-b border-r',
              filtroAtivo === 'atual' 
                ? 'bg-blue-700 text-white border-blue-700' 
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
            ]"
          >
            Atual
          </button>
          <button 
            @click="filtrarPeriodo('anteriores')" 
            :class="[
              'px-4 py-2 text-sm font-medium border-t border-b border-r rounded-r-lg',
              filtroAtivo === 'anteriores' 
                ? 'bg-blue-700 text-white border-blue-700' 
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
            ]"
          >
            Anteriores
          </button>
        </div>
      </div>
      
      <!-- Grid de diretores -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div 
          v-for="diretor in diretoresFiltrados" 
          :key="diretor.id" 
          class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl group"
        >
          <div class="relative h-64">
            <img 
              :src="diretor.imagem" 
              :alt="diretor.nome" 
              class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
            />
            <div 
              class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent 
                    opacity-70 group-hover:opacity-90 transition-opacity"
            ></div>
            
            <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
              <p class="text-xs font-medium text-blue-200 mb-1">{{ diretor.periodo }}</p>
              <h3 class="text-lg font-bold truncate">{{ diretor.nome }}</h3>
            </div>
            
            <button 
              @click="selecionarDiretor(diretor)" 
              class="absolute inset-0 w-full h-full cursor-pointer focus:outline-none"
              aria-label="Ver detalhes"
            ></button>
            
            <div class="absolute top-3 right-3">
              <span 
                v-if="diretor.periodo.includes('ATUALMENTE')" 
                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800"
              >
                Atual
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Mensagem quando não há resultados -->
      <div v-if="diretoresFiltrados.length === 0" class="text-center py-10">
        <p class="text-gray-500">Nenhum diretor encontrado para o filtro selecionado.</p>
      </div>
  
      <!-- Modal de detalhes -->
      <Transition name="fade">
        <div 
          v-if="diretorSelecionado" 
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
          @click.self="fecharModal"
        >
          <div 
            class="bg-white rounded-lg shadow-xl max-w-2xl w-full relative overflow-hidden"
            @click.stop
          >
            <div class="flex flex-col md:flex-row">
              <div class="md:w-2/5 relative">
                <img 
                  :src="diretorSelecionado.imagem" 
                  :alt="diretorSelecionado.nome" 
                  class="w-full h-64 md:h-full object-cover" 
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent md:hidden"></div>
              </div>
              
              <div class="md:w-3/5 p-6 relative">
                <button 
                  @click="fecharModal" 
                  class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-800 transition-colors"
                  aria-label="Fechar"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
                
                <div>
                  <span 
                    v-if="diretorSelecionado.periodo.includes('ATUALMENTE')" 
                    class="inline-block px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 mb-2"
                  >
                    Atual
                  </span>
                  <h3 class="text-2xl font-bold text-gray-800">{{ diretorSelecionado.nome }}</h3>
                  <p class="text-gray-600 mt-1 text-sm font-medium">{{ diretorSelecionado.periodo }}</p>
                  
                  <div class="mt-4 space-y-3 text-gray-700">
                    <p v-if="diretorSelecionado.historico">{{ diretorSelecionado.historico }}</p>
                    <div v-if="diretorSelecionado.realizacoes?.length" class="mt-4">
                      <h4 class="font-semibold text-gray-800 mb-2">Principais Realizações:</h4>
                      <ul class="list-disc pl-5 space-y-1">
                        <li v-for="(realizacao, index) in diretorSelecionado.realizacoes" :key="index">
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
      </Transition>
    </div>
  </template>
  
  <script setup>
  import { ref, computed } from 'vue';
  import { Head } from '@inertiajs/vue3';
  
  // Dados dos diretores
  const diretores = ref([
    { 
      id: 1, 
      nome: "MAISA FÉLIX RIBEIRO DE ARAÚJO", 
      periodo: "10/02/2023 - ATUALMENTE", 
      historico: "Delegada de Polícia Civil desde 2009, Maisa Félix possui vasta experiência em investigações criminais e gestão pública. Formada em Direito pela UFPB com especialização em Segurança Pública, assumiu a direção com o compromisso de modernizar os processos investigativos e fortalecer a integração entre as forças de segurança do estado.", 
      imagem: "https://i0.wp.com/www.maispb.com.br/wp-content/uploads/2021/05/Maisa-Felix.jpg?fit=1200%2C800&quality=90&strip=all&ssl=1",
      realizacoes: [
        "Implementação do sistema integrado de investigação criminal",
        "Modernização do parque tecnológico da instituição",
        "Ampliação do efetivo em 15% nas delegacias especializadas"
      ]
    },
    { 
      id: 2, 
      nome: "SEVERIANO PEDRO DO NASCIMENTO FILHO", 
      periodo: "11/09/2013 - 13/04/2022", 
      historico: "Com mais de 30 anos de carreira na Polícia Civil, Severiano Pedro foi responsável por importantes transformações na instituição. Durante seu período como diretor, promoveu a interiorização das ações policiais e o fortalecimento das unidades especializadas, além de priorizar a capacitação continuada dos servidores.", 
      imagem: "https://i0.wp.com/www.politika.com.br/wp-content/uploads/2019/09/delegado-severiano-pedro.jpg?ssl=1",
      realizacoes: [
        "Criação de 12 novas delegacias especializadas no interior",
        "Implementação do programa de capacitação continuada para servidores",
        "Reestruturação da Academia de Polícia Civil"
      ]
    },
    // Adicione mais diretores conforme necessário
  ]);
  
  // Estado para o diretor selecionado e filtro
  const diretorSelecionado = ref(null);
  const filtroAtivo = ref('todos');
  
  // Diretores filtrados com base no filtro ativo
  const diretoresFiltrados = computed(() => {
    if (filtroAtivo.value === 'todos') {
      return diretores.value;
    } else if (filtroAtivo.value === 'atual') {
      return diretores.value.filter(diretor => diretor.periodo.includes('ATUALMENTE'));
    } else {
      return diretores.value.filter(diretor => !diretor.periodo.includes('ATUALMENTE'));
    }
  });
  
  // Métodos
  const selecionarDiretor = (diretor) => {
    diretorSelecionado.value = diretor;
    // Adiciona classe ao body para prevenir scroll quando modal está aberto
    document.body.classList.add('overflow-hidden');
  };
  
  const fecharModal = () => {
    diretorSelecionado.value = null;
    // Remove classe do body quando modal é fechado
    document.body.classList.remove('overflow-hidden');
  };
  
  const filtrarPeriodo = (filtro) => {
    filtroAtivo.value = filtro;
  };
  </script>
  
  <style scoped>
  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.3s ease;
  }
  
  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
  }
  </style>