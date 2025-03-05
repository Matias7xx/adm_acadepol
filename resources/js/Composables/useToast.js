// resources/js/Composables/useToast.js
import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

export function useToast() {
  const toast = (message, type = 'info', duration = 5000) => {
    const id = toastId++;
    
    toasts.value.push({
      id,
      message,
      type,
      duration
    });
    
    setTimeout(() => {
      removeToast(id);
    }, duration);
    
    return id;
  };
  
  const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id);
    if (index !== -1) {
      toasts.value.splice(index, 1);
    }
  };
  
  return {
    toasts,
    toast,
    removeToast
  };
}