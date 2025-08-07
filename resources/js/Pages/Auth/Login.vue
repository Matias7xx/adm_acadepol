<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3'
import { mdiAsterisk, mdiAccountTieHat, mdiEye, mdiEyeOff } from '@mdi/js'
import LayoutGuest from '@/Layouts/Admin/LayoutGuest.vue'
import SectionFullScreen from '@/Components/SectionFullScreen.vue'
import CardBox from '@/Components/CardBox.vue'
import FormCheckRadioGroup from '@/Components/FormCheckRadioGroup.vue'
import FormField from '@/Components/FormField.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseDivider from '@/Components/BaseDivider.vue'
import BaseButton from '@/Components/BaseButton.vue'
import BaseButtons from '@/Components/BaseButtons.vue'
import NotificationBarInCard from '@/Components/NotificationBarInCard.vue'
import BaseLevel from '@/Components/BaseLevel.vue'
import Toast from '../Components/Toast.vue'
import imgUrl from '@/src/assets/logo-acadepol.png'
import { computed, ref, watch } from 'vue';
import { useToast } from '@/Composables/useToast';
import BaseIcon from '@/Components/BaseIcon.vue'

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
      <div class="flex items-center mt-2 justify-center">
        <img :src="imgUrl" class="w-auto h-16 sm:h-20 md:h-24 max-w-full" />
      </div>
      
        <!-- Somente para erros que não são de autenticação -->
        <!-- <FormValidationErrors /> -->
        
        <NotificationBarInCard 
          v-if="status"
          color="info"
        >
          {{ status }}
        </NotificationBarInCard>
        <div class="w-full mt-2 px-1 py-1 overflow-hidden text-center flex justify-center">
          <p class="text-gray-400 mb-3 text-sm sm:text-base px-2">{{ loginDescription }}</p>
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
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 transition-colors duration-200"
              :aria-label="isPasswordVisible ? 'Ocultar senha' : 'Mostrar senha'"
              tabindex="-1"
            >
              <BaseIcon 
                :path="isPasswordVisible ? mdiEyeOff : mdiEye" 
                class="h-5 w-5"
                size="20"
              />
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
          <BaseButtons class="flex-col sm:flex-row">
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