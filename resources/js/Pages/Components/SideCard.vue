<script setup>
import iconAlojamento from '@/src/assets/icon-alojamento.png';
</script>

<template>
  <div class="space-y-3">
    <!-- Título da seção -->
    <div class="flex items-center gap-2.5 mb-4">
      <span class="section-accent"></span>
      <h3 class="section-title">Serviços</h3>
    </div>

    <!-- Card Requerimentos -->
    <a href="/requerimentos/novo" class="service-card group">
      <div class="service-icon-wrap">
        <svg
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.75"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
      </div>
      <div class="flex-1 min-w-0">
        <h4 class="service-title">Requerimentos</h4>
        <p class="service-desc">
          Solicite 2ª via de certificados e declaração de participação em
          cursos.
        </p>
        <span class="service-link">Fazer requerimento →</span>
      </div>
    </a>

    <!-- Card Reserva de Alojamento -->
    <a href="/alojamento/escolha-tipo" class="service-card group">
      <div class="service-icon-wrap">
        <img
          :src="iconAlojamento"
          class="h-5 w-auto"
          alt=""
          aria-hidden="true"
        />
      </div>
      <div class="flex-1 min-w-0">
        <h4 class="service-title">Reserva de Alojamento</h4>
        <p class="service-desc">
          Faça a pré-reserva de alojamento da ACADEPOL.
        </p>
        <span class="service-link">Reservar agora →</span>
      </div>
    </a>
  </div>
</template>

<style scoped>
.section-accent {
  @apply block w-1 h-5 rounded-full flex-shrink-0;
  background-color: #bea55a;
}
.section-title {
  @apply text-sm font-semibold text-gray-700 uppercase tracking-wide;
}

.service-card {
  @apply flex items-start gap-3 bg-white border border-gray-200 rounded-lg p-4;
  @apply transition-all duration-150 cursor-pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  text-decoration: none;
}
.service-card:hover {
  @apply border-gray-300;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
}

.service-icon-wrap {
  @apply w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0;
  background-color: #faf5e8;
  color: #bea55a;
}

.service-title {
  @apply text-sm font-semibold text-gray-800 mb-0.5;
}
.service-desc {
  @apply text-xs text-gray-500 leading-relaxed mb-1.5;
}
.service-link {
  @apply text-xs font-medium transition-colors duration-150;
  color: #bea55a;
}
.group:hover .service-link {
  color: #a38e4d;
}

@media (prefers-reduced-motion: reduce) {
  .service-card {
    @apply transition-none;
  }
}
</style>
