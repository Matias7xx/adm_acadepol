<script setup>
import { computed } from 'vue'
import { containerMaxW } from '@/config.js';
import BaseLevel from '@/Components/BaseLevel.vue'

const year = computed(() => new Date().getFullYear())
</script>

<template>
  <footer class="py-2 px-6">
    <BaseLevel :class="containerMaxW">
      <div class="text-center md:text-left">
        <!-- <b>&copy;{{ year }}, <a
          href="https://acadepol.pb.gov.br/"
          target="_blank"
        >Academia de Ensino da Polícia Civil</a></b> -->
        <!--Get more with <a
          href="https://tailwind-vue.justboil.me/"
          target="_blank"
          class="text-blue-600"
        >Premium version</a>
      </div>
      <div class="md:py-2">
        <a href="https://justboil.me">
          <JustboilLogo class="w-auto h-8 md:h-6" />
        </a>-->
      </div>
    </BaseLevel>
  </footer>
</template>
