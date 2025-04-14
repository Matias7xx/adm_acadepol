<script setup>
import { computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import Header from '@/Pages/Components/Header.vue';
import SiteNavbar from '@/Pages/Components/SiteNavbar.vue';
import Footer from '@/Pages/Components/Footer.vue';

// Props
const props = defineProps({
  user: Object,
  mensagem: String,
  detalhes: Object,
  tipo: {
    type: String,
    default: 'alojamento',
    validator: (value) => ['alojamento', 'matricula', 'contato', 'requerimento'].includes(value)
  }
});

// Configurações específicas por tipo
const tipoConfig = computed(() => {
  const configs = {
    matricula: {
      titulo: 'Confirmação de Inscrição em Curso',
      icone: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
      corIcone: 'text-blue-600',
      bgIcone: 'bg-blue-100',
      mensagemPadrao: 'Sua inscrição foi recebida e será analisada pela equipe responsável.',
      voltarRota: route('cursos'),
      voltarTexto: 'Voltar aos Cursos',
      mensagemNotificacao: 'Você receberá uma notificação por e-mail quando sua inscrição for analisada.'
    },
    contato: {
      titulo: 'Confirmação de Mensagem Enviada',
      icone: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
      corIcone: 'text-green-600',
      bgIcone: 'bg-green-100',
      mensagemPadrao: 'Sua mensagem foi recebida e será respondida em breve.',
      voltarRota: route('home'),
      voltarTexto: 'Voltar ao Início',
      mensagemNotificacao: 'Você receberá uma resposta por e-mail assim que sua mensagem for analisada.'
    },
    alojamento: {
      titulo: 'Confirmação de Pré-Reserva de Alojamento',
      icone: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3',
      corIcone: 'text-amber-600',
      bgIcone: 'bg-amber-100',
      mensagemPadrao: 'Sua solicitação de pré-reserva foi recebida e será analisada pela administração.',
      voltarRota: route('home'),
      voltarTexto: 'Voltar ao Início',
      mensagemNotificacao: 'Você receberá um e-mail com a confirmação ou rejeição da sua solicitação de alojamento.'
    },
    requerimento: {
      titulo: 'Confirmação de Requerimento',
      icone: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
      corIcone: 'text-indigo-600',
      bgIcone: 'bg-indigo-100',
      mensagemPadrao: 'Seu requerimento foi enviado com sucesso e será analisado pela equipe responsável.',
      voltarRota: route('home'),
      voltarTexto: 'Voltar ao Início',
      mensagemNotificacao: 'Você receberá uma notificação por e-mail quando seu requerimento for analisado.'
    }
  };
  
  return configs[props.tipo] || configs.alojamento;
});

// Valores computados
const titulo = computed(() => tipoConfig.value.titulo);
const voltarRota = computed(() => tipoConfig.value.voltarRota);
const voltarTexto = computed(() => tipoConfig.value.voltarTexto);
const mensagemExibida = computed(() => props.mensagem || tipoConfig.value.mensagemPadrao);
const mensagemNotificacao = computed(() => tipoConfig.value.mensagemNotificacao);

// Formatação de data se necessário
const formatarData = (dataString) => {
  if (!dataString) return '';
  // Verifica se já está no formato desejado
  if (dataString.includes('/')) return dataString;
  
  try {
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR');
  } catch (e) {
    return dataString;
  }
};

// Formatação para o tipo de requerimento
const formatTipoRequerimento = (tipo) => {
  if (!tipo) return '';
  
  const tipos = {
    'segunda_via_certificado': '2ª Via de Certificado',
    'declaracao_participacao': 'Declaração de Participação em Curso',
    'outros': 'Outros'
  };
  
  return tipos[tipo] || tipo;
};

</script>

<template>
  <Head :title="titulo"/>
  <div class="min-h-screen bg-gray-100">
    <Header />
    <SiteNavbar />
    
    <!-- Cabeçalho -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-700 text-white py-6">
      <div class="container mx-auto flex justify-between items-center px-4">
        <h1 class="text-2xl font-bold">{{ titulo }}</h1>
        <Link :href="voltarRota" class="text-white hover:text-gray-200 transition flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          {{ voltarTexto }}
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-10 px-4">
      <div class="bg-white rounded-lg shadow-lg p-8 mb-6 max-w-3xl mx-auto border border-gray-200">
        <div class="text-center mb-10">
          <div class="mb-6 flex justify-center">
            <div :class="[tipoConfig.bgIcone, 'rounded-full p-5 shadow-sm transition-transform transform hover:scale-105']">
              <svg :class="[tipoConfig.corIcone, 'h-16 w-16']" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tipoConfig.icone" />
              </svg>
            </div>
          </div>
          <h2 class="text-3xl font-bold text-gray-800 mb-3">Solicitação Enviada!</h2>
          <p class="text-gray-600 max-w-xl mx-auto text-lg">{{ mensagemExibida }}</p>
        </div>
        
        <div v-if="detalhes" class="bg-gray-50 rounded-lg border border-gray-200 p-6 mb-8 max-w-2xl mx-auto">
          <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h3 class="text-xl font-semibold text-gray-700">Detalhes da Solicitação</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h4 class="text-sm uppercase tracking-wider text-gray-500 mb-2">Informações Pessoais</h4>
              <div class="space-y-2">
                <p class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span>
                    <span class="font-medium text-gray-700">Nome:</span>
                    <span class="text-gray-800 ml-1">{{ detalhes.nome }}</span>
                  </span>
                </p>
                
                <p v-if="detalhes.matricula" class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                  </svg>
                  <span>
                    <span class="font-medium text-gray-700">Matrícula:</span>
                    <span class="text-gray-800 ml-1">{{ detalhes.matricula }}</span>
                  </span>
                </p>
                
                <p v-if="detalhes.email" class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span>
                    <span class="font-medium text-gray-700">Email:</span>
                    <span class="text-gray-800 ml-1">{{ detalhes.email }}</span>
                  </span>
                </p>
                
                <p v-if="detalhes.telefone" class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <span>
                    <span class="font-medium text-gray-700">Telefone:</span>
                    <span class="text-gray-800 ml-1">{{ detalhes.telefone }}</span>
                  </span>
                </p>
              </div>
            </div>
            
            <div>
              <h4 class="text-sm uppercase tracking-wider text-gray-500 mb-2">Detalhes da {{ tipo === 'contato' ? 'Mensagem' : 'Solicitação' }}</h4>
              <div class="space-y-2">
                <template v-if="tipo === 'matricula'">
                  <p class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>
                      <span class="font-medium text-gray-700">Curso:</span>
                      <span class="text-gray-800 ml-1">{{ detalhes.curso }}</span>
                    </span>
                  </p>
                </template>

                <template v-if="tipo === 'contato'">
                  <p class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <span>
                      <span class="font-medium text-gray-700">Assunto:</span>
                      <span class="text-gray-800 ml-1">{{ detalhes.assunto }}</span>
                    </span>
                  </p>
                </template>
                
                <template v-if="tipo === 'requerimento'">
                  <p class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>
                      <span class="font-medium text-gray-700">Tipo:</span>
                      <span class="text-gray-800 ml-1">{{ formatTipoRequerimento(detalhes.tipo) }}</span>
                    </span>
                  </p>
                  <p v-if="detalhes.assunto" class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <span>
                      <span class="font-medium text-gray-700">Assunto:</span>
                      <span class="text-gray-800 ml-1">{{ detalhes.assunto }}</span>
                    </span>
                  </p>
                </template>
                
                <p v-if="detalhes.data_inicial || detalhes.data_inicio" class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span>
                    <span class="font-medium text-gray-700">{{ tipo === 'matricula' ? 'Período do Curso:' : 'Período:' }}</span>
                    <span class="text-gray-800 ml-1">
                      {{ formatarData(detalhes.data_inicial || detalhes.data_inicio) }} 
                      <template v-if="detalhes.data_final || detalhes.data_fim">
                        a {{ formatarData(detalhes.data_final || detalhes.data_fim) }}
                      </template>
                    </span>
                  </span>
                </p>
                
                <p class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>
                    <span class="font-medium text-gray-700">Data da Solicitação:</span>
                    <span class="text-gray-800 ml-1">{{ detalhes.created_at }}</span>
                  </span>
                </p>
                
                <p class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>
                    <span class="font-medium text-gray-700">Situação:</span>
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium ml-1">
                      Aguardando Análise
                    </span>
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="text-center space-y-6">
          <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-800 text-left">
                  {{ mensagemNotificacao }}
                </p>
              </div>
            </div>
          </div>
          
          <div class="flex flex-col sm:flex-row justify-center gap-4 mt-8">
            <Link
              :href="voltarRota"
              class="bg-gradient-to-r from-gray-800 to-gray-700 hover:from-gray-700 hover:to-gray-600 text-white py-3 px-8 rounded-md font-medium transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 shadow-md"
            >
              {{ voltarTexto }}
            </Link>
            
            <Link
              v-if="tipo === 'matricula'"
              :href="route('home')"
              class="bg-white hover:bg-gray-100 text-gray-800 border border-gray-300 py-3 px-8 rounded-md font-medium transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 shadow-sm"
            >
            Voltar ao Início
            </Link>
          </div>
        </div>
      </div>
    </div>
    <Footer />
  </div>
</template>