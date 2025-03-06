<script setup>
const { flash } = usePage().props
import { usePage } from '@inertiajs/vue3'
console.log('Flash message:', usePage().props.flash.message)
</script>

<template>
    <div v-if="$page.props.flash.message" 
         class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50 shadow-md"
         role="alert">
      <div class="flex">
        <div class="py-1">
          <svg class="h-6 w-6 text-green-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <p class="font-bold">{{ $page.props.flash.message }}</p>
        </div>
        <div class="ml-auto">
          <button @click="$page.props.flash.message = null" class="text-green-700">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </template>