<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { mdiMinus, mdiPlus } from '@mdi/js'
import { getButtonColor } from '@/colors.js'
import BaseIcon from '@/Components/BaseIcon.vue'
import AsideMenuList from '@/Components/AsideMenuList.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  isDropdownList: Boolean,
})

const { url, component } = usePage()

const itemHref = computed(() => (props.item && props.item.link) ? props.item.link : '')

const emit = defineEmits(['menu-click', 'dropdown-active'])

const hasColor = computed(() => props.item && props.item.color)

const isDropdownActive = ref(false)

const hasDropdown = computed(() => props.item.children && props.item.children.length)

const menuClick = event => {
  emit('menu-click', event, props.item)

  if (hasDropdown.value) {
    isDropdownActive.value = !isDropdownActive.value
  }
}

const dropdownActive = value => {
  isDropdownActive.value = value;
}

const isActive = computed(() => {
  if(props.item.link && url === props.item.link) {
    emit('dropdown-active', true)
    return true
  }
  return false
})

// Classes dinâmicas baseadas no estado
const itemClasses = computed(() => {
  const baseClasses = 'flex cursor-pointer transition-all duration-300 border-b border-transparent'
  const paddingClasses = props.isDropdownList ? 'py-3 px-6 text-sm pl-8' : 'py-3 px-4'
  
  if (isActive.value) {
    return `${baseClasses} ${paddingClasses} text-[#bea55a] font-semibold`
  }
  
  return `${baseClasses} ${paddingClasses} text-gray-400 hover:text-[#bea55a] hover:bg-opacity-10 hover:bg-[#bea55a] hover:border-[#bea55a] hover:transform hover:translate-x-1`
})

const iconClasses = computed(() => {
  if (isActive.value) {
    return 'flex-none text-[#bea55a]'
  }
  return 'flex-none transition-colors duration-300'
})
</script>

<template>
  <li>
    <component
      :is="itemHref ? Link : 'div'"
      :href="itemHref"
      :target="item.target ?? null"
      :class="itemClasses"
      @click="menuClick"
      :style="isActive ? 'border-right: 3px solid #bea55a; background: rgba(190, 165, 90, 0.2);' : ''"
    >
      <BaseIcon
        v-if="item.icon"
        :path="item.icon"
        :class="iconClasses"
        w="w-16"
        :size="18"
      />
      <span
        class="grow text-ellipsis line-clamp-1"
        :class="[{ 'pr-12': !hasDropdown }]"
      >{{ item.name }}</span>
      <BaseIcon
        v-if="hasDropdown"
        :path="isDropdownActive ? mdiMinus : mdiPlus"
        :class="iconClasses"
        w="w-12"
      />
    </component>
    <AsideMenuList
      v-if="hasDropdown"
      :menu="item.children"
      :class="['ml-4 border-l-2 border-[#bea55a]', isDropdownActive ? 'block' : 'hidden']"
      :style="isDropdownActive ? 'background: rgba(26, 26, 26, 0.95);' : ''"
      is-dropdown-list
      @dropdown-active="dropdownActive"
    />
  </li>
</template>