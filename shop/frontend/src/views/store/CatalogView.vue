<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@unhead/vue'
import axios from 'axios'
import {
  SlidersHorizontal,
  ChevronDown,
  X,
  RotateCcw,
  Loader2,
} from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'
import Breadcrumbs from '@/components/store/Breadcrumbs.vue'
import SubCategoryCircles from '@/components/store/SubCategoryCircles.vue'
import CatalogFilters from '@/components/store/CatalogFilters.vue'
import ProductCard from '@/components/store/ProductCard.vue'
import Pagination from '@/components/store/Pagination.vue'
import EmptyCatalogState from '@/components/store/EmptyCatalogState.vue'

const route = useRoute()
const router = useRouter()
const settingsStore = useSettingsStore()

// State
const products = ref([])
const totalProducts = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(12)

const facets = ref({
  brands: [],
  categories: [],
  colors: [],
  sizes: [],
  price_range: { min: 0, max: 350 },
})

const categoryDetails = ref(null)
const isLoading = ref(true)
const isUpdating = ref(false)
const mobileFiltersOpen = ref(false)

// Active filters model
const filters = ref({
  category: null,
  brand: null,
  color: null,
  size: null,
  rating: null,
  price_min: null,
  price_max: null,
  in_stock: null,
  on_sale: null,
  q: null,
})

const sort = ref('newest')

// Sort options
const sortOptions = [
  { value: 'newest', label: 'Newest Arrivals' },
  { value: 'popular', label: 'Customer Favorites' },
  { value: 'price_asc', label: 'Price: Low to High' },
  { value: 'price_desc', label: 'Price: High to Low' },
  { value: 'rating', label: 'Highest Rated' },
]

// Dynamic Page Titles & Descriptions
const pageType = computed(() => {
  if (route.name === 'sale' || route.path === '/sale') return 'sale'
  if (route.name === 'new' || route.path === '/new') return 'new'
  if (route.name === 'search' || route.query.q) return 'search'
  if (route.params.slug) return 'category'
  return 'shop'
})

const pageTitle = computed(() => {
  if (pageType.value === 'category' && categoryDetails.value) {
    return categoryDetails.value.name
  }
  if (pageType.value === 'sale') return 'Calm Archive Sale'
  if (pageType.value === 'new') return 'New Season Arrivals'
  if (pageType.value === 'search') {
    const term = route.query.q || filters.value.q || ''
    return term ? `Search: "${term}"` : 'Search Catalog'
  }
  return 'All Curated Goods'
})

const pageDescription = computed(() => {
  if (pageType.value === 'category' && categoryDetails.value?.description) {
    return categoryDetails.value.description
  }
  if (pageType.value === 'sale') {
    return 'End-of-season archive pieces and limited artisan surplus at reduced prices.'
  }
  if (pageType.value === 'new') {
    return 'Newly unboxed essentials from small European and Japanese workshops.'
  }
  if (pageType.value === 'search') {
    return `Catalog results for "${route.query.q || filters.value.q || ''}".`
  }
  return 'Explore our full collection of mindfully crafted apparel, homeware, and leather goods.'
})

const breadcrumbItems = computed(() => {
  if (pageType.value === 'category' && categoryDetails.value) {
    const crumbs = [{ label: 'Shop', url: '/shop' }]
    if (categoryDetails.value.breadcrumbs) {
      categoryDetails.value.breadcrumbs.forEach((b) => {
        crumbs.push({ label: b.name, url: `/category/${b.slug}` })
      })
    } else {
      crumbs.push({ label: categoryDetails.value.name, url: `/category/${categoryDetails.value.slug}` })
    }
    return crumbs
  }
  if (pageType.value === 'sale') {
    return [{ label: 'Shop', url: '/shop' }, { label: 'Sale', url: '/sale' }]
  }
  if (pageType.value === 'new') {
    return [{ label: 'Shop', url: '/shop' }, { label: 'New Arrivals', url: '/new' }]
  }
  if (pageType.value === 'search') {
    return [{ label: 'Shop', url: '/shop' }, { label: 'Search Results', url: null }]
  }
  return [{ label: 'All Products', url: '/shop' }]
})

