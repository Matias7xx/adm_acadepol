<script setup>
import { ref, computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'
import SiteNavbar from './Components/SiteNavbar.vue';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';

const props = defineProps({
  certificados: {
    type: Object,
    required: true
  }
})

const { toast } = useToast()

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR')
}

// Download de certificado
const baixarCertificado = (certificado) => {
  try {
    window.open(route('certificados.download', certificado.id), '_blank')
    toast.success('Download iniciado!')
  } catch (error) {
    console.error('Erro no download:', error)
    toast.error('Erro ao iniciar download')
  }
}

const estatisticas = computed(() => {
  const certificados = props.certificados.data || []
  
  return {
    total: certificados.length,
    regulares: certificados.filter(c => !c.tipo_origem || c.tipo_origem === 'matricula').length,
    sistema: certificados.filter(c => c.tipo_origem === 'curso_sistema').length,
    externos: certificados.filter(c => c.tipo_origem === 'curso_externo').length,
    horasTotais: certificados.reduce((total, c) => total + (parseInt(c.carga_horaria) || 0), 0)
  }
})
</script>

<template>
  <Head title="Meus Certificados"/>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <Header />
    <SiteNavbar />
    
    <div class="bg-gradient-to-r from-black to-gray-900 text-white py-5 shadow-md">
      <div class="container mx-auto flex justify-between items-center px-4">
        <div class="flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#bea55a] mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h1 class="text-2xl font-bold">Meus Certificados</h1>
        </div>
        <Link :href="route('home')" class="flex items-center text-[#bea55a] hover:text-amber-300 transition">
          <span>Início</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      
      <!-- Estatísticas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total de Certificados -->
        <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200 transform hover:scale-105 transition-transform">
          <div class="flex items-center">
            <div class="bg-gray-600 p-3 rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total de Certificados</p>
              <p class="text-2xl font-bold text-gray-900">{{ estatisticas.total }}</p>
            </div>
          </div>
        </div>

        <!-- Carga Horária Total -->
        <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200 transform hover:scale-105 transition-transform">
          <div class="flex items-center">
            <div class="bg-gray-600 p-3 rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Carga Horária Total</p>
              <p class="text-2xl font-bold text-gray-900">{{ estatisticas.horasTotais }}h</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Aviso sobre o Novo Sistema -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-blue-900 mb-2">
              Informação Importante sobre Certificados
            </h3>
            <div class="text-blue-800">
              <p class="mb-3">
                No momento, serão disponibilizados, <strong>majoritariamente</strong>, os certificados emitidos a partir da implementação do novo sistema.
              </p>
              <p class="mb-3">
                Certificados de cursos realizados antes da implementação do sistema atual podem não estar disponíveis para download nesta plataforma.
              </p>
              <p class="text-sm">
                <strong>Precisa de um certificado anterior?</strong> 
                Entre em contato conosco através do 
                <Link :href="route('contato.create')" class="text-blue-700 hover:text-blue-900 underline font-medium">
                  Fale Conosco
                </Link> 
                ou solicite uma 
                <Link :href="route('requerimentos.create')" class="text-blue-700 hover:text-blue-900 underline font-medium">
                  2ª via do certificado
                </Link>.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Card Principal de Certificados -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border border-gray-200">
        <div class="flex items-center justify-between mb-6 pb-3 border-b border-[#bea55a]">
          <div class="flex items-center">
            <div class="bg-amber-100 p-3 rounded-full mr-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#bea55a]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Seus Certificados</h2>
          </div>
          <!-- <div class="text-sm text-gray-500">{{ certificados.total }} certificado(s) encontrado(s)</div> -->
        </div>

        <!-- Lista de Certificados -->
        <div v-if="certificados.data && certificados.data.length > 0" class="space-y-6">
          <div 
            v-for="certificado in certificados.data" 
            :key="certificado.id"
            class="bg-gray-50 p-6 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 hover:border-[#bea55a]"
          >
            <div class="flex items-start justify-between">
              <!-- Informações do Certificado -->
              <div class="flex-1">
                <div class="flex items-center mb-3">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#bea55a] mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                      {{ certificado.nome_curso }}
                    </h3>
                  </div>
                </div>
                
                <!-- Detalhes -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <div class="flex items-center text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 0v1h6V7m-6 0H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-3" />
                    </svg>
                    <div>
                      <span class="text-xs text-gray-500 block">Data de Emissão</span>
                      <span class="font-medium">{{ formatDate(certificado.data_emissao) }}</span>
                    </div>
                  </div>
                  
                  <div class="flex items-center text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                      <span class="text-xs text-gray-500 block">Carga Horária</span>
                      <span class="font-medium">{{ certificado.carga_horaria }}h</span>
                    </div>
                  </div>
                  
                  <div class="flex items-center text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 0v1h6V7m-6 0H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-3" />
                    </svg>
                    <div>
                      <span class="text-xs text-gray-500 block">Data de Conclusão</span>
                      <span class="font-medium">{{ formatDate(certificado.data_conclusao_curso) }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Botão de Download -->
              <div class="ml-6 mt-6 flex-shrink-0">
                <button
                  @click="baixarCertificado(certificado)"
                  class="bg-[#bea55a] text-white py-2 px-6 rounded-md font-medium transition-all duration-300 shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-300 flex items-center"
                  :title="`Baixar certificado ${certificado.numero_certificado}`"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Baixar PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado Vazio -->
        <div v-else class="text-center py-12">
          <div class="bg-gray-100 p-6 rounded-full w-24 h-24 mx-auto mb-4 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            Nenhum certificado encontrado
          </h3>
          <p class="text-gray-500 mb-6">
            Você ainda não possui certificados emitidos. Complete um curso para receber seu certificado.
          </p>
          <Link 
            :href="route('cursos')"
            class="bg-[#bea55a] text-white py-3 px-6 rounded-md font-medium transition-all duration-300 shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-300 inline-flex items-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Ver Cursos Disponíveis
          </Link>
        </div>

        <!-- Paginação -->
        <div v-if="certificados.data && certificados.data.length > 0 && certificados.links" class="mt-8 border-t border-gray-200 pt-6">
          <nav class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <Link
                v-if="certificados.prev_page_url"
                :href="certificados.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Anterior
              </Link>
              <Link
                v-if="certificados.next_page_url"
                :href="certificados.next_page_url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Próximo
              </Link>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Mostrando
                  <span class="font-medium">{{ certificados.from || 0 }}</span>
                  até
                  <span class="font-medium">{{ certificados.to || 0 }}</span>
                  de
                  <span class="font-medium">{{ certificados.total || 0 }}</span>
                  certificados
                </p>
              </div>
              <div v-if="certificados.links && certificados.links.length > 3">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <template v-for="(link, index) in certificados.links" :key="index">
                    <Link
                      v-if="link.url"
                      :href="link.url"
                      :class="[
                        'relative inline-flex items-center px-2 py-2 border text-sm font-medium transition-colors',
                        link.active 
                          ? 'z-10 bg-amber-50 border-amber-500 text-amber-600' 
                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                        index === 0 ? 'rounded-l-md' : '',
                        index === certificados.links.length - 1 ? 'rounded-r-md' : ''
                      ]"
                      v-html="link.label"
                    />
                    <span
                      v-else
                      :class="[
                        'relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500',
                        index === 0 ? 'rounded-l-md' : '',
                        index === certificados.links.length - 1 ? 'rounded-r-md' : ''
                      ]"
                      v-html="link.label"
                    />
                  </template>
                </nav>
              </div>
            </div>
          </nav>
        </div>
      </div>
    </div>
    <Footer />
  </div>
</template>

<style scoped>
/* Animações suaves */
.transform {
  transition: all 0.2s ease-in-out;
}

.hover\:scale-105:hover {
  transform: scale(1.05);
}

/* Hover effects para cards */
.hover\:shadow-md:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Print styles */
@media print {
  .no-print {
    display: none !important;
  }
}
</style>