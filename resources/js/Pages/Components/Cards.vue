<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-7xl mx-auto px-4">
    <!-- Card Capacitação e Formação -->
    <div
      class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100"
    >
      <!-- Seção Cursos de Capacitação -->
      <div>
        <div class="bg-gray-50 py-4 px-10">
          <h2 class="text-gray-800 font-bold text-lg tracking-wide">
            CURSOS DE CAPACITAÇÃO
          </h2>
        </div>
        <div class="divide-y divide-gray-100 px-8">
          <div
            v-for="item in capacitacao"
            :key="item.id"
            class="hover:bg-gray-50 transition-colors duration-150"
          >
            <!-- Link para Certificados: <a> se não autenticado, <Link> se autenticado -->
            <Link
              v-if="item.titulo !== 'Certificados' || isAuthenticated"
              :href="item.link"
              class="flex items-center py-4 px-6 text-gray-700 font-medium hover:text-[#bea55a] transition-colors"
            >
              {{ item.titulo }}
            </Link>

            <!-- <a> tag para Certificados quando não autenticado -->
            <a
              v-else
              :href="item.link"
              class="flex items-center py-4 px-6 text-gray-700 font-medium hover:text-[#bea55a] transition-colors"
            >
              {{ item.titulo }}
            </a>
          </div>
        </div>
      </div>

      <!-- Seção Curso de Formação -->
      <div>
        <div class="bg-gray-50 py-4 px-10 border-t border-gray-100">
          <h2 class="text-gray-800 font-bold text-lg tracking-wide">
            CURSO DE FORMAÇÃO POLICIAL
          </h2>
        </div>
        <div class="divide-y divide-gray-100 px-8">
          <div
            v-for="item in formacao"
            :key="item.id"
            class="hover:bg-gray-50 transition-colors duration-150"
          >
            <Link
              :href="item.link"
              class="flex items-center py-4 px-6 text-gray-700 font-medium hover:text-[#bea55a] transition-colors"
            >
              {{ item.titulo }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Links Úteis -->
    <div
      class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100"
    >
      <div class="bg-gray-50 py-4 px-10">
        <h2 class="text-gray-800 font-bold text-lg tracking-wide">
          UTILIDADES
        </h2>
      </div>
      <div class="divide-y divide-gray-100 px-8">
        <div
          v-for="item in utilidade"
          :key="item.id"
          class="hover:bg-gray-50 transition-colors duration-150"
        >
          <a
            :href="item.link"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center py-4 px-6 text-gray-700 font-medium hover:text-[#bea55a] transition-colors"
          >
            {{ item.titulo }}
            <span
              v-if="item.novo"
              class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
            >
              Novo
            </span>
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
  {
    id: 2,
    titulo: 'Certificados',
    link: '/certificados/meus',
    requireAuth: true,
  },
  /* { id: 3, titulo: 'Banco de Currículos', link: '/banco-de-curriculos' }, */
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
