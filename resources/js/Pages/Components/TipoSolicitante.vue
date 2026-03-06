<script setup>
import { Link, Head } from '@inertiajs/vue3';
import LayoutGuest from '@/Layouts/Admin/LayoutGuest.vue';
import SectionFullScreen from '@/Components/SectionFullScreen.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseLevel from '@/Components/BaseLevel.vue';
import BaseDivider from '@/Components/BaseDivider.vue';
import imgUrl from '@/src/assets/logo-acadepol.png';
</script>

<template>
  <LayoutGuest>
    <Head title="Reserva de Alojamento - Tipo de Solicitante" />

    <SectionFullScreen v-slot="{ cardClass }" bg="white">
      <CardBox :class="[cardClass, 'px-6 md:px-10 py-8']">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
          <img :src="imgUrl" class="w-28 md:w-36" alt="ACADEPOL" />
        </div>

        <!-- Título -->
        <div class="text-center mb-8">
          <div class="flex items-center justify-center gap-2 mb-2">
            <h1
              class="text-lg md:text-xl font-semibold text-gray-800 tracking-wide"
            >
              Reserva de Alojamento
            </h1>
          </div>
          <p class="text-sm text-gray-500">
            Selecione seu perfil para prosseguir com a solicitação
          </p>
        </div>

        <!-- Opções -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Servidor da PCPB -->
          <Link
            :href="route('alojamento.reserva.form')"
            class="option-card group"
          >
            <div class="option-icon-wrapper">
              <!-- Ícone: crachá/servidor -->
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="option-icon"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c0 1.306.835 2.418 2 2.83"
                />
              </svg>
            </div>
            <div class="option-body">
              <h3 class="option-title">Servidor da PCPB</h3>
              <p class="option-desc">
                Servidor da Polícia Civil da Paraíba com acesso ao sistema.
              </p>
            </div>
            <svg
              class="option-arrow"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                clip-rule="evenodd"
              />
            </svg>
          </Link>

          <!-- Visitante -->
          <Link :href="route('visitante.formulario')" class="option-card group">
            <div class="option-icon-wrapper">
              <!-- Ícone: pessoa/visitante -->
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="option-icon"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
            </div>
            <div class="option-body">
              <h3 class="option-title">Visitante</h3>
              <p class="option-desc">
                Servidor de outro órgão ou visitante externo sem cadastro.
              </p>
            </div>
            <svg
              class="option-arrow"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                clip-rule="evenodd"
              />
            </svg>
          </Link>
        </div>

        <BaseDivider class="my-6" />

        <!-- Voltar -->
        <BaseLevel>
          <div></div>
          <Link :href="route('home')" class="back-link">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-4 h-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
            Voltar à página inicial
          </Link>
        </BaseLevel>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>

<style scoped>
/* Card de opção */
.option-card {
  @apply flex items-center gap-4 p-5 rounded-lg border border-gray-200 bg-white;
  @apply transition-all duration-200 cursor-pointer;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.option-card:hover {
  @apply border-gray-300 bg-gray-50;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Ícone */
.option-icon-wrapper {
  @apply flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center;
  @apply bg-amber-50 transition-colors duration-200;
}

.option-card:hover .option-icon-wrapper {
  background-color: #f5edd6;
}

.option-icon {
  @apply w-6 h-6;
  color: #bea55a;
}

/* Texto */
.option-body {
  @apply flex-1 text-left;
}

.option-title {
  @apply text-sm font-semibold text-gray-800 mb-0.5;
}

.option-desc {
  @apply text-xs text-gray-500 leading-relaxed;
}

/* Seta */
.option-arrow {
  @apply w-4 h-4 text-gray-300 flex-shrink-0 transition-all duration-200;
}

.option-card:hover .option-arrow {
  color: #bea55a;
  transform: translateX(2px);
}

/* Voltar */
.back-link {
  @apply flex items-center gap-1.5 text-sm text-gray-400 transition-colors duration-150;
}

.back-link:hover {
  color: #bea55a;
}

/* Redução de movimento */
@media (prefers-reduced-motion: reduce) {
  .option-card,
  .option-arrow,
  .option-icon-wrapper {
    @apply transition-none;
  }
}
</style>
