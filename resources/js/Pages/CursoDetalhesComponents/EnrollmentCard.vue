<script setup>
import { Link } from '@inertiajs/vue3';
import CursoDetalhe from '../CursoDetalhe.vue';

const props = defineProps({
  status: {
    type: String,
    default: null
  },
  capacidade: {
    type: Number,
    required: true
  },
  dataInicio: {
    type: String,
    required: true
  },
  curso: {  // Adicione a prop curso
    type: Object,
    required: true
  }
});

defineEmits(['enroll']);

const formatDate = (date) => {
  if (!date) return 'Não definido';
  return new Date(date).toLocaleDateString('pt-BR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  });
};
</script>

<template>
  <div class="bg-white rounded-lg shadow-md p-6 mb-6 sticky top-4">
    <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">Inscrições</h3>
    
    <div class="space-y-3 mb-6">
      <div class="flex justify-between">
        <span class="text-gray-600">Status:</span>
        <span class="font-medium" :class="{ 
          'text-green-600': status === 'aberto', 
          'text-red-600': status === 'fechado' || !status,
          'text-yellow-600': status === 'em andamento'
        }">
          {{ status || 'Indisponível' }}
        </span>
      </div>
      
      <div class="flex justify-between">
        <span class="text-gray-600">Vagas:</span>
        <span class="font-medium text-gray-800">{{ capacidade }}</span>
      </div>
      
      <div class="flex justify-between">
        <span class="text-gray-600">Período de matrícula:</span>
        <span class="font-medium text-gray-800">Até {{ formatDate(dataInicio) }}</span>
      </div>
    </div>
    
    <!-- Botão de Inscrição -->
    <div v-if="status === 'aberto' && curso && curso.id">
      <Link 
        :href="route('matricula', curso.id)" 
        class="block w-full bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-4 rounded-md text-center transition-colors duration-200"
      >
        Inscrever-se
      </Link>
    </div>
    
    <div v-else class="mt-3 text-sm text-center text-gray-500">
      Inscrições {{ status === 'em andamento' ? 'encerradas' : 'não disponíveis' }}
    </div>
  </div>
</template>