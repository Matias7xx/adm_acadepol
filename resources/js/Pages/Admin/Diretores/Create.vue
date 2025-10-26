<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { mdiAccount, mdiArrowLeftBoldOutline, mdiPlus } from '@mdi/js';
import LayoutAuthenticated from '@/Layouts/Admin/LayoutAuthenticated.vue';
import SectionMain from '@/Components/SectionMain.vue';
import SectionTitleLineWithButton from '@/Components/SectionTitleLineWithButton.vue';
import CardBox from '@/Components/CardBox.vue';
import FormField from '@/Components/FormField.vue';
import FormControl from '@/Components/FormControl.vue';
import BaseDivider from '@/Components/BaseDivider.vue';
import BaseButton from '@/Components/BaseButton.vue';
import { ref, computed, watch } from 'vue';

const form = useForm({
  nome: '',
  data_inicio: '',
  data_fim: '',
  historico: '',
  realizacoes: [],
  atual: false,
  imagem_file: null,
});

// Estados para gerenciamento da interface
const novaRealizacao = ref('');
const imagePreview = ref(null);
const dragOver = ref(false);

// Computed para verificar se pode salvar
const canSave = computed(() => {
  return form.nome.trim() && form.data_inicio && !form.processing;
});

// Watch para limpar data fim quando atual = true
watch(
  () => form.atual,
  novoValor => {
    if (novoValor) {
      form.data_fim = '';
    }
  }
);

// Funções para gerenciar realizações
const adicionarRealizacao = () => {
  const realizacao = novaRealizacao.value.trim();
  if (realizacao && !form.realizacoes.includes(realizacao)) {
    form.realizacoes.push(realizacao);
    novaRealizacao.value = '';
  }
};

const removerRealizacao = index => {
  form.realizacoes.splice(index, 1);
};

const adicionarRealizacaoEnter = event => {
  if (event.key === 'Enter') {
    event.preventDefault();
    adicionarRealizacao();
  }
};

// Funções para upload de imagem
const handleImageUpload = event => {
  const file = event.target.files[0];
  if (file) {
    processImageFile(file);
  }
};

const handleImageDrop = event => {
  event.preventDefault();
  dragOver.value = false;

  const file = event.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    processImageFile(file);
  }
};

const processImageFile = file => {
  // Validar tamanho (máx 2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('A imagem deve ter no máximo 2MB');
    return;
  }

  form.imagem_file = file;

  // Criar preview
  const reader = new FileReader();
  reader.onload = e => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeImage = () => {
  form.imagem_file = null;
  imagePreview.value = null;
  // Reset input file
  const fileInput = document.querySelector('input[type="file"]');
  if (fileInput) fileInput.value = '';
};

// Drag and drop handlers
const handleDragOver = event => {
  event.preventDefault();
  dragOver.value = true;
};

const handleDragLeave = () => {
  dragOver.value = false;
};

