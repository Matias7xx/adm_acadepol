<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  mdiAccountKey,
  mdiArrowLeftBoldOutline,
  mdiFileDocument,
  mdiSchool,
  mdiMapMarker,
  mdiCalendarRange,
} from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import FormField from '@/Components/FormField.vue';
import FormControl from '@/Components/FormControl.vue';
import FormCheckRadioGroup from '@/Components/FormCheckRadioGroup.vue';
import BaseDivider from '@/Components/BaseDivider.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseButtons from '@/Components/BaseButtons.vue';
import { ref } from 'vue';

const modalidadeOptions = {
  presencial: 'presencial',
  online: 'online',
  híbrido: 'híbrido',
};

const statusOptions = {
  aberto: 'aberto',
  'em andamento': 'em andamento',
  concluído: 'concluído',
  cancelado: 'cancelado',
};

const form = useForm({
  nome: '',
  descricao: '',
  imagem: '',
  data_inicio: '',
  data_fim: '',
  carga_horaria: '',
  pre_requisitos: [],
  enxoval: [],
  localizacao: '',
  capacidade_maxima: '',
  modalidade: 'presencial',
  /* material_complementar: [], */
  certificacao: false,
  certificacao_modelo: '',
  status: 'aberto',
  imagem_file: null,
});

// Para gerenciar campos de array
const novoPreRequisito = ref('');
const novoItemEnxoval = ref('');

// Funções para adicionar e remover itens dos arrays
const adicionarPreRequisito = () => {
  if (novoPreRequisito.value.trim()) {
    form.pre_requisitos.push(novoPreRequisito.value.trim());
    novoPreRequisito.value = '';
  }
};

const removerPreRequisito = index => {
  form.pre_requisitos.splice(index, 1);
};

const adicionarItemEnxoval = () => {
  if (novoItemEnxoval.value.trim()) {
    form.enxoval.push(novoItemEnxoval.value.trim());
    novoItemEnxoval.value = '';
  }
};

const removerItemEnxoval = index => {
  form.enxoval.splice(index, 1);
};

const handleImageUpload = event => {
  const file = event.target.files[0];
  if (file) {
    form.imagem_file = file;
  }
};

