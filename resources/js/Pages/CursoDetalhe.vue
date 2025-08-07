<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import Header from './Components/Header.vue';
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';
import CardSection from './CursoDetalhesComponents/CardSection.vue'
import InfoItem from './CursoDetalhesComponents/InfoItem.vue';
import CourseHeader from './CursoDetalhesComponents/CourseHeader.vue';
import EnrollmentCard from './CursoDetalhesComponents/EnrollmentCard.vue';
import SocialShareCard from './CursoDetalhesComponents/SocialShareCard.vue';

// Props recebendo o curso da rota
const props = defineProps({
  curso: {
    type: Object,
    required: true
  }
});

// Computed properties
const courseUrl = computed(() => {
  return `${window.location.origin}/cursos/${props.curso.id}`;
});

// Funções auxiliares para formatação
const formatarData = (data) => {
  if (!data) return 'Não definido';
  return new Date(data).toLocaleDateString('pt-BR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  });
};

const formatarTextoHtml = (texto) => {
  if (!texto) return 'Nenhuma informação disponível';
  
  try {
    // Tentar parsear o JSON se for um array
    let listaItens = Array.isArray(texto) ? texto : 
      (typeof texto === 'string' ? JSON.parse(texto) : []);

    // Se for array e não estiver vazio, criar lista HTML
    if (Array.isArray(listaItens) && listaItens.length > 0) {
      return `
        <ul class="list-disc list-inside space-y-2 text-gray-700">
          ${listaItens.map(item => `<li class="leading-relaxed">${item}</li>`).join('')}
        </ul>
      `;
    }
  } catch (e) {
    // Se não conseguir parsear como JSON, tratar como texto plano
  }

  // Fallback para texto plano
  if (typeof texto === 'string') {
    return texto
      .replace(/\n/g, '<br>')
      .replace(/- (.*?)(?=<br|$)/g, '<li class="ml-4">$1</li>')
      .replace(/(<li.*?<\/li>)/g, '<ul class="list-disc list-inside space-y-1 text-gray-700">$1</ul>');
  }

  return 'Nenhuma informação disponível';
};

// Verificar se há pré-requisitos
const temPreRequisitos = computed(() => {
  if (!props.curso.pre_requisitos) return false;
  
  try {
    const parsed = Array.isArray(props.curso.pre_requisitos) 
      ? props.curso.pre_requisitos 
      : JSON.parse(props.curso.pre_requisitos);
    return Array.isArray(parsed) && parsed.length > 0;
  } catch {
    return props.curso.pre_requisitos && props.curso.pre_requisitos.length > 0;
  }
});

// Verificar se há enxoval
const temEnxoval = computed(() => {
  if (!props.curso.enxoval) return false;
  
  try {
    const parsed = Array.isArray(props.curso.enxoval) 
      ? props.curso.enxoval 
      : JSON.parse(props.curso.enxoval);
    return Array.isArray(parsed) && parsed.length > 0;
  } catch {
    return props.curso.enxoval && props.curso.enxoval.length > 0;
  }
});
</script>

<template>
  <Head :title="'Curso - ' + curso.nome"/>
  <div class="bg-gray-100 min-h-screen pb-12">
    <Header />
    <SiteNavbar />
    
    <!-- Cabeçalho com imagem de capa do curso -->
    <CourseHeader 
      :imagem="curso.imagem"
      :nome="curso.nome"
      :modalidade="curso.modalidade"
      :status="curso.status"
    />
  
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Botão de retorno -->
      <div class="relative z-10 mt-3 mb-6">
        <Link 
          :href="route('cursos')"
          class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-medium text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 shadow-sm"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Voltar para Cursos
        </Link>
      </div>

      <div class="flex flex-col lg:flex-row gap-4 md:gap-6 lg:gap-8 -mt-2">
        <!-- Conteúdo principal -->
        <div class="w-full lg:w-2/3 space-y-4 md:space-y-6">
          <!-- Card de informações gerais -->
          <CardSection title="Sobre o curso">
            <div class="prose max-w-none text-gray-700">
              <p class="text-base leading-relaxed">{{ curso.descricao || 'Descrição do curso não disponível.' }}</p>
            </div>
            
            <!-- Detalhes adicionais -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 mt-4 md:mt-8">
              <InfoItem 
                icon="clock" 
                color="yellow" 
                label="Carga Horária" 
                :value="`${curso.carga_horaria} horas`" 
              />
              
              <InfoItem 
                icon="calendar" 
                color="blue" 
                label="Período" 
                :value="`${formatarData(curso.data_inicio)} a ${formatarData(curso.data_fim)}`" 
              />
              
              <InfoItem 
                icon="location" 
                color="green" 
                label="Localização" 
                :value="curso.localizacao || 'Local a definir'" 
              />
              
              <InfoItem 
                icon="users" 
                color="purple" 
                label="Capacidade" 
                :value="`${curso.capacidade_maxima} participantes`" 
              />
            </div>
          </CardSection>
          
          <!-- Pré-requisitos -->
          <CardSection title="Pré-requisitos">
            <div class="prose max-w-none text-gray-700">
              <div v-if="temPreRequisitos" v-html="formatarTextoHtml(curso.pre_requisitos)"></div>
              <p v-else class="text-gray-600 italic">Não há pré-requisitos específicos para este curso.</p>
            </div>
          </CardSection>
          
          <!-- Enxoval / Material necessário -->
          <CardSection title="Material necessário">
            <div class="prose max-w-none text-gray-700">
              <div v-if="temEnxoval" v-html="formatarTextoHtml(curso.enxoval)"></div>
              <p v-else class="text-gray-600 italic">Todos os materiais serão fornecidos durante o curso.</p>
            </div>
          </CardSection>

          <!-- Certificação -->
          <CardSection title="Certificação" v-if="curso.certificacao">
            <div class="prose max-w-none text-gray-700">
              <div class="flex items-center space-x-2 mb-3">
                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold text-green-700">Este curso oferece certificado de conclusão</span>
              </div>
              <p v-if="curso.certificacao_modelo" class="text-sm text-gray-600">
                {{ curso.certificacao_modelo }}
              </p>
            </div>
          </CardSection>
        </div>
        
        <!-- Sidebar com informações adicionais -->
        <div class="w-full lg:w-1/3 space-y-4 md:space-y-6">
          <div class="sticky top-4 space-y-4 md:space-y-6">
            <!-- Card de inscrição -->
            <EnrollmentCard 
              :status="curso.status"
              :capacidade="curso.capacidade_maxima"
              :dataInicio="curso.data_inicio"
              :curso="curso"
            />
            
            <!-- Compartilhar e ações -->
            <SocialShareCard 
              :courseUrl="courseUrl"
              :courseTitle="`Confira o curso ${curso.nome} na Acadepol!`" 
            />
          </div>
        </div>
      </div>
    </div>
  </div>
  <Footer />
</template>

<style scoped>
.prose ul {
  @apply list-disc list-inside space-y-1;
}

.prose li {
  @apply leading-relaxed;
}
</style>