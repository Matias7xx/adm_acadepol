<script setup>
import { Head } from '@inertiajs/vue3';
import SiteNavbar from './Components/SiteNavbar.vue';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';
import ExibirDocumentos from './Components/ExibirDocumentos.vue';

const DOCUMENT_URL = '/storage/documentos/ManualDoAluno.pdf';
const DOCUMENT_TITLE = 'Manual do Aluno';
const DOCUMENT_FILENAME = 'Manual_do_Aluno_ACADEPOL';
const DOCUMENT_DESCRIPTION =
  'Este documento contém todas as orientações, normas e procedimentos que os alunos devem seguir durante sua formação na Academia de Polícia Civil.';

// Informações adicionais para o componente de visualização
const additionalInfo = [
  'O manual contém informações essenciais sobre direitos e deveres dos alunos.',
  'Consulte sempre este documento para dúvidas sobre procedimentos acadêmicos e disciplinares.',
  'Mantenha-se atualizado com as normas da instituição para um melhor aproveitamento do curso.',
];
</script>

<template>
  <div class="bg-gray-100 min-h-screen">
    <Head>
      <title>Manual do Aluno - ACADEPOL</title>
      <meta name="description" :content="DOCUMENT_DESCRIPTION" />
      <meta
        name="keywords"
        content="manual do aluno, acadepol, academia de polícia, normas, orientações, formação policial"
      />
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
            <a href="/" class="text-gray-700 hover:text-blue-600"> Início </a>
          </li>
          <li>
            <div class="flex items-center">
              <svg
                class="w-5 h-5 text-gray-400"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="ml-1 text-gray-500 md:ml-2">Documentos</span>
            </div>
          </li>
          <li>
            <div class="flex items-center">
              <svg
                class="w-5 h-5 text-gray-400"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <span class="ml-1 text-gray-500 md:ml-2">Manual do Aluno</span>
            </div>
          </li>
        </ol>
      </nav>

      <!-- Coluna principal de conteúdo -->
      <div class="lg:col-span-2" id="documento">
        <ExibirDocumentos
          :documentUrl="DOCUMENT_URL"
          :documentTitle="DOCUMENT_TITLE"
          :fileName="DOCUMENT_FILENAME"
          aspectRatio="3/4"
          :description="DOCUMENT_DESCRIPTION"
          :additionalInfo="additionalInfo"
        />

        <!-- Informações complementares -->
        <div class="mt-8 bg-white shadow-md rounded-lg p-6">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">
            Importância do Manual do Aluno
          </h2>
          <p class="text-gray-600 mb-4">
            O Manual do Aluno é um guia completo que orienta todos os aspectos
            da vida acadêmica, desde a matrícula até a conclusão do curso. Ele
            contém informações sobre avaliações, frequência, conduta, uniformes,
            procedimentos administrativos e demais orientações essenciais.
          </p>
          <p class="text-gray-600">
            Este documento é uma ferramenta fundamental para o sucesso na
            formação policial, pois estabelece as bases para uma experiência de
            aprendizado disciplinada e produtiva. Todos os alunos devem
            familiarizar-se com seu conteúdo desde o primeiro dia de aula.
          </p>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<style scoped></style>
