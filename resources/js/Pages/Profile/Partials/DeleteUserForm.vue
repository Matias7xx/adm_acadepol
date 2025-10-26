<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const showPassword = ref(false);
const confirmationText = ref('');
const isButtonDisabled = ref(true);
const showDeletionWarning = ref(false);
const { toast } = useToast();

const form = useForm({
  password: '',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;
  nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
      toast.success('Sua conta foi excluída com sucesso');
    },
    onError: () => {
      passwordInput.value.focus();
      toast.error(
        'Não foi possível excluir sua conta. Verifique sua senha e tente novamente.'
      );
    },
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;
  confirmationText.value = '';
  isButtonDisabled.value = true;
  showDeletionWarning.value = false;
  form.reset();
};

const toggleShowPassword = () => {
  showPassword.value = !showPassword.value;
};

const toggleDeletionWarning = () => {
  showDeletionWarning.value = !showDeletionWarning.value;
};

const updateConfirmationText = text => {
  confirmationText.value = text;
  isButtonDisabled.value = text.toLowerCase().trim() !== 'excluir conta';
};
</script>

<template>
  <section class="space-y-6">
    <header>
      <h2 class="text-lg font-medium text-gray-900">Excluir Conta</h2>

      <p class="mt-1 text-sm text-gray-600">
        Uma vez que sua conta for excluída, todos os seus recursos e dados serão
        permanentemente apagados. Antes de excluir sua conta, por favor, faça o
        download de quaisquer dados ou informações que deseja manter.
      </p>
    </header>

    <DangerButton @click="confirmUserDeletion" class="flex items-center">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5 mr-2"
        viewBox="0 0 20 20"
        fill="currentColor"
      >
        <path
          fill-rule="evenodd"
          d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
          clip-rule="evenodd"
        />
      </svg>
      Excluir Conta
    </DangerButton>

    <Modal :show="confirmingUserDeletion" @close="closeModal" max-width="md">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Tem certeza que deseja excluir sua conta?
        </h2>

        <p class="mt-3 text-sm text-gray-600">
          Esta ação é irreversível. Uma vez que sua conta for excluída, todos os
          seus recursos e dados serão permanentemente apagados e você não terá
          mais acesso ao sistema.
        </p>

        <div
          class="mt-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700"
          :class="showDeletionWarning ? 'block' : 'block'"
        >
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg
                class="h-5 w-5 text-red-500"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium">Atenção</h3>
              <div class="mt-2 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                  <li>Todos os seus dados pessoais serão excluídos</li>
                  <li>
                    Você perderá acesso a todas as suas matrículas e cursos
                  </li>
                  <li>
                    Seu histórico e certificados não poderão ser recuperados
                  </li>
                  <li>Esta operação <strong>não pode ser desfeita</strong></li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6">
          <InputLabel for="password" value="Senha" />

          <div class="relative mt-1">
            <TextInput
              id="password"
              ref="passwordInput"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              class="block w-full pr-10"
              placeholder="Digite sua senha para confirmar"
              @keyup.enter="!isButtonDisabled && deleteUser()"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
              @click="toggleShowPassword"
            >
              <svg
                v-if="showPassword"
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
        </div>

        <div class="mt-6">
          <InputLabel for="confirmation" value="Confirmação" />
          <p class="text-sm text-gray-600 mb-2">
            Digite "excluir conta" para confirmar que deseja prosseguir:
          </p>
          <TextInput
            id="confirmation"
            v-model="confirmationText"
            type="text"
            class="block w-full"
            placeholder="excluir conta"
            @input="updateConfirmationText($event.target.value)"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>

          <DangerButton
            :class="{ 'opacity-25': form.processing || isButtonDisabled }"
            :disabled="form.processing || isButtonDisabled"
            @click="deleteUser"
          >
            <svg
              v-if="form.processing"
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            <span v-if="form.processing">Excluindo...</span>
            <span v-else>Excluir Conta</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </section>
</template>