// SEO Head Meta
useHead({
  title: computed(() => `${pageTitle.value} | ${settingsStore.siteName}`),
  meta: [
    { name: 'description', content: computed(() => pageDescription.value) },
    { property: 'og:title', content: computed(() => `${pageTitle.value} | ${settingsStore.siteName}`) },
    { property: 'og:description', content: computed(() => pageDescription.value) },
  ],
  link: [
    { rel: 'canonical', href: computed(() => window.location.origin + route.path) },
  ],
})

// Active Filter Chips
const activeFilterChips = computed(() => {
  const chips = []
  if (filters.value.brand) {
    const b = facets.value.brands?.find((x) => x.slug === filters.value.brand)
    chips.push({ key: 'brand', label: `Brand: ${b?.name || filters.value.brand}` })
  }
  if (filters.value.color) {
    chips.push({ key: 'color', label: `Color: ${filters.value.color}` })
  }
  if (filters.value.size) {
    chips.push({ key: 'size', label: `Size: ${filters.value.size}` })
  }
  if (filters.value.rating) {
    chips.push({ key: 'rating', label: `Rating: ${filters.value.rating}★+` })
  }
  if (filters.value.price_min || filters.value.price_max) {
    chips.push({
      key: 'price',
      label: `Price: $${filters.value.price_min || 0} – $${filters.value.price_max || facets.value.price_range?.max || 350}`,
    })
  }
  if (filters.value.in_stock) {
    chips.push({ key: 'in_stock', label: 'In Stock' })
  }
  if (filters.value.on_sale && pageType.value !== 'sale') {
    chips.push({ key: 'on_sale', label: 'On Sale' })
  }
  return chips
})

const removeFilterChip = (key) => {
  if (key === 'price') {
    filters.value.price_min = null
    filters.value.price_max = null
  } else {
    filters.value[key] = null
  }
  syncToUrl()
  fetchProducts()
}

const clearAllFilters = () => {
  filters.value = {
    category: route.params.slug || null,
    brand: null,
    color: null,
    size: null,
    rating: null,
    price_min: null,
    price_max: null,
    in_stock: null,
    on_sale: pageType.value === 'sale' ? '1' : null,
    q: null,
  }
  syncToUrl()
  fetchProducts()
}

// Sync current internal state into URL Query
const syncToUrl = (newPage = 1) => {
  const query = {}

  if (filters.value.brand) query.brand = filters.value.brand
  if (filters.value.color) query.color = filters.value.color
  if (filters.value.size) query.size = filters.value.size
  if (filters.value.rating) query.rating = filters.value.rating
  if (filters.value.price_min) query.price_min = filters.value.price_min
  if (filters.value.price_max) query.price_max = filters.value.price_max
  if (filters.value.in_stock) query.in_stock = filters.value.in_stock
  if (filters.value.on_sale && pageType.value !== 'sale') query.on_sale = filters.value.on_sale
  if (filters.value.q) query.q = filters.value.q
  if (route.query.q) query.q = route.query.q

  if (sort.value && sort.value !== 'newest') query.sort = sort.value
  if (newPage > 1) query.page = String(newPage)

  router.push({ path: route.path, query })
}

// Read URL Query into internal state
const parseUrlQuery = () => {
  const q = route.query

  filters.value.brand = q.brand || null
  filters.value.color = q.color || null
  filters.value.size = q.size || null
  filters.value.rating = q.rating || null
  filters.value.price_min = q.price_min ? Number(q.price_min) : null
  filters.value.price_max = q.price_max ? Number(q.price_max) : null
  filters.value.in_stock = q.in_stock || null
  filters.value.on_sale = pageType.value === 'sale' ? '1' : (q.on_sale || null)
  filters.value.q = q.q || null

  if (route.params.slug) {
    filters.value.category = route.params.slug
  } else {
    filters.value.category = q.category || null
  }

  if (pageType.value === 'new') {
    sort.value = 'newest'
  } else {
    sort.value = q.sort || 'newest'
  }

  currentPage.value = q.page ? Number(q.page) : 1
}

// Fetch Filter Metadata Facets
const fetchFacets = async () => {
  try {
    const res = await axios.get('/api/catalog/filters')
    facets.value = res.data.data
  } catch (err) {
    console.warn('Could not load catalog facets:', err)
  }
}

