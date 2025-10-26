<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showPasswordConfirmation = ref(false);
const { toast } = useToast();

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      toast.success('Senha atualizada com sucesso!');
    },
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation');
        passwordInput.value.focus();
        toast.error('Erro ao atualizar senha. Verifique os requisitos.');
      }
      if (form.errors.current_password) {
        form.reset('current_password');
        currentPasswordInput.value.focus();
        toast.error('A senha atual está incorreta.');
      }
    },
  });
};

const toggleCurrentPassword = () => {
  showCurrentPassword.value = !showCurrentPassword.value;
};

const toggleNewPassword = () => {
  showNewPassword.value = !showNewPassword.value;
};

const togglePasswordConfirmation = () => {
  showPasswordConfirmation.value = !showPasswordConfirmation.value;
};
</script>

<template>
  <section>
    <header>
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
        Atualizar Senha
      </h2>

      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        Certifique-se de que sua conta esteja usando uma senha longa e aleatória
        para se manter segura.
      </p>
    </header>

    <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
      <div>
        <InputLabel for="current_password" value="Senha Atual" />

        <div class="relative mt-1">
          <TextInput
            id="current_password"
            ref="currentPasswordInput"
            v-model="form.current_password"
            :type="showCurrentPassword ? 'text' : 'password'"
            class="mt-1 block w-full pr-10"
            autocomplete="current-password"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
            @click="toggleCurrentPassword"
          >
            <svg
              v-if="showCurrentPassword"
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
              <path
                fill-rule="evenodd"
                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                clip-rule="evenodd"
              />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"
                clip-rule="evenodd"
              />
              <path
                d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"
              />
            </svg>
          </button>
        </div>

        <InputError :message="form.errors.current_password" class="mt-2" />
      </div>

      <div>
        <InputLabel for="password" value="Nova Senha" />

        <div class="relative mt-1">
          <TextInput
            id="password"
            ref="passwordInput"
            v-model="form.password"
            :type="showNewPassword ? 'text' : 'password'"
            class="mt-1 block w-full pr-10"
            autocomplete="new-password"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
            @click="toggleNewPassword"
          >
            <svg
              v-if="showNewPassword"
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
              <path
                fill-rule="evenodd"
                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                clip-rule="evenodd"
              />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"
                clip-rule="evenodd"
              />
              <path
                d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"
              />
            </svg>
          </button>
        </div>

        <InputError :message="form.errors.password" class="mt-2" />
        <p class="text-xs text-gray-500 mt-1">
          A senha deve conter pelo menos 8 caracteres e incluir letras
          maiúsculas, minúsculas, números e símbolos.
        </p>
      </div>

      <div>
        <InputLabel for="password_confirmation" value="Confirmar Senha" />

        <div class="relative mt-1">
          <TextInput
            id="password_confirmation"
            v-model="form.password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            class="mt-1 block w-full pr-10"
            autocomplete="new-password"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
            @click="togglePasswordConfirmation"
          >
            <svg
              v-if="showPasswordConfirmation"
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
              <path
                fill-rule="evenodd"
                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                clip-rule="evenodd"
              />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"
                clip-rule="evenodd"
              />
              <path
                d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"
              />
            </svg>
          </button>
        </div>

        <InputError :message="form.errors.password_confirmation" class="mt-2" />
      </div>

      <div class="flex items-center gap-4">
        <PrimaryButton
          :disabled="form.processing"
          class="px-4 py-2 bg-[#bea55a] hover:bg-[#a89048]"
        >
          <span v-if="form.processing">Atualizando...</span>
          <span v-else>Atualizar Senha</span>
        </PrimaryButton>
      </div>
    </form>
  </section>
</template>
