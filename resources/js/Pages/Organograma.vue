<script setup>
import { Head } from '@inertiajs/vue3';
import SiteNavbar from './Components/SiteNavbar.vue';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';
import ExibirDocumentos from './Components/ExibirDocumentos.vue';

const DOCUMENT_URL = '/storage/documentos/OrganogramaPCPB.pdf';
const DOCUMENT_TITLE = 'Organograma da Polícia Civil';
const DOCUMENT_FILENAME = 'Organograma_Policia_Civil';
const DOCUMENT_DESCRIPTION = 'Estrutura organizacional e hierárquica atualizada da Polícia Civil da Paraíba.';

const additionalInfo = [
  'Este organograma apresenta a estrutura atual da Polícia Civil da Paraíba, conforme a última reestruturação organizacional.',
  'As unidades especializadas estão destacadas conforme suas respectivas competências.',
];
</script>

<template>
  <div class="bg-gray-100 min-h-screen">
    <Head>
      <title>Organograma - Polícia Civil</title>
      <meta name="description" :content="DOCUMENT_DESCRIPTION" />
      <meta name="keywords" content="organograma, polícia civil, estrutura, hierarquia, paraíba" />
    </Head>
    
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
              <span class="ml-1 text-gray-500 md:ml-2">Organograma</span>
            </div>
          </li>
        </ol>
      </nav>
      
      <ExibirDocumentos 
        :documentUrl="DOCUMENT_URL"
        :documentTitle="DOCUMENT_TITLE"
        documentType="pdf"
        :fileName="DOCUMENT_FILENAME"
        aspectRatio="4/3"
        :description="DOCUMENT_DESCRIPTION"
        :additionalInfo="additionalInfo"
      />
      
      <!-- Informações adicionais -->
      <div class="mt-8 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Sobre o Organograma Institucional</h2>
        <p class="text-gray-600 mb-4">
          O organograma institucional é uma representação gráfica da estrutura hierárquica e organizacional da Polícia Civil da Paraíba, 
          demonstrando as relações entre os diferentes departamentos, divisões e unidades que compõem a instituição.
        </p>
        <p class="text-gray-600">
          Esta representação visual ajuda a compreender o fluxo de autoridade, os níveis de responsabilidade e a distribuição 
          de competências dentro da organização policial, servindo como guia para servidores e cidadãos.
        </p>
      </div>
    </main>
    
    <Footer />
  </div>
</template>