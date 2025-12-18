<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  images: {
    type: Array,
    required: true,
    validator: value => value.every(img => typeof img === 'string'),
  },
});

const currentIndex = ref(0);
const isZoomed = ref(false);
const zoomedImageIndex = ref(0);

const showPrevious = () => {
  currentIndex.value =
    currentIndex.value > 0 ? currentIndex.value - 1 : props.images.length - 1;
};

const showNext = () => {
  currentIndex.value =
    currentIndex.value < props.images.length - 1 ? currentIndex.value + 1 : 0;
};

const goToSlide = index => {
  currentIndex.value = index;
};

const openZoom = index => {
  zoomedImageIndex.value = index;
  isZoomed.value = true;
  document.body.style.overflow = 'hidden';
};

const closeZoom = () => {
  isZoomed.value = false;
  document.body.style.overflow = '';
};

const zoomPrevious = () => {
  zoomedImageIndex.value =
    zoomedImageIndex.value > 0
      ? zoomedImageIndex.value - 1
      : props.images.length - 1;
};

const zoomNext = () => {
  zoomedImageIndex.value =
    zoomedImageIndex.value < props.images.length - 1
      ? zoomedImageIndex.value + 1
      : 0;
};

const handleKeydown = event => {
  if (event.key === 'Escape' && isZoomed.value) closeZoom();
  else if (event.key === 'ArrowLeft' && isZoomed.value) zoomPrevious();
  else if (event.key === 'ArrowRight' && isZoomed.value) zoomNext();
};

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>

