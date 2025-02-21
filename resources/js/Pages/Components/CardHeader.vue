<script setup>
defineProps({
  title: {
    type: String,
    default: "teste",
  }
});
</script>

<template>
    <div class="flex items-center gap-3 px-7 py-4 border-b-2 border-gray-200">
      <!-- Ícone de Certificado/Diploma -->
      <!-- <svg class="w-7 h-7 text-gray-500" viewBox="0 0 24 24" fill="currentColor">
        <path d="M5 3v18h14V3H5zm12 2v14H7V5h10zm-5 10.5c1.1 0 2-.9 2-2h2c0 2.2-1.8 4-4 4s-4-1.8-4-4h2c0 1.1.9 2 2 2zM9 8h6v2H9V8z"/>
      </svg> -->
  
      <!-- Título -->
      <h2 class="text-lg font-bold tracking-wide uppercase text-gray-800">
        {{ title }}
      </h2>
    </div>
  </template>
  
  