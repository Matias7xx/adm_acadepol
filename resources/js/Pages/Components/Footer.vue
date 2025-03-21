<script setup>
import SocialMedia from './SocialMedia.vue';
import governoPb from '@/src/assets/governopb.png'
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <footer class="bg-black text-gray-300">
    <div class="max-w-6xl mx-auto px-4 md:px-6 lg:px-8 py-6 md:py-10">
      <!-- Grid Principal -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 md:gap-8 items-center">
        
        <!-- Coluna 1: Institucional -->
        <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-3">
          <h2 class="text-lg font-bold text-white tracking-wide">ACADEPOL</h2>
          <p class="text-sm leading-relaxed text-gray-300 max-w-xs">
            Academia de Polícia Civil da Paraíba. Formação e capacitação para segurança pública.
          </p>
        </div>

        <!-- Coluna 2: Links Úteis -->
        <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-3">
          <h2 class="text-lg font-bold text-white tracking-wide">Links Úteis</h2>
          <ul class="space-y-2">
            <li>
              <a href="/cursos" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Cursos
              </a>
            </li>
            <li>
              <a href="#" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Processos Seletivos
              </a>
            </li>
            <li>
              <a href="#" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Biblioteca Virtual
              </a>
            </li>
            <li>
              <a href="/fale-conosco" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Contato
              </a>
            </li>
          </ul>
        </div>

        <!-- Coluna 3: Redes Sociais -->
        <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-1">
          <h2 class="text-lg font-bold text-white tracking-wide">Redes Sociais</h2>

          <!-- SocialMedia agora em branco e mais próximo do título -->
          <SocialMedia class="w-full flex justify-center sm:justify-start"
          colorSetup="fill-gray-400 relative z-10 transition-all duration-500 group-hover:fill-white"
          xConfig="absolute top-full left-0 w-full h-full rounded-full bg-gray-700 z-0 transition-all duration-500 group-hover:top-0" />
        </div>

        <!-- Coluna 4: Logo do Governo -->
        <div class="flex justify-center md:justify-end">
          <a href="https://paraiba.pb.gov.br/" target="_blank"
         class="transform transition hover:opacity-90">
            <img 
              class="h-12 sm:h-14 md:h-16 w-auto" 
              :src="governoPb" 
              alt="Logo Governo da Paraíba"
            >
          </a>
        </div>
      </div>

      <!-- Copyright -->
      <div class="mt-6 md:mt-8 pt-4 border-t border-gray-800 text-center">
        <p class="text-xs text-gray-400">
          &copy; {{ new Date().getFullYear() }} ACADEPOL. Todos os direitos reservados.
        </p>
      </div>
    </div>
  </footer>
</template>


