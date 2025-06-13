import {
    mdiMenu,
    mdiClockOutline,
    mdiCloud,
    mdiCrop,
    mdiAccount,
    mdiCogOutline,
    mdiEmail,
    mdiLogout,
    mdiThemeLightDark,
    mdiGithub
  } from '@mdi/js'
  
  export default [
    /* {
      icon: mdiMenu,
      label: 'Menu',
      menu: [
        {
          icon: mdiClockOutline,
          label: 'Item Um'
        },
        {
          icon: mdiCloud,
          label: 'Item Dois'
        },
        {
          isDivider: true
        },
        {
          icon: mdiCrop,
          label: 'Item Três'
        }
      ]
    }, */
    {
      isCurrentUser: true,
      menu: [
        {
          icon: mdiAccount,
          label: 'Meu Perfil',
          to: '/admin/edit-account-info'
        },
        /* {
          icon: mdiCogOutline,
          label: 'Configurações'
        },
        {
          icon: mdiEmail,
          label: 'Mensagens'
        }, */
        {
          isDivider: true
        },
        {
          icon: mdiLogout,
          label: 'Log Out',
          isLogout: true
        }
      ]
    },
    {
      icon: mdiThemeLightDark,
      label: 'Light/Dark',
      isDesktopNoLabel: true,
      isToggleLightDark: true
    },
    /*{
      icon: mdiGithub,
      label: 'GitHub',
      isDesktopNoLabel: true,
      href: 'https://github.com/balajidharma/laravel-vue-admin-panel',
      target: '_blank'
    },*/
    {
      icon: mdiLogout,
      label: 'Log out',
      isDesktopNoLabel: true,
      isLogout: true
    }
  ]