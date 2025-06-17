<script setup>
import { mdiLogout, mdiClose } from '@mdi/js'
import { computed } from 'vue'
import AsideMenuList from '@/Components/AsideMenuList.vue'
import AsideMenuItem from '@/Components/AsideMenuItem.vue'
import BaseIcon from '@/Components/BaseIcon.vue'

defineProps({
  menu: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['menu-click', 'aside-lg-close-click'])

const logoutItem = computed(() => ({
  name: 'Logout',
  icon: mdiLogout,
  color: 'info',
  isLogout: true
}))

const menuClick = (event, item) => {
  emit('menu-click', event, item)
}

const asideLgCloseClick = (event) => {
  emit('aside-lg-close-click', event)
}

const appName = import.meta.env.VITE_APP_NAME || 'ACADEPOL ADMIN';

</script>

<template>
  <aside
    id="aside"
    class="w-64 fixed flex z-40 top-0 h-screen transition-position overflow-hidden"
  >
    <div 
      class="flex-1 flex flex-col overflow-hidden"
      style="background: #000000;"
    >
      <div 
        class="flex flex-row h-14 items-center justify-between text-white"
        style="background: #000000; border-bottom: 1px solid #bea55a;"
      >
        <div class="flex items-center justify-center flex-1">
          <img src="/images/logo-acadepol.png" alt="Logo" class="w-13 h-12 object-contain" />
        </div>
        <button class="hidden lg:inline-block xl:hidden p-3 text-gray-300 hover:text-[#bea55a]" @click.prevent="asideLgCloseClick">
          <BaseIcon :path="mdiClose" />
        </button>
      </div>
      <div
        class="flex-1 overflow-y-auto overflow-x-hidden"
        style="scrollbar-width: thin; scrollbar-color: #bea55a #000000;"
      >
        <AsideMenuList :menu="menu" @menu-click="menuClick" />
      </div>

      <ul>
        <AsideMenuItem :item="logoutItem" @menu-click="menuClick" />
      </ul>
    </div>
  </aside>
</template>

<style scoped>
/* Scrollbar personalizada */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #000000;
}

::-webkit-scrollbar-thumb {
  background: #bea55a;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #d4c166;
}
</style>