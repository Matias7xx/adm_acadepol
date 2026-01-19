<template>
  <div
    class="relative inline-block text-left"
    ref="dropdownRef"
    @mouseenter="!isMobile && openDropdown()"
    @mouseleave="!isMobile && delayedClose()"
  >
    <button
      type="button"
      @click="handleButtonClick"
      class="inline-flex w-full bg-[#bea55a] justify-center gap-x-1.5 rounded-md px-3 py-2 text-sm font-semibold text-black-300 hover:bg-[#a38e4d] transition-colors duration-200"
    >
      Institucional
      <svg
        class="-mr-1 size-5 text-black-400 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white ring-1 shadow-lg ring-gray-700 focus:outline-none"
        @mouseenter="!isMobile && cancelClose()"
        @mouseleave="!isMobile && closeDropdown()"
      >
        <div class="py-1">
          <Link
            v-for="(item, index) in menuItems"
            :key="index"
            :href="item.href"
            @click="closeDropdown"
            class="block px-3 py-2 text-sm text-black hover:text-[#bea55a] rounded-md font-medium hover:bg-gray-100 transition-colors duration-200"
          >
            {{ item.text }}
          </Link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';

const isOpen = ref(false);
const isMobile = ref(false);
const dropdownRef = ref(null);
let closeTimeout = null;

// Detecta se é dispositivo móvel
const checkIsMobile = () => {
  isMobile.value = window.innerWidth < 768 || 'ontouchstart' in window;
};

// Manipula clique no botão
const handleButtonClick = () => {
  if (isMobile.value) {
    isOpen.value = !isOpen.value;
  } else {
    // No desktop
    isOpen.value = true;
  }
};

// Abre o dropdown (usado no hover para desktop)
const openDropdown = () => {
  if (closeTimeout) {
    clearTimeout(closeTimeout);
  }
  isOpen.value = true;
};

// Fecha com delay (usado no hover para desktop)
const delayedClose = () => {
  closeTimeout = setTimeout(() => {
    isOpen.value = false;
  }, 100);
};

// Fecha imediatamente
const closeDropdown = () => {
  isOpen.value = false;
};

// Cancela o fechamento
const cancelClose = () => {
  if (closeTimeout) {
    clearTimeout(closeTimeout);
  }
};

// Fecha dropdown ao clicar fora (para mobile)
const handleClickOutside = event => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false;
  }
};

// Fecha dropdown com ESC
const handleEscapeKey = event => {
  if (event.key === 'Escape') {
    isOpen.value = false;
  }
};

const menuItems = [
  { text: 'História', href: '/historia' },
  { text: 'Missão', href: '/missao' },
  { text: 'Diretores e Ex-Diretores', href: '/diretores' },
  { text: 'Estrutura Física', href: '/estrutura' },
  { text: 'Regimento Interno', href: '/regimento-interno' },
  { text: 'Organograma', href: '/organograma' },
];

onMounted(() => {
  checkIsMobile();
  window.addEventListener('resize', checkIsMobile);
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleEscapeKey);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkIsMobile);
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleEscapeKey);
  if (closeTimeout) {
    clearTimeout(closeTimeout);
  }
});
</script>
