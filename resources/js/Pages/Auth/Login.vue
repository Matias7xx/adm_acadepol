<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'
import { mdiAccount, mdiAsterisk, mdiAccountTieHat } from '@mdi/js'
import LayoutGuest from '@/Layouts/Admin/LayoutGuest.vue'
import SectionFullScreen from '@/Components/SectionFullScreen.vue'
import CardBox from '@/Components/CardBox.vue'
import FormCheckRadioGroup from '@/Components/FormCheckRadioGroup.vue'
import FormField from '@/Components/FormField.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseDivider from '@/Components/BaseDivider.vue'
import BaseButton from '@/Components/BaseButton.vue'
import BaseButtons from '@/Components/BaseButtons.vue'
import FormValidationErrors from '@/Components/FormValidationErrors.vue'
import NotificationBarInCard from '@/Components/NotificationBarInCard.vue'
import BaseLevel from '@/Components/BaseLevel.vue'
import imgUrl from '@/src/assets/logo-acadepol.png'

const props = defineProps({
  canResetPassword: Boolean,
  status: {
    type: String,
    default: null
  }
})

const form = useForm({
  //email: '',
  matricula: '',
  password: '',
  remember: []
})

const submit = () => {
  form
    .transform(data => ({
      ... data,
      remember: form.remember && form.remember.length ? 'on' : ''
    }))
    .post(route('login'), {
      onFinish: () => form.reset('password'),
    })
}
</script>

<template>
  <LayoutGuest>
    <Head title="Login" />
    
    <SectionFullScreen
      v-slot="{ cardClass }"
      bg="white"
    >
      <CardBox
        :class="cardClass"
        form
        @submit.prevent="submit"
      >
      <div class="flex items-center mt-2 justify-center "><img :src="imgUrl" class=""/></div>
        <FormValidationErrors />
        <NotificationBarInCard 
          v-if="status"
          color="info"
        >
          {{ status }}
        </NotificationBarInCard>

        <FormField
          label="Matrícula"
          label-for="matricula"
          help="Por favor, informe sua matrícula"
        >
          <FormControl
            v-model="form.matricula"
            :icon="mdiAccountTieHat"
            id="matricula"
            autocomplete="matricula"
            type="text"
            required
          />
        </FormField>

        <FormField
          label="Senha"
          label-for="password"
          help="Por favor, informe sua senha"
        >
          <FormControl
            v-model="form.password"
            :icon="mdiAsterisk"
            type="password"
            id="password"
            autocomplete="current-password"
            required
          />
        </FormField>

        <FormCheckRadioGroup
          v-model="form.remember"
          name="remember"
          :options="{ remember: 'Lembrar-me' }"
        />

        <BaseDivider />

        <BaseLevel>
          <BaseButtons>
            <BaseButton
              class="bg-[#bea54a] text-[black] hover:bg-[#a38e5d]"
              type="submit"
              label="Login"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
            />
            <BaseButton
              v-if="canResetPassword"
              :route-name="route('password.request')"
              class="bg-[#bea54a] text-[black] hover:bg-[#a38e5d]"
              outline
              label="Esqueci a senha"
            />
          </BaseButtons>
         <!--  <Link
            :href="route('register')"
          >
            Cadastrar
          </Link> -->

          <Link
            :href="route('home')"
            class="hover:text-[#a38e5d]"
          >
            Voltar
          </Link>
        </BaseLevel>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
