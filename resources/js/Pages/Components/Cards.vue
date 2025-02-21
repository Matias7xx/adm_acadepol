<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-7xl mx-auto px-4">
    <!-- Card Capacitação -->
    <div class="bg-white border-t-4 border-gray-200 rounded-lg shadow-lg">
      <!-- Seção Cursos de Capacitação -->
      <CardHeader title="CURSOS DE CAPACITAÇÃO" />
      <div class="p-6 space-y-4">
        <div v-for="item in capacitacao" :key="item.id" 
             class="flex items-center py-3 group border-b last:border-b-0">
          <Link :href="item.link" 
             class="flex items-center text-gray-800 font-semibold group-hover:text-[#bea55a] text-base transition-colors duration-200">
            <span class="mr-2">
              <svg class="w-5 h-5 text-[#bea55a]" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </span> 
            {{ item.titulo }}
          </Link>
        </div>
      </div>

      <!-- Seção Curso de Formação -->
      <CardHeader title="CURSO DE FORMAÇÃO POLICIAL" class="border-t" />
      <div class="p-6 space-y-4">
        <div v-for="item in formacao" :key="item.id" 
             class="flex items-center py-3 group border-b last:border-b-0">
          <Link :href="item.link" 
             class="flex items-center text-gray-800 font-semibold group-hover:text-[#bea55a] text-base transition-colors duration-200">
            <span class="mr-2">
              <svg class="w-5 h-5 text-[#bea55a]" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </span> 
            {{ item.titulo }}
          </Link>
        </div>
      </div>
    </div>

    <!-- Links Úteis -->
    <div class="bg-white border-t-4 border-gray-200 rounded-lg shadow-lg">
      <CardHeader title="UTILIDADES" />
      <div class="p-6 space-y-4">
        <div v-for="item in utilidade" :key="item.id" 
             class="flex items-center py-3 group border-b last:border-b-0">
          <a :href="item.link" 
             target="_blank" 
             rel="noopener noreferrer"
             class="flex items-center text-gray-800 font-semibold group-hover:text-[#bea55a] text-base transition-colors duration-200">
            <span class="mr-2">
              <svg class="w-5 h-5 text-[#bea55a]" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </span> 
            {{ item.titulo }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import CardHeader from './CardHeader.vue'

const capacitacao = ref([
  { id: 1, titulo: 'Cursos', link: '' },
  { id: 2, titulo: 'Certificados', link: '' },
  { id: 3, titulo: 'Banco de Currículos', link: '' },
  // ... adicione mais itens conforme necessário
])

const formacao = ref([
  { id: 1, titulo: 'Concursos', link: '' },
  { id: 2, titulo: 'Manual do Aluno', link: '' },
  // ... adicione mais itens conforme necessário
])

const utilidade = ref([
  { id: 1, titulo: 'Boletim Interno', link: '' },
  { id: 2, titulo: 'Delegacia Online', link: '' },
  { id: 3, titulo: 'Disque Denúncia', link: '' },
  { id: 4, titulo: 'Portal do Servidor', link: '' },
  { id: 5, titulo: 'Certidão de Antecedentes Criminais', link: '' },
  { id: 6, titulo: 'Emissão de RG', link: '' },
  { id: 7, titulo: 'Fale Conosco', link: '' },
  // ... adicione mais itens conforme necessário
])
</script>