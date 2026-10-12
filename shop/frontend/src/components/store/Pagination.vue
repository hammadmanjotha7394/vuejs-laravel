<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  lastPage: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['changePage'])

const pages = computed(() => {
  const current = props.currentPage
  const last = props.lastPage
  const delta = 2
  const range = []
  const rangeWithDots = []
  let l

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i)
    }
  }

  for (const i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  }

  return rangeWithDots
})

const selectPage = (page) => {
  if (page === '...' || page === props.currentPage || page < 1 || page > props.lastPage) return
  emit('changePage', page)
}
</script>

<template>
  <nav
    v-if="lastPage > 1"
    class="flex items-center justify-center gap-1.5 py-8"
    aria-label="Pagination"
  >
    <!-- Previous Button -->
    <button
      type="button"
      @click="selectPage(currentPage - 1)"
      :disabled="currentPage <= 1"
      class="p-2 sm:px-3 sm:py-2 rounded-pill border border-line bg-white hover:bg-surface text-ink text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
      aria-label="Previous page"
    >
      <ChevronLeft class="w-4 h-4" />
      <span class="hidden sm:inline">Prev</span>
    </button>

    <!-- Page numbers -->
    <template v-for="(p, index) in pages" :key="'page-' + index">
      <span
        v-if="p === '...'"
        class="px-2 py-1 text-xs text-ink-muted select-none"
      >
        &hellip;
      </span>
      <button
        v-else
        type="button"
        @click="selectPage(p)"
        class="w-9 h-9 rounded-full text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand"
        :class="p === currentPage ? 'bg-brand text-white shadow-sm' : 'border border-line bg-white hover:bg-surface text-ink'"
        :aria-current="p === currentPage ? 'page' : undefined"
      >
        {{ p }}
      </button>
    </template>

    <!-- Next Button -->
    <button
      type="button"
      @click="selectPage(currentPage + 1)"
      :disabled="currentPage >= lastPage"
      class="p-2 sm:px-3 sm:py-2 rounded-pill border border-line bg-white hover:bg-surface text-ink text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
      aria-label="Next page"
    >
      <span class="hidden sm:inline">Next</span>
      <ChevronRight class="w-4 h-4" />
    </button>
  </nav>
</template>
