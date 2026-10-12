<script setup>
import { ref, computed, watch } from 'vue'
import { Play, Maximize2, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import ProductLightbox from './ProductLightbox.vue'

const props = defineProps({
  images: {
    type: Array,
    default: () => [],
  },
  videoUrl: {
    type: String,
    default: null,
  },
  videoPoster: {
    type: String,
    default: null,
  },
  activeVariantImage: {
    type: String,
    default: null,
  },
})

const activeIndex = ref(0)
const isVideoActive = ref(false)
const lightboxOpen = ref(false)

// Combined media items: all images + optional video item
const mediaItems = computed(() => {
  const items = [...props.images]
  return items
})

const currentImage = computed(() => {
  if (isVideoActive.value) return null
  return mediaItems.value[activeIndex.value] || mediaItems.value[0] || null
})

// Watch variant image changes to automatically jump gallery
watch(
  () => props.activeVariantImage,
  (newImgUrl) => {
    if (!newImgUrl) return
    const index = props.images.findIndex(
      (img) => img.url === newImgUrl || img.medium_url === newImgUrl || img.large_url === newImgUrl
    )
    if (index !== -1) {
      isVideoActive.value = false
      activeIndex.value = index
    }
  }
)

const selectImage = (idx) => {
  isVideoActive.value = false
  activeIndex.value = idx
}

const selectVideo = () => {
  isVideoActive.value = true
}

const nextMedia = () => {
  if (isVideoActive.value) {
    isVideoActive.value = false
    activeIndex.value = 0
    return
  }
  if (props.videoUrl && activeIndex.value === mediaItems.value.length - 1) {
    isVideoActive.value = true
    return
  }
  activeIndex.value = (activeIndex.value + 1) % mediaItems.value.length
}

const prevMedia = () => {
  if (isVideoActive.value) {
    isVideoActive.value = false
    activeIndex.value = mediaItems.value.length - 1
    return
  }
  if (activeIndex.value === 0 && props.videoUrl) {
    isVideoActive.value = true
    return
  }
  activeIndex.value = (activeIndex.value - 1 + mediaItems.value.length) % mediaItems.value.length
}

// Touch swipe support for mobile
let touchStartX = 0
let touchEndX = 0

const handleTouchStart = (e) => {
  touchStartX = e.changedTouches[0].screenX
}

const handleTouchEnd = (e) => {
  touchEndX = e.changedTouches[0].screenX
  if (touchStartX - touchEndX > 40) {
    nextMedia()
  } else if (touchEndX - touchStartX > 40) {
    prevMedia()
  }
}
</script>

<template>
  <div class="space-y-4 select-none">
    <!-- Main Media Display (Aspect Ratio 4:5) -->
    <div
      class="relative aspect-[4/5] w-full rounded-card border border-line bg-surface overflow-hidden group shadow-sm"
      @touchstart="handleTouchStart"
      @touchend="handleTouchEnd"
    >
      <!-- Video Player Display -->
      <div v-if="isVideoActive && videoUrl" class="w-full h-full bg-black flex items-center justify-center">
        <video
          :src="videoUrl"
          :poster="videoPoster"
          controls
          autoplay
          playsinline
          class="w-full h-full object-contain"
        ></video>
      </div>

      <!-- Main Product Image Display -->
      <div
        v-else
        class="w-full h-full cursor-zoom-in"
        @click="lightboxOpen = true"
      >
        <img
          v-if="currentImage"
          :src="currentImage.large_url || currentImage.medium_url || currentImage.url"
          :alt="currentImage.alt || 'Product image'"
          class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
          loading="eager"
          width="800"
          height="1000"
        />

        <!-- Click to expand pill button -->
        <button
          type="button"
          @click.stop="lightboxOpen = true"
          class="absolute bottom-3 right-3 z-10 px-3 py-1.5 rounded-pill bg-white/90 backdrop-blur-sm border border-line text-ink text-xs font-semibold shadow-sm hover:bg-white transition flex items-center gap-1.5 opacity-0 group-hover:opacity-100"
          aria-label="Enlarge image"
        >
          <Maximize2 class="w-3.5 h-3.5" />
          <span>Zoom</span>
        </button>
      </div>

      <!-- Mobile Previous/Next Touch Arrow Overlays -->
      <button
        v-if="mediaItems.length > 1 || videoUrl"
        type="button"
        @click="prevMedia"
        class="sm:hidden absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm text-ink flex items-center justify-center shadow-sm"
        aria-label="Previous"
      >
        <ChevronLeft class="w-4 h-4" />
      </button>

      <button
        v-if="mediaItems.length > 1 || videoUrl"
        type="button"
        @click="nextMedia"
        class="sm:hidden absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm text-ink flex items-center justify-center shadow-sm"
        aria-label="Next"
      >
        <ChevronRight class="w-4 h-4" />
      </button>

      <!-- Mobile Dots Indicator -->
      <div
        v-if="mediaItems.length > 1 || videoUrl"
        class="sm:hidden absolute bottom-3 inset-x-0 z-10 flex items-center justify-center gap-1.5"
      >
        <span
          v-for="(img, idx) in mediaItems"
          :key="'dot-' + idx"
          class="h-1.5 rounded-full transition-all duration-200"
          :class="!isVideoActive && activeIndex === idx ? 'w-5 bg-brand' : 'w-1.5 bg-black/30'"
        ></span>
        <span
          v-if="videoUrl"
          class="h-1.5 rounded-full transition-all duration-200"
          :class="isVideoActive ? 'w-5 bg-brand' : 'w-1.5 bg-black/30'"
        ></span>
      </div>
    </div>

    <!-- Thumbnails Strip (Desktop & Tablet) -->
    <div
      v-if="mediaItems.length > 1 || videoUrl"
      class="hidden sm:flex items-center gap-3 overflow-x-auto pb-2 no-scrollbar"
    >
      <!-- Image Thumbnails -->
      <button
        v-for="(img, idx) in mediaItems"
        :key="'g-thumb-' + idx"
        type="button"
        @click="selectImage(idx)"
        class="w-16 h-20 rounded-lg overflow-hidden border-2 transition flex-shrink-0 bg-surface shadow-xs"
        :class="!isVideoActive && activeIndex === idx ? 'border-brand ring-2 ring-brand/20 scale-102' : 'border-line/80 hover:border-ink-muted/50'"
        :aria-label="`Thumbnail ${idx + 1}`"
      >
        <img
          :src="img.thumb_url || img.url"
          :alt="img.alt || 'Thumbnail'"
          class="w-full h-full object-cover"
          loading="lazy"
        />
      </button>

      <!-- Video Thumbnail with Play Badge -->
      <button
        v-if="videoUrl"
        type="button"
        @click="selectVideo"
        class="relative w-16 h-20 rounded-lg overflow-hidden border-2 transition flex-shrink-0 bg-black shadow-xs flex items-center justify-center group"
        :class="isVideoActive ? 'border-brand ring-2 ring-brand/20 scale-102' : 'border-line/80 hover:border-ink-muted/50'"
        aria-label="Play video preview"
      >
        <img
          v-if="videoPoster || images[0]"
          :src="videoPoster || images[0]?.thumb_url || images[0]?.url"
          alt="Video thumbnail"
          class="w-full h-full object-cover opacity-60 group-hover:opacity-80 transition"
        />
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center shadow-md">
            <Play class="w-3.5 h-3.5 fill-current ml-0.5" />
          </div>
        </div>
      </button>
    </div>

    <!-- Fullscreen Lightbox Modal -->
    <ProductLightbox
      :is-open="lightboxOpen"
      :images="images"
      :initial-index="activeIndex"
      @close="lightboxOpen = false"
    />
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
