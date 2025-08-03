<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import DropdownInstitucional from './DropdownInstitucional.vue';
import DropdownServicos from './DropdownServicos.vue';
import SocialMedia from './SocialMedia.vue';
import Search from './Search.vue';
import Header from './Header.vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
const { toast } = useToast();
import Toast from './Toast.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

function navigateToCursos() {
  router.visit(route('admin.cursos.index'));
}

// Função para extrair primeiro e último nome
function formatName(fullName) {
  if (!fullName) return '';
  
  const names = fullName.trim().split(' ');
  if (names.length === 1) return names[0];
  
  // Retorna o primeiro e último nome
  return `${names[0]} ${names[names.length - 1]}`;
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
              <DropdownServicos />
              <Link href="/cursos" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Cursos</Link>
              <!-- <Link href="#" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Banco de Currículos</Link> -->
              <Link href="/concursos" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Concursos</Link>
              <Link href="/fale-conosco" class="text-black hover:bg-[#a38e4d] rounded-md px-3 py-2 text-sm font-medium">Fale Conosco</Link>
            </div>

            <!-- Área de Login e Social -->
            <div class="hidden sm:flex items-center space-x-4">
              <SocialMedia />
              <div class="flex items-center">
                <template v-if="$page.props.auth.user">
                  <div class="flex items-center">
                    <!-- Settings Dropdown -->
                    <div class="relative">
                      <Dropdown 
                        align="right" 
                        width="48"
                        dropdownClasses="mt-1"
                        :contentClasses="['py-2', 'font-medium']"
                      >
                        <template #trigger>
                          <button
                            type="button"
                            class="flex items-center px-3 py-2 text-sm font-medium text-black bg-transparent hover:bg-[#a38e4d] rounded-md transition-colors"
                          >
                            {{ formatName($page.props.auth.user.name) }}
                            <svg
                              class="ml-2 -mr-0.5 h-4 w-4"
                              xmlns="http://www.w3.org/2000/svg"
                              viewBox="0 0 20 20"
                              fill="currentColor"
                            >
                              <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                              />
                            </svg>
                          </button>
                        </template>

                        <template #content>
                          <DropdownLink :href="route('profile.edit')">
                            <span class="flex items-center">
                              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-2">
                                <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                              </svg>
                              Perfil
                            </span>
                          </DropdownLink>
                          
                          <div class="border-t border-gray-100 my-1"></div>
                          
                          <DropdownLink :href="route('logout')" method="post" as="button" class="w-full text-left">
                            <span class="flex items-center text-red-600">
                              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-2">
                                <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                              </svg>
                              Sair
                            </span>
                          </DropdownLink>
                        </template>
                      </Dropdown>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <a :href="route('login')" class="inline-flex items-center justify-center text-black font-bold px-4 py-2 rounded-md text-sm hover:bg-[#a38e4d] transition-colors">
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
            <DropdownServicos />
            <Link href="/cursos" class="text-black hover:text-[#a38e4d] px-3 py-2">Cursos</Link>
            <!-- <Link href="#" class="text-black hover:text-[#a38e4d] px-3 py-2">Banco de Currículos</Link> -->
            <Link href="/concursos" class="text-black hover:text-[#a38e4d] px-3 py-2">Concursos</Link>
            <Link href="/fale-conosco" class="text-black hover:text-[#a38e4d] px-3 py-2">Fale Conosco</Link>
            
            <!-- Redes Sociais no Mobile -->
            <div class="mt-3 border-t border-gray-200 pt-2 flex justify-center">
              <SocialMedia />
            </div>
            
            <!-- Login no Mobile -->
            <div class="border-t border-gray-200 mt-2 pt-2">
              <template v-if="$page.props.auth.user">
                <div class="flex flex-col items-center">
                  <span class="text-gray-700 font-medium py-2">{{ formatName($page.props.auth.user.name) }}</span>
                  <div class="flex space-x-2 mt-2 w-full">
                    <Link :href="route('profile.edit')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 text-center py-2 px-3 rounded-md text-sm font-medium transition-colors flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-1">
                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                      </svg>
                      Perfil
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="flex-1 bg-red-500 hover:bg-red-600 text-white text-center py-2 px-3 rounded-md text-sm font-medium transition-colors flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-1">
                        <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                      </svg>
                      Sair
                    </Link>
                  </div>
                </div>
              </template>
              <template v-else>
                <Link :href="route('login')" class="block w-full bg-black text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-800 text-center transition-colors">
                  LOGIN
                </Link>
              </template>
            </div>
          </div>
        </div>
      </nav>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="container mx-auto">
      <slot />
    </div>
  </div>
</template>