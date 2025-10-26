<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-gray-600/50 flex items-center justify-center z-[60]"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white rounded-lg p-4 w-full max-w-2xl mx-4 max-h-[85vh] overflow-hidden"
    >
      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
          Detalhes da Ocupação
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
          type="button"
        >
          <svg
            class="w-5 h-5"
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

      <!-- Conteúdo -->
      <div class="overflow-y-auto max-h-[calc(85vh-8rem)]">
        <div v-if="!ocupacao" class="text-center py-12">
          <svg
            class="w-12 h-12 mx-auto text-gray-400 mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
            />
          </svg>
          <p class="text-gray-500">Nenhuma ocupação selecionada</p>
        </div>

        <div v-else class="space-y-4">
          <!-- Informações do Hóspede -->
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h4
              class="font-medium text-gray-900 mb-3 text-sm flex items-center"
            >
              <svg
                class="w-4 h-4 mr-2 text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              Informações do Hóspede
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >Nome</label
                >
                <p class="text-gray-900 mt-1 text-sm">
                  {{ ocupacao.hospede_nome || 'Não informado' }}
                </p>
              </div>
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >Tipo</label
                >
                <p class="text-gray-900 mt-1 text-sm">
                  {{ ocupacao.hospede_tipo || 'Não informado' }}
                </p>
              </div>
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >CPF</label
                >
                <p class="text-gray-900 mt-1 text-sm">
                  {{ formatarCPF(ocupacao.hospede_documento) }}
                </p>
              </div>
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >Telefone</label
                >
                <p class="text-gray-900 mt-1 text-sm">
                  {{ ocupacao.hospede_telefone || 'Não informado' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Informações da Ocupação -->
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h4
              class="font-medium text-gray-900 mb-3 text-sm flex items-center"
            >
              <svg
                class="w-4 h-4 mr-2 text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                />
              </svg>
              Detalhes da Estadia
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >Check-in</label
                >
                <p class="text-gray-900 mt-1 text-sm">
                  {{ ocupacao.checkin_at || 'Não informado' }}
                </p>
              </div>
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >Check-out Previsto</label
                >
                <p class="text-gray-900 mt-1 text-sm">
                  {{ ocupacao.checkout_previsto || 'Não informado' }}
                </p>
              </div>
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >Tempo Decorrido</label
                >
                <p class="text-gray-900 mt-1 text-sm">
                  {{ ocupacao.duracao || 'Calculando...' }}
                </p>
              </div>
              <div class="bg-white rounded p-3 border border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase"
                  >Status</label
                >
                <div class="mt-1">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    <div
                      class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"
                    ></div>
                    Ativo
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Observações -->
          <div
            v-if="ocupacao.observacoes"
            class="bg-gray-50 rounded-lg p-4 border border-gray-200"
          >
            <h4
              class="font-medium text-gray-900 mb-3 text-sm flex items-center"
            >
              <svg
                class="w-4 h-4 mr-2 text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              Observações
            </h4>
            <div class="bg-white rounded p-3 border border-gray-200">
              <p class="text-gray-700 text-sm leading-relaxed">
                {{ ocupacao.observacoes }}
              </p>
            </div>
          </div>

          <!-- Informações da Reserva -->
          <div
            v-if="ocupacao.reserva_id"
            class="bg-gray-50 rounded-lg p-4 border border-gray-200"
          >
            <div class="flex items-center justify-between">
              <div>
                <h4 class="font-medium text-gray-900 text-sm flex items-center">
                  <svg
                    class="w-4 h-4 mr-2 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                  </svg>
                  Reserva Vinculada
                </h4>
                <div class="mt-2 space-y-1">
                  <p class="text-sm text-gray-700">
                    ID: #{{ ocupacao.reserva_id }}
                  </p>
                  <p class="text-xs text-gray-600">
                    {{ formatarTipoReserva(ocupacao.reserva_type) }}
                  </p>
                </div>
              </div>
              <button
                @click="$emit('ver-reserva', ocupacao)"
                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors flex items-center gap-2"
                type="button"
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
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <span>Ver Reserva</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div
        class="flex justify-end space-x-2 pt-4 border-t border-gray-200 mt-4"
      >
        <button
          @click="$emit('close')"
          class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
          type="button"
        >
          Fechar
        </button>
        <button
          v-if="ocupacao"
          @click="$emit('checkout', ocupacao)"
          class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded transition-colors flex items-center gap-2"
          type="button"
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
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          <span>Realizar Check-out</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  ocupacao: {
    type: Object,
    default: null,
  },
});

// Emits
const emit = defineEmits(['close', 'checkout', 'ver-reserva']);

// Método para formatar CPF
const formatarCPF = cpf => {
  if (!cpf || cpf === 'Não informado') return 'Não informado';

  // Remove caracteres não numéricos
  const numeros = cpf.replace(/\D/g, '');

  // Formata o CPF
  if (numeros.length === 11) {
    return numeros.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
  }

  return cpf;
};

// Método para formatar tipo de reserva
const formatarTipoReserva = tipo => {
  if (!tipo) return 'Não informado';

  const tipos = {
    'App\\Models\\Alojamento': 'Reserva de Alojamento',
    'App\\Models\\Visitante': 'Reserva de Visitante',
  };

  return tipos[tipo] || tipo;
};
</script>
