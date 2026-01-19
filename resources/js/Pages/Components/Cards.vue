<template>
  <div class="cards-container">
    <!-- Card Capacitação e Formação -->
    <div class="card">
      <!-- Seção Cursos de Capacitação -->
      <div>
        <div class="card-header">
          <h2 class="card-title">CURSOS DE CAPACITAÇÃO</h2>
        </div>
        <div class="card-content">
          <div v-for="item in capacitacao" :key="item.id" class="card-item">
            <!-- Link para Certificados: <a> se não autenticado, <Link> se autenticado -->
            <Link
              v-if="item.titulo !== 'Certificados' || isAuthenticated"
              :href="item.link"
              class="card-link"
            >
              {{ item.titulo }}
            </Link>

            <!-- <a> tag para Certificados quando não autenticado -->
            <a v-else :href="item.link" class="card-link">
              {{ item.titulo }}
            </a>
          </div>
        </div>
      </div>

      <!-- Seção Curso de Formação -->
      <div>
        <div class="card-header card-header-separator">
          <h2 class="card-title">CURSO DE FORMAÇÃO POLICIAL</h2>
        </div>
        <div class="card-content">
          <div v-for="item in formacao" :key="item.id" class="card-item">
            <Link :href="item.link" class="card-link">
              {{ item.titulo }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Links Úteis -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">UTILIDADES</h2>
      </div>
      <div class="card-content">
        <div v-for="item in utilidade" :key="item.id" class="card-item">
          <a
            :href="item.link"
            target="_blank"
            rel="noopener noreferrer"
            class="card-link"
          >
            {{ item.titulo }}
            <span v-if="item.novo" class="badge-novo"> Novo </span>
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

// Verificar se o usuário está autenticado
const isAuthenticated = computed(() => page.props.auth?.user);

const capacitacao = ref([
  { id: 1, titulo: 'Cursos', link: '/cursos' },
  { id: 2, titulo: 'Ensino a Distância', link: '/banco-de-curriculos' },
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
/* Container principal - RESPONSIVO */
.cards-container {
  @apply grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 lg:gap-8;
  @apply max-w-7xl mx-auto px-4 md:px-6 lg:px-8;
}

/* Card */
.card {
  @apply bg-gray-50 rounded-md shadow-lg overflow-hidden border border-gray-50;
}

/* Header do card - RESPONSIVO */
.card-header {
  @apply bg-gray-100 py-3 px-6 md:py-4 md:px-10;
}

.card-header-separator {
  @apply border-t border-gray-100;
}

/* Título - RESPONSIVO */
.card-title {
  @apply text-gray-700 font-bold text-base md:text-lg tracking-wide uppercase whitespace-nowrap;
}

/* Conteúdo */
.card-content {
  @apply divide-y divide-gray-100 px-4 md:px-6 lg:px-8;
}

.card-item {
  @apply hover:bg-gray-50 transition-colors duration-150;
}

/* Links - RESPONSIVO */
.card-link {
  @apply flex items-center py-3 px-4 md:py-4 md:px-6;
  @apply text-gray-700 font-medium text-sm md:text-base;
  @apply hover:text-[#bea55a] transition-colors;
}

/* Badge Novo */
.badge-novo {
  @apply ml-2 inline-flex items-center px-2 py-0.5 rounded;
  @apply text-xs font-medium bg-red-100 text-red-800;
}

/* Mobile extra pequeno (< 375px) */
@media (max-width: 374px) {
  .cards-container {
    @apply px-3 gap-4;
  }

  .card-header {
    @apply py-2.5 px-4;
  }

  .card-content {
    @apply px-3;
  }

  .card-link {
    @apply py-2.5 px-3 text-xs;
  }

  .card-title {
    @apply text-sm;
  }
}

/* Tablet pequeno (768-1023px) */
@media (min-width: 768px) and (max-width: 1023px) {
  .cards-container {
    @apply gap-5;
  }

  .card-link {
    @apply py-3.5 px-5;
  }
}

/* Desktop grande (> 1280px) */
@media (min-width: 1280px) {
  .cards-container {
    @apply gap-10;
  }

  .card-header {
    @apply py-4 px-12;
  }

  .card-content {
    @apply px-10;
  }

  .card-link {
    @apply py-4 px-7;
  }

  .card-title {
    @apply text-xl;
  }
}

/* Hover */
@media (hover: hover) {
  .card-item:hover {
    @apply bg-gray-100;
  }
}

/* Redução de movimento */
@media (prefers-reduced-motion: reduce) {
  .card-item,
  .card-link {
    @apply transition-none;
  }
}
</style>
