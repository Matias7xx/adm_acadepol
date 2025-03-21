<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import SiteNavbar from './Components/SiteNavbar.vue';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';
import ExibirDocumentos from './Components/ExibirDocumentos.vue';

const DOCUMENT_URL = '/storage/documentos/RegimentoInternoAcadepol.pdf';
const DOCUMENT_TITLE = 'Regimento Interno da ACADEPOL';
const DOCUMENT_FILENAME = 'Regimento_Interno_ACADEPOL';
const DOCUMENT_DESCRIPTION = 'Este documento estabelece as normas e diretrizes que regulamentam o funcionamento interno da Academia de Polícia Civil.';

// Informações adicionais para o componente de visualização
const additionalInfo = [
  'Este documento é de uso interno e regula as condutas na Academia de Polícia.',
  'Todos os alunos e servidores devem seguir as normas estabelecidas neste regimento.',
  'Em caso de dúvidas, consulte a Diretoria da ACADEPOL.'
];

// Estado para controle de seções expandidas
const expandedSection = ref(null);

// Método para alternar a seção expandida
const toggleSection = (sectionId) => {
  expandedSection.value = expandedSection.value === sectionId ? null : sectionId;
};
</script>

<template>
  <div class="bg-gray-100 min-h-screen">
    <Head>
      <title>Regimento Interno - ACADEPOL</title>
      <meta name="description" :content="DOCUMENT_DESCRIPTION" />
      <meta name="keywords" content="regimento interno, acadepol, academia de polícia, normas, diretrizes" />
    </Head>
    
    <!-- Componentes de layout -->
    <SiteNavbar />
    <Header />
    
    <!-- Conteúdo principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Breadcrumbs para navegação -->
      <nav class="flex mb-4 text-sm" aria-label="Navegação">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
          <li class="inline-flex items-center">
            <a href="/" class="text-gray-700 hover:text-blue-600">
              Início
            </a>
          </li>
          <li>
            <div class="flex items-center">
              <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span class="ml-1 text-gray-500 md:ml-2">Documentos</span>
            </div>
          </li>
          <li>
            <div class="flex items-center">
              <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span class="ml-1 text-gray-500 md:ml-2">Regimento Interno</span>
            </div>
          </li>
        </ol>
      </nav>
         
        <!-- Coluna principal de conteúdo -->
        <div class="lg:col-span-2" id="documento">
          <ExibirDocumentos 
            :documentUrl="DOCUMENT_URL"
            :documentTitle="DOCUMENT_TITLE"
            documentType="pdf"
            :fileName="DOCUMENT_FILENAME"
            aspectRatio="3/4"
            :description="DOCUMENT_DESCRIPTION"
            :additionalInfo="additionalInfo"
          />
          
          <!-- Informações complementares -->
          <div class="mt-8 bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Importância do Regimento Interno</h2>
            <p class="text-gray-600 mb-4">
              O Regimento Interno da ACADEPOL é o instrumento normativo que estabelece a estrutura organizacional, 
              as atribuições dos órgãos que compõem a Academia, bem como as normas de funcionamento e procedimentos 
              a serem adotados.
            </p>
            <p class="text-gray-600">
              É fundamental que todos os envolvidos no processo de ensino-aprendizagem, sejam servidores, professores 
              ou alunos, conheçam e sigam este regimento para garantir o bom funcionamento institucional e a qualidade 
              da formação profissional.
            </p>
          </div>
        </div>
    </main>
    
    <Footer />
  </div>
</template>