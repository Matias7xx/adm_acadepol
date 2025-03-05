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

// Equipamentos baseados no curso (exemplo - pode ser dinâmico baseado no tipo de curso)
const equipamentos = ref([
  'Colete balístico',
  'Óculos de proteção',
  'Protetor auricular',
  'Calçado apropriado',
  'Vestimenta adequada conforme normas da instituição'
]);

// Estado do formulário
const formData = ref({
  aceitaTermos: false,
  experiencia: '',
  cursosAnteriores: '',
  expectativas: '',
  restricoesSaude: '',
  equipamentosConfirmados: Array(equipamentos.value.length).fill(false),
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
  form = useForm({
    curso_id: props.curso.id,
    dados_adicionais: dadosAdicionais
  });

  form.post(route('matricula.store'), {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: (errors) => {
      isSubmitting.value = false;
      console.error(errors);
    }
  });
};
</script>

<template>
  <Head :title="'Matrícula - '+ curso.nome"/>
  <div class="min-h-screen bg-gray-100">
    <Header />
    <SiteNavbar />
    <!-- Cabeçalho -->
    <div class="bg-black text-white py-4">
      <div class="container mx-auto flex justify-between items-center px-4">
        <h1 class="text-xl font-bold">{{ curso.nome }} - Formulário de Inscrição</h1>
        <Link :href="route('cursos')" class="text-amber-400 hover:text-amber-300 transition">
          Voltar aos Cursos
        </Link>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="container mx-auto py-8 px-4">
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Dados da Inscrição</h2>
        
        <!-- Informações do Curso -->
        <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Informações do Curso</h3>
            <div class="space-y-2">
              <p><span class="font-medium">Curso:</span> {{ curso.nome }}</p>
              <p><span class="font-medium">Período:</span> {{ formatarData(curso.data_inicio) }} a {{ formatarData(curso.data_fim) }}</p>
              <p><span class="font-medium">Carga Horária:</span> {{ curso.carga_horaria }}h</p>
              <p><span class="font-medium">Localização:</span> {{ curso.localizacao }}</p>
            </div>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Informações do Aluno</h3>
            <div class="space-y-2">
              <p><span class="font-medium">Nome:</span> {{ user.name }}</p>
              <p><span class="font-medium">Matrícula:</span> {{ user.matricula }}</p>
              <p><span class="font-medium">Email:</span> {{ user.email }}</p>
            </div>
          </div>
        </div>

        <form @submit.prevent="submeterInscricao">
          <!-- Termos e Condições -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Termos e Condições</h3>
            <div class="bg-gray-50 p-4 rounded-lg mb-4 text-sm h-40 overflow-y-auto border">
              <p class="mb-3">O aluno inscrito neste curso compromete-se a:</p>
              <ol class="list-decimal ml-5 space-y-2">
                <li>Comparecer a todas as aulas nos horários estabelecidos;</li>
                <li>Cumprir com todas as atividades e avaliações propostas;</li>
                <li>Trazer todos os materiais necessários listados nos pré-requisitos;</li>
                <li>Seguir as normas de segurança durante as atividades práticas;</li>
                <li>Manter conduta compatível com os princípios éticos da instituição;</li>
                <li>Comunicar com antecedência qualquer impossibilidade de comparecimento;</li>
                <li>Estar ciente que a matrícula está sujeita à aprovação pela coordenação do curso.</li>
              </ol>
            </div>
            <div class="flex items-start mb-4">
              <div class="flex items-center h-5">
                <input id="termos" v-model="formData.aceitaTermos" type="checkbox" class="w-4 h-4 border-gray-300 rounded" required>
              </div>
              <label for="termos" class="ml-2 text-sm font-medium text-gray-700">
                Declaro que li e aceito os termos e condições para participação no curso
              </label>
            </div>
          </div>

          <!-- Informações Adicionais -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Informações Adicionais</h3>
            
            <!-- Experiência Prévia -->
            <div class="mb-4">
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

            <!-- Cursos Anteriores -->
            <div class="mb-4">
              <label for="cursosAnteriores" class="block text-sm font-medium text-gray-700 mb-1">
                Cursos relacionados que já participou (opcional)
              </label>
              <textarea 
                id="cursosAnteriores" 
                v-model="formData.cursosAnteriores" 
                rows="3"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                placeholder="Liste os cursos relacionados que você já participou"
              ></textarea>
            </div>

            <!-- Expectativas -->
            <div class="mb-4">
              <label for="expectativas" class="block text-sm font-medium text-gray-700 mb-1">
                Quais são suas expectativas para este curso?
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

            <!-- Restrições de Saúde -->
            <div class="mb-4">
              <label for="restricoesSaude" class="block text-sm font-medium text-gray-700 mb-1">
                Possui alguma restrição de saúde que precisamos conhecer? (opcional)
              </label>
              <textarea 
                id="restricoesSaude" 
                v-model="formData.restricoesSaude" 
                rows="2"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                placeholder="Informe se possui alguma restrição médica para atividades físicas"
              ></textarea>
            </div>
            
            <!-- Equipamentos -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Confirme os equipamentos que você possui para o curso:
              </label>
              <div class="space-y-2">
                <div class="flex items-start" v-for="(item, index) in equipamentos" :key="index">
                  <div class="flex items-center h-5">
                    <input 
                      :id="'equipamento-' + index" 
                      type="checkbox" 
                      v-model="formData.equipamentosConfirmados[index]"
                      class="w-4 h-4 border-gray-300 rounded"
                    >
                  </div>
                  <label :for="'equipamento-' + index" class="ml-2 text-sm text-gray-700">
                    {{ item }}
                  </label>
                </div>
              </div>
            </div>
            
            <!-- Observações Adicionais -->
            <div class="mb-4">
              <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">
                Observações adicionais (opcional)
              </label>
              <textarea 
                id="observacoes" 
                v-model="formData.observacoes" 
                rows="3"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200"
                placeholder="Alguma informação adicional relevante para sua inscrição"
              ></textarea>
            </div>
          </div>

          <!-- Botões de Ação -->
          <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 mt-8">
            <button 
              type="submit" 
              class="bg-amber-500 hover:bg-amber-600 text-white py-2 px-6 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-amber-300"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting">Enviando...</span>
              <span v-else>Enviar Inscrição</span>
            </button>
            <Link
              :href="route('cursos')"
              class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-6 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
            >
              Cancelar
            </Link>
          </div>
        </form>
      </div>
    </div>
    <Footer />
  </div>
</template>