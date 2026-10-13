<script setup>
import { RouterLink } from 'vue-router'

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
    class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16"
    aria-label="Browse by category"
  >
    <div class="text-center mb-8 sm:mb-12">
      <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
        Shop By Curated Collection
      </h2>
      <p class="text-xs sm:text-sm text-ink-muted mt-2">
        Mindfully organized essentials for wardrobe, living spaces, and daily rituals.
      </p>
    </div>

    <!-- Category Tiles Container: Mobile Scroll / Desktop Centered Grid -->
    <div class="flex items-center gap-6 sm:gap-8 lg:gap-10 overflow-x-auto pb-4 pt-2 px-2 snap-x snap-mandatory justify-start md:justify-center no-scrollbar">
      <div
        v-for="cat in categories"
        :key="'circ-cat-' + cat.id"
        class="flex-shrink-0 snap-center text-center group"
      >
        <RouterLink
          :to="'/category/' + cat.slug"
          class="flex flex-col items-center gap-3 focus:outline-none"
        >
          <!-- Circular Image with Subtle Ring on Hover -->
          <div
            class="w-24 h-24 md:w-32 lg:w-36 md:h-32 lg:h-36 rounded-full overflow-hidden border border-line bg-surface p-1 shadow-sm transition-all duration-300 group-hover:scale-105 group-hover:shadow-md group-hover:border-brand/40 group-hover:ring-4 group-hover:ring-brand/10"
          >
            <div class="w-full h-full rounded-full overflow-hidden bg-gray-100">
              <img
                :src="cat.image || 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=400&q=80'"
                :alt="cat.name"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                loading="lazy"
                width="144"
                height="144"
              />
            </div>
          </div>

          <!-- Category Name Underneath -->
          <span
            class="text-xs sm:text-sm font-semibold text-ink group-hover:text-brand transition-colors block max-w-[120px] truncate"
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