// Fetch Category Details (when on /category/:slug)
const fetchCategoryDetails = async (slug) => {
  try {
    const res = await axios.get(`/api/categories/${slug}`)
    categoryDetails.value = res.data.data
  } catch (err) {
    categoryDetails.value = null
  }
}

// Main Fetch Products Query
const fetchProducts = async () => {
  if (products.value.length > 0) {
    isUpdating.value = true
  } else {
    isLoading.value = true
  }

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      sort: sort.value,
    }

    if (filters.value.category) params.category = filters.value.category
    if (filters.value.brand) params.brand = filters.value.brand
    if (filters.value.color) params.color = filters.value.color
    if (filters.value.size) params.size = filters.value.size
    if (filters.value.rating) params.rating = filters.value.rating
    if (filters.value.price_min) params.price_min = filters.value.price_min
    if (filters.value.price_max) params.price_max = filters.value.price_max
    if (filters.value.in_stock) params.in_stock = filters.value.in_stock
    if (filters.value.on_sale) params.on_sale = filters.value.on_sale
    if (filters.value.q) params.q = filters.value.q
    if (route.query.q) params.q = route.query.q

    const res = await axios.get('/api/products', { params })
    products.value = res.data.data || []
    totalProducts.value = res.data.total ?? 0
    currentPage.value = res.data.current_page ?? 1
    lastPage.value = res.data.last_page ?? 1
  } catch (err) {
    console.error('Failed to load catalog products:', err)
  } finally {
    isLoading.value = false
    isUpdating.value = false
  }
}

const handlePageChange = (newPage) => {
  currentPage.value = newPage
  syncToUrl(newPage)
  fetchProducts()

  // Smooth scroll up to top of catalog listing
  const anchor = document.getElementById('catalog-top')
  if (anchor) {
    anchor.scrollIntoView({ behavior: 'smooth' })
  }
}

const handleSortChange = () => {
  currentPage.value = 1
  syncToUrl(1)
  fetchProducts()
}

const handleFiltersUpdate = (newFilters) => {
  filters.value = { ...newFilters }
  currentPage.value = 1
  syncToUrl(1)
  fetchProducts()
}

// Watch route changes (URL parameters, back/forward button)
watch(
  () => [route.path, route.query],
  async () => {
    parseUrlQuery()
    if (route.params.slug) {
      await fetchCategoryDetails(route.params.slug)
    } else {
      categoryDetails.value = null
    }
    fetchProducts()
  }
)

onMounted(async () => {
  parseUrlQuery()
  await Promise.allSettled([
    fetchFacets(),
    route.params.slug ? fetchCategoryDetails(route.params.slug) : Promise.resolve(),
  ])
  fetchProducts()
})
</script>

