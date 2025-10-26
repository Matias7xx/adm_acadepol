<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  mdiArrowLeft,
  mdiContentSave,
  mdiAccountKey,
  mdiAlertBoxOutline, // Adicionar importação faltante
} from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import BaseButton from '@/Components/BaseButton.vue';
import FormField from '@/Components/FormField.vue';
import FormControl from '@/Components/FormControl.vue';
import FormFilePicker from '@/Components/FormFilePicker.vue';
import BaseButtons from '@/Components/BaseButtons.vue';
import NotificationBar from '@/Components/NotificationBar.vue';
import { ref, computed } from 'vue';

const props = defineProps({
  curso: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

// Converter as strings JSON para objetos JavaScript
const preRequisitos = ref(
  props.curso.pre_requisitos
    ? typeof props.curso.pre_requisitos === 'string'
      ? JSON.parse(props.curso.pre_requisitos)
      : props.curso.pre_requisitos
    : []
);

const enxoval = ref(
  props.curso.enxoval
    ? typeof props.curso.enxoval === 'string'
      ? JSON.parse(props.curso.enxoval)
      : props.curso.enxoval
    : []
);

const form = useForm({
  nome: props.curso.nome,
  descricao: props.curso.descricao || '',
  imagem: props.curso.imagem || '',
  imagem_file: null,
  data_inicio: props.curso.data_inicio,
  data_fim: props.curso.data_fim,
  carga_horaria: props.curso.carga_horaria,
  pre_requisitos: preRequisitos.value,
  enxoval: enxoval.value,
  localizacao: props.curso.localizacao,
  capacidade_maxima: props.curso.capacidade_maxima,
  modalidade: props.curso.modalidade,
  certificacao: Boolean(props.curso.certificacao),
  certificacao_modelo: props.curso.certificacao_modelo || '',
  status: props.curso.status,
});

console.log('Formulário inicializado:', form);

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

// Para gerenciar campos de array
const novoPreRequisito = ref('');
const novoItemEnxoval = ref('');

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

const onFileChange = e => {
  const file = e.target.files[0];
  if (file) {
    form.imagem_file = file;
  }
};

const submit = () => {
  console.log('Enviando formulário:', form);

  form
    .transform(data => ({
      ...data,
      _method: 'PUT', // Simular método PUT para Laravel
    }))
    .post(route('admin.cursos.update', props.curso.id), {
      preserveScroll: true,
      forceFormData: true, // Importante para upload de arquivos
      onError: errors => {
        console.error('Erros no envio:', errors);
      },
      onSuccess: () => {
        console.log('Formulário enviado com sucesso');
      },
    });
};

const previewImagem = computed(() => {
  return form.imagem_file
    ? URL.createObjectURL(form.imagem_file)
    : props.curso.imagem;
});
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Editar Curso" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccountKey"
        title="Editar Curso"
        main
      >
        <BaseButton
          :route-name="route('admin.cursos.index')"
          :icon="mdiArrowLeft"
          label="Voltar"
          color="info"
          outlined
        />
      </SectionTitleLineWithButton>

      <NotificationBar
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>

      <!-- IMPORTANTE: Modificar o formulário para usar enctype multipart/form-data -->
      <CardBox is-form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Nome do Curso -->
          <FormField label="Nome do Curso" :error="errors.nome">
            <FormControl
              v-model="form.nome"
              :error="errors.nome"
              placeholder="Nome do curso"
              required
            />
          </FormField>

          <!-- Carga Horária -->
          <FormField
            label="Carga Horária (horas)"
            :error="errors.carga_horaria"
          >
            <FormControl
              v-model="form.carga_horaria"
              type="number"
              :error="errors.carga_horaria"
              placeholder="Ex: 40"
              required
            />
          </FormField>

          <!-- Data Início -->
          <FormField label="Data de Início" :error="errors.data_inicio">
            <FormControl
              v-model="form.data_inicio"
              type="date"
              :error="errors.data_inicio"
              required
            />
          </FormField>

          <!-- Data Fim -->
          <FormField label="Data de Término" :error="errors.data_fim">
            <FormControl
              v-model="form.data_fim"
              type="date"
              :error="errors.data_fim"
              required
            />
          </FormField>

          <!-- Localização -->
          <FormField label="Localização" :error="errors.localizacao">
            <FormControl
              v-model="form.localizacao"
              :error="errors.localizacao"
              placeholder="Endereço ou sala"
              required
            />
          </FormField>

          <!-- Capacidade -->
          <FormField
            label="Capacidade Máxima"
            :error="errors.capacidade_maxima"
          >
            <FormControl
              v-model="form.capacidade_maxima"
              type="number"
              :error="errors.capacidade_maxima"
              placeholder="Número de vagas"
              required
            />
          </FormField>

          <!-- Modalidade -->
          <FormField label="Modalidade" :error="errors.modalidade">
            <FormControl
              v-model="form.modalidade"
              type="select"
              :options="modalidadeOptions"
              :error="errors.modalidade"
              required
            />
          </FormField>

          <!-- Status -->
          <FormField label="Status" :error="errors.status">
            <FormControl
              v-model="form.status"
              type="select"
              :options="statusOptions"
              :error="errors.status"
              required
            />
          </FormField>

          <!-- Imagem -->
          <FormField
            class="col-span-1 md:col-span-2"
            label="Imagem do Curso"
            :error="errors.imagem_file"
          >
            <div v-if="previewImagem" class="mb-4">
              <p class="text-sm text-gray-500 mb-2">Imagem atual:</p>
              <img
                :src="previewImagem"
                alt="Preview"
                class="max-h-40 rounded"
              />
            </div>
            <FormFilePicker
              v-model="form.imagem_file"
              accept="image/*"
              label="Selecionar nova imagem"
              @change="onFileChange"
            />
          </FormField>

          <!-- Descrição -->
          <FormField
            class="col-span-1 md:col-span-2"
            label="Descrição"
            :error="errors.descricao"
          >
            <FormControl
              v-model="form.descricao"
              type="textarea"
              :error="errors.descricao"
              placeholder="Descrição do curso"
              rows="4"
            />
          </FormField>

          <!-- Certificação -->
          <FormField
            label="Certificação"
            :error="errors.certificacao"
            class="flex items-center space-x-2"
          >
            <FormControl
              v-model="form.certificacao"
              type="checkbox"
              :error="errors.certificacao"
              label="Emitir certificado ao final do curso"
            />
          </FormField>

          <!-- <FormField
            v-if="form.certificacao"
            label="Modelo de Certificado"
            :error="errors.certificacao_modelo"
          >
            <FormControl
              v-model="form.certificacao_modelo"
              :error="errors.certificacao_modelo"
              placeholder="Referência ou modelo do certificado"
            />
          </FormField> -->

          <!-- Pré-requisitos (lista) -->
          <div class="col-span-1 md:col-span-2">
            <FormField label="Pré-requisitos">
              <div class="flex mb-2">
                <FormControl
                  v-model="novoPreRequisito"
                  placeholder="Adicionar pré-requisito"
                  class="flex-grow mr-2"
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
            </FormField>
          </div>

          <!-- Enxoval (lista) -->
          <div class="col-span-1 md:col-span-2">
            <FormField label="Enxoval (itens necessários)">
              <div class="flex mb-2">
                <FormControl
                  v-model="novoItemEnxoval"
                  placeholder="Adicionar item ao enxoval"
                  class="flex-grow mr-2"
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
            </FormField>
          </div>
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="button"
              color="info"
              :icon="mdiContentSave"
              label="Salvar Alterações"
              :disabled="form.processing"
              :loading="form.processing"
              @click="submit"
            />
            <BaseButton
              :route-name="route('admin.cursos.index')"
              label="Cancelar"
              color="info"
              outlined
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
