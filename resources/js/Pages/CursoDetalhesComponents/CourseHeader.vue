<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  imagem: { type: String, required: false, default: null },
  nome: { type: String, required: true },
  modalidade: { type: String, required: true },
  status: { type: String, default: null },
});

const imagemFalhou = ref(false);
const temImagem = computed(
  () => props.imagem && !imagemFalhou.value && props.imagem.trim() !== ''
);
const handleImageError = () => {
  imagemFalhou.value = true;
};

const statusConfig = {
  aberto: { label: 'Inscrições Abertas', class: 'badge-open' },
  'em andamento': { label: 'Em Andamento', class: 'badge-ongoing' },
  concluído: { label: 'Concluído', class: 'badge-done' },
  cancelado: { label: 'Cancelado', class: 'badge-cancelled' },
};

const statusInfo = computed(
  () =>
    statusConfig[props.status?.toLowerCase()] ?? {
      label: 'Inativo',
      class: 'badge-cancelled',
    }
);
</script>

<template>
  <div class="course-header">
    <!-- Imagem de fundo -->
    <img
      v-if="temImagem"
      :src="imagem"
      :alt="nome"
      class="course-bg-img"
      style="filter: brightness(0.65)"
      @error="handleImageError"
    />

    <!-- Fallback sem imagem -->
    <div v-else class="course-bg-fallback">
      <svg
        class="fallback-icon"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1"
          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
        />
      </svg>
    </div>

    <!-- Overlay -->
    <div class="course-overlay"></div>

    <!-- Conteúdo -->
    <div class="course-content">
      <div class="course-badges">
        <span class="badge-modalidade">{{ modalidade }}</span>
        <span :class="['badge-status', statusInfo.class]">{{
          statusInfo.label
        }}</span>
      </div>
      <h1 class="course-title">{{ nome }}</h1>
    </div>
  </div>
</template>

<style scoped>
.course-header {
  @apply relative h-72 w-full overflow-hidden;
}

.course-bg-img {
  @apply absolute inset-0 w-full h-full object-cover object-center;
}

.course-bg-fallback {
  @apply absolute inset-0 w-full h-full;
  background: linear-gradient(135deg, #1f2937 0%, #374151 50%, #1f2937 100%);
}

.fallback-icon {
  @apply absolute w-20 h-20 text-white opacity-10;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.course-overlay {
  @apply absolute inset-0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.8) 0%,
    rgba(0, 0, 0, 0.2) 60%,
    transparent 100%
  );
}

.course-content {
  @apply relative z-10 container mx-auto px-4 sm:px-6 lg:px-8;
  @apply flex flex-col justify-end h-full pb-7;
}

.course-badges {
  @apply flex items-center flex-wrap gap-2 mb-3;
}

.badge-modalidade {
  @apply text-xs font-semibold px-2.5 py-1 rounded text-white;
  background-color: #bea55a;
}

.badge-status {
  @apply text-xs font-semibold px-2.5 py-1 rounded text-white;
}

.badge-open {
  @apply bg-emerald-600;
}
.badge-ongoing {
  @apply bg-blue-600;
}
.badge-done {
  @apply bg-gray-500;
}
.badge-cancelled {
  @apply bg-red-600;
}

.course-title {
  @apply text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}
</style>
