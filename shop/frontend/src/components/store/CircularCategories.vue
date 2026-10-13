<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronLeft, ChevronRight, ArrowRight, Sparkles } from 'lucide-vue-next'

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})

const scrollContainerRef = ref(null)
const scrollProgress = ref(0)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

const updateScrollState = () => {
  if (!scrollContainerRef.value) return
  const { scrollLeft, scrollWidth, clientWidth } = scrollContainerRef.value
  const maxScroll = Math.max(0, scrollWidth - clientWidth)

  scrollProgress.value = maxScroll > 0 ? Math.min(1, Math.max(0, scrollLeft / maxScroll)) : 0
  canScrollLeft.value = scrollLeft > 12
  canScrollRight.value = scrollLeft < maxScroll - 12
}

const scroll = (direction) => {
  if (!scrollContainerRef.value) return
  const amount = 240
  scrollContainerRef.value.scrollBy({
    left: direction === 'left' ? -amount : amount,
    behavior: 'smooth',
  })
}

onMounted(() => {
  nextTick(() => {
    updateScrollState()
    window.addEventListener('resize', updateScrollState, { passive: true })
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', updateScrollState)
})
</script>

<template>
  <section
    v-if="categories.length > 0"
    class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14 overflow-hidden"
    aria-label="Browse by category"
  >
    <!-- Section Header -->
    <div class="text-center max-w-2xl mx-auto mb-6 sm:mb-8">
      <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-pill bg-brand/10 text-brand text-[11px] font-semibold tracking-wide mb-2.5 border border-brand/15">
        <Sparkles class="w-3 h-3" />
        <span>Curated Collections</span>
      </div>

      <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
        Shop By Curated Collection
      </h2>
      <p class="text-xs sm:text-sm text-ink-muted mt-1.5 max-w-md mx-auto">
        Mindfully organized essentials for wardrobe, living spaces, and daily rituals.
      </p>
    </div>

    <!-- Category Pills Quick-Navigation Bar -->
    <div
      class="flex items-center gap-2 overflow-x-auto pb-3 -mx-4 px-4 sm:mx-0 sm:px-0 justify-start sm:justify-center no-scrollbar mb-4 sm:mb-6"
      role="navigation"
      aria-label="Category pill filters"
    >
      <RouterLink
        to="/shop"
        class="shrink-0 px-3.5 py-1.5 rounded-pill text-xs font-semibold bg-brand text-white shadow-xs transition hover:opacity-90"
      >
        All Categories
      </RouterLink>

      <RouterLink
        v-for="cat in categories"
        :key="'chip-' + cat.id"
        :to="'/category/' + cat.slug"
        class="shrink-0 px-3.5 py-1.5 rounded-pill text-xs font-medium bg-surface hover:bg-white text-ink border border-line hover:border-brand/40 transition hover:shadow-xs"
      >
        {{ cat.name }}
      </RouterLink>
    </div>

    <!-- Circular Tiles Carousel Relative Wrapper -->
    <div class="relative group/carousel">
      <!-- Edge Fade Masks (Mobile & Desktop) -->
      <div
        v-show="canScrollLeft"
        class="pointer-events-none absolute left-0 top-0 bottom-6 w-8 bg-gradient-to-r from-page to-transparent z-10 transition-opacity duration-200"
      ></div>
      <div
        v-show="canScrollRight"
        class="pointer-events-none absolute right-0 top-0 bottom-6 w-8 bg-gradient-to-l from-page to-transparent z-10 transition-opacity duration-200"
      ></div>

      <!-- Left Arrow Button (Desktop / Tablet) -->
      <button
        v-show="canScrollLeft"
        type="button"
        @click="scroll('left')"
        class="hidden sm:flex absolute -left-3 top-1/2 -translate-y-8 z-20 w-9 h-9 rounded-full bg-white/95 border border-line shadow-md items-center justify-center text-ink hover:text-brand hover:scale-105 transition"
        aria-label="Scroll categories left"
      >
        <ChevronLeft class="w-4 h-4" />
      </button>

      <!-- Right Arrow Button (Desktop / Tablet) -->
      <button
        v-show="canScrollRight"
        type="button"
        @click="scroll('right')"
        class="hidden sm:flex absolute -right-3 top-1/2 -translate-y-8 z-20 w-9 h-9 rounded-full bg-white/95 border border-line shadow-md items-center justify-center text-ink hover:text-brand hover:scale-105 transition"
        aria-label="Scroll categories right"
      >
        <ChevronRight class="w-4 h-4" />
      </button>

      <!-- Category Tiles Scroll Container: Full bleed on mobile, centered on large screens -->
      <div
        ref="scrollContainerRef"
        @scroll.passive="updateScrollState"
        class="flex items-start gap-4 sm:gap-6 lg:gap-8 overflow-x-auto pb-2 pt-1 -mx-4 px-4 sm:mx-0 sm:px-0 snap-x snap-mandatory scroll-smooth no-scrollbar justify-start md:justify-center"
      >
        <div
          v-for="cat in categories"
          :key="'circ-cat-' + cat.id"
          class="shrink-0 snap-start text-center group"
        >
          <RouterLink
            :to="'/category/' + cat.slug"
            class="flex flex-col items-center gap-2.5 focus:outline-none"
          >
            <!-- Circular Image Card -->
            <div
              class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 lg:w-32 lg:h-32 rounded-full overflow-hidden border border-line bg-surface p-1 shadow-2xs transition-all duration-300 group-hover:scale-105 group-hover:shadow-md group-hover:border-brand/40 group-hover:ring-4 group-hover:ring-brand/10"
            >
              <div class="w-full h-full rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                <img
                  v-if="cat.image"
                  :src="cat.image"
                  :alt="cat.name"
                  class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                  loading="lazy"
                  width="128"
                  height="128"
                />
                <div
                  v-else
                  class="w-full h-full rounded-full bg-brand/10 text-brand font-bold text-lg sm:text-xl flex items-center justify-center select-none"
                >
                  {{ cat.name?.charAt(0) || 'C' }}
                </div>
              </div>
            </div>

            <!-- Category Name -->
            <span
              class="text-xs sm:text-sm font-semibold text-ink group-hover:text-brand transition-colors block max-w-[80px] sm:max-w-[100px] truncate"
            >
              {{ cat.name }}
            </span>
          </RouterLink>
        </div>

        <!-- End Card: "View All" Circle -->
        <div class="shrink-0 snap-start text-center group">
          <RouterLink
            to="/shop"
            class="flex flex-col items-center gap-2.5 focus:outline-none"
          >
            <div
              class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 lg:w-32 lg:h-32 rounded-full border-2 border-dashed border-line group-hover:border-brand/60 bg-surface/50 group-hover:bg-brand/5 flex items-center justify-center text-ink-muted group-hover:text-brand group-hover:scale-105 transition-all duration-300 shadow-2xs"
            >
              <ArrowRight class="w-5 h-5 sm:w-6 sm:h-6 transition-transform group-hover:translate-x-0.5" />
            </div>

            <span
              class="text-xs sm:text-sm font-semibold text-ink group-hover:text-brand transition-colors block max-w-[80px] sm:max-w-[100px] truncate"
            >
              View All
            </span>
          </RouterLink>
        </div>
      </div>

      <!-- Mobile Scroll Progress Capsule Track -->
      <div v-if="categories.length > 3" class="flex sm:hidden items-center justify-center pt-3">
        <div class="w-16 h-1 rounded-full bg-line/80 overflow-hidden relative">
          <div
            class="h-full bg-brand rounded-full transition-all duration-150 ease-out"
            :style="{
              width: '35%',
              transform: `translateX(${scrollProgress * 185}%)`,
            }"
          ></div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