<template>
  <div class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12" id="catalog-top">
    <!-- Breadcrumbs -->
    <Breadcrumbs :items="breadcrumbItems" />

    <!-- Sub-category Circles Row (when on category page with children) -->
    <SubCategoryCircles
      v-if="categoryDetails?.children && categoryDetails.children.length > 0"
      :subcategories="categoryDetails.children"
    />

    <!-- Catalog Header: Title + Description -->
    <div class="pt-4 pb-8 sm:pb-10 border-b border-line flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="max-w-2xl space-y-2">
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-ink">
          {{ pageTitle }}
        </h1>
        <p class="text-xs sm:text-sm text-ink-muted leading-relaxed">
          {{ pageDescription }}
        </p>
      </div>

      <!-- Result Count Summary -->
      <div class="text-xs text-ink-muted self-start md:self-auto font-medium">
        <span v-if="!isLoading">
          Showing <strong class="text-ink">{{ products.length }}</strong> of <strong class="text-ink">{{ totalProducts }}</strong> results
        </span>
      </div>
    </div>

    <!-- Active Filter Chips + Sort Bar -->
    <div class="py-4 flex flex-wrap items-center justify-between gap-4 border-b border-line/60">
      <!-- Left: Mobile Filter Button & Active Filter Chips -->
      <div class="flex items-center flex-wrap gap-2">
        <!-- Mobile Filter Drawer Trigger -->
        <button
          type="button"
          @click="mobileFiltersOpen = true"
          class="lg:hidden inline-flex items-center gap-2 px-3.5 py-2 rounded-pill border border-line bg-surface hover:bg-white text-xs font-semibold text-ink transition shadow-sm"
        >
          <SlidersHorizontal class="w-3.5 h-3.5 text-brand" />
          <span>Filters</span>
          <span
            v-if="activeFilterChips.length > 0"
            class="px-1.5 py-0.2 rounded-pill bg-brand text-white text-[10px]"
          >
            {{ activeFilterChips.length }}
          </span>
        </button>

        <!-- Active Filter Chips -->
        <div v-if="activeFilterChips.length > 0" class="flex flex-wrap items-center gap-1.5">
          <span
            v-for="chip in activeFilterChips"
            :key="'chip-' + chip.key"
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-pill bg-surface border border-line text-xs text-ink font-medium"
          >
            <span>{{ chip.label }}</span>
            <button
              type="button"
              @click="removeFilterChip(chip.key)"
              class="text-ink-muted hover:text-ink p-0.5 rounded-full hover:bg-black/5"
              :aria-label="`Remove ${chip.label}`"
            >
              <X class="w-3 h-3" />
            </button>
          </span>

          <button
            type="button"
            @click="clearAllFilters"
            class="text-xs text-brand hover:underline font-semibold ml-1 inline-flex items-center gap-1"
          >
            <RotateCcw class="w-3 h-3" />
            <span>Clear all</span>
          </button>
        </div>
      </div>

      <!-- Right: Sort Dropdown -->
      <div class="flex items-center gap-2 ml-auto">
        <label for="catalog-sort" class="text-xs text-ink-muted font-medium hidden sm:inline">Sort by:</label>
        <div class="relative">
          <select
            id="catalog-sort"
            v-model="sort"
            @change="handleSortChange"
            class="appearance-none pl-3.5 pr-8 py-2 rounded-pill border border-line bg-white text-xs font-semibold text-ink focus:outline-none focus:border-brand cursor-pointer shadow-sm"
          >
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-ink-muted absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      </div>
    </div>

    <!-- Main Content Layout: Sidebar + Product Grid -->
    <div class="pt-8 flex items-start gap-8">
      <!-- Faceted Desktop Filter Sidebar + Mobile Drawer -->
      <CatalogFilters
        v-model="filters"
        :facets="facets"
        :mobile-open="mobileFiltersOpen"
        @close-mobile="mobileFiltersOpen = false"
        @update:model-value="handleFiltersUpdate"
        @clear="clearAllFilters"
      />

      <!-- Right Area: Products Grid -->
      <div class="flex-1 min-w-0">
        <!-- Initial Loading Skeletons -->
        <div
          v-if="isLoading"
          class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6"
        >
          <div
            v-for="i in 8"
            :key="'skel-' + i"
            class="aspect-[4/5] bg-surface rounded-card animate-pulse border border-line"
          ></div>
        </div>

        <!-- Empty State -->
        <EmptyCatalogState
          v-else-if="products.length === 0"
          :search-query="filters.q || route.query.q"
          @clear-filters="clearAllFilters"
        />

        <!-- Product Grid (Retains previous results with reduced opacity while updating) -->
        <div v-else class="relative">
          <!-- Subtle Updating Indicator -->
          <div
            v-if="isUpdating"
            class="absolute top-2 right-2 z-20 px-3 py-1 bg-white/90 backdrop-blur-md rounded-pill border border-line shadow-sm text-xs text-brand font-semibold flex items-center gap-1.5"
          >
            <Loader2 class="w-3.5 h-3.5 animate-spin" />
            <span>Updating...</span>
          </div>

          <div
            class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 transition-opacity duration-200"
            :class="{ 'opacity-60 pointer-events-none': isUpdating }"
          >
            <ProductCard
              v-for="product in products"
              :key="'cat-prod-' + product.id"
              :product="product"
            />
          </div>

          <!-- Pagination Bar -->
          <Pagination
            :current-page="currentPage"
            :last-page="lastPage"
            :total="totalProducts"
            @change-page="handlePageChange"
          />
        </div>
      </div>
    </div>
  </div>
</template>
