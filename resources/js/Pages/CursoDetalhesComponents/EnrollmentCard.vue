<script setup>
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Toast from '../Components/Toast.vue';

const { toast } = useToast();

const props = defineProps({
  status: {
    type: String,
    default: null,
  },
  capacidade: {
    type: Number,
    required: true,
  },
  dataInicio: {
    type: String,
    required: true,
  },
  curso: {
    type: Object,
    required: true,
  },
});

defineEmits(['enroll']);

const handleEnrollment = cursoId => {
  router.visit(route('matricula', cursoId), {
    preserveState: true,
    preserveScroll: true,
    onError: errors => {
      if (errors.unauthenticated) {
        window.sessionStorage.setItem('intended_curso_id', cursoId);
        return;
      }
      if (errors.enrollment) {
        toast.error(errors.enrollment);
      }
    },
    onSuccess: () => {},
  });
};

const formatDate = date => {
  if (!date) return 'Não definido';
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};
</script>

<template>
  <div class="enrollment-card">
    <Toast />

    <!-- Header -->
    <div class="enrollment-header">
      <span class="enrollment-accent"></span>
      <h3 class="enrollment-title">Inscrições</h3>
    </div>

    <!-- Dados -->
    <div class="enrollment-body">
      <div class="enrollment-rows">
        <div class="enrollment-row">
          <span class="row-label">Status</span>
          <span
            class="row-value"
            :class="{
              'status-open': status === 'aberto',
              'status-closed': status === 'fechado' || !status,
              'status-ongoing': status === 'em andamento',
            }"
          >
            {{ status || 'Indisponível' }}
          </span>
        </div>

        <div class="enrollment-row">
          <span class="row-label">Vagas</span>
          <span class="row-value text-gray-800">{{ capacidade }}</span>
        </div>

        <div class="enrollment-row">
          <span class="row-label">Prazo de matrícula</span>
          <span class="row-value text-gray-800"
            >Até {{ formatDate(dataInicio) }}</span
          >
        </div>
      </div>

      <!-- Botão -->
      <div v-if="status === 'aberto' && curso && curso.id" class="mt-5">
        <button @click="handleEnrollment(curso.id)" class="enroll-btn">
          Inscrever-se
        </button>
      </div>
      <div v-else class="mt-5 text-center text-sm text-gray-400">
        Inscrições
        {{ status === 'em andamento' ? 'encerradas' : 'não disponíveis' }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.enrollment-card {
  @apply bg-white rounded-lg overflow-hidden border border-gray-200;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.enrollment-header {
  @apply flex items-center px-5 py-4 border-b border-gray-100;
}

.enrollment-title {
  @apply text-lg font-bold text-gray-800 tracking-wide uppercase;
}

.enrollment-body {
  @apply p-6;
}

.enrollment-rows {
  @apply divide-y divide-gray-50;
}

.enrollment-row {
  @apply flex items-center justify-between py-2.5;
}

.row-label {
  @apply text-sm text-gray-500;
}

.row-value {
  @apply text-sm font-medium;
}

.status-open {
  @apply text-emerald-600;
}

.status-closed {
  @apply text-red-500;
}

.status-ongoing {
  @apply text-amber-600;
}

.enroll-btn {
  @apply w-full py-2.5 px-4 rounded-md text-sm font-semibold text-white;
  @apply transition-colors duration-150;
  background-color: #bea55a;
}

.enroll-btn:hover {
  background-color: #a38e4d;
}

@media (prefers-reduced-motion: reduce) {
  .enroll-btn {
    @apply transition-none;
  }
}
</style>
