<script setup>
import { usePage } from '@inertiajs/vue3'
import { mdiChevronUp, mdiChevronDown } from '@mdi/js'
import { Link, router } from '@inertiajs/vue3'
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { useMainStore } from '@/Stores/main.js'
import BaseIcon from '@/Components/BaseIcon.vue'
import UserAvatarCurrentUser from '@/Components/UserAvatarCurrentUser.vue'
import NavBarMenuList from '@/Components/NavBarMenuList.vue'
import BaseDivider from '@/Components/BaseDivider.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['menu-click'])

const is = computed(() => {
  if (props.item.href) {
    return 'a'
  }

  if (props.item.to) {
    return Link
  }

  return 'div'
})

const componentClass = computed(() => {
  const base = [
    isDropdownActive.value
      ? `text-[#bea55a]`
      : `text-white hover:text-[#bea55a]`,
    'transition-colors duration-200',
    props.item.menu ? 'lg:py-2 lg:px-3' : 'py-2 px-3'
  ]

  if (props.item.isDesktopNoLabel) {
    base.push('lg:w-16', 'lg:justify-center')
  }

  return base
})

const itemLabel = computed(() =>
  props.item.isCurrentUser ? usePage().props.auth.user.name : props.item.label
)

const isDropdownActive = ref(false)

const menuClick = (event) => {
  emit('menu-click', event, props.item)

  if (props.item.menu) {
    isDropdownActive.value = !isDropdownActive.value
  }
}

const logoutItemClick = () => {
  router.post(route('logout'))
}

const menuClickDropdown = (event, item) => {
  emit('menu-click', event, item)
}

const root = ref(null)

const forceClose = (event) => {
  if (root.value && !root.value.contains(event.target)) {
    isDropdownActive.value = false
  }
}

onMounted(() => {
  if (props.item.menu) {
    window.addEventListener('click', forceClose)
  }
})

onBeforeUnmount(() => {
  if (props.item.menu) {
    window.removeEventListener('click', forceClose)
  }
})
</script>

<template>
  <BaseDivider v-if="item.isDivider" nav-bar />
  <component
    :is="is"
    v-else
    ref="root"
    class="block lg:flex items-center relative cursor-pointer"
    :class="componentClass"
    :href="item.href ?? (item.to ?? null)"
    :target="item.target ?? null"
    @click="menuClick"
  >
    <div
      class="flex items-center"
      :class="{
        'lg:bg-transparent lg:dark:bg-transparent p-3 lg:p-0': item.menu
      }"
      :style="item.menu ? 'background: #000000;' : ''"
    >
      <UserAvatarCurrentUser v-if="item.isCurrentUser" class="w-6 h-6 mr-3 inline-flex" />
      <BaseIcon v-if="item.icon" :path="item.icon" class="transition-colors" />
      <span
        class="px-2 transition-colors"
        :class="{ 'lg:hidden': item.isDesktopNoLabel && item.icon }"
        >{{ itemLabel }}</span
      >
      <BaseIcon
        v-if="item.menu"
        :path="isDropdownActive ? mdiChevronUp : mdiChevronDown"
        class="hidden lg:inline-flex transition-colors"
      />
    </div>
    <div
      v-if="item.menu"
      class="text-sm lg:border lg:absolute lg:top-full lg:left-0 lg:min-w-full lg:z-20 lg:rounded-lg lg:shadow-lg"
      style="background: linear-gradient(180deg, #000000 0%, #1a1a1a 100%); border-color: #333;"
      :class="{ 'lg:hidden': !isDropdownActive }"
    >
      <NavBarMenuList :menu="item.menu" @menu-click="menuClickDropdown" />
    </div>
  </component>
</template>