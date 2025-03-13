<script setup>
import { computed } from 'vue'
import { useDarkModeStore } from '@/Stores/darkMode.js'
import { gradientBgPurplePink, gradientBgDark, gradientBgPinkRed } from '@/colors'
import imgUrl from '@/src/assets/logo-pcpb1.png'

const props = defineProps({
  bg: {
    type: String,
    required: true,
    validator: value => ['purplePink', 'pinkRed'].includes(value)
  }
})

const colorClass = computed(() => {
  if (useDarkModeStore().isEnabled) {
    return gradientBgDark
  }

  switch (props.bg) {
    case 'purplePink':
      return gradientBgPurplePink
    case 'pinkRed':
      return  gradientBgPinkRed
  }
  
  return ''
})
</script>

<template>
  <div
    class="flex min-h-screen items-center justify-center"
    :class="colorClass"
  >
  <!-- <div><img :src="imgUrl" class=""/></div> -->
    <slot card-class="w-11/12 md:w-7/12 lg:w-6/12 xl:w-4/12 shadow-2xl" />
  </div>
</template>
