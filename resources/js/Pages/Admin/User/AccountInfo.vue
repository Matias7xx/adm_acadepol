<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiAccount,
  mdiAccountCircle,
  mdiLock,
  mdiMail,
  mdiAsterisk,
  mdiFormTextboxPassword,
  mdiArrowLeftBoldOutline,
  mdiAlertBoxOutline,
  mdiIdCard,
  mdiBriefcase,
  mdiOfficeBuilding,
  mdiPhone,
  mdiCalendar,
} from "@mdi/js"
import SectionMain from "@/Components/SectionMain.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseDivider from "@/Components/BaseDivider.vue"
import FormField from "@/Components/FormField.vue"
import FormControl from "@/Components/FormControl.vue"
import BaseButton from "@/Components/BaseButton.vue"
import BaseButtons from "@/Components/BaseButtons.vue"
import NotificationBar from "@/Components/NotificationBar.vue"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import InputMask from "@/Components/InputMask.vue"

const props = defineProps({
  user: {
    type: Object,
    default: () => ({}),
  },
})

const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
  matricula: props.user.matricula,
  cpf: props.user.cpf || '',
  cargo: props.user.cargo || '',
  orgao: props.user.orgao || '',
  telefone: props.user.telefone || '',
  data_nascimento: props.user.data_nascimento ? props.user.data_nascimento.split('T')[0] : '',
})

