<script setup>
import { ref, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  matriculaId: {
    type: Number,
    required: true,
  },
  matriculaInfo: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'confirm']);

const motivo = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');

watch(
  () => props.isOpen,
  value => {
    if (value) {
      // Reset form when modal opens
      motivo.value = '';
      errorMessage.value = '';
      isSubmitting.value = false;
    }
  }
);

function closeModal() {
  emit('close');
}

function confirmarRejeicao() {
  // Validar motivo
  if (!motivo.value || motivo.value.trim().length < 5) {
    errorMessage.value =
      'Por favor, forneça um motivo válido com pelo menos 5 caracteres.';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = '';

  // Emitir evento para o componente pai processar a rejeição
  emit('confirm', {
    matriculaId: props.matriculaId,
    motivo: motivo.value.trim(),
  });
}
</script>

<template>
  <Dialog as="div" class="relative z-50" :open="isOpen" @close="closeModal">
    <div class="fixed inset-0 bg-black/30" aria-hidden="true" />

    <div class="fixed inset-0 flex items-center justify-center p-4">
      <DialogPanel class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <DialogTitle
          as="h3"
          class="text-lg font-medium leading-6 text-gray-900 mb-4"
        >
          Rejeitar Matrícula
        </DialogTitle>

        <div class="mt-2">
          <p class="text-sm text-gray-500 mb-4">
            Você está prestes a rejeitar a matrícula de
            <span class="font-semibold">{{ matriculaInfo?.aluno?.name }}</span>
            no curso
            <span class="font-semibold">{{ matriculaInfo?.curso?.nome }}</span
            >.
          </p>

          <div class="mb-4">
            <label
              for="motivo"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Motivo da Rejeição *
            </label>
            <textarea
              id="motivo"
              v-model="motivo"
              rows="4"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Explique o motivo da rejeição. Esta informação será enviada ao solicitante."
              :disabled="isSubmitting"
            ></textarea>
            <p v-if="errorMessage" class="mt-1 text-sm text-red-600">
              {{ errorMessage }}
            </p>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <button
            type="button"
            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            @click="closeModal"
            :disabled="isSubmitting"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            @click="confirmarRejeicao"
            :disabled="isSubmitting"
          >
            <span v-if="isSubmitting">
              <svg
                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              Processando...
            </span>
            <span v-else>Rejeitar Matrícula</span>
          </button>
        </div>
      </DialogPanel>
    </div>
  </Dialog>
</template>
