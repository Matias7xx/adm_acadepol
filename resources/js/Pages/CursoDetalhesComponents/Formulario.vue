<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Header from '../Components/Header.vue';
import SiteNavbar from '../Components/SiteNavbar.vue';
import Footer from '../Components/Footer.vue';

// Props
const props = defineProps({
  curso: {
    type: Object,
    required: true
  },
  user: {
    type: Object,
    required: true
  }
});

// Toast notification
const { toast } = useToast();

// Controle de visualização dos termos
const termoVisivel = ref(false);

// Função para alternar visibilidade dos termos
const toggleTermos = () => {
  termoVisivel.value = !termoVisivel.value;
};

// Equipamentos baseados no curso
const equipamentos = computed(() => {
  if (!props.curso.enxoval) return [];
  
  try {
    // Se o enxoval já estiver em formato de array
    if (Array.isArray(props.curso.enxoval)) {
      return props.curso.enxoval;
    }
    
    // Caso contrário, tenta analisar como JSON
    return JSON.parse(props.curso.enxoval);
  } catch (e) {
    console.error('Erro ao processar enxoval:', e);
    return [];
  }
});

// Estado do formulário
const formData = ref({
  aceitaTermos: false,
  experiencia: '',
  cursosAnteriores: '',
  expectativas: '',
  restricoesSaude: '',
  equipamentosConfirmados: computed(() => Array(equipamentos.value.length).fill(false)), //Inicializar os itens do enxoval (array)
  observacoes: ''
});

const isSubmitting = ref(false);

// Função auxiliar para formatar datas
const formatarData = (dataString) => {
  const data = new Date(dataString);
  return new Intl.DateTimeFormat('pt-BR').format(data);
};

// Função para submeter o formulário
const submeterInscricao = () => {
  // Validar aceite de termos
  if (!formData.value.aceitaTermos) {
    toast.error('Você precisa aceitar os termos para continuar');
    return;
  }

  // Validar campos obrigatórios
  if (!formData.value.expectativas) {
    toast.error('Por favor, preencha suas expectativas para o curso');
    return;
  }
  
  isSubmitting.value = true;
  
  // Preparar os dados para envio
  const dadosAdicionais = {
    experiencia: formData.value.experiencia,
    cursosAnteriores: formData.value.cursosAnteriores,
    expectativas: formData.value.expectativas,
    restricoesSaude: formData.value.restricoesSaude,
    equipamentosConfirmados: equipamentos.value.filter((_, index) => 
      formData.value.equipamentosConfirmados[index]
    ),
    observacoes: formData.value.observacoes,
    dataInscricao: new Date().toISOString()
  };
  
  // Usar o Inertia form helper
  const form = useForm({
    curso_id: props.curso.id,
    dados_adicionais: dadosAdicionais
  });

  form.post(route('matricula.store'), {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false;
      // Toast de sucesso será exibido automaticamente pela resposta
    },
    onError: (errors) => {
      isSubmitting.value = false;
      console.error(errors);
      if (errors.message) {
        toast.error(errors.message);
      } else {
        toast.error('Ocorreu um erro ao enviar a inscrição. Por favor, tente novamente.');
      }
    }
  });
};
</script>

