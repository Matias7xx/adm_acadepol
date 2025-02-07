<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'
import { mdiEmail, mdiFormTextboxPassword } from '@mdi/js'
import LayoutGuest from '@/Layouts/Admin/LayoutGuest.vue'
import SectionFullScreen from '@/Components/SectionFullScreen.vue'
import CardBox from '@/Components/CardBox.vue'
import FormField from '@/Components/FormField.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseDivider from '@/Components/BaseDivider.vue'
import BaseButton from '@/Components/BaseButton.vue'
import FormValidationErrors from '@/Components/FormValidationErrors.vue'

const props = defineProps({
  email: {
    type: String,
    default: null
  },
  token: {
    type: String,
    default: null
  }
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form
    .post(route('password.update'), {
      onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
  <LayoutGuest>
    <Head title="Reset Password" />

    <SectionFullScreen
      v-slot="{ cardClass }"
      bg="white"
    >
      <CardBox
        :class="cardClass"
        form
        @submit.prevent="submit"
      >
        <FormValidationErrors />

        <FormField
          label="E-mail"
          label-for="email"
          help="Por favor, informe seu email"
        >
          <FormControl
            v-model="form.email"
            :icon="mdiEmail"
            autocomplete="email"
            type="email"
            id="email"
            required
          />
        </FormField>

        <FormField
          label="Password"
          label-for="password"
          help="Por favor, informe uma nova senha"
        >
          <FormControl
            v-model="form.password"
            :icon="mdiFormTextboxPassword"
            type="password"
            autocomplete="new-password"
            id="password"
            required
          />
        </FormField>

        <FormField
          label="Confirm Password"
          label-for="password_confirmation"
          help="Por favor, confirme a nova senha"
        >
          <FormControl
            v-model="form.password_confirmation"
            :icon="mdiFormTextboxPassword"
            type="password"
            autocomplete="new-password"
            id="password_confirmation"
            required
          />
        </FormField>

        <BaseDivider />

        <BaseButton
          type="submit"
          color="info"
          label="Resetar senha"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing"
        />
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
