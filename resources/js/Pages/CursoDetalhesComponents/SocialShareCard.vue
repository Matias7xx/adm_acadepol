<template>
  <div class="share-card">
    <div class="share-header">
      <span class="share-accent"></span>
      <h3 class="share-title">Compartilhar</h3>
    </div>
    <div class="share-body">
      <div class="share-buttons">
        <a
          v-for="network in socialNetworks"
          :key="network.name"
          :href="network.url"
          :title="`Compartilhar no ${network.displayName}`"
          :class="['share-btn', network.colorClass]"
          target="_blank"
          rel="noopener noreferrer"
          @click.prevent="shareOnSocial(network.name)"
        >
          <component :is="network.icon" class="w-4 h-4" />
          <span class="share-btn-label">{{ network.displayName }}</span>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, h } from 'vue';

const props = defineProps({
  courseUrl: {
    type: String,
    required: true,
  },
  courseTitle: {
    type: String,
    default: 'Veja este curso interessante!',
  },
});

const XIcon = p =>
  h(
    'svg',
    {
      xmlns: 'http://www.w3.org/2000/svg',
      width: '16',
      height: '16',
      fill: 'currentColor',
      viewBox: '0 0 24 24',
      ...p,
    },
    [
      h('path', {
        d: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
      }),
    ]
  );

const WhatsAppIcon = p =>
  h(
    'svg',
    {
      xmlns: 'http://www.w3.org/2000/svg',
      width: '16',
      height: '16',
      fill: 'currentColor',
      viewBox: '0 0 24 24',
      ...p,
    },
    [
      h('path', {
        d: 'M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z',
      }),
    ]
  );

const FacebookIcon = p =>
  h(
    'svg',
    {
      xmlns: 'http://www.w3.org/2000/svg',
      width: '16',
      height: '16',
      fill: 'currentColor',
      viewBox: '0 0 24 24',
      ...p,
    },
    [
      h('path', {
        d: 'M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z',
      }),
    ]
  );

const ShareIcon = p =>
  h(
    'svg',
    {
      xmlns: 'http://www.w3.org/2000/svg',
      width: '16',
      height: '16',
      fill: 'none',
      viewBox: '0 0 24 24',
      stroke: 'currentColor',
      ...p,
    },
    [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z',
      }),
    ]
  );

const socialNetworks = ref([
  {
    name: 'whatsapp',
    displayName: 'WhatsApp',
    icon: WhatsAppIcon,
    colorClass: 'btn-whatsapp',
    url: `https://wa.me/?text=${encodeURIComponent(props.courseTitle + ' ' + props.courseUrl)}`,
  },
  {
    name: 'facebook',
    displayName: 'Facebook',
    icon: FacebookIcon,
    colorClass: 'btn-facebook',
    url: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.courseUrl)}`,
  },
  {
    name: 'x',
    displayName: 'X',
    icon: XIcon,
    colorClass: 'btn-x',
    url: `https://twitter.com/intent/tweet?url=${encodeURIComponent(props.courseUrl)}&text=${encodeURIComponent(props.courseTitle)}`,
  },
  {
    name: 'share',
    displayName: 'Copiar link',
    icon: ShareIcon,
    colorClass: 'btn-share',
    url: '#',
  },
]);

const shareOnSocial = async network => {
  const url = props.courseUrl;
  const title = props.courseTitle;

  try {
    switch (network) {
      case 'x':
        window.open(
          `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
          '_blank',
          'width=600,height=400'
        );
        break;
      case 'facebook':
        window.open(
          `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
          '_blank',
          'width=600,height=400'
        );
        break;
      case 'whatsapp':
        window.open(
          `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`,
          '_blank'
        );
        break;
      case 'share':
        if (navigator.share) {
          await navigator.share({ title, url });
        } else {
          await navigator.clipboard.writeText(url);
        }
        break;
    }
  } catch (error) {
    console.error('Erro ao compartilhar:', error);
  }
};
</script>

<style scoped>
.share-card {
  @apply bg-white rounded-lg overflow-hidden border border-gray-200;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.share-header {
  @apply flex items-center px-5 py-4 border-b border-gray-100;
}

.share-title {
  @apply text-lg font-bold text-gray-800 tracking-wide uppercase;
}

.share-body {
  @apply p-6;
}

.share-buttons {
  @apply grid grid-cols-2 gap-2;
}

.share-btn {
  @apply flex items-center gap-2 px-3 py-2 rounded-md text-white text-xs font-medium;
  @apply transition-opacity duration-150;
}

.share-btn:hover {
  @apply opacity-90;
}

.share-btn-label {
  @apply text-xs font-medium;
}

.btn-whatsapp {
  background-color: #25d366;
}
.btn-facebook {
  background-color: #1877f2;
}
.btn-x {
  background-color: #18181b;
}
.btn-share {
  background-color: #6b7280;
}

@media (prefers-reduced-motion: reduce) {
  .share-btn {
    @apply transition-none;
  }
}
</style>
