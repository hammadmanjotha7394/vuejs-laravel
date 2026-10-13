<script setup>
import { ref, computed, watch } from 'vue'
import {
  X,
  RotateCcw,
  Star,
  Check,
  ChevronDown,
} from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  facets: {
    type: Object,
    default: () => ({
      brands: [],
      categories: [],
      colors: [],
      sizes: [],
      price_range: { min: 0, max: 350 },
    }),
  },
  mobileOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'closeMobile', 'apply', 'clear'])

const settingsStore = useSettingsStore()

// Local copy of active filters
const localFilters = ref({ ...props.modelValue })

// Sync when external modelValue changes (e.g. from URL change)
watch(
  () => props.modelValue,
  (newVal) => {
    localFilters.value = { ...newVal }
  },
  { deep: true }
)

// Accordion collapse states
const openSections = ref({
  price: true,
  brand: true,
  color: true,
  size: true,
  rating: true,
  availability: true,
})

const toggleSection = (key) => {
  openSections.value[key] = !openSections.value[key]
}

const updateFilter = (key, val) => {
  if (val === null || val === undefined || val === '') {
    delete localFilters.value[key]
  } else {
    localFilters.value[key] = val
  }
  emit('update:modelValue', { ...localFilters.value })
}

const toggleBrand = (slug) => {
  if (localFilters.value.brand === slug) {
    updateFilter('brand', null)
  } else {
    updateFilter('brand', slug)
  }
}

const toggleColor = (name) => {
  if (localFilters.value.color === name) {
    updateFilter('color', null)
  } else {
    updateFilter('color', name)
  }
}

const toggleSize = (size) => {
  if (localFilters.value.size === size) {
    updateFilter('size', null)
  } else {
    updateFilter('size', size)
  }
}

const toggleRating = (r) => {
  if (String(localFilters.value.rating) === String(r)) {
    updateFilter('rating', null)
  } else {
    updateFilter('rating', r)
  }
}

const handleClearAll = () => {
  localFilters.value = {}
  emit('clear')
}

const handleApply = () => {
  emit('apply', { ...localFilters.value })
  emit('closeMobile')
}

// Active filters count badge
const activeFilterCount = computed(() => {
  let count = 0
  const f = localFilters.value
  if (f.brand) count++
  if (f.color) count++
  if (f.size) count++
  if (f.rating) count++
  if (f.in_stock) count++
  if (f.on_sale) count++
  if (f.price_min || f.price_max) count++
  return count
})

const minPossiblePrice = computed(() => props.facets?.price_range?.min ?? 0)
const maxPossiblePrice = computed(() => props.facets?.price_range?.max ?? 350)
</script>

