
<script setup>
import { defineProps } from 'vue';

defineProps({
  icon: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: 'gray'
  },
  label: {
    type: String,
    required: true
  },
  value: {
    type: String,
    required: true
  }
});
</script>

<template>
    <div class="flex items-start">
      <div :class="`bg-${color}-100 p-3 rounded-full mr-4`">
        <IconComponent :name="icon" :color="color" />
      </div>
      <div>
        <h3 class="text-md font-semibold text-gray-800">{{ label }}</h3>
        <p class="text-gray-600">{{ value }}</p>
      </div>
    </div>
  </template>