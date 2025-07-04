<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { useMainStore } from '@/Stores/main'
import FormControlIcon from '@/Components/FormControlIcon.vue'

const props = defineProps({
  name: {
    type: String,
    default: null
  },
  id: {
    type: String,
    default: null
  },
  autocomplete: {
    type: String,
    default: null
  },
  placeholder: {
    type: String,
    default: null
  },
  inputmode: {
    type: String,
    default: null
  },
  icon: {
    type: String,
    default: null
  },
  options: {
    type: [Array, Object],
    default: null
  },
  type: {
    type: String,
    default: 'text'
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
    default: ''
  },
  required: Boolean,
  borderless: Boolean,
  transparent: Boolean,
  ctrlKFocus: Boolean,
  readonly: Boolean,
  disabled: Boolean,
  error: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'setRef'])

const computedValue = computed({
  get: () => props.modelValue,
  set: value => {
    emit('update:modelValue', value)
  }
})

const inputElClass = computed(() => {
  const base = [
    'px-3 py-2 max-w-full focus:ring focus:outline-none rounded w-full',
    'dark:placeholder-gray-400',
    computedType.value === 'textarea' ? 'h-24' : 'h-12',
    props.borderless ? 'border-0' : 'border',
    props.transparent ? 'bg-transparent' : 'bg-white dark:bg-slate-800',
    props.error ? 'border-red-400' : 'border-gray-700'
  ]

  if (props.icon) {
    base.push('pl-10')
  }

  if (props.readonly) {
    base.push('bg-gray-100 dark:bg-gray-700 cursor-not-allowed')
  }
  
  if (props.disabled) {
    base.push('bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-60')
  }

  return base
})

const computedType = computed(() => props.options ? 'select' : props.type)

const controlIconH = computed(() => props.type === 'textarea' ? 'h-full' : 'h-12')

const mainStore = useMainStore()

const selectEl = ref(null)
const textareaEl = ref(null)
const inputEl = ref(null)

onMounted(() => {
  if (selectEl.value) {
    emit('setRef', selectEl.value)
  } else if (textareaEl.value) {
    emit('setRef', textareaEl.value)
  } else {
    emit('setRef', inputEl.value)
  }
})

if (props.ctrlKFocus) {
  const fieldFocusHook = e => {
    if (e.ctrlKey && e.key === 'k') {
      e.preventDefault()
      inputEl.value.focus()
    } else if (e.key === 'Escape') {
      inputEl.value.blur()
    }
  }

  onMounted(() => {
    if (!mainStore.isFieldFocusRegistered) {
      window.addEventListener('keydown', fieldFocusHook)
      mainStore.isFieldFocusRegistered = true
    } else {
      // console.error('Duplicate field focus event')
    }
  })

  onBeforeUnmount(() => {
    window.removeEventListener('keydown', fieldFocusHook)
    mainStore.isFieldFocusRegistered = false
  })
}
</script>

<template>
  <div class="relative">
    <select
      v-if="computedType === 'select'"
      :id="id"
      v-model="computedValue"
      :name="name"
      :class="inputElClass"
      :disabled="disabled"
      :readonly="readonly"
    >
      <option value="" v-if="placeholder"> {{ placeholder }}</option>
      <option
        v-for="(option, index) in options"
        :key="index"
        :value="index"
        v-html="option"
      >
      </option>
    </select>
    <textarea
      v-else-if="computedType === 'textarea'"
      :id="id"
      v-model="computedValue"
      :class="inputElClass"
      :name="name"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
    />
    <input
      v-else
      :id="id"
      ref="inputEl"
      v-model="computedValue"
      :name="name"
      :inputmode="inputmode"
      :autocomplete="autocomplete"
      :required="required"
      :placeholder="placeholder"
      :type="computedType"
      :class="inputElClass"
      :disabled="disabled"
      :readonly="readonly"
    >
    <FormControlIcon
      v-if="icon"
      :icon="icon"
      :h="controlIconH"
    />
    <slot />
  </div>
</template>