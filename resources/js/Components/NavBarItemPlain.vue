<script setup>
defineProps({
  display: {
    type: String,
    default: 'flex'
  },
  useMargin: Boolean
})
</script>

<template>
  <div
    :class="[display, useMargin ? 'my-2 mx-3' : 'py-2 px-3']"
    class="items-center cursor-pointer text-white hover:text-[#bea55a] transition-colors duration-200"
  >
    <slot />
  </div>
</template>