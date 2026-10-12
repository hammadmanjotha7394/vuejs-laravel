<script setup>
import { RouterLink } from 'vue-router'

const props = defineProps({
  subcategories: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <div
    v-if="subcategories && subcategories.length > 0"
    class="py-4 border-b border-line mb-8"
    aria-label="Sub-categories"
  >
    <div class="flex items-center gap-4 sm:gap-6 overflow-x-auto pb-2 snap-x snap-mandatory no-scrollbar">
      <RouterLink
        v-for="sub in subcategories"
        :key="'subcat-' + sub.id"
        :to="'/category/' + sub.slug"
        class="flex-shrink-0 snap-start flex flex-col items-center gap-2 group focus:outline-none"
      >
        <div
          class="w-16 h-16 sm:w-20 sm:h-20 rounded-full border border-line bg-surface p-1 shadow-sm transition-all duration-300 group-hover:scale-105 group-hover:border-brand/40 group-hover:ring-4 group-hover:ring-brand/10 overflow-hidden"
        >
          <img
            v-if="sub.image"
            :src="sub.image"
            :alt="sub.name"
            class="w-full h-full object-cover rounded-full"
            loading="lazy"
            width="80"
            height="80"
          />
          <div
            v-else
            class="w-full h-full rounded-full bg-brand/10 text-brand flex items-center justify-center font-bold text-xs"
          >
            {{ sub.name.charAt(0) }}
          </div>
        </div>
        <span class="text-xs font-semibold text-ink group-hover:text-brand transition-colors max-w-[90px] truncate text-center">
          {{ sub.name }}
        </span>
      </RouterLink>
    </div>
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
