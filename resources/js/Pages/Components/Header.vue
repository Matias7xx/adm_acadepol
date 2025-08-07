<script setup>
import logoPC from '@/src/assets/logo-pcpb2.png'
import logoAC from '@/src/assets/logo-acadepol.png'
import Search from './Search.vue'

import { Link } from '@inertiajs/vue3';

</script>

<template>
  <div class="bg-[black] py-5 md:py-7 lg:py-10 shadow-md">
    <div class="max-w-screen-xl mx-auto px-4 md:px-8 lg:px-14 flex flex-col md:flex-row items-center justify-between gap-4 md:gap-6">
      
      <!-- Logos -->
      <div class="flex items-center justify-center md:justify-start gap-4 md:gap-6">
        <Link :href="route('home')" class="shrink-0">
          <img class="h-12 sm:h-14 md:h-16 w-auto" :src="logoAC" alt="Logo Acadepol">
        </Link>
        
        <Link :href="route('home')" class="shrink-0">
          <img class="h-12 sm:h-14 md:h-16 w-auto" :src="logoPC" alt="Logo PC">
        </Link>
      </div>

      <!-- Links -->
       <div class="grid grid-cols-1 sm:flex items-center justify-center md:justify-end gap-3 sm:gap-4 md:gap-6 w-full md:w-auto">
        <Search />
        <!-- <a 
          href="http://localhost:8001/login" 
          class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 text-white rounded-md hover:text-[#bea55a] transition duration-300 justify-center min-w-[200px] sm:min-w-0"
        >
          <img :src="iconComputer" alt="Acadepol Online" class="w-auto h-8 sm:h-9 md:h-10" />
          <span class="text-base sm:text-lg md:text-xl font-medium uppercase tracking-wide">Portal do Aluno</span>
        </a>

        <a 
          href="#" 
          class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 text-white rounded-md hover:text-[#bea55a] transition duration-300 justify-center min-w-[200px] sm:min-w-0"
        >
          <img :src="iconGlobe" alt="EAD" class="w-auto h-8 sm:h-9 md:h-10" />
          <span class="text-base sm:text-lg md:text-xl font-medium uppercase tracking-wide">EaD</span>
        </a> -->
      </div> 

    </div>
  </div>
</template>
