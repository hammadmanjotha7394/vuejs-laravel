<script setup>
import { RouterLink } from 'vue-router'
import { SearchX, RotateCcw } from 'lucide-vue-next'

const props = defineProps({
  searchQuery: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['clearFilters'])
</script>

<template>
  <div class="py-16 sm:py-24 text-center px-4">
    <div class="max-w-md mx-auto space-y-5">
      <div class="w-16 h-16 rounded-full bg-surface border border-line flex items-center justify-center text-ink-muted mx-auto shadow-sm">
        <SearchX class="w-8 h-8" />
      </div>

      <div class="space-y-2">
        <h3 class="text-xl font-bold tracking-tight text-ink">
          {{ searchQuery ? `No results found for "${searchQuery}"` : 'No matching products found' }}
        </h3>
        <p class="text-xs sm:text-sm text-ink-muted leading-relaxed">
          Try expanding your price range, clearing specific filters, or checking back for new artisan releases.
        </p>
      </div>

      <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
          type="button"
          @click="emit('clearFilters')"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
        >
          <RotateCcw class="w-3.5 h-3.5" />
          <span>Reset All Filters</span>
        </button>

        <RouterLink
          to="/shop"
          class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-pill border border-line bg-white hover:bg-surface text-ink text-xs font-semibold transition"
        >
          Browse All Products
        </RouterLink>
      </div>
    </div>
  </div>
</template>
