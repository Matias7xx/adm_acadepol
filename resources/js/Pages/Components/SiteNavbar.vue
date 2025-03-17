<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import DropdownInstitucional from './DropdownInstitucional.vue';
import DropdownServiços from './DropdownServiços.vue';
import SocialMedia from './SocialMedia.vue';
import Search from './Search.vue';
import Header from './Header.vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
const { toast } = useToast();
import Toast from './Toast.vue';

function navigateToCursos() {
  router.visit(route('admin.cursos.index'));
}

const menuOpen = ref(false);
const isMenuOpen = ref(false);

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value;
};

defineProps({
  canLogin: Boolean,
});

const isScrolled = ref(false);
const headerHeight = ref(0);

// Função para verificar o scroll
function handleScroll() {
  isScrolled.value = window.scrollY > 50;
}

// Função para medir a altura da navbar + barra de pesquisa
function measureHeaderHeight() {
  const header = document.querySelector('.header-container');
  if (header) {
    headerHeight.value = header.offsetHeight;
  }
  // Ajustar o padding do conteúdo para evitar sobreposição
  document.body.style.paddingTop = `${headerHeight.value}px`;
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  nextTick(() => {
    measureHeaderHeight();
  });
  window.addEventListener('resize', measureHeaderHeight);
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', measureHeaderHeight);
});
</script>

<template>
  <div>
    <!-- Container fixo do cabeçalho -->
    <div class="header-container fixed top-0 left-0 right-0 z-50 bg-white shadow-lg">
      <Toast />

      <!-- Navbar -->
      <nav class="bg-[#bea55a] shadow-md transition-all duration-300">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center h-14">
            <div class="hidden sm:flex space-x-6 items-center">
              <DropdownInstitucional />
              <DropdownServiços />
              <Link href="/cursos" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Cursos</Link>
              <Link href="#" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Banco de Currículos</Link>
              <Link href="#" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Concursos e Seleções</Link>
              <Link href="#" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Fale Conosco</Link>
            </div>

            <!-- Área de Login e Social -->
            <div class="hidden sm:flex items-center space-x-6">
              <SocialMedia />
              <div class="flex items-center space-x-4">
                <template v-if="$page.props.auth.user">
                  <span class="text-black text-sm font-bold uppercase tracking-wide">{{ $page.props.auth.user.name }}</span>
                  <Link :href="route('logout')" method="post" as="button" class="text-black px-4 py-2 rounded-md text-sm font-bold hover:bg-[#a38e4d]">
                    LOGOUT
                  </Link>
                </template>
                <template v-else>
                  <a :href="route('login')" class="text-black px-4 py-2 rounded-md text-sm font-bold hover:bg-[#a38e4d]">
                    LOGIN
                  </a>
                </template>
              </div>
            </div>

            <!-- Botão Mobile -->
            <div class="sm:hidden">
              <button @click="toggleMenu" class="text-black hover:text-white focus:outline-none">
                <svg v-if="!isMenuOpen" class="h-6 w-6" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-16 6h16" />
                </svg>
                <svg v-else class="h-6 w-6" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Menu Mobile -->
        <div v-if="isMenuOpen" class="sm:hidden bg-white border-t border-gray-200">
          <div class="flex flex-col space-y-2 p-4">
            <DropdownInstitucional />
            <DropdownServiços />
            <Link href="/cursos" class="text-black hover:text-[#a38e4d] px-3 py-2">Cursos</Link>
            <Link href="#" class="text-black hover:text-[#a38e4d] px-3 py-2">Banco de Currículos</Link>
            <Link href="#" class="text-black hover:text-[#a38e4d] px-3 py-2">Concursos e Seleções</Link>
            <Link href="#" class="text-black hover:text-[#a38e4d] px-3 py-2">Fale Conosco</Link>
            <!-- Redes Sociais no Mobile -->
            <div class="mt-3 border-t border-gray-200 pt-2 flex justify-center">
              <SocialMedia />
            </div>
            <!-- Login no Mobile -->
            <div class="border-t border-gray-200 mt-2 pt-2 text-center">
              <template v-if="$page.props.auth.user">
                <span class="block text-gray-700 px-3 py-2">{{ $page.props.auth.user.name }}</span>
                <Link :href="route('logout')" method="post" as="button" class="w-full bg-black text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-[#a38e4d] mt-2">
                  LOGOUT
                </Link>
              </template>
              <template v-else>
                <Link :href="route('login')" class="w-full bg-black text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-[#a38e4d] block text-center">
                  LOGIN
                </Link>
              </template>
            </div>
          </div>
        </div>
      </nav>
    </div>
    <!-- Ajuste para compensar a navbar + barra de pesquisa -->
    <!-- <div class="mt-[20px]"></div> -->
    <!-- Conteúdo Principal -->
    <div class="container mx-auto">
      <slot />
    </div>
  </div>
</template>
