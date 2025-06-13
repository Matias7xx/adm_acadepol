<script setup>
import SocialMedia from './SocialMedia.vue';
import governoPb from '@/src/assets/governopb.png'
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <footer class="bg-black text-gray-300">
    <div class="max-w-6xl mx-auto px-4 md:px-6 lg:px-8 py-6 md:py-10">
      <!-- Grid Principal -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 md:gap-8 items-start">
        
        <!-- Coluna 1: Institucional -->
        <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-3 lg:col-span-1">
          <h2 class="text-lg font-bold text-white tracking-wide">ACADEPOL</h2>
          <p class="text-sm leading-relaxed text-gray-300 max-w-xs">
            Academia de Polícia Civil da Paraíba. Formação e capacitação para segurança pública.
          </p>
        </div>

        <!-- Coluna 2: Links Úteis -->
        <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-3 lg:col-span-1">
          <h2 class="text-lg font-bold text-white tracking-wide">Links Úteis</h2>
          <ul class="space-y-2">
            <li>
              <a href="/cursos" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Cursos
              </a>
            </li>
            <!-- <li>
              <a href="#" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Biblioteca Virtual
              </a>
            </li> -->
            <li>
              <a href="/fale-conosco" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Contato
              </a>
            </li>
            <li>
              <a href="/regimento-interno" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Regimento Interno
              </a>
            </li>
            <li>
              <a href="/organograma" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Organograma
              </a>
            </li>
            <li>
              <a href="/estrutura" class="text-sm hover:text-[#a38e4d] transition-colors duration-200">
                Estrutura
              </a>
            </li>
          </ul>
        </div>

        <!-- Coluna 3: Endereço e Contato -->
        <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-3 lg:col-span-1">
          <h2 class="text-lg font-bold text-white tracking-wide">Endereço</h2>
          <div class="text-sm text-gray-300 space-y-2">
            <p class="leading-relaxed">
              Rodovia Ministro Abelardo Jurema, s/n<br>
              (PB 008, KM 07) Costa do Sol<br>
              João Pessoa/PB<br>
              CEP: 58048-010
            </p>
            <div class="space-y-1">
              <p class="font-semibold text-white">Telefone:</p>
              <p>(83) 3612-8600</p>
            </div>
          </div>
        </div>

        <!-- Coluna 4: Redes Sociais -->
        <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-3 lg:col-span-1">
          <h2 class="text-lg font-bold text-white tracking-wide">Redes Sociais</h2>

          <!-- SocialMedia -->
          <div class="w-full flex justify-center sm:justify-start">
            <SocialMedia class="!py-0 !justify-start"
            colorSetup="fill-gray-400 relative z-10 transition-all duration-500 group-hover:fill-white"
            xConfig="absolute top-full left-0 w-full h-full rounded-full bg-gray-700 z-0 transition-all duration-500 group-hover:top-0" />
          </div>
        </div>

        <!-- Coluna 5: Logo do Governo -->
        <div class="flex justify-center lg:justify-end lg:col-span-1">
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

      <!-- Copyright e Créditos -->
      <div class="mt-6 md:mt-8 pt-4 border-t border-gray-800">
        <div class="flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
          <p class="text-xs text-gray-400 text-center sm:text-left">
            &copy; {{ new Date().getFullYear() }} ACADEPOL. Todos os direitos reservados.
          </p>
          <p class="text-xs text-gray-400 text-center sm:text-right">
            <span class="text-[#a38e4d] font-medium">Diretoria de Tecnologia da Informação</span>
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>