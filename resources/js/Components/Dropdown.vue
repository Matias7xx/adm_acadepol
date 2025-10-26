<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
  align: {
    type: String,
    default: 'right',
  },
  width: {
    default: '48',
  },
  contentClasses: {
    default: () => ['py-1', 'bg-white'],
  },
  dropdownClasses: {
    type: String,
    default: '',
  },
});

const closeOnEscape = e => {
  if (open.value && e.key === 'Escape') {
    open.value = false;
  }
};

const closeOnClickOutside = e => {
  if (open.value && !dropdown.value?.contains(e.target)) {
    open.value = false;
  }
};

onMounted(() => {
  document.addEventListener('keydown', closeOnEscape);
  document.addEventListener('click', closeOnClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
  document.removeEventListener('click', closeOnClickOutside);
});

const widthClass = computed(() => {
  const widths = {
    48: 'w-48',
    56: 'w-56',
    64: 'w-64',
    auto: 'w-auto',
  };

  return widths[props.width.toString()] || 'w-48';
});

const alignmentClasses = computed(() => {
  if (props.align === 'left') {
    return 'origin-top-left left-0';
  } else if (props.align === 'right') {
    return 'origin-top-right right-0';
  } else {
    return 'origin-top';
  }
});

const open = ref(false);
const dropdown = ref(null);
</script>

<template>
  <div class="relative" ref="dropdown">
    <div @click.stop="open = !open">
      <slot name="trigger" />
    </div>

    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-show="open"
        class="absolute z-50 mt-2 rounded-md shadow-lg"
        :class="[widthClass, alignmentClasses, dropdownClasses]"
      >
        <div
          class="rounded-md ring-1 ring-black/5 shadow-xl bg-white"
          :class="contentClasses"
        >
          <slot name="content" />
        </div>
      </div>
    </transition>
  </div>
</template>
