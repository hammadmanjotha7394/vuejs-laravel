<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { Search, X, Folder, ArrowRight, Loader2 } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const router = useRouter()
const settingsStore = useSettingsStore()

const inputRef = ref(null)
const query = ref('')
const loading = ref(false)
const results = ref({ products: [], categories: [] })
const selectedIndex = ref(-1)

let debounceTimer = null

const performSearch = () => {
  clearTimeout(debounceTimer)
  const term = query.value.trim()
  if (!term) {
    results.value = { products: [], categories: [] }
    loading.value = false
    return
  }

  loading.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const response = await axios.get(`/api/search/suggest?q=${encodeURIComponent(term)}`)
      results.value = response.data.data || { products: [], categories: [] }
    } catch (err) {
      console.error('Search suggestion error:', err)
    } finally {
      loading.value = false
    }
  }, 220)
}

const totalItems = () => {
  return results.value.categories.length + results.value.products.length
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    emit('close')
    return
  }

  const count = totalItems()
  if (count === 0) return

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    selectedIndex.value = (selectedIndex.value + 1) % count
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    selectedIndex.value = (selectedIndex.value - 1 + count) % count
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (selectedIndex.value >= 0) {
      // Pick selected item
      if (selectedIndex.value < results.value.categories.length) {
        const cat = results.value.categories[selectedIndex.value]
        navigateToCategory(cat.slug)
      } else {
        const prod =
          results.value.products[selectedIndex.value - results.value.categories.length]
        navigateToProduct(prod.slug)
      }
    } else if (query.value.trim()) {
      submitSearch()
    }
  }
}

const navigateToCategory = (slug) => {
  emit('close')
  router.push(`/category/${slug}`)
}

const navigateToProduct = (slug) => {
  emit('close')
  router.push(`/products/${slug}`)
}

const submitSearch = () => {
  if (!query.value.trim()) return
  emit('close')
  router.push(`/shop?q=${encodeURIComponent(query.value.trim())}`)
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      query.value = ''
      results.value = { products: [], categories: [] }
      selectedIndex.value = -1
      document.body.style.overflow = 'hidden'
      nextTick(() => {
        inputRef.value?.focus()
      })
    } else {
      document.body.style.overflow = ''
      clearTimeout(debounceTimer)
    }
  }
)

onUnmounted(() => {
  document.body.style.overflow = ''
  clearTimeout(debounceTimer)
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
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex flex-col justify-start items-center p-4 sm:p-6 md:p-12"
      role="dialog"
      aria-modal="true"
      aria-label="Search Catalog"
      @click.self="emit('close')"
      @keydown="handleKeyDown"
    >
      <div
        class="w-full max-w-2xl bg-white rounded-card shadow-2xl border border-line overflow-hidden flex flex-col transition-all"
        @click.stop
      >
        <!-- Search bar input -->
        <div class="p-4 sm:p-5 border-b border-line flex items-center gap-3">
          <Search class="w-5 h-5 text-ink-muted flex-shrink-0" />
          <input
            ref="inputRef"
            v-model="query"
            @input="performSearch"
            type="search"
            placeholder="Search products, materials, collections..."
            class="flex-1 bg-transparent text-ink placeholder:text-ink-muted text-sm sm:text-base outline-none font-medium"
            autocomplete="off"
            spellcheck="false"
          />
          <div v-if="loading" class="text-ink-muted animate-spin">
            <Loader2 class="w-4 h-4" />
          </div>
          <button
            type="button"
            @click="emit('close')"
            class="p-1 rounded-full text-ink-muted hover:text-ink hover:bg-surface transition"
            aria-label="Close search"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Suggestions dropdown / body -->
        <div class="max-h-[60vh] overflow-y-auto p-4 sm:p-5 divide-y divide-line/60">
          <!-- Empty initial hint -->
          <div
            v-if="!query.trim()"
            class="py-6 text-center text-xs text-ink-muted space-y-2"
          >
            <p>Type to search across our conscious collection.</p>
            <p class="text-[11px] text-gray-400">Press <kbd class="px-1.5 py-0.5 bg-gray-100 rounded border border-gray-200">Esc</kbd> to exit</p>
          </div>

          <!-- No results found -->
          <div
            v-else-if="!loading && results.products.length === 0 && results.categories.length === 0"
            class="py-8 text-center text-sm text-ink-muted"
          >
            No results found for "<span class="font-semibold text-ink">{{ query }}</span>"
          </div>

          <!-- Categories suggestions -->
          <div v-if="results.categories.length > 0" class="pb-3">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted mb-2">
              Categories
            </div>
            <div class="space-y-1">
              <button
                v-for="(cat, idx) in results.categories"
                :key="'cat-' + cat.id"
                type="button"
                @click="navigateToCategory(cat.slug)"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm transition"
                :class="selectedIndex === idx ? 'bg-surface font-semibold text-brand' : 'hover:bg-surface/80 text-ink'"
              >
                <div class="flex items-center gap-2.5">
                  <Folder class="w-4 h-4 text-ink-muted" />
                  <span>{{ cat.name }}</span>
                </div>
                <ArrowRight class="w-3.5 h-3.5 text-ink-muted opacity-60" />
              </button>
            </div>
          </div>

          <!-- Products suggestions -->
          <div v-if="results.products.length > 0" class="pt-3">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted mb-2">
              Products
            </div>
            <div class="space-y-1.5">
              <button
                v-for="(prod, idx) in results.products"
                :key="'prod-' + prod.id"
                type="button"
                @click="navigateToProduct(prod.slug)"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg transition"
                :class="selectedIndex === (results.categories.length + idx) ? 'bg-surface ring-1 ring-brand/30' : 'hover:bg-surface/80'"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-lg bg-surface border border-line overflow-hidden flex-shrink-0 flex items-center justify-center">
                    <img
                      v-if="prod.thumbnail"
                      :src="prod.thumbnail"
                      :alt="prod.name"
                      class="w-full h-full object-cover"
                    />
                    <span v-else class="text-xs text-ink-muted font-mono font-semibold">
                      {{ prod.name.charAt(0) }}
                    </span>
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs sm:text-sm font-medium text-ink truncate">
                      {{ prod.name }}
                    </p>
                    <p class="text-xs text-brand font-semibold">
                      {{ settingsStore.formatPrice(prod.price) }}
                    </p>
                  </div>
                </div>
                <ArrowRight class="w-3.5 h-3.5 text-ink-muted opacity-60 flex-shrink-0 ml-2" />
              </button>
            </div>
          </div>
        </div>

        <!-- Footer / View all button -->
        <div
          v-if="query.trim()"
          class="p-3 sm:p-4 bg-surface/50 border-t border-line flex items-center justify-between text-xs"
        >
          <span class="text-ink-muted">
            Viewing suggestions for "<strong class="text-ink">{{ query }}</strong>"
          </span>
          <button
            type="button"
            @click="submitSearch"
            class="inline-flex items-center gap-1.5 font-semibold text-brand hover:opacity-85 transition"
          >
            <span>See all results</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
