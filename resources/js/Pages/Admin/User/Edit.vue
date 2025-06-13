<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiAccountKey,
  mdiArrowLeftBoldOutline,
  mdiAccount,
  mdiEmail,
  mdiIdentifier,
  mdiIdCard,
  mdiOfficeBuilding,
  mdiBriefcase,
  mdiCalendar,
  mdiPhone,
  mdiKey
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
import InputMask from '@/Components/InputMask.vue'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({}),
  },
  roles: {
    type: Object,
    default: () => ({}),
  },
  userHasRoles: {
    type: Object,
    default: () => ({}),
  }
})

const form = useForm({
  _method: 'put',
  name: props.user.name,
  email: props.user.email,
  matricula: props.user.matricula,
  cpf: props.user.cpf || '',
  cargo: props.user.cargo || '',
  orgao: props.user.orgao || '',
  lotacao: props.user.lotacao || '',
  telefone: props.user.telefone || '',
  data_nascimento: props.user.data_nascimento ? props.user.data_nascimento.split('T')[0] : '',
  password: '',
  password_confirmation: '',
  roles: props.userHasRoles
})

const submitEdit = () => {
  // Remove caracteres não numéricos do CPF
  form.cpf = form.cpf ? form.cpf.replace(/\D/g, '') : '';
  
  // Remove caracteres não numéricos do telefone
  form.telefone = form.telefone ? form.telefone.replace(/\D/g, '') : '';
  
  // Enviar formulário - Correto: usando props.user.id em vez de user.id
  form.put(route('admin.user.update', props.user.id));
}

</script>

<template>
  <LayoutAuthenticated>
    <Head title="Atualizar Usuário" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccountKey"
        title="Atualizar Usuário"
        main
      >
        <BaseButton
          :route-name="route('admin.user.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      <CardBox
        form
        @submit.prevent="submitEdit"
      >
        <FormField
          label="Nome"
          :class="{ 'text-red-400': form.errors.name }"
        >
          <FormControl
            v-model="form.name"
            :icon="mdiAccount"
            type="text"
            placeholder="Informe o Nome"
            :error="form.errors.name"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.name">
              {{ form.errors.name }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="E-mail"
          :class="{ 'text-red-400': form.errors.email }"
        >
          <FormControl
            v-model="form.email"
            :icon="mdiEmail"
            type="text"
            placeholder="Informe o E-mail"
            :error="form.errors.email"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.email">
              {{ form.errors.email }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Matrícula"
          :class="{ 'text-red-400': form.errors.matricula }"
        >
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
              <svg
                class="w-5 h-5 text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path :d="mdiIdentifier"></path>
              </svg>
            </span>
            <InputMask
              v-model="form.matricula"
              mask="#######"
              class="px-3 py-2 h-12 bg-white dark:bg-slate-800 border border-gray-700 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full pl-10"
              placeholder="Informe a Matrícula (7 dígitos)"
            />
          </div>
          <div class="text-red-400 text-sm" v-if="form.errors.matricula">
            {{ form.errors.matricula }}
          </div>
        </FormField>

        <FormField
          label="CPF"
          :class="{ 'text-red-400': form.errors.cpf }"
        >
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
              <svg
                class="w-5 h-5 text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path :d="mdiIdCard"></path>
              </svg>
            </span>
            <InputMask
              v-model="form.cpf"
              mask="###.###.###-##"
              class="px-3 py-2 h-12 bg-white dark:bg-slate-800 border border-gray-700 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full pl-10"
              placeholder="Informe o CPF"
            />
          </div>
          <div class="text-red-400 text-sm" v-if="form.errors.cpf">
            {{ form.errors.cpf }}
          </div>
        </FormField>

        <FormField
          label="Cargo"
          :class="{ 'text-red-400': form.errors.cargo }"
        >
          <FormControl
            v-model="form.cargo"
            :icon="mdiBriefcase"
            type="text"
            placeholder="Informe o Cargo"
            :error="form.errors.cargo"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.cargo">
              {{ form.errors.cargo }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Órgão"
          :class="{ 'text-red-400': form.errors.orgao }"
        >
          <FormControl
            v-model="form.orgao"
            :icon="mdiOfficeBuilding"
            type="text"
            placeholder="Informe o Órgão"
            :error="form.errors.orgao"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.orgao">
              {{ form.errors.orgao }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Lotação"
          :class="{ 'text-red-400': form.errors.lotacao }"
        >
          <FormControl
            v-model="form.lotacao"
            :icon="mdiOfficeBuilding"
            type="text"
            placeholder="Informe a Lotação"
            :error="form.errors.lotacao"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.lotacao">
              {{ form.errors.lotacao }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Telefone"
          :class="{ 'text-red-400': form.errors.telefone }"
        >
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
              <svg
                class="w-5 h-5 text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path :d="mdiPhone"></path>
              </svg>
            </span>
            <InputMask
              v-model="form.telefone"
              mask="(##) #####-####"
              class="px-3 py-2 h-12 bg-white dark:bg-slate-800 border border-gray-700 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full pl-10"
              placeholder="Informe o Telefone"
            />
          </div>
          <div class="text-red-400 text-sm" v-if="form.errors.telefone">
            {{ form.errors.telefone }}
          </div>
        </FormField>

        <FormField
          label="Data de Nascimento"
          :class="{ 'text-red-400': form.errors.data_nascimento }"
        >
          <FormControl
            v-model="form.data_nascimento"
            :icon="mdiCalendar"
            type="date"
            placeholder="Informe a Data de Nascimento"
            :error="form.errors.data_nascimento"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.data_nascimento">
              {{ form.errors.data_nascimento }}
            </div>
          </FormControl>
        </FormField>

        <BaseDivider />

        <FormField
          label="Senha"
          :class="{ 'text-red-400': form.errors.password }"
        >
          <FormControl
            v-model="form.password"
            :icon="mdiKey"
            type="password"
            placeholder="Informe a Senha"
            :error="form.errors.password"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.password">
              {{ form.errors.password }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Confirmação de Senha"
          :class="{ 'text-red-400': form.errors.password }"
        >
          <FormControl
            v-model="form.password_confirmation"
            :icon="mdiKey"
            type="password"
            placeholder="Confirme a Senha"
            :error="form.errors.password"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.password">
              {{ form.errors.password }}
            </div>
          </FormControl>
        </FormField>

        <BaseDivider />

        <FormField
          label="Função"
          wrap-body
        >
          <FormCheckRadioGroup
            v-model="form.roles"
            name="roles"
            is-column
            :options="props.roles"
          />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton
              type="submit"
              color="info"
              label="Atualizar"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
            />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>