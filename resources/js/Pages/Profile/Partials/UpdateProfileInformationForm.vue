<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  mustVerifyEmail: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const user = usePage().props.auth.user;
const { toast } = useToast();

// Função para formatar data do backend (YYYY-MM-DD) para input date
const formatDateForInput = dateString => {
  if (!dateString) return '';
  // Se já está no formato YYYY-MM-DD, retorna
  if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
    return dateString;
  }
  // Se vier como objeto Date ou string, converte
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
};

const form = useForm({
  name: user?.name || '',
  email: user?.email || '',
  matricula: user?.matricula || '',
  cargo: user?.cargo || '',
  lotacao: user?.lotacao || '',
  telefone: user?.telefone || '',
  documento: user?.cpf || '',
  data_nascimento: formatDateForInput(user?.data_nascimento) || '',
});

const updateProfileInformation = () => {
  form.patch(route('profile.update'), {
    errorBag: 'updateProfileInformation',
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Informações atualizadas com sucesso!');
    },
    onError: () => {
      toast.error('Ocorreu um erro ao atualizar suas informações.');
    },
  });
};

const resetForm = () => {
  form.name = user?.name || '';
  form.email = user?.email || '';
  form.matricula = user?.matricula || '';
  form.cargo = user?.cargo || '';
  form.lotacao = user?.lotacao || '';
  form.telefone = user?.telefone || '';
  form.documento = user?.cpf || '';
  form.data_nascimento = formatDateForInput(user?.data_nascimento) || '';
  toast.info('Formulário restaurado com os valores originais');
};
</script>

<template>
  <section>
    <form @submit.prevent="updateProfileInformation" class="space-y-6">
      <div class="grid grid-cols-1 gap-6 mt-4 sm:grid-cols-2">
        <div>
          <InputLabel for="name" value="Nome Completo" />
          <TextInput
            id="name"
            type="text"
            class="mt-1 block w-full bg-slate-100"
            v-model="form.name"
            required
            autofocus
            readonly
            autocomplete="name"
          />
          <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
          <InputLabel for="matricula" value="Matrícula" />
          <TextInput
            id="matricula"
            type="text"
            class="mt-1 block w-full bg-slate-100"
            v-model="form.matricula"
            readonly
          />
          <InputError class="mt-2" :message="form.errors.matricula" />
        </div>

        <div>
          <InputLabel for="email" value="Email" />
          <TextInput
            id="email"
            type="email"
            class="mt-1 block w-full bg-slate-100"
            readonly
            v-model="form.email"
            required
            autocomplete="username"
          />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div>
          <InputLabel for="telefone" value="Telefone" />
          <TextInput
            id="telefone"
            type="tel"
            class="mt-1 block w-full"
            v-model="form.telefone"
            placeholder="(00) 00000-0000"
          />
          <InputError class="mt-2" :message="form.errors.telefone" />
        </div>

        <div>
          <InputLabel for="cargo" value="Cargo/Função" />
          <TextInput
            id="cargo"
            type="text"
            class="mt-1 block w-full bg-slate-100"
            readonly
            v-model="form.cargo"
          />
          <InputError class="mt-2" :message="form.errors.cargo" />
        </div>

        <div>
          <InputLabel for="lotacao" value="Lotação" />
          <TextInput
            id="lotacao"
            type="text"
            readonly
            class="mt-1 block w-full bg-slate-100"
            v-model="form.lotacao"
          />
          <InputError class="mt-2" :message="form.errors.lotacao" />
        </div>

        <div>
          <InputLabel for="documento" value="CPF" />
          <TextInput
            id="documento"
            type="text"
            class="mt-1 block w-full bg-slate-100"
            readonly
            v-model="form.documento"
          />
          <InputError class="mt-2" :message="form.errors.documento" />
        </div>

        <!-- Data de Nascimento -->
        <div>
          <InputLabel for="data_nascimento" value="Data de Nascimento" />
          <TextInput
            id="data_nascimento"
            type="date"
            class="mt-1 block w-full"
            v-model="form.data_nascimento"
          />
          <InputError class="mt-2" :message="form.errors.data_nascimento" />
        </div>
      </div>

      <div v-if="mustVerifyEmail && user?.email_verified_at === null">
        <p class="text-sm mt-2 text-gray-800">
          Seu e-mail não foi verificado.
          <Link
            :href="route('verification.send')"
            method="post"
            as="button"
            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#bea55a]"
          >
            Clique aqui para reenviar o e-mail de verificação.
          </Link>
        </p>

        <div
          v-show="status === 'verification-link-sent'"
          class="mt-2 font-medium text-sm text-green-600"
        >
          Um novo link de verificação foi enviado para seu endereço de e-mail.
        </div>
      </div>

      <div class="flex items-center justify-end mt-6">
        <button
          type="button"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#bea55a] mr-3"
          @click="resetForm"
          :disabled="form.processing"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 mr-1"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Restaurar
        </button>

        <PrimaryButton
          :disabled="form.processing"
          class="bg-[#bea55a] hover:bg-[#a89048]"
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
          <span v-if="form.processing">Salvando...</span>
          <span v-else>Salvar Alterações</span>
        </PrimaryButton>
      </div>
    </form>
  </section>
</template>
