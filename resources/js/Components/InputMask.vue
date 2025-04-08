<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    mask: {
        type: String,
        required: true
    },
    maskPlaceholder: {
        type: String,
        default: '_'
    },
    id: {
        type: String,
        default: ''
    },
    type: {
        type: String,
        default: 'text'
    },
    class: {
        type: String,
        default: ''
    },
    autocomplete: {
        type: String,
        default: 'off'
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);

const value = ref(props.modelValue);

onMounted(() => {
    if (input.value) {
        input.value.value = formatWithMask(props.modelValue);
    }
});

// Formato a mascara de acordo com o tipo
const formatWithMask = (val) => {
    if (!val) return '';

    let maskedValue = '';
    let unmaskedValue = val.replace(/\D/g, '');
    let maskIndex = 0;
    let valueIndex = 0;

    while (maskIndex < props.mask.length && valueIndex < unmaskedValue.length) {
        if (props.mask[maskIndex] === '#') {
            maskedValue += unmaskedValue[valueIndex];
            valueIndex++;
        } else {
            maskedValue += props.mask[maskIndex];
        }
        maskIndex++;
    }

    return maskedValue;
};

// Atualiza o valor quando o input muda
const updateValue = (event) => {
    let cursorPosition = event.target.selectionStart;
    let oldLength = event.target.value.length;
    
    // Aplicar máscara
    let newValue = formatWithMask(event.target.value);
    
    // Emitir o valor
    emit('update:modelValue', newValue);
    value.value = newValue;
    
    // Ajustar a posição do cursor após a formatação
    setTimeout(() => {
        if (newValue.length > oldLength) {
            cursorPosition += (newValue.length - oldLength);
        }
        event.target.setSelectionRange(cursorPosition, cursorPosition);
    }, 0);
};
</script>

<template>
    <input
        ref="input"
        :id="id"
        :type="type"
        :class="class"
        :value="modelValue"
        :autocomplete="autocomplete"
        :placeholder="placeholder || mask.replace(/#/g, maskPlaceholder)"
        :required="required"
        :disabled="disabled"
        @input="updateValue"
    />
</template>