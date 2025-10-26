<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  href: {
    type: String,
    default: '#',
  },
  method: {
    type: String,
    default: 'get',
  },
  as: {
    type: String,
    default: 'a',
  },
  active: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <Link
    :href="href"
    :method="method"
    :as="as"
    class="flex items-center w-full px-4 py-2 text-sm font-medium transition-colors duration-150 ease-in-out"
    :class="[
      active
        ? 'bg-[#a38e4d]/10 text-[#a38e4d]'
        : 'text-gray-700 hover:bg-[#a38e4d]/10 hover:text-[#a38e4d]',
    ]"
  >
    <slot />
  </Link>
</template>
