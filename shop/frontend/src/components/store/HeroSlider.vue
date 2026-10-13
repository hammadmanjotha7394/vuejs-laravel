<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronLeft, ChevronRight, ArrowRight } from 'lucide-vue-next'

const props = defineProps({
  slides: {
    type: Array,
    default: () => [],
  },
})

const currentIndex = ref(0)
const isPaused = ref(false)
let autoplayTimer = null

// Mobile touch swipe tracking
let touchStartX = 0
let touchEndX = 0

const activeSlide = computed(() => {
  if (!props.slides.length) return null
  return props.slides[currentIndex.value] || props.slides[0]
})

/**
 * Compute effective black overlay opacity (0 to 0.95).
 * Automatically upgrades legacy 0.20 / 0.25 default seed values to 0.55 so white text is always crisp.
 */
const getOverlayOpacity = (slide) => {
  if (!slide || slide.overlay_opacity === undefined || slide.overlay_opacity === null || slide.overlay_opacity === '') {
    return 0.55
  }
  const val = Number(slide.overlay_opacity)
  if (Number.isNaN(val)) return 0.55
  // Upgrade old low-contrast seed values (0.20 or 0.25) to 0.55 for clear readability
  if (val === 0.25 || val === 0.2) {
    return 0.55
  }
  return Math.min(0.95, Math.max(0, val))
}

/**
 * Directional dark gradient behind the text area so bright background spots never wash out white typography.
 */
const getScrimStyle = (slide) => {
  const base = getOverlayOpacity(slide)
  if (base <= 0) return { opacity: 0 }
  const pos = slide?.text_position || 'left'
  const strong = Math.min(0.85, base * 0.85 + 0.15).toFixed(2)
  const mid = Math.min(0.65, base * 0.5).toFixed(2)

  if (pos === 'right') {
    return {
      background: `linear-gradient(270deg, rgba(0,0,0,${strong}) 0%, rgba(0,0,0,${mid}) 55%, rgba(0,0,0,0.05) 100%)`,
    }
  }
  if (pos === 'center') {
    return {
      background: `radial-gradient(circle at center, rgba(0,0,0,${strong}) 0%, rgba(0,0,0,${mid}) 65%, rgba(0,0,0,0.15) 100%)`,
    }
  }
  return {
    background: `linear-gradient(90deg, rgba(0,0,0,${strong}) 0%, rgba(0,0,0,${mid}) 55%, rgba(0,0,0,0.05) 100%)`,
  }
}

const nextSlide = () => {
  if (!props.slides.length) return
  currentIndex.value = (currentIndex.value + 1) % props.slides.length
}

const prevSlide = () => {
  if (!props.slides.length) return
  currentIndex.value = (currentIndex.value - 1 + props.slides.length) % props.slides.length
}

const goToSlide = (index) => {
  currentIndex.value = index
}

const startAutoplay = () => {
  stopAutoplay()
  autoplayTimer = setInterval(() => {
    if (!isPaused.value && props.slides.length > 1) {
      nextSlide()
    }
  }, 6000)
}

const stopAutoplay = () => {
  if (autoplayTimer) {
    clearInterval(autoplayTimer)
    autoplayTimer = null
  }
}

const handleTouchStart = (e) => {
  touchStartX = e.changedTouches[0].screenX
}

const handleTouchEnd = (e) => {
  touchEndX = e.changedTouches[0].screenX
  handleSwipe()
}

const handleSwipe = () => {
  const threshold = 40
  if (touchStartX - touchEndX > threshold) {
    nextSlide()
  } else if (touchEndX - touchStartX > threshold) {
    prevSlide()
  }
}

onMounted(() => {
  startAutoplay()
})

onUnmounted(() => {
  stopAutoplay()
})
</script>

