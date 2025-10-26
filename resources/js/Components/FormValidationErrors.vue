<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NotificationBarInCard from '@/Components/NotificationBarInCard.vue';

const errors = computed(() => usePage().props.errors);

const hasErrors = computed(() => Object.keys(errors.value).length > 0);
</script>

<template>
  <NotificationBarInCard v-if="hasErrors" color="danger">
    <div class="flex flex-col space-y-1">
      <span v-for="(error, key) in errors" :key="key" class="text-sm">
        {{ Array.isArray(error) ? error[0] : error }}
      </span>
    </div>
  </NotificationBarInCard>
</template>
