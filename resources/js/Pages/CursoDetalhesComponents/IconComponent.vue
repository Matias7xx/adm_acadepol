<script setup>
defineProps({
  name: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    class="h-5 w-5"
    style="color: #bea55a"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
    stroke-width="1.75"
  >
    <path
      v-if="name === 'clock'"
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
    />
    <path
      v-else-if="name === 'calendar'"
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
    />
    <path
      v-else-if="name === 'location'"
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"
    />
    <path
      v-else-if="name === 'users'"
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
    />
    <path
      v-else
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
    />
  </svg>
</template>