<template>
  <Head :title="'Matrícula - '+ curso.nome"/>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <Header />
    <SiteNavbar />
    
    <!-- Cabeçalho com gradiente -->
    <div class="bg-black text-white py-5 shadow-md" style="background: linear-gradient(to right, #111827, #000000)">
      <div class="container mx-auto flex justify-between items-center px-4">
        <div class="flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h1 class="text-2xl font-bold">{{ curso.nome }} - Formulário de Inscrição</h1>
        </div>
        <Link :href="route('cursos')" class="flex items-center text-amber-400 hover:text-amber-300 transition">
          <span>Voltar aos Cursos</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border border-gray-200">
        <div class="flex items-center justify-between mb-6 pb-3 border-b border-amber-200">
          <div class="flex items-center">
            <div class="bg-amber-100 p-3 rounded-full mr-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Dados da Inscrição</h2>
          </div>
          <div class="text-sm text-gray-500">* Campos obrigatórios</div>
        </div>
        
        <!-- Informações do Curso e Aluno -->
        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border-l-4 border-amber-500 p-4 mb-8 shadow-sm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-semibold text-amber-800 mb-3">Informações do Curso</h3>
              <div class="space-y-2 text-amber-700">
                <p><span class="font-medium">Curso:</span> {{ curso.nome }}</p>
                <p><span class="font-medium">Período:</span> {{ formatarData(curso.data_inicio) }} a {{ formatarData(curso.data_fim) }}</p>
                <p><span class="font-medium">Carga Horária:</span> {{ curso.carga_horaria }}h</p>
                <p><span class="font-medium">Localização:</span> {{ curso.localizacao }}</p>
              </div>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-amber-800 mb-3">Informações do Aluno</h3>
              <div class="space-y-2 text-amber-700">
                <p><span class="font-medium">Nome:</span> {{ user.name }}</p>
                <p><span class="font-medium">Matrícula:</span> {{ user.matricula }}</p>
                <p><span class="font-medium">Lotação:</span> {{ user.lotacao }}</p>
                <p><span class="font-medium">Email:</span> {{ user.email }}</p>
              </div>
            </div>
          </div>
        </div>

        <form @submit.prevent="submeterInscricao" class="space-y-8">
          <!-- Termos e Condições -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 10-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd" />
              </svg>
              Termos e Condições
            </h3>
            
            <div class="flex justify-end mb-2">
              <button 
                type="button" 
                @click="toggleTermos" 
                class="text-amber-600 hover:text-amber-800 text-sm font-medium"
              >
                {{ termoVisivel ? 'Ocultar Termos' : 'Ver Termos Completos' }}
              </button>
            </div>
            
            <div 
              :class="{'h-40': !termoVisivel, 'h-auto': termoVisivel}"
              class="bg-gray-50 p-4 rounded-lg mb-4 text-sm overflow-y-auto border transition-all duration-300"
            >
              <h4 class="font-bold mb-2">TERMO DE PARTICIPAÇÃO NO CURSO</h4>
              
              <p class="mb-3">Ao se inscrever neste curso, o aluno compromete-se a:</p>
              
              <ol class="list-decimal ml-5 space-y-2 mb-4">
                <li>Comparecer a todas as aulas nos horários estabelecidos;</li>
                <li>Cumprir com todas as atividades e avaliações propostas;</li>
                <li>Trazer todos os materiais necessários listados nos pré-requisitos;</li>
                <li>Seguir as normas de segurança durante as atividades práticas;</li>
                <li>Manter conduta compatível com os princípios éticos da instituição;</li>
                <li>Comunicar com antecedência qualquer impossibilidade de comparecimento;</li>
                <li>Estar ciente que a matrícula está sujeita à aprovação pela coordenação do curso.</li>
              </ol>
              
              <p>A ACADEPOL se reserva o direito de cancelar a matrícula em caso de descumprimento das normas estabelecidas.</p>
            </div>
            
            <div class="flex items-start mb-4">
              <div class="flex items-center h-5">
                <input 
                  id="aceitaTermos" 
                  v-model="formData.aceitaTermos" 
                  type="checkbox" 
                  class="w-4 h-4 border-gray-300 rounded"
                  required
                >
              </div>
              <label for="aceitaTermos" class="ml-2 text-sm font-medium text-gray-700">
                Declaro que li e aceito os termos e condições para participação no curso
              </label>
            </div>
          </div>

          <!-- Informações Adicionais -->
          <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              Informações Adicionais
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Experiência Prévia -->
              <div>
                <label for="experiencia" class="block text-sm font-medium text-gray-700 mb-1">
                  Experiência prévia com o tema do curso
                </label>
                <select 
                  id="experiencia" 
                  v-model="formData.experiencia" 
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                >
                  <option value="">Selecione uma opção</option>
                  <option value="nenhuma">Nenhuma experiência</option>
                  <option value="basica">Experiência básica</option>
                  <option value="intermediaria">Experiência intermediária</option>
                  <option value="avancada">Experiência avançada</option>
                </select>
              </div>

              <!-- Restrições de Saúde -->
              <div>
                <label for="restricoesSaude" class="block text-sm font-medium text-gray-700 mb-1">
                  Possui alguma restrição de saúde? *
                </label>
                <input 
                  id="restricoesSaude" 
                  v-model="formData.restricoesSaude" 
                  type="text"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="Informe se possui alguma restrição médica para atividades físicas"
                  required
                >
              </div>

              <!-- Cursos Anteriores -->
              <div class="md:col-span-2">
                <label for="cursosAnteriores" class="block text-sm font-medium text-gray-700 mb-1">
                  Cursos relacionados que já participou (opcional)
                </label>
                <textarea 
                  id="cursosAnteriores" 
                  v-model="formData.cursosAnteriores" 
                  rows="2"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="Liste os cursos relacionados que você já participou"
                ></textarea>
              </div>

              <!-- Expectativas -->
              <div class="md:col-span-2">
                <label for="expectativas" class="block text-sm font-medium text-gray-700 mb-1">
                  Quais são suas expectativas para este curso? *
                </label>
                <textarea 
                  id="expectativas" 
                  v-model="formData.expectativas" 
                  rows="3"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                  placeholder="Descreva o que você espera aprender ou desenvolver com este curso"
                  required
                ></textarea>
              </div>
            </div>
          </div>
          
          <!-- Equipamentos -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-5V9a1 1 0 10-2 0v1H4a2 2 0 110-4h1.17C5.06 5.687 5 5.35 5 5zm4 1V5a1 1 0 10-1 1h1zm3 0a1 1 0 10-1-1v1h1z" clip-rule="evenodd" />
                <path d="M9 11H3v5a2 2 0 002 2h4v-7zM11 18h4a2 2 0 002-2v-5h-6v7z" />
              </svg>
              Equipamentos Necessários
            </h3>
            
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
              <p class="text-sm text-gray-600 mb-3">
                Confirme que você possui ou tem acesso aos seguintes equipamentos necessários para o curso:
              </p>
              
              <div class="space-y-3">
                <div v-for="(item, index) in equipamentos" :key="index" class="flex items-start">
                  <div class="flex items-center h-5">
                    <input 
                      :id="'equipamento-' + index" 
                      type="checkbox" 
                      v-model="formData.equipamentosConfirmados[index]"
                      class="w-4 h-4 border-gray-300 rounded text-amber-600 focus:ring-amber-500"
                    >
                  </div>
                  <label :for="'equipamento-' + index" class="ml-2 text-sm text-gray-700">
                    {{ item }}
                  </label>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Observações Adicionais -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Observações Adicionais</h3>
            
            <div>
              <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">
                Outras informações relevantes (opcional)
              </label>
              <textarea 
                id="observacoes" 
                v-model="formData.observacoes" 
                rows="3"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                placeholder="Informe se há alguma outra informação importante para sua participação no curso"
              ></textarea>
            </div>
          </div>
          
          <!-- Botões de Ação -->
          <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 mt-8 pt-6 border-t border-gray-200">
            <button 
              type="submit" 
              class="bg-amber-500 hover:bg-amber-600 text-white py-3 px-8 rounded-md font-medium transition-all duration-300 shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-300 flex items-center justify-center"
              :disabled="isSubmitting"
              :class="{'opacity-75 cursor-not-allowed': isSubmitting}"
              style="background: linear-gradient(to right, #f59e0b, #d97706)"
            >
              <svg v-if="!isSubmitting" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <svg v-if="isSubmitting" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-if="isSubmitting">Enviando...</span>
              <span v-else>Enviar Inscrição</span>
            </button>
            <Link
              :href="route('cursos')"
              class="text-center border border-gray-300 bg-white text-gray-700 py-3 px-8 rounded-md font-medium transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 flex items-center justify-center"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
              Cancelar
            </Link>
          </div>
        </form>
      </div>
    </div>
    <Footer />
  </div>
</template>

<style scoped>
/* Estilos específicos para este componente */
.transition-all {
  transition-property: all;
}
.duration-300 {
  transition-duration: 300ms;
}

/* Estilos para inputs quando focados */
input:focus, select:focus, textarea:focus {
  --tw-ring-color: rgba(245, 158, 11, 0.2);
}
</style>