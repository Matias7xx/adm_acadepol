<template>
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 v-if="title" class="text-2xl font-bold mb-4 text-gray-800 border-b pb-2">{{ title }}</h2>
      <slot></slot>
    </div>
  </template>
  
  <script setup>
  defineProps({
    title: {
      type: String,
      default: ''
    }
  });
  </script>