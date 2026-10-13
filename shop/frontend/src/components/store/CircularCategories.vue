<script setup>
import { RouterLink } from 'vue-router'
import { Sparkles, ArrowRight } from 'lucide-vue-next'

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <section
    v-if="categories.length > 0"
    class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12"
    aria-label="Browse by category"
  >
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6 sm:mb-8">
      <div>
        <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-pill bg-brand/10 text-brand text-[11px] font-semibold tracking-wide mb-2 border border-brand/15">
          <Sparkles class="w-3 h-3" />
          <span>Curated Collections</span>
        </div>

        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
          Shop By Curated Collection
        </h2>
        <p class="text-xs sm:text-sm text-ink-muted mt-1 max-w-lg leading-relaxed">
          Mindfully organized essentials for wardrobe, living spaces, and daily rituals.
        </p>
      </div>

      <!-- Header Action Link -->
      <RouterLink
        to="/shop"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-surface border border-line hover:border-brand/40 text-xs font-semibold text-ink hover:text-brand transition shadow-2xs self-start sm:self-auto"
      >
        <span>All Categories</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </RouterLink>
    </div>

    <!-- Category Tiles:
         Mobile (< sm): Clean, smooth touch-scrolling row without white overlay lines
         Desktop (sm+): Responsive wrapped/centered grid where all categories fit in full without any cropping!
    -->
    <div
      class="flex sm:flex-wrap items-start sm:justify-center gap-4 sm:gap-6 lg:gap-8 overflow-x-auto sm:overflow-visible pb-2 sm:pb-0 -mx-4 px-4 sm:mx-0 sm:px-0 snap-x snap-mandatory no-scrollbar"
    >
      <div
        v-for="cat in categories"
        :key="'circ-cat-' + cat.id"
        class="shrink-0 snap-start text-center group flex flex-col items-center"
      >
        <RouterLink
          :to="'/category/' + cat.slug"
          class="flex flex-col items-center gap-2.5 focus:outline-none"
        >
          <!-- Circular Image Card -->
          <div
            class="w-20 h-20 sm:w-24 sm:h-24 md:w-26 md:h-26 lg:w-28 lg:h-28 rounded-full overflow-hidden border border-line bg-surface p-1 shadow-2xs transition-all duration-300 group-hover:scale-105 group-hover:shadow-md group-hover:border-brand/40 group-hover:ring-4 group-hover:ring-brand/10 shrink-0"
          >
            <div class="w-full h-full rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
              <img
                v-if="cat.image"
                :src="cat.image"
                :alt="cat.name"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                loading="lazy"
                width="112"
                height="112"
              />
              <div
                v-else
                class="w-full h-full rounded-full bg-brand/10 text-brand font-bold text-lg sm:text-xl flex items-center justify-center select-none"
              >
                {{ cat.name?.charAt(0) || 'C' }}
              </div>
            </div>
          </div>

          <!-- Full Category Name (Centered, up to 2 lines, no ellipses) -->
          <span
            class="text-xs sm:text-sm font-semibold text-ink group-hover:text-brand transition-colors text-center w-20 sm:w-24 md:w-26 lg:w-28 line-clamp-2 leading-tight px-0.5 min-h-[2rem] flex items-start justify-center"
          >
            {{ cat.name }}
          </span>
        </RouterLink>
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
