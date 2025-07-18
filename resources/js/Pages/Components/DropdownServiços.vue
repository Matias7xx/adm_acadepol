<template>
  <div 
    class="relative inline-block text-left" 
    @mouseenter="openDropdown"
    @mouseleave="delayedClose"
  >
    <button 
      type="button" 
      class="inline-flex w-full justify-center gap-x-1.5 rounded-md px-3 py-2 text-sm font-semibold text-black-300 hover:bg-[#a38e4d] transition-colors duration-200" 
    >
      Serviços
      <svg class="-mr-1 size-5 text-black-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
      </svg>
    </button>

    <div 
      v-if="isOpen"
      class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white ring-1 shadow-lg ring-gray-700 focus:outline-none"
      @mouseenter="cancelClose"
      @mouseleave="closeDropdown"
    >
      <div class="py-1">
        <a 
          v-for="(item, index) in menuItems" 
          :key="index"
          :href="item.href"
          class="block px-3 py-2 text-sm text-black hover:text-[#bea55a] rounded-md font-medium hover:bg-gray-100 transition-colors duration-200"  
        >
          {{ item.text }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const isOpen = ref(false);
let closeTimeout = null;

const openDropdown = () => {
  isOpen.value = true;
};

const delayedClose = () => {
  closeTimeout = setTimeout(() => {
    isOpen.value = false;
  }, 100); // Pequeno delay antes de fechar
};

const closeDropdown = () => {
  isOpen.value = false;
};

const cancelClose = () => {
  if (closeTimeout) {
    clearTimeout(closeTimeout);
  }
};

const menuItems = [
  { text: 'Requerimentos', href: '/requerimentos/novo' },
  { text: 'Reservar Alojamento', href: '/alojamento/escolha-tipo' },
  /* { text: 'Agendamentos', href: '#' } */
];
</script>
