<script setup>
import IconComponent from './IconComponent.vue';

defineProps({
  icon: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <div class="info-item">
    <div class="info-icon-wrapper">
      <IconComponent :name="icon" />
    </div>
    <div class="info-text">
      <p class="info-label">{{ label }}</p>
      <p class="info-value">{{ value }}</p>
    </div>
  </div>
</template>

<style scoped>
.info-item {
  @apply flex items-start gap-3;
}

.info-icon-wrapper {
  @apply flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center;
  background-color: #faf5e8;
}

.info-text {
  @apply flex flex-col;
}

.info-label {
  @apply text-xs font-medium text-gray-500 uppercase tracking-wide;
}

.info-value {
  @apply text-sm font-semibold text-gray-800 mt-0.5;
}
</style>
