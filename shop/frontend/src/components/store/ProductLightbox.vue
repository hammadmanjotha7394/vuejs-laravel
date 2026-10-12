<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { X, ChevronLeft, ChevronRight, ZoomIn, ZoomOut } from 'lucide-vue-next'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  images: {
    type: Array,
    required: true,
  },
  initialIndex: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['close'])

const currentIndex = ref(props.initialIndex)
const isZoomed = ref(false)

watch(
  () => props.initialIndex,
  (val) => {
    currentIndex.value = val
    isZoomed.value = false
  }
)

watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      document.body.style.overflow = 'hidden'
      isZoomed.value = false
    } else {
      document.body.style.overflow = ''
    }
  }
)

const next = () => {
  if (props.images.length <= 1) return
  currentIndex.value = (currentIndex.value + 1) % props.images.length
  isZoomed.value = false
}

const prev = () => {
  if (props.images.length <= 1) return
  currentIndex.value = (currentIndex.value - 1 + props.images.length) % props.images.length
  isZoomed.value = false
}

const toggleZoom = () => {
  isZoomed.value = !isZoomed.value
}

const handleKeyDown = (e) => {
  if (!props.isOpen) return
  if (e.key === 'Escape') emit('close')
  if (e.key === 'ArrowRight') next()
  if (e.key === 'ArrowLeft') prev()
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 bg-black/95 backdrop-blur-md flex flex-col justify-between p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-label="Product Image Lightbox"
      @click.self="emit('close')"
    >
      <!-- Top Action Bar -->
      <div class="flex items-center justify-between text-white z-20">
        <!-- Image Counter -->
        <span class="text-xs font-semibold px-3 py-1 rounded-pill bg-white/10 backdrop-blur-sm">
          {{ currentIndex + 1 }} / {{ images.length }}
        </span>

        <!-- Zoom & Close Controls -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="toggleZoom"
            class="p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition focus:outline-none"
            :aria-label="isZoomed ? 'Zoom out' : 'Zoom in'"
          >
            <ZoomOut v-if="isZoomed" class="w-5 h-5" />
            <ZoomIn v-else class="w-5 h-5" />
          </button>

          <button
            type="button"
            @click="emit('close')"
            class="p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition focus:outline-none"
            aria-label="Close lightbox"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Main Display Area -->
      <div class="flex-1 flex items-center justify-center relative overflow-hidden my-4 select-none">
        <!-- Previous Button -->
        <button
          v-if="images.length > 1"
          type="button"
          @click="prev"
          class="absolute left-2 sm:left-4 z-20 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center backdrop-blur-sm transition focus:outline-none"
          aria-label="Previous image"
        >
          <ChevronLeft class="w-6 h-6" />
        </button>

        <!-- Image with Zoom Transition -->
        <div
          class="max-w-4xl max-h-[80vh] flex items-center justify-center overflow-auto cursor-zoom-in"
          :class="{ 'cursor-zoom-out': isZoomed }"
          @click="toggleZoom"
        >
          <img
            :src="images[currentIndex]?.large_url || images[currentIndex]?.url || images[currentIndex]"
            :alt="images[currentIndex]?.alt || 'Enlarged product image'"
            class="max-w-full max-h-[80vh] object-contain transition-transform duration-300 ease-out"
            :class="{ 'scale-150': isZoomed }"
          />
        </div>

        <!-- Next Button -->
        <button
          v-if="images.length > 1"
          type="button"
          @click="next"
          class="absolute right-2 sm:right-4 z-20 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center backdrop-blur-sm transition focus:outline-none"
          aria-label="Next image"
        >
          <ChevronRight class="w-6 h-6" />
        </button>
      </div>

      <!-- Bottom Thumbnail Strip -->
      <div class="flex items-center justify-center gap-2 overflow-x-auto py-2 z-20">
        <button
          v-for="(img, idx) in images"
          :key="'thumb-' + idx"
          type="button"
          @click="currentIndex = idx; isZoomed = false"
          class="w-12 h-12 rounded-lg overflow-hidden border-2 transition flex-shrink-0"
          :class="currentIndex === idx ? 'border-white scale-105' : 'border-transparent opacity-60 hover:opacity-100'"
        >
          <img
            :src="img.thumb_url || img.url || img"
            :alt="img.alt || 'Thumbnail'"
            class="w-full h-full object-cover"
          />
        </button>
      </div>
    </div>
  </Transition>
</template>
