<script setup>
import iconAlojamento from '@/src/assets/icon-alojamento.png';
</script>

<template>
  <div class="space-y-3 sm:space-y-4">
    <h3
      class="text-xl sm:text-2xl font-semibold text-gray-700 mb-3 sm:mb-4 pl-2 border-l-4 border-[#bea55a]"
    >
      Serviços
    </h3>

    <!-- Card Requerimentos -->
    <div
      class="bg-white hover:bg-gray-50 shadow rounded-lg transition-all duration-300 overflow-hidden group"
    >
      <div class="p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
        <div
          class="w-10 h-10 sm:w-12 sm:h-12 flex-shrink-0 rounded-full bg-gray-300 flex items-center justify-center"
        >
          <svg
            class="h-5 w-5 sm:h-6 sm:w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
        </div>

        <div class="flex-1 min-w-0">
          <h4
            class="text-sm sm:text-base font-bold text-gray-800 group-hover:text-gray-900"
          >
            REQUERIMENTOS
          </h4>
          <p class="text-xs sm:text-sm text-gray-600 mt-1">
            Solicite 2ª via de certificados e declaração de participação em
            cursos.
          </p>
          <a
            href="/requerimentos/novo"
            class="text-xs sm:text-sm font-medium text-[#bea55a] hover:text-[#9e8743] inline-block mt-1.5 sm:mt-2 group-hover:underline"
          >
            Fazer requerimento →
          </a>
        </div>
      </div>
    </div>

    <!-- Card Reserva de Alojamento -->
    <div
      class="bg-white hover:bg-gray-50 shadow rounded-lg transition-all duration-300 overflow-hidden group"
    >
      <div class="p-3 sm:p-4 flex items-center gap-3 sm:gap-4">
        <div
          class="w-10 h-10 sm:w-12 sm:h-12 flex-shrink-0 rounded-full bg-gray-300 flex items-center justify-center"
        >
          <img
            :src="iconAlojamento"
            class="h-5 w-auto sm:h-6"
            alt="Ícone de alojamento"
          />
        </div>

        <div class="flex-1 min-w-0">
          <h4
            class="text-sm sm:text-base font-bold text-gray-800 group-hover:text-gray-900"
          >
            RESERVA DE ALOJAMENTO
          </h4>
          <p class="text-xs sm:text-sm text-gray-600 mt-1">
            Faça a pré-reserva de Alojamento.
          </p>
          <a
            href="/alojamento/escolha-tipo"
            class="text-xs sm:text-sm font-medium text-[#bea55a] hover:text-[#9e8743] inline-block mt-1.5 sm:mt-2 group-hover:underline"
          >
            Reservar agora →
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.group {
  transition: all 0.2s ease-in-out;
}

.group:hover {
  transform: translateY(-2px);
}

/* Responsividade para telas muito pequenas */
@media (max-width: 374px) {
  .space-y-3 {
    gap: 0.5rem;
  }
}

/* hover no desktop */
@media (min-width: 1024px) {
  .group:hover {
    box-shadow:
      0 10px 15px -3px rgb(0 0 0 / 0.1),
      0 4px 6px -4px rgb(0 0 0 / 0.1);
  }
}
</style>
