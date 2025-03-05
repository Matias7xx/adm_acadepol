<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiAccountKey,
  mdiArrowLeftBoldOutline
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import FormField from '@/Components/FormField.vue'
import FormControl from '@/Components/FormControl.vue'
import FormCheckRadioGroup from '@/Components/FormCheckRadioGroup.vue'
import BaseDivider from '@/Components/BaseDivider.vue'
import BaseButton from '@/Components/BaseButton.vue'
import BaseButtons from '@/Components/BaseButtons.vue'

/* const props = defineProps({
  roles: {
    type: Object,
    default: () => ({}),
  }
}) */

const form = useForm({
  nome: '',
  descricao: '',
  imagem: '',
  data_inicio: '',
  data_fim: '',
  carga_horaria: '',
  pre_requisitos: '',
  enxoval: '',
  localizacao: '',
  capacidade_maxima: '',
  modalidade: 'presencial',
  material_complementar: '',
  certificacao: false,
  certificacao_modelo: '',
  status: 'Aberto',
  imagem_file: null,
})

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.imagem_file = file;
  }
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Novo Curso" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccountKey"
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
      <CardBox
        form
        @submit.prevent="form.post(route('admin.cursos.store'))"
      >
        <FormField
          label="Nome"
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
            placeholder="Descrição do curso"
            :error="form.errors.descricao"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.descricao">
              {{ form.errors.descricao }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Imagem (URL ou Upload)"
          :class="{ 'text-red-400': form.errors.imagem }"
        >
          <FormControl
            v-model="form.imagem"
            type="text"
            placeholder="URL da imagem"
            :error="form.errors.imagem"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.imagem">
              {{ form.errors.imagem }}
            </div>
          </FormControl>
          <FormControl
            v-model="form.imagem"
            type="file"
            placeholder="Insira uma imagem"
            @change="handleImageUpload"
            :error="form.errors.imagem"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.imagem">
              {{ form.errors.imagem }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Data de Início"
          :class="{ 'text-red-400': form.errors.data_inicio }"
        >
          <FormControl
            v-model="form.data_inicio"
            type="date"
            placeholder="Informe a data de início do Curso"
            :error="form.errors.data_inicio"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.data_inicio">
              {{ form.errors.data_inicio }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Data de Término"
          :class="{ 'text-red-400': form.errors.data_fim }"
        >
          <FormControl
            v-model="form.data_fim"
            type="date"
            placeholder="Informe a data de término do Curso"
            :error="form.errors.data_fim"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.data_fim">
              {{ form.errors.data_fim }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Carga Horária"
          :class="{ 'text-red-400': form.errors.carga_horaria }"
        >
          <FormControl
            v-model="form.carga_horaria"
            type="number"
            placeholder="Informe a carga horária do Curso"
            :error="form.errors.carga_horaria"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.carga_horaria">
              {{ form.errors.carga_horaria }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Cursos Pré-Requisitos"
          :class="{ 'text-red-400': form.errors.pre_requisitos }"
        >
          <FormControl
            v-model="form.pre_requisitos"
            type="text"
            placeholder="Existe algum curso pré-requisito requerido?"
            :error="form.errors.pre_requisitos"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.pre_requisitos">
              {{ form.errors.pre_requisitos }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Enxoval"
          :class="{ 'text-red-400': form.errors.enxoval }"
        >
          <FormControl
            v-model="form.enxoval"
            type="text"
            placeholder="Itens necessários separados por vírgula"
            :error="form.errors.enxoval"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.enxoval">
              {{ form.errors.enxoval }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Localização do curso"
          :class="{ 'text-red-400': form.errors.localizacao }"
        >
          <FormControl
            v-model="form.localizacao"
            type="text"
            placeholder="Informe a localização onde o curso será relaizado."
            :error="form.errors.localizacao"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.localizacao">
              {{ form.errors.localizacao }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Capacidade Máxima de alunos"
          :class="{ 'text-red-400': form.errors.capacidade_maxima }"
        >
          <FormControl
            v-model="form.capacidade_maxima"
            type="text"
            placeholder="Informe a capacidade máxima de alunos que podem ser matriculados."
            :error="form.errors.capacidade_maxima"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.capacidade_maxima">
              {{ form.errors.capacidade_maxima }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Modalidade do Curso"
          :class="{ 'text-red-400': form.errors.modalidade }"
        >
          <FormControl
            v-model="form.modalidade"
            type="select"
            :options="['presencial', 'online', 'híbrido']"
            placeholder="Informe a modalidade do Curso"
            :error="form.errors.modalidade"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.modalidade">
              {{ form.errors.modalidade }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Documentação Necessária"
          :class="{ 'text-red-400': form.errors.nome }"
        >
          <FormControl
            v-model="form.material_complementar"
            type="text"
            placeholder="Documentação necessária para matrícula."
            :error="form.errors.material_complementar"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.material_complementar">
              {{ form.errors.material_complementar }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Certificação"
          :class="{ 'text-red-400': form.errors.certificacao }"
        >
          <FormControl
            v-model="form.certificacao"
            type="checkbox"
            placeholder="O curso gera certificado?"
            :error="form.errors.certificacao"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.certificacao">
              {{ form.errors.certificacao }}
            </div>
          </FormControl>
        </FormField>

        <BaseDivider />

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="info"
              label="Cadastrar"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>
