<template>
  <div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Estrutura Física da ACADEPOL</h2>
    
    <!-- Descrição -->
    <div class="mb-8">
      <p class="text-gray-700 mb-4">
        {{ descricao }}
      </p>
    </div>

    <!-- Filtros para categorias -->
    <div class="flex flex-wrap gap-2 mb-6">
      <button 
        v-for="categoria in categorias" 
        :key="categoria"
        @click="filtrarPor(categoria)"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
        :class="categoriaAtiva === categoria ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'"
      >
        {{ categoria }}
      </button>
      <button 
        @click="filtrarPor('Todos')"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
        :class="categoriaAtiva === 'Todos' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'"
      >
        Todos
      </button>
    </div>

    <!-- Galeria de fotos em grid responsivo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div 
        v-for="(foto, index) in fotosFiltradas" 
        :key="index"
        class="group relative overflow-hidden rounded-lg shadow-md cursor-pointer h-64"
        @click="abrirModal(foto)"
      >
        <img 
          :src="foto.url" 
          :alt="foto.titulo" 
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300"></div>
        <div class="absolute bottom-0 left-0 right-0 p-4 text-white transform translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/80 to-transparent">
          <h3 class="font-semibold">{{ foto.titulo }}</h3>
          <p class="text-sm opacity-90">{{ foto.descricao }}</p>
        </div>
      </div>
    </div>

    <!-- Modal para visualização ampliada -->
    <div v-if="fotoSelecionada" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-80" @click.self="fecharModal">
      <div class="relative max-w-4xl max-h-screen flex flex-col bg-white rounded-lg shadow-2xl overflow-hidden">
        <!-- Botão de fechar -->
        <button @click="fecharModal" class="absolute top-2 right-2 w-8 h-8 flex items-center justify-center rounded-full bg-white text-gray-800 z-10 hover:bg-gray-200">
          <span class="text-xl">&times;</span>
        </button>
        
        <!-- Imagem ampliada -->
        <div class="relative w-full max-h-[70vh] overflow-hidden">
          <img :src="fotoSelecionada.url" :alt="fotoSelecionada.titulo" class="object-contain max-h-[70vh] w-full" />
        </div>
        
        <!-- Informações -->
        <div class="p-4 bg-white">
          <h3 class="text-xl font-bold text-gray-800 mb-2">{{ fotoSelecionada.titulo }}</h3>
          <p class="text-gray-700">{{ fotoSelecionada.descricao }}</p>
          <p class="text-sm text-gray-500 mt-2">Categoria: {{ fotoSelecionada.categoria }}</p>
        </div>
        
        <!-- Navegação -->
        <div class="flex justify-between p-4 bg-gray-100">
          <button 
            @click="navegarFoto(-1)" 
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-800"
            :disabled="indiceAtual === 0"
            :class="{ 'opacity-50 cursor-not-allowed': indiceAtual === 0 }"
          >
            Anterior
          </button>
          <span class="text-gray-600">{{ indiceAtual + 1 }} de {{ fotosFiltradas.length }}</span>
          <button 
            @click="navegarFoto(1)" 
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-800"
            :disabled="indiceAtual === fotosFiltradas.length - 1"
            :class="{ 'opacity-50 cursor-not-allowed': indiceAtual === fotosFiltradas.length - 1 }"
          >
            Próximo
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'GaleriaFotos',
  setup() {
    const descricao = 'Conheça a estrutura física da ACADEPOL através de nossa galeria de fotos. Oferecemos instalações modernas e equipadas para a formação e treinamento dos profissionais de segurança pública.';
    const categorias = ['Salas de Aula', 'Laboratórios', 'Área Externa', 'Auditório', 'Biblioteca', 'Instalações Esportivas'];
    const categoriaAtiva = ref('Todos');
    const fotoSelecionada = ref(null);
    const indiceAtual = ref(0);

    const fotos = [
      {
        id: 1,
        titulo: 'Fachada Principal',
        descricao: 'Entrada principal da ACADEPOL',
        categoria: 'Área Externa',
        url: '/assets/images/estrutura/fachada.jpg'
      },
      {
        id: 2,
        titulo: 'Laboratório de Informática',
        descricao: 'Laboratório equipado com computadores de última geração',
        categoria: 'Laboratórios',
        url: '/assets/images/estrutura/lab-informatica.jpg'
      },
      {
        id: 3,
        titulo: 'Sala de Aula Interativa',
        descricao: 'Sala com recursos multimídia para aulas dinâmicas',
        categoria: 'Salas de Aula',
        url: '/assets/images/estrutura/sala-aula.jpg'
      },
      {
        id: 4,
        titulo: 'Biblioteca',
        descricao: 'Acervo completo com obras especializadas em segurança pública',
        categoria: 'Biblioteca',
        url: '/assets/images/estrutura/biblioteca.jpg'
      },
      {
        id: 5,
        titulo: 'Auditório Principal',
        descricao: 'Capacidade para 300 pessoas, equipado com sistema de som e projeção',
        categoria: 'Auditório',
        url: '/assets/images/estrutura/auditorio.jpg'
      },
      {
        id: 6,
        titulo: 'Laboratório de Balística',
        descricao: 'Ambiente para análises e estudos balísticos',
        categoria: 'Laboratórios',
        url: '/assets/images/estrutura/lab-balistica.jpg'
      },
      {
        id: 7,
        titulo: 'Quadra Poliesportiva',
        descricao: 'Espaço para atividades físicas e treinamento tático',
        categoria: 'Instalações Esportivas',
        url: '/assets/images/estrutura/quadra.jpg'
      },
      {
        id: 8,
        titulo: 'Estande de Tiro',
        descricao: 'Ambiente seguro para treinamento de tiro',
        categoria: 'Instalações Esportivas',
        url: '/assets/images/estrutura/estande-tiro.jpg'
      }
    ];

    const fotosFiltradas = computed(() => {
      if (categoriaAtiva.value === 'Todos') {
        return fotos;
      }
      return fotos.filter(foto => foto.categoria === categoriaAtiva.value);
    });

    const filtrarPor = (categoria) => {
      categoriaAtiva.value = categoria;
      fotoSelecionada.value = null;
    };

    const abrirModal = (foto) => {
      fotoSelecionada.value = foto;
      indiceAtual.value = fotosFiltradas.value.findIndex(f => f.id === foto.id);
    };

    const fecharModal = () => {
      fotoSelecionada.value = null;
    };

    const navegarFoto = (direcao) => {
      const novoIndice = indiceAtual.value + direcao;
      if (novoIndice >= 0 && novoIndice < fotosFiltradas.value.length) {
        indiceAtual.value = novoIndice;
        fotoSelecionada.value = fotosFiltradas.value[novoIndice];
      }
    };

    return {
      descricao,
      categorias,
      categoriaAtiva,
      fotoSelecionada,
      indiceAtual,
      fotosFiltradas,
      filtrarPor,
      abrirModal,
      fecharModal,
      navegarFoto
    };
  }
};
</script>