const passwordForm = useForm({
  old_password: null,
  new_password: null,
  confirm_password: null,
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccount" title="Perfil" main>
        <BaseButton
          :route-name="route('admin.dashboard')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      <NotificationBar
        :key="Date.now()"
        v-if="$page.props.flash.message"
        color="success"
        :icon="mdiAlertBoxOutline"
      >
        {{ $page.props.flash.message }}
      </NotificationBar>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <CardBox
          title="Editar Perfil"
          :icon="mdiAccountCircle"
          form
          @submit.prevent="profileForm.post(route('admin.account.info.store'))"
        >
          <FormField
            label="Nome"
            help="Obrigatório. Seu nome"
            :class="{ 'text-red-400': profileForm.errors.name }"
          >
            <FormControl
              v-model="profileForm.name"
              :icon="mdiAccount"
              name="name"
              required
              :error="profileForm.errors.name"
            >
              <div class="text-red-400 text-sm" v-if="profileForm.errors.name">
                {{ profileForm.errors.name }}
              </div>
            </FormControl>
          </FormField>
          
          <FormField
            label="E-mail"
            help="Obrigatório. Seu e-mail"
            :class="{ 'text-red-400': profileForm.errors.email }"
          >
            <FormControl
              v-model="profileForm.email"
              :icon="mdiMail"
              type="email"
              name="email"
              required
              :error="profileForm.errors.email"
            >
              <div class="text-red-400 text-sm" v-if="profileForm.errors.email">
                {{ profileForm.errors.email }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="Matrícula"
            help="Obrigatório. Sua matrícula"
            :class="{ 'text-red-400': profileForm.errors.matricula }"
          >
            <FormControl
              v-model="profileForm.matricula"
              :icon="mdiIdCard"
              name="matricula"
              required
              :error="profileForm.errors.matricula"
            >
              <div class="text-red-400 text-sm" v-if="profileForm.errors.matricula">
                {{ profileForm.errors.matricula }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="CPF"
            :class="{ 'text-red-400': profileForm.errors.cpf }"
          >
            <div class="relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">
                  <svg viewBox="0 0 24 24" width="16" height="16" class="inline-block">
                    <path fill="currentColor" d="M2,3H22C23.05,3 24,3.95 24,5V19C24,20.05 23.05,21 22,21H2C0.95,21 0,20.05 0,19V5C0,3.95 0.95,3 2,3M14,6V7H22V6H14M14,8V9H21.5L22,9V8H14M14,10V11H21V10H14M8,13.91C6,13.91 2,15 2,17V18H14V17C14,15 10,13.91 8,13.91M8,6A3,3 0 0,0 5,9A3,3 0 0,0 8,12A3,3 0 0,0 11,9A3,3 0 0,0 8,6Z"></path>
                  </svg>
                </span>
              </div>
              <InputMask
                v-model="profileForm.cpf"
                mask="###.###.###-##"
                class="
                  rounded-md
                  shadow-sm
                  border-gray-300
                  focus:border-indigo-300
                  focus:ring
                  focus:ring-indigo-200
                  focus:ring-opacity-50
                  block 
                  w-full 
                  pl-9 
                  pr-12
                  py-2
                "
                placeholder="000.000.000-00"
                name="cpf"
              />
            </div>
            <div class="text-red-400 text-sm" v-if="profileForm.errors.cpf">
              {{ profileForm.errors.cpf }}
            </div>
          </FormField>

          <FormField
            label="Cargo"
            :class="{ 'text-red-400': profileForm.errors.cargo }"
          >
            <FormControl
              v-model="profileForm.cargo"
              :icon="mdiBriefcase"
              name="cargo"
              :error="profileForm.errors.cargo"
            >
              <div class="text-red-400 text-sm" v-if="profileForm.errors.cargo">
                {{ profileForm.errors.cargo }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="Órgão"
            :class="{ 'text-red-400': profileForm.errors.orgao }"
          >
            <FormControl
              v-model="profileForm.orgao"
              :icon="mdiOfficeBuilding"
              name="orgao"
              :error="profileForm.errors.orgao"
            >
              <div class="text-red-400 text-sm" v-if="profileForm.errors.orgao">
                {{ profileForm.errors.orgao }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="Telefone"
            :class="{ 'text-red-400': profileForm.errors.telefone }"
          >
            <div class="relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">
                  <svg viewBox="0 0 24 24" width="16" height="16" class="inline-block">
                    <path fill="currentColor" d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z"></path>
                  </svg>
                </span>
              </div>
              <InputMask
                v-model="profileForm.telefone"
                mask="(##) #####-####"
                class="
                  rounded-md
                  shadow-sm
                  border-gray-300
                  focus:border-indigo-300
                  focus:ring
                  focus:ring-indigo-200
                  focus:ring-opacity-50
                  block 
                  w-full 
                  pl-9 
                  pr-12
                  py-2
                "
                placeholder="(00) 00000-0000"
                name="telefone"
              />
            </div>
            <div class="text-red-400 text-sm" v-if="profileForm.errors.telefone">
              {{ profileForm.errors.telefone }}
            </div>
          </FormField>

          <FormField
            label="Data de Nascimento"
            :class="{ 'text-red-400': profileForm.errors.data_nascimento }"
          >
            <FormControl
              v-model="profileForm.data_nascimento"
              :icon="mdiCalendar"
              type="date"
              name="data_nascimento"
              :error="profileForm.errors.data_nascimento"
            >
              <div class="text-red-400 text-sm" v-if="profileForm.errors.data_nascimento">
                {{ profileForm.errors.data_nascimento }}
              </div>
            </FormControl>
          </FormField>

          <template #footer>
            <BaseButtons>
              <BaseButton color="info" type="submit" label="Salvar" />
            </BaseButtons>
          </template>
        </CardBox>

        <CardBox
          title="Alterar Senha"
          :icon="mdiLock"
          form
          @submit.prevent="
            passwordForm.post(route('admin.account.password.store'), {
              preserveScroll: true,
              onSuccess: () => passwordForm.reset(),
            })
          "
        >
          <FormField
            label="Senha atual"
            help="Obrigatório. Sua senha atual"
            :class="{ 'text-red-400': passwordForm.errors.old_password }"
          >
            <FormControl
              v-model="passwordForm.old_password"
              :icon="mdiAsterisk"
              name="old_password"
              type="password"
              required
              :error="passwordForm.errors.old_password"
            >
              <div
                class="text-red-400 text-sm"
                v-if="passwordForm.errors.old_password"
              >
                {{ passwordForm.errors.old_password }}
              </div>
            </FormControl>
          </FormField>

          <BaseDivider />

          <FormField
            label="Nova senha"
            help="Obrigatório. Nova senha"
            :class="{ 'text-red-400': passwordForm.errors.new_password }"
          >
            <FormControl
              v-model="passwordForm.new_password"
              :icon="mdiFormTextboxPassword"
              name="new_password"
              type="password"
              required
              :error="passwordForm.errors.new_password"
            >
              <div
                class="text-red-400 text-sm"
                v-if="passwordForm.errors.new_password"
              >
                {{ passwordForm.errors.new_password }}
              </div>
            </FormControl>
          </FormField>

          <FormField
            label="Confirmar senha"
            help="Obrigatório. Repita a nova senha"
            :class="{ 'text-red-400': passwordForm.errors.confirm_password }"
          >
            <FormControl
              v-model="passwordForm.confirm_password"
              :icon="mdiFormTextboxPassword"
              name="confirm_password"
              type="password"
              required
              :error="passwordForm.errors.confirm_password"
            >
              <div
                class="text-red-400 text-sm"
                v-if="passwordForm.errors.confirm_password"
              >
                {{ passwordForm.errors.confirm_password }}
              </div>
            </FormControl>
          </FormField>

          <template #footer>
            <BaseButtons>
              <BaseButton type="submit" color="info" label="Salvar" />
            </BaseButtons>
          </template>
        </CardBox>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>