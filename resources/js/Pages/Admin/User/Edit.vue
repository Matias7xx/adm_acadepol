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
  password: '',
  password_confirmation: '',
  roles: props.userHasRoles
})
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Atualizar usuário" />
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
        @submit.prevent="form.post(route('admin.user.update', props.user.id))"
      >
        <FormField
          label="Informe o Nome"
          :class="{ 'text-red-400': form.errors.name }"
        >
          <FormControl
            v-model="form.name"
            type="text"
            placeholder="Nome"
            :error="form.errors.name"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.name">
              {{ form.errors.name }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Informe o E-mail"
          :class="{ 'text-red-400': form.errors.email }"
        >
          <FormControl
            v-model="form.email"
            type="text"
            placeholder="E-mail"
            :error="form.errors.email"
          >
            <div class="text-red-400 text-sm" v-if="form.errors.email">
              {{ form.errors.email }}
            </div>
          </FormControl>
        </FormField>

        <FormField
          label="Senha"
          :class="{ 'text-red-400': form.errors.password }"
        >
          <FormControl
            v-model="form.password"
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