const submit = () => {
  form.post(route('admin.cursos.store'), {
    preserveScroll: true,
    forceFormData: true,
  });
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Novo Curso" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiSchool"
        title="Cadastrar Curso"
        main
      >
        <BaseButton
          :route-name="route('admin.cursos.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      <CardBox form>
        <!-- Informações Básicas -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Informações Básicas</h3>

          <FormField
            label="Nome do Curso"
            :class="{ 'text-red-400': form.errors.nome }"
          >
            <FormControl
              v-model="form.nome"
              type="text"
              placeholder="Informe o Nome do Curso"
              :error="form.errors.nome"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.nome">
                {{ form.errors.nome }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="Descrição"
            :class="{ 'text-red-400': form.errors.descricao }"
          >
            <FormControl
              v-model="form.descricao"
              type="textarea"
              placeholder="Descrição detalhada do curso"
              :error="form.errors.descricao"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.descricao">
                {{ form.errors.descricao }}
              </div>
            </FormControl>
          </FormField>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Data de Início"
              :class="{ 'text-red-400': form.errors.data_inicio }"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_inicio"
                type="date"
                :error="form.errors.data_inicio"
              >
                <div
                  class="text-red-400 text-sm"
                  v-if="form.errors.data_inicio"
                >
                  {{ form.errors.data_inicio }}
                </div>
              </FormControl>
            </FormField>

            <FormField
              label="Data de Término"
              :class="{ 'text-red-400': form.errors.data_fim }"
              :icon="mdiCalendarRange"
            >
              <FormControl
                v-model="form.data_fim"
                type="date"
                :error="form.errors.data_fim"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.data_fim">
                  {{ form.errors.data_fim }}
                </div>
              </FormControl>
            </FormField>
          </div>
        </div>

        <!-- Detalhes do Curso -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Detalhes do Curso</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Carga Horária (horas)"
              :class="{ 'text-red-400': form.errors.carga_horaria }"
            >
              <FormControl
                v-model="form.carga_horaria"
                type="number"
                min="1"
                placeholder="Ex: 40"
                :error="form.errors.carga_horaria"
              >
                <div
                  class="text-red-400 text-sm"
                  v-if="form.errors.carga_horaria"
                >
                  {{ form.errors.carga_horaria }}
                </div>
              </FormControl>
            </FormField>

            <FormField
              label="Capacidade Máxima de Alunos"
              :class="{ 'text-red-400': form.errors.capacidade_maxima }"
            >
              <FormControl
                v-model="form.capacidade_maxima"
                type="number"
                min="1"
                placeholder="Ex: 30"
                :error="form.errors.capacidade_maxima"
              >
                <div
                  class="text-red-400 text-sm"
                  v-if="form.errors.capacidade_maxima"
                >
                  {{ form.errors.capacidade_maxima }}
                </div>
              </FormControl>
            </FormField>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Modalidade do Curso"
              :class="{ 'text-red-400': form.errors.modalidade }"
            >
              <FormControl
                v-model="form.modalidade"
                type="select"
                :options="modalidadeOptions"
                :error="form.errors.modalidade"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.modalidade">
                  {{ form.errors.modalidade }}
                </div>
              </FormControl>
            </FormField>

            <FormField
              label="Status do Curso"
              :class="{ 'text-red-400': form.errors.status }"
            >
              <FormControl
                v-model="form.status"
                type="select"
                :options="statusOptions"
                :error="form.errors.status"
              >
                <div class="text-red-400 text-sm" v-if="form.errors.status">
                  {{ form.errors.status }}
                </div>
              </FormControl>
            </FormField>
          </div>

          <FormField
            label="Localização"
            :class="{ 'text-red-400': form.errors.localizacao }"
            :icon="mdiMapMarker"
          >
            <FormControl
              v-model="form.localizacao"
              type="text"
              placeholder="Local onde o curso será realizado"
              :error="form.errors.localizacao"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.localizacao">
                {{ form.errors.localizacao }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="Certificação"
            :class="{ 'text-red-400': form.errors.certificacao }"
          >
            <div class="flex items-center space-x-2">
              <FormControl
                v-model="form.certificacao"
                type="checkbox"
                :error="form.errors.certificacao"
              />
              <span>O curso emite certificado?</span>
            </div>
            <div class="text-red-400 text-sm" v-if="form.errors.certificacao">
              {{ form.errors.certificacao }}
            </div>
          </FormField>
        </div>

        <!-- Recursos e Requisitos -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Recursos e Requisitos</h3>

          <!-- Pré-requisitos (lista) -->
          <div class="mb-6">
            <FormField label="Pré-requisitos">
              <div class="flex mb-2">
                <FormControl
                  v-model="novoPreRequisito"
                  placeholder="Adicionar pré-requisito"
                  class="flex-grow mr-2"
                  :error="form.errors.pre_requisitos"
                />
                <BaseButton
                  type="button"
                  color="info"
                  label="Adicionar"
                  @click="adicionarPreRequisito"
                />
              </div>
              <div
                v-if="form.pre_requisitos && form.pre_requisitos.length > 0"
                class="mt-2"
              >
                <ul class="list-disc pl-5">
                  <li
                    v-for="(item, index) in form.pre_requisitos"
                    :key="index"
                    class="mb-1 flex items-center"
                  >
                    <span class="flex-grow">{{ item }}</span>
                    <button
                      type="button"
                      @click="removerPreRequisito(index)"
                      class="text-red-500 hover:text-red-700"
                    >
                      <span>Remover</span>
                    </button>
                  </li>
                </ul>
              </div>
              <div v-else class="text-gray-500 mt-2">
                Nenhum pré-requisito adicionado.
              </div>
              <div
                class="text-red-400 text-sm"
                v-if="form.errors.pre_requisitos"
              >
                {{ form.errors.pre_requisitos }}
              </div>
            </FormField>
          </div>

          <!-- Enxoval (lista) -->
          <div class="mb-6">
            <FormField label="Enxoval (itens necessários)">
              <div class="flex mb-2">
                <FormControl
                  v-model="novoItemEnxoval"
                  placeholder="Adicionar item ao enxoval"
                  class="flex-grow mr-2"
                  :error="form.errors.enxoval"
                />
                <BaseButton
                  type="button"
                  color="info"
                  label="Adicionar"
                  @click="adicionarItemEnxoval"
                />
              </div>
              <div v-if="form.enxoval && form.enxoval.length > 0" class="mt-2">
                <ul class="list-disc pl-5">
                  <li
                    v-for="(item, index) in form.enxoval"
                    :key="index"
                    class="mb-1 flex items-center"
                  >
                    <span class="flex-grow">{{ item }}</span>
                    <button
                      type="button"
                      @click="removerItemEnxoval(index)"
                      class="text-red-500 hover:text-red-700"
                    >
                      <span>Remover</span>
                    </button>
                  </li>
                </ul>
              </div>
              <div v-else class="text-gray-500 mt-2">
                Nenhum item de enxoval adicionado.
              </div>
              <div class="text-red-400 text-sm" v-if="form.errors.enxoval">
                {{ form.errors.enxoval }}
              </div>
            </FormField>
          </div>
        </div>

        <!-- Imagem do Curso -->
        <div class="p-4 rounded-lg mb-6">
          <h3 class="font-semibold text-lg mb-4">Imagem do Curso</h3>

          <FormField label="Upload de Imagem">
            <FormControl
              type="file"
              accept="image/*"
              @change="handleImageUpload"
              :error="form.errors.imagem_file"
            >
              <div class="text-red-400 text-sm" v-if="form.errors.imagem_file">
                {{ form.errors.imagem_file }}
              </div>
            </FormControl>
            <p class="text-sm text-gray-500 mt-1">
              Formatos aceitos: JPG, PNG, GIF (máx. 2MB)
            </p>
          </FormField>
        </div>

        <BaseDivider />

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="button"
              color="light"
              label="Cancelar"
              :route-name="route('admin.cursos.index')"
              :class="{ 'opacity-75': form.processing }"
              :disabled="form.processing"
            />
            <BaseButton
              type="button"
              color="info"
              label="Cadastrar Curso"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
              @click="submit"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
