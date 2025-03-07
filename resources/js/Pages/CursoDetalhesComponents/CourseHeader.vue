<script setup>
defineProps({
  imagem: {
    type: String,
    required: true
  },
  nome: {
    type: String,
    required: true
  },
  modalidade: {
    type: String,
    required: true
  },
  status: {
    type: String,
    default: null
  }
});
</script>
  
<template>
    <div class="relative h-80 w-full overflow-hidden">
      <img 
        :src="imagem" 
        :alt="nome" 
        class="absolute inset-0 w-full h-full object-cover object-center"
        style="filter: brightness(0.7)"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
      <div class="relative z-10 container mx-auto px-4 flex h-full items-end pb-8 text-white">
        <div>
          <div class="flex items-center mb-2">
            <span class="bg-yellow-600 text-white px-3 py-1 text-sm font-medium rounded">{{ modalidade }}</span>
            <span v-if="status" class="ml-2 bg-green-600 text-white px-3 py-1 text-sm font-medium rounded">{{ status }}</span>
            <span v-else class="ml-2 bg-red-600 text-white px-3 py-1 text-sm font-medium rounded">Inativo</span>
          </div>
          <h1 class="text-4xl font-bold mb-2">{{ nome }}</h1>
        </div>
      </div>
    </div>
</template>