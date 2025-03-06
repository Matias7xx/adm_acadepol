<script setup>
  import { ref, computed } from 'vue';
  import { usePage, Head } from '@inertiajs/vue3';
  // Componentes
  import Header from './Components/Header.vue';
  import SiteNavbar from './Components/SiteNavbar.vue';
  import Footer from './Components/Footer.vue';
  import CardSection from './CursoDetalhesComponents/CardSection.vue'
  import InfoItem from './CursoDetalhesComponents/InfoItem.vue';
  import CourseHeader from './CursoDetalhesComponents/CourseHeader.vue';
  import EnrollmentCard from './CursoDetalhesComponents/EnrollmentCard.vue';
  import SocialShareCard from './CursoDetalhesComponents/SocialShareCard.vue';
  import { useToast } from '@/Composables/useToast'; 

  // Props recebendo o curso da rota
  const props = defineProps({
    curso: {
      type: Object,
      required: true
    }
  });
  
  const { showToast } = useToast();
  
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
    if (!texto) return '';
    
    // Converter texto com quebras de linha em HTML
    return texto
      .replace(/\n/g, '<br>')
      // Converter listas com hífens em listas HTML
      .replace(/- (.*?)(?=<br|$)/g, '<li>$1</li>')
      // Envolver conjuntos de itens de lista em tags <ul>
      .replace(/<li>(.*?)(?=<li>|$)/gs, match => {
        if (match.includes('<li>')) {
          return '<ul>' + match + '</ul>';
        }
        return match;
      });
  };
  
  /* // Métodos
  const handleEnrollment = () => {
    if (props.curso.status !== 'Aberto') {
      showToast('Inscrições não disponíveis no momento', 'error');
      return;
    }
    
    // Aqui você pode adicionar a lógica para processar a inscrição
    // Por exemplo, usando Inertia para fazer uma requisição ao backend
    // Inertia.post(`/cursos/${props.curso.id}/inscricao`);
    
    showToast('Inscrição realizada com sucesso!', 'success');
  }; */
  </script>

<template>
  <Head :title="'Curso - '+ curso.nome"/>
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
  
    <div class="container mx-auto px-4 sm:px-6 -mt-6">
      <div class="flex flex-col lg:flex-row gap-4 md:gap-6 lg:gap-8">
        <!-- Conteúdo principal -->
        <div class="w-full lg:w-2/3 space-y-4 md:space-y-6">
          <!-- Card de informações gerais -->
          <CardSection title="Sobre o curso">
            <div class="prose max-w-none text-gray-700">
              <p>{{ curso.descricao }}</p>
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
                :value="curso.localizacao" 
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
              <div v-if="curso.pre_requisitos" v-html="formatarTextoHtml(curso.pre_requisitos)"></div>
              <p v-else>Não há pré-requisitos específicos para este curso.</p>
            </div>
          </CardSection>
          
          <!-- Enxoval / Material necessário -->
          <CardSection title="Material necessário">
            <div class="prose max-w-none text-gray-700">
              <div v-if="curso.enxoval" v-html="formatarTextoHtml(curso.enxoval)"></div>
              <p v-else>Todos os materiais serão fornecidos durante o curso.</p>
            </div>
          </CardSection>
        </div>
        
        <!-- Sidebar com informações adicionais -->
        <div class="w-full lg:w-1/3 mt-4 lg:mt-0">
          <div class="sticky top-4 space-y-4 md:space-y-6">
            <!-- Card de inscrição -->
            <EnrollmentCard 
              :status="curso.status"
              :capacidade="curso.capacidade_maxima"
              :dataInicio="curso.data_inicio"
              :curso="curso"
              
              @enroll="handleEnrollment"
            />
            
            <!-- Compartilhar e ações -->
            <SocialShareCard 
              courseUrl="https://acadepol.pb.gov.br/cursos/cursotal" 
              courseTitle="Confira este curso incrível fornecido pela Acadepol!" 
            />
          </div>
        </div>
      </div>
    </div>
    <Footer />
  </div>
</template>