<template>
  <div class="carousel-container">
    <div class="carousel-wrapper">
      <div class="carousel-main">
        <div class="image-container">
          <img
            :src="images[currentIndex]"
            :alt="`Imagem ${currentIndex + 1}`"
            class="carousel-image"
          />
          <button
            @click="openZoom(currentIndex)"
            class="zoom-button"
            aria-label="Ampliar imagem"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
              />
            </svg>
          </button>
          <div class="counter">
            {{ currentIndex + 1 }} / {{ images.length }}
          </div>
        </div>
        <template v-if="images.length > 1">
          <button
            @click="showPrevious"
            class="nav-button nav-button-left"
            aria-label="Imagem anterior"
          >
            <svg
              class="w-6 h-6 sm:w-8 sm:h-8"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>
          <button
            @click="showNext"
            class="nav-button nav-button-right"
            aria-label="Próxima imagem"
          >
            <svg
              class="w-6 h-6 sm:w-8 sm:h-8"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </button>
        </template>
      </div>
      <div v-if="images.length > 1" class="thumbnails">
        <button
          v-for="(image, index) in images"
          :key="index"
          @click="goToSlide(index)"
          :class="['thumbnail', { 'thumbnail-active': currentIndex === index }]"
          :aria-label="`Ir para imagem ${index + 1}`"
        >
          <img
            :src="image"
            :alt="`Miniatura ${index + 1}`"
            class="thumbnail-image"
          />
        </button>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="zoom-modal">
        <div v-if="isZoomed" class="zoom-modal" @click.self="closeZoom">
          <div class="zoom-overlay" @click="closeZoom"></div>
          <div class="zoom-content">
            <button
              @click="closeZoom"
              class="zoom-close"
              aria-label="Fechar zoom"
            >
              <svg
                class="w-8 h-8"
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
            <div class="zoom-counter">
              {{ zoomedImageIndex + 1 }} / {{ images.length }}
            </div>
            <div class="zoom-image-container">
              <img
                :src="images[zoomedImageIndex]"
                :alt="`Imagem ampliada ${zoomedImageIndex + 1}`"
                class="zoom-image"
              />
            </div>
            <template v-if="images.length > 1">
              <button
                @click.stop="zoomPrevious"
                class="zoom-nav zoom-nav-left"
                aria-label="Imagem anterior"
              >
                <svg
                  class="w-10 h-10"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7"
                  />
                </svg>
              </button>
              <button
                @click.stop="zoomNext"
                class="zoom-nav zoom-nav-right"
                aria-label="Próxima imagem"
              >
                <svg
                  class="w-10 h-10"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </button>
            </template>
            <div class="zoom-hint">
              <span class="text-xs sm:text-sm"
                ><kbd class="kbd">ESC</kbd> fechar<template
                  v-if="images.length > 1"
                >
                  • <kbd class="kbd">←</kbd>
                  <kbd class="kbd">→</kbd> navegar</template
                ></span
              >
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.carousel-container {
  width: 100%;
  margin: 1rem 0;
}
.carousel-wrapper {
  width: 100%;
  border-radius: 0.5rem;
  overflow: hidden;
  background: #f9fafb;
}
.carousel-main {
  position: relative;
  width: 100%;
  aspect-ratio: 16/9;
  background: #000;
  overflow: hidden;
}
.image-container {
  position: relative;
  width: 100%;
  height: 100%;
}
.carousel-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: opacity 0.3s ease;
}
.carousel-image:hover {
  opacity: 0.95;
}
.zoom-button {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(0, 0, 0, 0.7);
  color: #fff;
  padding: 0.75rem;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
  backdrop-filter: blur(4px);
}
.zoom-button:hover {
  background: rgba(0, 0, 0, 0.9);
  transform: scale(1.1);
}
.counter {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: rgba(0, 0, 0, 0.7);
  color: #fff;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
  backdrop-filter: blur(4px);
}
.nav-button {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.9);
  color: #1f2937;
  padding: 0.75rem;
  border: none;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
  backdrop-filter: blur(4px);
}
.nav-button:hover {
  background: #fff;
  transform: translateY(-50%) scale(1.1);
}
.nav-button-left {
  left: 1rem;
}
.nav-button-right {
  right: 1rem;
}
.thumbnails {
  display: flex;
  gap: 0.5rem;
  padding: 1rem;
  overflow-x: auto;
  background: #fff;
  scrollbar-width: thin;
  scrollbar-color: #bea55a #f3f4f6;
}
.thumbnails::-webkit-scrollbar {
  height: 6px;
}
.thumbnails::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 3px;
}
.thumbnails::-webkit-scrollbar-thumb {
  background: #bea55a;
  border-radius: 3px;
}
.thumbnail {
  flex-shrink: 0;
  width: 5rem;
  height: 3.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.375rem;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
  background: 0 0;
}
.thumbnail:hover {
  border-color: #bea55a;
  transform: scale(1.05);
}
.thumbnail-active {
  border-color: #bea55a;
  box-shadow: 0 0 0 3px rgba(190, 165, 90, 0.2);
}
.thumbnail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.zoom-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.zoom-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.95);
  backdrop-filter: blur(4px);
}
.zoom-content {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}
.zoom-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  padding: 0.75rem;
  border: none;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
  backdrop-filter: blur(4px);
}
.zoom-close:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: rotate(90deg);
}
.zoom-counter {
  position: absolute;
  top: 1rem;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  padding: 0.75rem 1.5rem;
  border-radius: 9999px;
  font-size: 1rem;
  font-weight: 600;
  backdrop-filter: blur(4px);
  z-index: 10;
}
.zoom-image-container {
  max-width: 95%;
  max-height: 85%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.zoom-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: 0.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
  animation: zoom-in 0.3s ease-out;
}
@keyframes zoom-in {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
.zoom-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  padding: 1rem;
  border: none;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
  backdrop-filter: blur(4px);
}
.zoom-nav:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-50%) scale(1.1);
}
.zoom-nav-left {
  left: 2rem;
}
.zoom-nav-right {
  right: 2rem;
}
.zoom-hint {
  position: absolute;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  padding: 0.75rem 1.5rem;
  border-radius: 9999px;
  backdrop-filter: blur(4px);
  z-index: 10;
}
.kbd {
  display: inline-block;
  padding: 0.125rem 0.375rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 0.25rem;
  font-family: monospace;
  font-size: 0.75rem;
  font-weight: 600;
  margin: 0 0.25rem;
}
.zoom-modal-enter-active,
.zoom-modal-leave-active {
  transition: opacity 0.3s ease;
}
.zoom-modal-enter-from,
.zoom-modal-leave-to {
  opacity: 0;
}
.zoom-modal-enter-active .zoom-overlay,
.zoom-modal-leave-active .zoom-overlay {
  transition: opacity 0.3s ease;
}
.zoom-modal-enter-from .zoom-overlay,
.zoom-modal-leave-to .zoom-overlay {
  opacity: 0;
}
@media (max-width: 640px) {
  .carousel-main {
    aspect-ratio: 4/3;
  }
  .thumbnails {
    padding: 0.75rem;
    gap: 0.375rem;
  }
  .thumbnail {
    width: 4rem;
    height: 3rem;
  }
  .counter,
  .zoom-counter {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
  }
  .zoom-nav {
    padding: 0.75rem;
  }
  .zoom-nav-left {
    left: 0.5rem;
  }
  .zoom-nav-right {
    right: 0.5rem;
  }
  .zoom-close {
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.5rem;
  }
  .zoom-hint {
    bottom: 1rem;
    font-size: 0.75rem;
    padding: 0.5rem 1rem;
  }
  .zoom-content {
    padding: 1rem;
  }
}
@media (max-width: 480px) {
  .nav-button,
  .zoom-button {
    padding: 0.5rem;
  }
}
</style>