const submit = () => {
  if (!canSave.value) return;

  form.post(route('admin.directors.store'), {
    preserveScroll: true,
    forceFormData: true,
  });
};
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Novo Diretor" />

    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiAccount"
        title="Cadastrar Diretor"
        main
      >
        <BaseButton
          :route-name="route('admin.directors.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna principal - Informações básicas -->
        <div class="lg:col-span-2">
          <CardBox class="mb-6">
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                  Informações Básicas
                </h3>

                <FormField
                  label="Nome do Diretor"
                  :class="{ 'text-red-400': form.errors.nome }"
                >
                  <FormControl
                    v-model="form.nome"
                    type="text"
                    placeholder="Nome completo do diretor"
                    :error="form.errors.nome"
                    autofocus
                  />
                  <div
                    v-if="form.errors.nome"
                    class="text-red-400 text-sm mt-1"
                  >
                    {{ form.errors.nome }}
                  </div>
                </FormField>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <FormField
                    label="Data de Início"
                    :class="{ 'text-red-400': form.errors.data_inicio }"
                  >
                    <FormControl
                      v-model="form.data_inicio"
                      type="date"
                      :error="form.errors.data_inicio"
                    />
                    <div
                      v-if="form.errors.data_inicio"
                      class="text-red-400 text-sm mt-1"
                    >
                      {{ form.errors.data_inicio }}
                    </div>
                  </FormField>

                  <FormField
                    label="Data de Término"
                    :class="{ 'text-red-400': form.errors.data_fim }"
                  >
                    <FormControl
                      v-model="form.data_fim"
                      type="date"
                      :error="form.errors.data_fim"
                      :disabled="form.atual"
                      :class="{ 'opacity-50': form.atual }"
                    />
                    <div
                      v-if="form.errors.data_fim"
                      class="text-red-400 text-sm mt-1"
                    >
                      {{ form.errors.data_fim }}
                    </div>
                  </FormField>
                </div>

                <FormField label="Status">
                  <div class="flex items-center space-x-3 p-3 rounded-lg">
                    <FormControl
                      v-model="form.atual"
                      type="checkbox"
                      :error="form.errors.atual"
                    />
                    <label
                      class="text-sm font-medium text-gray-700 cursor-pointer"
                      @click="form.atual = !form.atual"
                    >
                      É o diretor atual
                    </label>
                    <div
                      v-if="form.atual"
                      class="flex items-center text-green-600 text-sm"
                    >
                      <svg
                        class="w-4 h-4 mr-1"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      Diretor atual
                    </div>
                  </div>
                  <div
                    v-if="form.errors.atual"
                    class="text-red-400 text-sm mt-1"
                  >
                    {{ form.errors.atual }}
                  </div>
                </FormField>
              </div>

              <BaseDivider />

              <!-- Histórico -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                  Histórico Profissional
                </h3>

                <FormField
                  label="Biografia/Histórico"
                  :class="{ 'text-red-400': form.errors.historico }"
                >
                  <FormControl
                    v-model="form.historico"
                    type="textarea"
                    placeholder="Breve histórico ou biografia do diretor..."
                    :error="form.errors.historico"
                    rows="4"
                  />
                  <div
                    v-if="form.errors.historico"
                    class="text-red-400 text-sm mt-1"
                  >
                    {{ form.errors.historico }}
                  </div>
                </FormField>
              </div>

              <BaseDivider />

              <!-- Realizações -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                  Principais Realizações
                </h3>

                <div class="space-y-3">
                  <div class="flex gap-2">
                    <FormControl
                      v-model="novaRealizacao"
                      placeholder="Digite uma realização e pressione Enter..."
                      class="flex-1"
                      @keydown="adicionarRealizacaoEnter"
                    />
                    <BaseButton
                      type="button"
                      color="info"
                      :icon="mdiPlus"
                      @click="adicionarRealizacao"
                      :disabled="!novaRealizacao.trim()"
                    />
                  </div>

                  <div v-if="form.realizacoes.length > 0" class="space-y-2">
                    <div
                      v-for="(realizacao, index) in form.realizacoes"
                      :key="index"
                      class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg group hover:bg-blue-100 transition-colors"
                    >
                      <div
                        class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"
                      ></div>
                      <span class="flex-1 text-sm text-gray-700">{{
                        realizacao
                      }}</span>
                      <button
                        type="button"
                        @click="removerRealizacao(index)"
                        class="p-1 text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity"
                        :title="`Remover '${realizacao}'`"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                          />
                        </svg>
                      </button>
                    </div>
                  </div>

                  <div v-else class="text-center py-8 text-gray-500">
                    <svg
                      class="w-12 h-12 mx-auto mb-3 text-gray-300"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                      />
                    </svg>
                    <p class="text-sm">Nenhuma realização adicionada</p>
                    <p class="text-xs text-gray-400 mt-1">
                      Digite no campo acima para adicionar
                    </p>
                  </div>

                  <div
                    v-if="form.errors.realizacoes"
                    class="text-red-400 text-sm"
                  >
                    {{ form.errors.realizacoes }}
                  </div>
                </div>
              </div>
            </div>
          </CardBox>
        </div>

        <!-- Sidebar - Upload de imagem -->
        <div class="lg:col-span-1">
          <CardBox>
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">
                Imagem do Diretor
              </h3>

              <!-- Preview da imagem -->
              <div v-if="imagePreview" class="relative">
                <img
                  :src="imagePreview"
                  alt="Preview da imagem"
                  class="w-full h-48 object-cover rounded-lg shadow-sm"
                />
                <button
                  type="button"
                  @click="removeImage"
                  class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                  title="Remover imagem"
                >
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>

              <!-- Área de upload -->
              <div
                v-else
                @drop="handleImageDrop"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                :class="[
                  'border-2 border-dashed rounded-lg p-6 text-center transition-all cursor-pointer hover:border-blue-400 hover:bg-blue-50',
                  dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-300',
                ]"
                @click="$refs.fileInput.click()"
              >
                <svg
                  class="w-12 h-12 mx-auto mb-3 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                  />
                </svg>
                <p class="text-sm text-gray-600 mb-1">
                  Clique ou arraste uma imagem
                </p>
                <p class="text-xs text-gray-400">PNG, JPG até 2MB</p>
              </div>

              <input
                ref="fileInput"
                type="file"
                accept="image/*"
                @change="handleImageUpload"
                class="hidden"
              />

              <div v-if="form.errors.imagem_file" class="text-red-400 text-sm">
                {{ form.errors.imagem_file }}
              </div>
            </div>
          </CardBox>

          <!-- Botões de ação -->
          <div class="mt-6 space-y-3">
            <BaseButton
              type="button"
              color="info"
              label="Cadastrar Diretor"
              class="w-full"
              :class="{ 'opacity-50': !canSave }"
              :disabled="!canSave"
              @click="submit"
            />
            <BaseButton
              :route-name="route('admin.directors.index')"
              label="Cancelar"
              color="light"
              class="w-full"
              :disabled="form.processing"
            />
          </div>
        </div>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