<template>
  <section
    v-if="slides.length > 0"
    class="relative w-full overflow-hidden bg-gray-950 min-h-[500px] sm:min-h-[580px] lg:min-h-[640px] flex items-center"
    @mouseenter="isPaused = true"
    @mouseleave="isPaused = false"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
    aria-roledescription="carousel"
    aria-label="Promotional highlights"
  >
    <!-- Slides Layer (Fade Transition) -->
    <div
      v-for="(slide, index) in slides"
      :key="'slide-' + slide.id"
      class="absolute inset-0 transition-opacity duration-700 ease-in-out"
      :class="index === currentIndex ? 'opacity-100 z-10 pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'"
      :aria-hidden="index !== currentIndex"
    >
      <!-- Background Responsive Images -->
      <picture class="absolute inset-0 w-full h-full">
        <!-- Mobile Image -->
        <source
          v-if="slide.image_mobile"
          :srcset="slide.image_mobile"
          media="(max-width: 767px)"
        />
        <!-- Desktop Image -->
        <img
          :src="slide.image_desktop"
          :alt="slide.title"
          class="w-full h-full object-cover object-center"
          :loading="index === 0 ? 'eager' : 'lazy'"
          :fetchpriority="index === 0 ? 'high' : 'auto'"
          width="1920"
          height="800"
        />
      </picture>

      <!-- Layer 1: Full-Bleed Black Overlay with Admin Configurable Opacity -->
      <div
        class="absolute inset-0 bg-black transition-opacity duration-300"
        :style="{ opacity: getOverlayOpacity(slide) }"
      ></div>

      <!-- Layer 2: Directional Dark Scrim Behind Text for Guaranteed Contrast -->
      <div
        class="absolute inset-0 pointer-events-none transition-all duration-300"
        :style="getScrimStyle(slide)"
      ></div>

      <!-- Slide Text Content -->
      <div class="relative z-20 h-full max-w-container mx-auto px-6 sm:px-8 lg:px-12 flex items-center">
        <div
          class="w-full max-w-2xl py-16 sm:py-20 text-white"
          :class="{
            'text-left mr-auto': slide.text_position === 'left' || !slide.text_position,
            'text-center mx-auto': slide.text_position === 'center',
            'text-right ml-auto': slide.text_position === 'right',
          }"
        >
          <!-- Clean Large Headline -->
          <h2
            class="text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-[1.1] text-white"
            style="text-shadow: 0 2px 18px rgba(0, 0, 0, 0.65), 0 1px 4px rgba(0, 0, 0, 0.5)"
          >
            {{ slide.title }}
          </h2>

          <!-- Subtext (one short line) -->
          <p
            v-if="slide.subtitle"
            class="text-sm sm:text-base lg:text-lg text-white/95 font-medium leading-relaxed mt-4 max-w-xl"
            :class="{ 'mx-auto': slide.text_position === 'center', 'ml-auto': slide.text_position === 'right' }"
            style="text-shadow: 0 1px 10px rgba(0, 0, 0, 0.75)"
          >
            {{ slide.subtitle }}
          </p>

          <!-- Single Pill Button CTA -->
          <div
            v-if="slide.button_text"
            class="mt-8 flex"
            :class="{
              'justify-start': slide.text_position === 'left' || !slide.text_position,
              'justify-center': slide.text_position === 'center',
              'justify-end': slide.text_position === 'right',
            }"
          >
            <RouterLink
              :to="slide.button_url || '/shop'"
              class="px-7 py-3.5 rounded-pill bg-white text-ink text-xs sm:text-sm font-semibold hover:bg-brand hover:text-white transition duration-200 inline-flex items-center gap-2 shadow-xl"
            >
              <span>{{ slide.button_text }}</span>
              <ArrowRight class="w-4 h-4" />
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Arrows (Desktop) -->
    <div
      v-if="slides.length > 1"
      class="hidden md:flex absolute inset-x-4 top-1/2 -translate-y-1/2 z-30 justify-between pointer-events-none"
    >
      <button
        type="button"
        @click="prevSlide"
        class="pointer-events-auto w-11 h-11 rounded-full bg-white/85 hover:bg-white text-ink flex items-center justify-center backdrop-blur-md shadow-md hover:scale-105 transition"
        aria-label="Previous slide"
      >
        <ChevronLeft class="w-5 h-5" />
      </button>

      <button
        type="button"
        @click="nextSlide"
        class="pointer-events-auto w-11 h-11 rounded-full bg-white/85 hover:bg-white text-ink flex items-center justify-center backdrop-blur-md shadow-md hover:scale-105 transition"
        aria-label="Next slide"
      >
        <ChevronRight class="w-5 h-5" />
      </button>
    </div>

    <!-- Navigation Dots (Centered Bottom) -->
    <div
      v-if="slides.length > 1"
      class="absolute bottom-6 inset-x-0 z-30 flex items-center justify-center gap-2.5"
    >
      <button
        v-for="(slide, index) in slides"
        :key="'dot-' + slide.id"
        type="button"
        @click="goToSlide(index)"
        class="h-2 rounded-full transition-all duration-300"
        :class="index === currentIndex ? 'w-8 bg-white shadow' : 'w-2 bg-white/50 hover:bg-white/80'"
        :aria-label="`Go to slide ${index + 1}`"
      ></button>
    </div>
  </section>
</template>
