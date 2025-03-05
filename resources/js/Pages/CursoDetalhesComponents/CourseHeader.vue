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
    <div class="relative h-80 w-full bg-gray-800">
      <img 
        :src="imagem" 
        :alt="nome" 
        class="w-full h-full object-cover opacity-75"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
      <div class="absolute bottom-0 left-0 p-8 text-white">
        <div class="flex items-center mb-2">
          <span class="bg-yellow-600 text-white px-3 py-1 text-sm font-medium rounded">{{ modalidade }}</span>
          <span v-if="status" class="ml-2 bg-green-600 text-white px-3 py-1 text-sm font-medium rounded">{{ status }}</span>
          <span v-else class="ml-2 bg-red-600 text-white px-3 py-1 text-sm font-medium rounded">Inativo</span>
        </div>
        <h1 class="text-4xl font-bold mb-2">{{ nome }}</h1>
      </div>
    </div>
  </template>