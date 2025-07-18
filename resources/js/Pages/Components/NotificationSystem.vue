<template>
  <div class="fixed top-4 right-4 z-[100] space-y-3" style="z-index: 9999;">
    <transition-group name="notification" tag="div">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="getNotificationClass(notification.type)"
        class="min-w-80 max-w-sm rounded-lg shadow-lg p-4 border border-opacity-20"
      >
        <div class="flex items-start">
          <!-- Icon -->
          <div class="flex-shrink-0">
            <svg 
              v-if="notification.type === 'success'" 
              class="w-6 h-6 text-green-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <svg 
              v-else-if="notification.type === 'error'" 
              class="w-6 h-6 text-red-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <svg 
              v-else-if="notification.type === 'warning'" 
              class="w-6 h-6 text-yellow-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <svg 
              v-else 
              class="w-6 h-6 text-blue-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          
          <!-- Content -->
          <div class="ml-3 flex-1">
            <h4 v-if="notification.title" class="text-sm font-semibold mb-1" :class="getTitleClass(notification.type)">
              {{ notification.title }}
            </h4>
            <p class="text-sm" :class="getTextClass(notification.type)">
              {{ notification.message }}
            </p>
          </div>
          
          <!-- Close Button -->
          <div class="ml-4 flex-shrink-0">
            <button
              @click="removeNotification(notification.id)"
              class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
              :class="getCloseButtonClass(notification.type)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// Estado reativo
const notifications = ref([])

// Métodos CSS
const getNotificationClass = (type) => {
  const baseClass = 'backdrop-blur-sm shadow-lg'
  switch (type) {
    case 'success':
      return `${baseClass} bg-green-50/95 border-green-200`
    case 'error':
      return `${baseClass} bg-red-50/95 border-red-200`
    case 'warning':
      return `${baseClass} bg-yellow-50/95 border-yellow-200`
    default:
      return `${baseClass} bg-blue-50/95 border-blue-200`
  }
}

const getTitleClass = (type) => {
  switch (type) {
    case 'success':
      return 'text-green-800'
    case 'error':
      return 'text-red-800'
    case 'warning':
      return 'text-yellow-800'
    default:
      return 'text-blue-800'
  }
}

const getTextClass = (type) => {
  switch (type) {
    case 'success':
      return 'text-green-700'
    case 'error':
      return 'text-red-700'
    case 'warning':
      return 'text-yellow-700'
    default:
      return 'text-blue-700'
  }
}

const getCloseButtonClass = (type) => {
  switch (type) {
    case 'success':
      return 'text-green-400 hover:text-green-600 focus:ring-green-500'
    case 'error':
      return 'text-red-400 hover:text-red-600 focus:ring-red-500'
    case 'warning':
      return 'text-yellow-400 hover:text-yellow-600 focus:ring-yellow-500'
    default:
      return 'text-blue-400 hover:text-blue-600 focus:ring-blue-500'
  }
}

// Métodos para adicionar e remover notificações
const addNotification = (notification) => {
  const id = Date.now() + Math.random()
  const newNotification = {
    id,
    type: notification.type || 'info',
    title: notification.title,
    message: notification.message,
    duration: notification.duration || 5000
  }
  
  notifications.value.push(newNotification)
  
  // Auto-remover após duração especificada
  if (newNotification.duration > 0) {
    setTimeout(() => {
      removeNotification(id)
    }, newNotification.duration)
  }
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
  }
}

const clearAll = () => {
  notifications.value = []
}

// Métodos de conveniência
const showSuccess = (message, title = 'Sucesso!') => {
  addNotification({ type: 'success', title, message })
}

const showError = (message, title = 'Erro!') => {
  addNotification({ type: 'error', title, message, duration: 7000 })
}

const showWarning = (message, title = 'Atenção!') => {
  addNotification({ type: 'warning', title, message })
}

const showInfo = (message, title = null) => {
  addNotification({ type: 'info', title, message })
}

// Expor métodos para uso global
window.NotificationSystem = {
  show: addNotification,
  success: showSuccess,
  error: showError,
  warning: showWarning,
  info: showInfo,
  clear: clearAll
}

// Event listener para notificações globais
onMounted(() => {
  window.addEventListener('notification', (event) => {
    addNotification(event.detail)
  })
})

onUnmounted(() => {
  window.removeEventListener('notification', () => {})
})
</script>

<style scoped>
.notification-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.notification-leave-active {
  transition: all 0.3s ease-in;
}

.notification-enter-from {
  transform: translateX(100%) scale(0.9);
  opacity: 0;
}

.notification-leave-to {
  transform: translateX(100%) scale(0.9);
  opacity: 0;
}

.notification-move {
  transition: transform 0.3s ease;
}
</style>