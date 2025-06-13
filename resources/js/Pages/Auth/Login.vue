<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3'
import { mdiAccount, mdiAsterisk, mdiAccountTieHat, mdiEye, mdiEyeOff } from '@mdi/js'
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
import Toast from '../Components/Toast.vue'
import imgUrl from '@/src/assets/logo-acadepol.png'
import { computed, ref, watch } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  canResetPassword: Boolean,
  status: {
    type: String,
    default: null
  },
  intendedAction: String, // Nova propriedade para a ação pretendida
})

// Toast
const { toast } = useToast();

// Título personalizado baseado na ação pretendida
const loginTitle = computed(() => {
    switch (props.intendedAction) {
        case 'matricula_curso':
            return 'Faça login para se matricular no curso';
        case 'reserva_alojamento':
            return 'Faça login para solicitar alojamento';
        default:
            return 'Login';
    }
});

// Texto personalizado baseado na ação pretendida
const loginDescription = computed(() => {
    switch (props.intendedAction) {
        case 'matricula_curso':
            return 'Para completar sua matrícula no curso, por favor faça login com suas credenciais.';
        case 'reserva_alojamento':
            return 'Para solicitar uma reserva de alojamento, por favor faça login com suas credenciais.';
        default:
            return 'Insira suas credenciais para acessar o sistema.';
    }
});

const form = useForm({
  matricula: '',
  password: '',
  remember: []
})

// Estado para controlar visibilidade da senha
const isPasswordVisible = ref(false)

// Observar erros e mostrar no Toast quando forem erros de autenticação
watch(() => usePage().props.errors, (newErrors) => {
  if (newErrors.matricula) {
    // Traduzir a mensagem de erro para português
    let errorMessage = newErrors.matricula;
    if (errorMessage === 'These credentials do not match our records.') {
      errorMessage = 'Credenciais inválidas. Verifique sua matrícula e senha.';
    } else if (errorMessage.includes('Too many login attempts')) {
      // Traduzir mensagem de tentativas excessivas
      const matches = errorMessage.match(/(\d+) seconds/);
      const seconds = matches ? matches[1] : '60';
      errorMessage = `Muitas tentativas de login. Tente novamente em ${seconds} segundos.`;
    }
    
    toast.error(errorMessage);
  }
}, { immediate: true, deep: true });

const submit = () => {
  form
    .transform(data => ({
      ...data,
      remember: form.remember && form.remember.length ? 'on' : ''
    }))
    .post(route('login'), {
      onFinish: () => form.reset('password')
    })
}
</script>

<template>
  <LayoutGuest>
    <Head :title="loginTitle" />
    <Toast />
    
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
      
        <!-- Somente para erros que não são de autenticação -->
        <!-- <FormValidationErrors /> -->
        
        <NotificationBarInCard 
          v-if="status"
          color="info"
        >
          {{ status }}
        </NotificationBarInCard>
        <div class="w-full mt-2 px-1 py-1 overflow-hidden text-center flex justify-center">
          <p class="text-gray-400 mb-3">{{ loginDescription }}</p>
        </div>
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
          <div class="relative">
            <FormControl
              v-model="form.password"
              :icon="mdiAsterisk"
              :type="isPasswordVisible ? 'text' : 'password'"
              id="password"
              autocomplete="current-password"
              required
            />
            <button 
              type="button" 
              @click="isPasswordVisible = !isPasswordVisible" 
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700"
              tabindex="-1"
            >
              <svg 
                xmlns="http://www.w3.org/2000/svg" 
                class="h-5 w-5" 
                viewBox="0 0 20 20" 
                fill="currentColor"
              >
                <path 
                  v-if="!isPasswordVisible" 
                  d="M10 12a2 2 0 100-4 2 2 0 000 4z" 
                />
                <path 
                  v-if="!isPasswordVisible" 
                  d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" 
                />
                <path 
                  v-else 
                  fill-rule="evenodd" 
                  d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0020 10c-1.274-4.057-5.064-7-9.542-7-1.81 0-3.532.518-5.014 1.454l-1.737-1.737z" 
                  clip-rule="evenodd" 
                />
              </svg>
            </button>
          </div>
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
              :label="form.processing ? 'Entrando...' : 'Login'"
              :class="{ 'opacity-25': form.processing }"
              :disabled="form.processing"
            />
            <BaseButton
              v-if="canResetPassword"
              :href="route('password.request')"
              class="bg-[#bea54a] text-[black] hover:bg-[#a38e5d]"
              outline
              label="Esqueci a senha"
            />
          </BaseButtons>

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