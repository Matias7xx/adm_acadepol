<template>
  <div class="card-section">
    <div v-if="title" class="card-section-header">
      <span class="card-section-accent"></span>
      <h2 class="card-section-title">{{ title }}</h2>
    </div>
    <div class="card-section-body">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
});
</script>

<style scoped>
.card-section {
  @apply bg-white rounded-lg overflow-hidden border border-gray-200;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.card-section-header {
  @apply flex items-center px-5 py-4 border-b border-gray-100;
}

.card-section-title {
  @apply text-lg font-bold text-gray-800 tracking-wide uppercase;
}

.card-section-body {
  @apply p-6;
}
</style>