<template>
  <div>
    <!-- MOBILE BOTTOM-SHEET / SLIDE-OVER DRAWER -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileOpen"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm lg:hidden"
        @click="emit('closeMobile')"
        aria-hidden="true"
      ></div>
    </Transition>

    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-y-full lg:translate-x-full"
      enter-to-class="translate-y-0 lg:translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-y-0 lg:translate-x-0"
      leave-to-class="translate-y-full lg:translate-x-full"
    >
      <div
        v-if="mobileOpen"
        class="fixed inset-x-0 bottom-0 z-50 max-h-[85vh] bg-white rounded-t-2xl shadow-2xl flex flex-col lg:hidden border-t border-line overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Filter Catalog"
      >
        <!-- Mobile Drawer Top Bar -->
        <div class="p-4 border-b border-line flex items-center justify-between bg-surface/50">
          <div class="flex items-center gap-2">
            <h3 class="font-bold text-ink text-sm sm:text-base">Filter Catalog</h3>
            <span
              v-if="activeFilterCount > 0"
              class="px-2 py-0.5 rounded-pill bg-brand text-white text-[10px] font-bold"
            >
              {{ activeFilterCount }}
            </span>
          </div>

          <button
            type="button"
            @click="emit('closeMobile')"
            class="p-1.5 rounded-full text-ink-muted hover:text-ink hover:bg-surface transition"
            aria-label="Close filters"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Scrollable Filter Content in Mobile Drawer -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
          <div class="space-y-6">
            <!-- Reuse Filter Form Sections Component Block -->
            <!-- Price Range -->
            <div class="border-b border-line/60 pb-5">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-ink">Price Range</h4>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex-1">
                  <label class="text-[10px] text-ink-muted uppercase block mb-1">Min</label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-ink-muted">{{ settingsStore.currencySymbol }}</span>
                    <input
                      type="number"
                      v-model.number="localFilters.price_min"
                      @change="updateFilter('price_min', localFilters.price_min)"
                      :placeholder="String(minPossiblePrice)"
                      class="w-full pl-6 pr-2 py-1.5 bg-surface border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
                    />
                  </div>
                </div>
                <span class="text-ink-muted mt-5">&ndash;</span>
                <div class="flex-1">
                  <label class="text-[10px] text-ink-muted uppercase block mb-1">Max</label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-ink-muted">{{ settingsStore.currencySymbol }}</span>
                    <input
                      type="number"
                      v-model.number="localFilters.price_max"
                      @change="updateFilter('price_max', localFilters.price_max)"
                      :placeholder="String(maxPossiblePrice)"
                      class="w-full pl-6 pr-2 py-1.5 bg-surface border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Brand Filter -->
            <div v-if="facets.brands && facets.brands.length > 0" class="border-b border-line/60 pb-5">
              <h4 class="text-xs font-bold uppercase tracking-wider text-ink mb-3">Brand</h4>
              <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                <button
                  v-for="b in facets.brands"
                  :key="'mb-' + b.id"
                  type="button"
                  @click="toggleBrand(b.slug)"
                  class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition"
                  :class="localFilters.brand === b.slug ? 'bg-brand/10 font-bold text-brand' : 'hover:bg-surface text-ink'"
                >
                  <span>{{ b.name }}</span>
                  <span class="text-[11px] text-ink-muted">({{ b.products_count }})</span>
                </button>
              </div>
            </div>

            <!-- Color Swatches Filter -->
            <div v-if="facets.colors && facets.colors.length > 0" class="border-b border-line/60 pb-5">
              <h4 class="text-xs font-bold uppercase tracking-wider text-ink mb-3">Color Swatches</h4>
              <div class="flex flex-wrap gap-2.5">
                <button
                  v-for="c in facets.colors"
                  :key="'mc-' + c.name"
                  type="button"
                  @click="toggleColor(c.name)"
                  class="w-7 h-7 rounded-full border border-line flex items-center justify-center transition shadow-sm"
                  :class="localFilters.color === c.name ? 'ring-2 ring-brand ring-offset-2' : 'hover:scale-110'"
                  :style="{ backgroundColor: c.hex }"
                  :title="c.name"
                  :aria-label="c.name"
                >
                  <Check v-if="localFilters.color === c.name" class="w-3.5 h-3.5 text-white drop-shadow-sm" />
                </button>
              </div>
            </div>

            <!-- Size Chips Filter -->
            <div v-if="facets.sizes && facets.sizes.length > 0" class="border-b border-line/60 pb-5">
              <h4 class="text-xs font-bold uppercase tracking-wider text-ink mb-3">Sizes</h4>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="s in facets.sizes"
                  :key="'ms-' + s"
                  type="button"
                  @click="toggleSize(s)"
                  class="px-3 py-1 rounded-pill text-xs font-medium border transition"
                  :class="localFilters.size === s ? 'bg-brand text-white border-brand' : 'border-line bg-white hover:bg-surface text-ink'"
                >
                  {{ s }}
                </button>
              </div>
            </div>

            <!-- Rating Filter -->
            <div class="border-b border-line/60 pb-5">
              <h4 class="text-xs font-bold uppercase tracking-wider text-ink mb-3">Customer Rating</h4>
              <div class="space-y-1.5">
                <button
                  type="button"
                  @click="toggleRating(4)"
                  class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition"
                  :class="String(localFilters.rating) === '4' ? 'bg-brand/10 font-bold text-brand' : 'hover:bg-surface text-ink'"
                >
                  <div class="flex items-center gap-1.5">
                    <div class="flex text-amber-400">
                      <Star v-for="i in 4" :key="'r4-' + i" class="w-3.5 h-3.5 fill-current" />
                    </div>
                    <span>4★ & above</span>
                  </div>
                  <Check v-if="String(localFilters.rating) === '4'" class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Availability & Sale Toggles -->
            <div class="space-y-3 pt-1">
              <label class="flex items-center justify-between cursor-pointer text-xs">
                <span class="font-medium text-ink">In Stock Only</span>
                <input
                  type="checkbox"
                  v-model="localFilters.in_stock"
                  @change="updateFilter('in_stock', localFilters.in_stock ? '1' : null)"
                  class="w-4 h-4 rounded text-brand focus:ring-brand border-line"
                />
              </label>

              <label class="flex items-center justify-between cursor-pointer text-xs">
                <span class="font-medium text-ink">On Sale Only</span>
                <input
                  type="checkbox"
                  v-model="localFilters.on_sale"
                  @change="updateFilter('on_sale', localFilters.on_sale ? '1' : null)"
                  class="w-4 h-4 rounded text-brand focus:ring-brand border-line"
                />
              </label>
            </div>
          </div>
        </div>

        <!-- Sticky Mobile Bottom Bar -->
        <div class="p-4 border-t border-line bg-surface/50 grid grid-cols-2 gap-3">
          <button
            type="button"
            @click="handleClearAll"
            class="py-2.5 px-4 rounded-pill border border-line bg-white text-ink text-xs font-semibold hover:bg-surface transition flex items-center justify-center gap-1.5"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Clear All</span>
          </button>
          <button
            type="button"
            @click="handleApply"
            class="py-2.5 px-4 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
          >
            Apply Filters
          </button>
        </div>
      </div>
    </Transition>

    <!-- DESKTOP FILTER SIDEBAR -->
    <aside class="hidden lg:block w-64 lg:w-72 flex-shrink-0 space-y-6 pr-6 border-r border-line/60">
      <div class="flex items-center justify-between pb-3 border-b border-line">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-ink text-sm">Filters</h3>
          <span
            v-if="activeFilterCount > 0"
            class="px-2 py-0.5 rounded-pill bg-brand text-white text-[10px] font-bold"
          >
            {{ activeFilterCount }}
          </span>
        </div>

        <button
          v-if="activeFilterCount > 0"
          type="button"
          @click="handleClearAll"
          class="text-xs text-brand hover:opacity-80 font-medium inline-flex items-center gap-1"
        >
          <RotateCcw class="w-3 h-3" />
          <span>Reset</span>
        </button>
      </div>

      <!-- Price Range Accordion -->
      <div class="border-b border-line/60 pb-5">
        <button
          type="button"
          @click="toggleSection('price')"
          class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-ink mb-3 focus:outline-none"
        >
          <span>Price Range</span>
          <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openSections.price }" />
        </button>

        <div v-show="openSections.price" class="space-y-3">
          <div class="flex items-center gap-2">
            <div class="flex-1">
              <label class="text-[10px] text-ink-muted uppercase block mb-1">Min</label>
              <div class="relative">
                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-ink-muted">{{ settingsStore.currencySymbol }}</span>
                <input
                  type="number"
                  v-model.number="localFilters.price_min"
                  @change="updateFilter('price_min', localFilters.price_min)"
                  :placeholder="String(minPossiblePrice)"
                  class="w-full pl-6 pr-2 py-1.5 bg-surface border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
                />
              </div>
            </div>
            <span class="text-ink-muted mt-5">&ndash;</span>
            <div class="flex-1">
              <label class="text-[10px] text-ink-muted uppercase block mb-1">Max</label>
              <div class="relative">
                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-ink-muted">{{ settingsStore.currencySymbol }}</span>
                <input
                  type="number"
                  v-model.number="localFilters.price_max"
                  @change="updateFilter('price_max', localFilters.price_max)"
                  :placeholder="String(maxPossiblePrice)"
                  class="w-full pl-6 pr-2 py-1.5 bg-surface border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Brand Filter Accordion -->
      <div v-if="facets.brands && facets.brands.length > 0" class="border-b border-line/60 pb-5">
        <button
          type="button"
          @click="toggleSection('brand')"
          class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-ink mb-3 focus:outline-none"
        >
          <span>Brand</span>
          <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openSections.brand }" />
        </button>

        <div v-show="openSections.brand" class="space-y-1 max-h-48 overflow-y-auto pr-1">
          <button
            v-for="b in facets.brands"
            :key="'b-' + b.id"
            type="button"
            @click="toggleBrand(b.slug)"
            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition"
            :class="localFilters.brand === b.slug ? 'bg-brand/10 font-bold text-brand' : 'hover:bg-surface text-ink'"
          >
            <span>{{ b.name }}</span>
            <span class="text-[11px] text-ink-muted">({{ b.products_count }})</span>
          </button>
        </div>
      </div>

      <!-- Color Swatches Accordion -->
      <div v-if="facets.colors && facets.colors.length > 0" class="border-b border-line/60 pb-5">
        <button
          type="button"
          @click="toggleSection('color')"
          class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-ink mb-3 focus:outline-none"
        >
          <span>Color Swatches</span>
          <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openSections.color }" />
        </button>

        <div v-show="openSections.color" class="flex flex-wrap gap-2 pt-1">
          <button
            v-for="c in facets.colors"
            :key="'c-' + c.name"
            type="button"
            @click="toggleColor(c.name)"
            class="w-7 h-7 rounded-full border border-line flex items-center justify-center transition shadow-sm"
            :class="localFilters.color === c.name ? 'ring-2 ring-brand ring-offset-2' : 'hover:scale-110'"
            :style="{ backgroundColor: c.hex }"
            :title="c.name"
            :aria-label="c.name"
          >
            <Check v-if="localFilters.color === c.name" class="w-3.5 h-3.5 text-white drop-shadow-sm" />
          </button>
        </div>
      </div>

      <!-- Size Chips Accordion -->
      <div v-if="facets.sizes && facets.sizes.length > 0" class="border-b border-line/60 pb-5">
        <button
          type="button"
          @click="toggleSection('size')"
          class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-ink mb-3 focus:outline-none"
        >
          <span>Size</span>
          <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openSections.size }" />
        </button>

        <div v-show="openSections.size" class="flex flex-wrap gap-1.5 pt-1">
          <button
            v-for="s in facets.sizes"
            :key="'s-' + s"
            type="button"
            @click="toggleSize(s)"
            class="px-3 py-1 rounded-pill text-xs font-medium border transition"
            :class="localFilters.size === s ? 'bg-brand text-white border-brand' : 'border-line bg-white hover:bg-surface text-ink'"
          >
            {{ s }}
          </button>
        </div>
      </div>

      <!-- Customer Rating Accordion -->
      <div class="border-b border-line/60 pb-5">
        <button
          type="button"
          @click="toggleSection('rating')"
          class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-ink mb-3 focus:outline-none"
        >
          <span>Rating</span>
          <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openSections.rating }" />
        </button>

        <div v-show="openSections.rating" class="space-y-1">
          <button
            type="button"
            @click="toggleRating(4)"
            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition"
            :class="String(localFilters.rating) === '4' ? 'bg-brand/10 font-bold text-brand' : 'hover:bg-surface text-ink'"
          >
            <div class="flex items-center gap-1.5">
              <div class="flex text-amber-400">
                <Star v-for="i in 4" :key="'ds-r4-' + i" class="w-3.5 h-3.5 fill-current" />
              </div>
              <span>4★ & above</span>
            </div>
            <Check v-if="String(localFilters.rating) === '4'" class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Availability & Sale Toggles -->
      <div class="space-y-3 pt-1">
        <label class="flex items-center justify-between cursor-pointer text-xs">
          <span class="font-medium text-ink">In Stock Only</span>
          <input
            type="checkbox"
            v-model="localFilters.in_stock"
            @change="updateFilter('in_stock', localFilters.in_stock ? '1' : null)"
            class="w-4 h-4 rounded text-brand focus:ring-brand border-line"
          />
        </label>

        <label class="flex items-center justify-between cursor-pointer text-xs">
          <span class="font-medium text-ink">On Sale Only</span>
          <input
            type="checkbox"
            v-model="localFilters.on_sale"
            @change="updateFilter('on_sale', localFilters.on_sale ? '1' : null)"
            class="w-4 h-4 rounded text-brand focus:ring-brand border-line"
          />
        </label>
      </div>
    </aside>
  </div>
</template>
