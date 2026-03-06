<template>
  <div class="cards-container">
    <!-- Card Capacitação e Formação -->
    <div class="card">
      <!-- Seção Cursos de Capacitação -->
      <div>
        <div class="card-header">
          <span class="card-header-accent"></span>
          <h2 class="card-title">Cursos de Capacitação</h2>
        </div>
        <div class="card-content">
          <div v-for="item in capacitacao" :key="item.id" class="card-item">
            <Link
              v-if="item.titulo !== 'Certificados' || isAuthenticated"
              :href="item.link"
              class="card-link"
            >
              <span>{{ item.titulo }}</span>
              <svg
                class="card-arrow"
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
            <a v-else :href="item.link" class="card-link">
              <span>{{ item.titulo }}</span>
              <svg
                class="card-arrow"
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
            </a>
          </div>
        </div>
      </div>

      <!-- Seção Curso de Formação -->
      <div>
        <div class="card-header card-header-separator">
          <span class="card-header-accent"></span>
          <h2 class="card-title">Curso de Formação Policial</h2>
        </div>
        <div class="card-content">
          <div v-for="item in formacao" :key="item.id" class="card-item">
            <Link :href="item.link" class="card-link">
              <span>{{ item.titulo }}</span>
              <svg
                class="card-arrow"
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
        </div>
      </div>
    </div>

    <!-- Card Utilidades -->
    <div class="card">
      <div class="card-header">
        <span class="card-header-accent"></span>
        <h2 class="card-title">Utilidades</h2>
      </div>
      <div class="card-content">
        <div v-for="item in utilidade" :key="item.id" class="card-item">
          <a
            :href="item.link"
            target="_blank"
            rel="noopener noreferrer"
            class="card-link"
          >
            <span>{{ item.titulo }}</span>
            <div class="card-link-right">
              <span v-if="item.novo" class="badge-novo">Novo</span>
              <svg
                class="card-arrow"
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
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const isAuthenticated = computed(() => page.props.auth?.user);

const capacitacao = ref([
  { id: 1, titulo: 'Cursos', link: '/cursos' },
  {
    id: 2,
    titulo: 'Ensino a Distância',
    link: 'https://ead.acadepol.apps.pc.pb.gov.br/',
  },
  {
    id: 3,
    titulo: 'Certificados',
    link: '/certificados/meus',
    requireAuth: true,
  },
  { id: 4, titulo: 'Dados Estatísticos e Metas', link: '/dados-estatisticos' },
]);

const formacao = ref([
  { id: 1, titulo: 'Concursos', link: '/concursos' },
  { id: 2, titulo: 'Manual do Aluno', link: '/manual-aluno' },
]);

const utilidade = ref([
  {
    id: 1,
    titulo: 'Boletim Interno',
    link: 'https://policiacivil.pb.gov.br/boletim-interno-1',
  },
  {
    id: 2,
    titulo: 'Delegacia Online',
    link: 'https://delegaciaonline.pb.gov.br/',
  },
  {
    id: 3,
    titulo: 'Disque Denúncia',
    link: 'https://policiacivil.pb.gov.br/servicos/disque-denuncia-197',
  },
  {
    id: 4,
    titulo: 'Portal do Servidor',
    link: 'https://portaldoservidor.pb.gov.br/',
  },
  {
    id: 5,
    titulo: 'Certidão de Antecedentes Criminais',
    link: 'https://www.policiacivil.pb.gov.br/servicos/certidao-de-antecedentes-criminais',
  },
  {
    id: 6,
    titulo: 'Emissão de RG',
    link: 'https://www.policiacivil.pb.gov.br/servicos/emissao-de-rg',
  },
  { id: 7, titulo: 'Fale Conosco', link: '/fale-conosco', novo: false },
]);
</script>

<style scoped>
/* Container */
.cards-container {
  @apply grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 lg:gap-8;
  @apply max-w-7xl mx-auto px-4 md:px-6 lg:px-8;
}

/* Card */
.card {
  @apply bg-white rounded-lg overflow-hidden;
  @apply border border-gray-200;
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.06),
    0 1px 2px rgba(0, 0, 0, 0.04);
}

/* Header */
.card-header {
  @apply flex items-center gap-3 py-4 px-6 bg-white;
  @apply border-b border-gray-100;
}

.card-header-separator {
  @apply border-t border-gray-100;
}

.card-header-accent {
  @apply block w-1 h-5 rounded-full flex-shrink-0;
  background-color: #bea55a;
}

/* Título */
.card-title {
  @apply text-gray-800 font-semibold text-sm tracking-wide uppercase;
}

/* Conteúdo */
.card-content {
  @apply divide-y divide-gray-50;
}

/* Item */
.card-item {
  @apply transition-colors duration-150;
}

/* Link */
.card-link {
  @apply flex items-center justify-between;
  @apply py-3.5 px-6;
  @apply text-gray-600 text-sm font-medium;
  @apply transition-all duration-150;
}

.card-link:hover {
  @apply bg-gray-50 text-gray-900;
}

.card-link:hover .card-arrow {
  @apply text-[#bea55a] translate-x-0.5;
}

/* Lado direito do link */
.card-link-right {
  @apply flex items-center gap-2;
}

/* Seta */
.card-arrow {
  @apply w-4 h-4 text-gray-300 flex-shrink-0;
  @apply transition-all duration-150;
}

/* Badge Novo */
.badge-novo {
  @apply inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold;
  @apply bg-amber-50 text-amber-700 border border-amber-200;
}

/* Mobile extra pequeno */
@media (max-width: 374px) {
  .cards-container {
    @apply px-3 gap-4;
  }

  .card-header {
    @apply py-3 px-4;
  }

  .card-link {
    @apply py-3 px-4 text-xs;
  }

  .card-title {
    @apply text-xs;
  }
}

/* Desktop grande */
@media (min-width: 1280px) {
  .cards-container {
    @apply gap-10;
  }
}

/* Redução de movimento */
@media (prefers-reduced-motion: reduce) {
  .card-item,
  .card-link,
  .card-arrow {
    @apply transition-none;
  }
}
</style>
