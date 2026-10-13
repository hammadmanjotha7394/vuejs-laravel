<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import axios from 'axios'
import {
  Star,
  Heart,
  Share2,
  ShoppingBag,
  Truck,
  ShieldCheck,
  RotateCcw,
  CheckCircle2,
  AlertTriangle,
  XCircle,
  Plus,
  Minus,
  Loader2,
  ArrowRight,
  Zap,
} from 'lucide-vue-next'

import { useCartStore } from '@/stores/cart'
import { useWishlistStore } from '@/stores/wishlist'
import { useToastStore } from '@/stores/toast'
import { useSettingsStore } from '@/stores/settings'

import Breadcrumbs from '@/components/store/Breadcrumbs.vue'
import ProductGallery from '@/components/store/ProductGallery.vue'
import ProductAccordions from '@/components/store/ProductAccordions.vue'
import ProductReviews from '@/components/store/ProductReviews.vue'
import ProductCard from '@/components/store/ProductCard.vue'
import MobileStickyBar from '@/components/store/MobileStickyBar.vue'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const toastStore = useToastStore()
const settingsStore = useSettingsStore()

const STORAGE_RECENTLY_VIEWED = 'boutique_recently_viewed_v1'

const isLoading = ref(true)
const notFound = ref(false)
const product = ref(null)
const relatedProducts = ref([])
const recentlyViewed = ref([])

// Variant selection state: map of option_type_id -> option_value_id
const selectedOptions = ref({})
const activeVariantImage = ref(null)
const quantity = ref(1)
const isAddingToCart = ref(false)

// Mobile sticky bar observer
const addToCartBtnRef = ref(null)
const showMobileStickyBar = ref(false)
let observer = null

// Fetch product details from API
const fetchProduct = async (slug) => {
  isLoading.value = true
  notFound.value = false
  product.value = null
  selectedOptions.value = {}
  activeVariantImage.value = null
  quantity.value = 1

  try {
    const res = await axios.get(`/api/products/${slug}`)
    const data = res.data.data
    product.value = data.product
    relatedProducts.value = data.related_products || []

    // Initialize variant selection if variants exist
    if (product.value.variants && product.value.variants.length > 0) {
      // Find the first active in-stock variant, or the first variant
      const defaultVariant =
        product.value.variants.find((v) => v.is_active && v.stock_qty > 0) ||
        product.value.variants[0]

      if (defaultVariant && defaultVariant.option_values) {
        defaultVariant.option_values.forEach((ov) => {
          selectedOptions.value[ov.option_type_id] = ov.id
        })
        if (defaultVariant.image) {
          activeVariantImage.value = defaultVariant.image
        }
      }
    }

    // Handle Recently Viewed Storage
    handleRecentlyViewed(product.value)
  } catch (err) {
    if (err.response?.status === 404) {
      notFound.value = true
    } else {
      toastStore.error('Failed to load product details.')
    }
  } finally {
    isLoading.value = false
    nextTick(() => {
      setupIntersectionObserver()
    })
  }
}

// Track recently viewed products in localStorage
const handleRecentlyViewed = (currentProd) => {
  try {
    const raw = localStorage.getItem(STORAGE_RECENTLY_VIEWED)
    let list = raw ? JSON.parse(raw) : []
    if (!Array.isArray(list)) list = []

    // Filter out the current product for display
    recentlyViewed.value = list.filter((p) => p.id !== currentProd.id).slice(0, 4)

    // Prepare current product item for saving
    const currentItem = {
      id: currentProd.id,
      name: currentProd.name,
      slug: currentProd.slug,
      sku: currentProd.sku,
      price: currentProd.base_price,
      compare_at_price: currentProd.compare_at_price,
      is_on_sale: currentProd.compare_at_price > currentProd.base_price,
      sale_percentage:
        currentProd.compare_at_price > currentProd.base_price
          ? Math.round(
              ((currentProd.compare_at_price - currentProd.base_price) /
                currentProd.compare_at_price) *
                100
            )
          : 0,
      rating: currentProd.rating_summary?.average || 5.0,
      reviews_count: currentProd.rating_summary?.count || 0,
      primary_image: currentProd.images?.[0] || null,
      second_image: currentProd.images?.[1] || null,
      has_variants: (currentProd.variants || []).length > 0,
      in_stock: !currentProd.track_stock || currentProd.stock_qty > 0,
    }

    // Prepend and limit to 10 items in storage
    const updated = [currentItem, ...list.filter((p) => p.id !== currentProd.id)].slice(0, 10)
    localStorage.setItem(STORAGE_RECENTLY_VIEWED, JSON.stringify(updated))
  } catch (e) {
    console.warn('Recently viewed storage error:', e)
  }
}

// Match the currently selected variant based on selectedOptions
const currentVariant = computed(() => {
  if (!product.value || !product.value.variants || product.value.variants.length === 0) {
    return null
  }

  return (
    product.value.variants.find((v) => {
      if (!v.option_values || v.option_values.length === 0) return false
      return v.option_values.every((ov) => selectedOptions.value[ov.option_type_id] === ov.id)
    }) || null
  )
})

// Current dynamic price
const currentPrice = computed(() => {
  if (currentVariant.value) {
    return Number(currentVariant.value.price)
  }
  return Number(product.value?.base_price || 0)
})

// Current compare-at price
const compareAtPrice = computed(() => {
  if (currentVariant.value) {
    return currentVariant.value.compare_at_price ? Number(currentVariant.value.compare_at_price) : null
  }
  return product.value?.compare_at_price ? Number(product.value.compare_at_price) : null
})

// Current SKU
const currentSku = computed(() => {
  if (currentVariant.value) {
    return currentVariant.value.sku
  }
  return product.value?.sku || ''
})

// Sale calculation
const isOnSale = computed(() => {
  return compareAtPrice.value && compareAtPrice.value > currentPrice.value
})

const salePercentage = computed(() => {
  if (!isOnSale.value) return 0
  return Math.round(((compareAtPrice.value - currentPrice.value) / compareAtPrice.value) * 100)
})

// Stock calculation
const stockQty = computed(() => {
  if (currentVariant.value) {
    return currentVariant.value.stock_qty ?? 0
  }
  return product.value?.stock_qty ?? 0
})

const inStock = computed(() => {
  if (!product.value?.track_stock) return true
  return stockQty.value > 0
})

const stockStatus = computed(() => {
  if (!product.value?.track_stock) {
    return { status: 'in_stock', label: 'In Stock & Ready to Ship', color: 'text-emerald-700 bg-emerald-50 border-emerald-200' }
  }
  if (stockQty.value <= 0) {
    return { status: 'out_of_stock', label: 'Currently Out of Stock', color: 'text-rose-700 bg-rose-50 border-rose-200' }
  }
  const lowThreshold = product.value.low_stock_threshold || 5
  if (stockQty.value <= lowThreshold) {
    return {
      status: 'low_stock',
      label: `Only ${stockQty.value} left in stock — order soon`,
      color: 'text-amber-800 bg-amber-50 border-amber-200',
    }
  }
  return { status: 'in_stock', label: 'In Stock & Ready to Ship', color: 'text-emerald-700 bg-emerald-50 border-emerald-200' }
})

// Check if an option value combination is available and in stock
const isOptionAvailable = (optionTypeId, optionValueId) => {
  if (!product.value?.variants) return true

  // Check if any variant containing this option value has stock
  return product.value.variants.some((v) => {
    if (!v.is_active || (product.value.track_stock && v.stock_qty <= 0)) return false
    return v.option_values?.some((ov) => ov.id === optionValueId)
  })
}

// Select option value and update image if linked
const selectOptionValue = (optionTypeId, optionValue) => {
  selectedOptions.value[optionTypeId] = optionValue.id

  // If a matching variant has an image, jump gallery to that image
  nextTick(() => {
    if (currentVariant.value?.image) {
      activeVariantImage.value = currentVariant.value.image
    }
  })
}

// Quantity stepper
const incrementQuantity = () => {
  if (product.value?.track_stock && quantity.value >= stockQty.value) return
  quantity.value++
}

const decrementQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

// Wishlist toggle
const isWishlisted = computed(() => {
  return product.value ? wishlistStore.hasItem(product.value.id) : false
})

const toggleWishlist = () => {
  if (product.value) {
    wishlistStore.toggle(product.value)
  }
}

// Add to Cart
const handleAddToCart = async () => {
  if (!inStock.value || !product.value) return

  isAddingToCart.value = true
  try {
    const success = await cartStore.addItem(product.value, currentVariant.value, quantity.value)
    if (success) {
      cartStore.openDrawer()
    }
  } finally {
    isAddingToCart.value = false
  }
}

// Instant Checkout / Buy Now
const isInstantCheckingOut = ref(false)
const handleInstantCheckout = async () => {
  if (!inStock.value || !product.value || isInstantCheckingOut.value) return

  isInstantCheckingOut.value = true
  try {
    const success = await cartStore.addItem(product.value, currentVariant.value, quantity.value)
    if (success) {
      toastStore.info('Proceeding directly to checkout...')
      await router.push('/checkout')
    }
  } finally {
    isInstantCheckingOut.value = false
  }
}

// Share product link
const shareProduct = async () => {
  const shareData = {
    title: product.value?.name || 'Product',
    text: product.value?.short_description || '',
    url: window.location.href,
  }

  if (navigator.share) {
    try {
      await navigator.share(shareData)
    } catch (e) {
      // User cancelled share
    }
  } else {
    try {
      await navigator.clipboard.writeText(window.location.href)
      toastStore.info('Product link copied to clipboard!')
    } catch (e) {
      toastStore.error('Could not copy link.')
    }
  }
}

const notifyEmail = ref('')
const notifySubmitted = ref(false)
const notifyLoading = ref(false)

const subscribeBackInStock = async () => {
  if (!notifyEmail.value.trim() || !product.value) return
  notifyLoading.value = true
  try {
    await apiClient.post(`/products/${product.value.id}/back-in-stock`, {
      email: notifyEmail.value.trim(),
      variant_id: selectedVariant.value?.id || null,
    })
    notifySubmitted.value = true
    toastStore.addToast('We will email you as soon as this item returns in stock.', 'success')
  } catch (e) {
    toastStore.addToast('Please enter a valid email address.', 'error')
  } finally {
    notifyLoading.value = false
  }
}

// Scroll to reviews section smoothly
const scrollToReviews = () => {
  const el = document.getElementById('reviews-section')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}

// Mobile Sticky Bar Intersection Observer setup
const setupIntersectionObserver = () => {
  if (observer) {
    observer.disconnect()
    observer = null
  }

  if (!addToCartBtnRef.value) return

  observer = new IntersectionObserver(
    (entries) => {
      const entry = entries[0]
      // When the main button is scrolled out of view towards the top
      showMobileStickyBar.value = !entry.isIntersecting && entry.boundingClientRect.top < 0
    },
    { threshold: 0 }
  )

  observer.observe(addToCartBtnRef.value)
}

// Breadcrumb items
const breadcrumbs = computed(() => {
  if (!product.value) return []
  const list = [{ label: 'Shop', url: '/shop' }]
  if (product.value.category) {
    list.push({
      label: product.value.category.name,
      url: `/category/${product.value.category.slug}`,
    })
  }
  list.push({ label: product.value.name, url: null })
  return list
})

// Watch route slug changes
watch(
  () => route.params.slug,
  (newSlug) => {
    if (newSlug) {
      window.scrollTo({ top: 0, behavior: 'smooth' })
      fetchProduct(newSlug)
    }
  },
  { immediate: true }
)

onMounted(() => {
  window.addEventListener('scroll', handleWindowScroll, { passive: true })
})

onUnmounted(() => {
  if (observer) observer.disconnect()
  window.removeEventListener('scroll', handleWindowScroll)
})

const handleWindowScroll = () => {
  if (!addToCartBtnRef.value) return
  const rect = addToCartBtnRef.value.getBoundingClientRect()
  showMobileStickyBar.value = rect.bottom < 0
}

// SEO & Structured Data (Product, Offer, AggregateRating)
const pageTitle = computed(() => {
  if (!product.value) return 'Product Details'
  return product.value.seo?.title || `${product.value.name} | ${settingsStore.siteName}`
})

const pageDescription = computed(() => {
  return product.value?.seo?.description || product.value?.short_description || ''
})

const pageImage = computed(() => {
  return (
    product.value?.seo?.social_image ||
    product.value?.images?.[0]?.large_url ||
    product.value?.images?.[0]?.url ||
    ''
  )
})

const structuredData = computed(() => {
  if (!product.value) return null

  const schema = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.value.name,
    description: product.value.short_description || product.value.description,
    sku: currentSku.value,
    image: (product.value.images || []).map((img) => img.url),
    offers: {
      '@type': 'Offer',
      price: currentPrice.value.toFixed(2),
      priceCurrency: 'USD',
      availability: inStock.value ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
      url: window.location.href,
    },
  }

  if (product.value.brand) {
    schema.brand = {
      '@type': 'Brand',
      name: product.value.brand.name,
    }
  }

  if (product.value.rating_summary && product.value.rating_summary.count > 0) {
    schema.aggregateRating = {
      '@type': 'AggregateRating',
      ratingValue: product.value.rating_summary.average,
      reviewCount: product.value.rating_summary.count,
    }
  }

  return schema
})

useHead({
  title: pageTitle,
  meta: [
    { name: 'description', content: pageDescription },
    { property: 'og:title', content: pageTitle },
    { property: 'og:description', content: pageDescription },
    { property: 'og:image', content: pageImage },
    { property: 'og:type', content: 'product' },
    { property: 'og:url', content: computed(() => window.location.href) },
  ],
  script: [
    {
      type: 'application/ld+json',
      innerHTML: computed(() => (structuredData.value ? JSON.stringify(structuredData.value) : '')),
    },
  ],
})
</script>

<template>
  <div class="min-h-screen bg-bg text-ink pb-20">
    <!-- Breadcrumbs Navigation Bar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
      <Breadcrumbs v-if="breadcrumbs.length > 0" :items="breadcrumbs" />
    </div>

    <!-- 404 Not Found State -->
    <div
      v-if="notFound"
      class="max-w-3xl mx-auto px-4 py-24 text-center space-y-4"
    >
      <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4">
        <XCircle class="w-8 h-8" />
      </div>
      <h1 class="text-3xl font-extrabold text-ink tracking-tight">Product Not Found</h1>
      <p class="text-sm text-ink-muted max-w-md mx-auto">
        The piece you are looking for has been archived, renamed, or is no longer available in our collection.
      </p>
      <div class="pt-4">
        <RouterLink
          to="/shop"
          class="inline-flex items-center gap-2 px-6 py-3 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
        >
          <span>Explore Catalog</span>
          <ArrowRight class="w-4 h-4" />
        </RouterLink>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div
      v-else-if="isLoading"
      class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-pulse"
    >
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <!-- Gallery Skeleton (55%) -->
        <div class="lg:col-span-7 space-y-4">
          <div class="aspect-[4/5] w-full bg-surface rounded-card border border-line"></div>
          <div class="flex gap-3">
            <div v-for="i in 4" :key="i" class="w-16 h-20 bg-surface rounded-lg border border-line"></div>
          </div>
        </div>

        <!-- Purchase Panel Skeleton (45%) -->
        <div class="lg:col-span-5 space-y-6">
          <div class="h-4 w-24 bg-surface rounded"></div>
          <div class="h-8 w-3/4 bg-surface rounded"></div>
          <div class="h-6 w-32 bg-surface rounded"></div>
          <div class="h-16 w-full bg-surface rounded"></div>
          <div class="h-10 w-full bg-surface rounded"></div>
          <div class="h-12 w-full bg-surface rounded-pill"></div>
        </div>
      </div>
    </div>

    <!-- Main Product Content -->
    <div
      v-else-if="product"
      class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
    >
      <!-- Top Section: 55% Gallery + 45% Sticky Purchase Panel -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 xl:gap-14 items-start">
        <!-- LEFT: Gallery (55% on desktop -> col-span-7) -->
        <div class="lg:col-span-7">
          <ProductGallery
            :images="product.images || []"
            :video-url="product.video_url"
            :video-poster="product.video_poster"
            :active-variant-image="activeVariantImage"
          />
        </div>

        <!-- RIGHT: Sticky Purchase Panel (45% on desktop -> col-span-5) -->
        <div class="lg:col-span-5 lg:sticky lg:top-24 self-start space-y-6">
          <!-- Brand & Badges -->
          <div class="flex items-center justify-between gap-2">
            <span
              v-if="product.brand"
              class="text-xs font-bold uppercase tracking-wider text-brand"
            >
              {{ product.brand.name }}
            </span>
            <span
              v-else-if="product.category"
              class="text-xs font-semibold text-ink-muted uppercase tracking-wider"
            >
              {{ product.category.name }}
            </span>

            <!-- SKU Code -->
            <span v-if="currentSku" class="text-[11px] font-mono text-ink-muted">
              SKU: {{ currentSku }}
            </span>
          </div>

          <!-- Product Name -->
          <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight leading-snug">
            {{ product.name }}
          </h1>

          <!-- Rating Summary Anchor Link -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="scrollToReviews"
              class="group flex items-center gap-1.5 focus:outline-none"
              aria-label="View customer reviews"
            >
              <div class="flex items-center text-amber-400">
                <Star
                  v-for="i in 5"
                  :key="'star-' + i"
                  class="w-4 h-4 fill-current"
                />
              </div>
              <span class="text-xs font-bold text-ink group-hover:text-brand transition">
                {{ product.rating_summary?.average || '5.0' }}
              </span>
              <span class="text-xs text-ink-muted underline decoration-line group-hover:text-brand transition">
                ({{ product.rating_summary?.count || 0 }} reviews)
              </span>
            </button>
          </div>

          <!-- Price Display with Compare-At and Save % Badge -->
          <div class="flex items-baseline gap-3 flex-wrap">
            <span class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
              ${{ currentPrice.toFixed(2) }}
            </span>

            <span
              v-if="isOnSale"
              class="text-base sm:text-lg text-ink-muted line-through font-medium"
            >
              ${{ compareAtPrice.toFixed(2) }}
            </span>

            <span
              v-if="isOnSale"
              class="px-2.5 py-0.5 rounded-pill bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider"
            >
              Save {{ salePercentage }}%
            </span>
          </div>

          <!-- Short Description -->
          <p
            v-if="product.short_description"
            class="text-xs sm:text-sm text-ink-muted leading-relaxed"
          >
            {{ product.short_description }}
          </p>

          <hr class="border-line" />

          <!-- Option Selectors (Colors, Sizes, etc.) -->
          <div
            v-if="product.option_types && product.option_types.length > 0"
            class="space-y-5"
          >
            <div
              v-for="ot in product.option_types"
              :key="'opt-type-' + ot.id"
              class="space-y-2"
            >
              <!-- Option Type Header -->
              <div class="flex items-center justify-between text-xs font-semibold text-ink">
                <span>{{ ot.name }}</span>
                <span class="text-ink-muted font-normal">
                  {{
                    ot.values.find((val) => val.id === selectedOptions[ot.id])?.name ||
                    'Select an option'
                  }}
                </span>
              </div>

              <!-- Option Values List -->
              <!-- Case A: Color Swatches as Circular Buttons -->
              <div
                v-if="ot.name.toLowerCase().includes('color') || ot.values.some((v) => v.hex_color)"
                class="flex items-center gap-2.5 flex-wrap"
              >
                <button
                  v-for="val in ot.values"
                  :key="'val-' + val.id"
                  type="button"
                  @click="selectOptionValue(ot.id, val)"
                  :disabled="!isOptionAvailable(ot.id, val.id)"
                  class="w-8 h-8 rounded-full border transition-all relative flex items-center justify-center p-0.5 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed"
                  :class="[
                    selectedOptions[ot.id] === val.id
                      ? 'ring-2 ring-brand ring-offset-2 border-transparent scale-105'
                      : 'border-line hover:border-ink-muted hover:scale-102',
                  ]"
                  :title="val.name"
                  :aria-label="`Color: ${val.name}`"
                >
                  <span
                    class="w-full h-full rounded-full border border-black/10 shadow-inner"
                    :style="{ backgroundColor: val.hex_color || '#e2e8f0' }"
                  ></span>
                  <!-- Strike-through slash if out of stock -->
                  <span
                    v-if="!isOptionAvailable(ot.id, val.id)"
                    class="absolute inset-0 flex items-center justify-center pointer-events-none"
                  >
                    <span class="w-full h-[1.5px] bg-ink/70 rotate-45 rounded"></span>
                  </span>
                </button>
              </div>

              <!-- Case B: Size or other Options as Pill Chips -->
              <div
                v-else
                class="flex items-center gap-2 flex-wrap"
              >
                <button
                  v-for="val in ot.values"
                  :key="'val-' + val.id"
                  type="button"
                  @click="selectOptionValue(ot.id, val)"
                  :disabled="!isOptionAvailable(ot.id, val.id)"
                  class="px-4 py-2 rounded-pill text-xs font-semibold border transition focus:outline-none disabled:opacity-40 disabled:line-through disabled:cursor-not-allowed"
                  :class="[
                    selectedOptions[ot.id] === val.id
                      ? 'bg-brand text-white border-brand shadow-xs'
                      : 'bg-white text-ink border-line hover:border-ink-muted hover:bg-surface',
                  ]"
                  :aria-label="`${ot.name}: ${val.name}`"
                >
                  {{ val.name }}
                </button>
              </div>
            </div>
          </div>

          <!-- Stock Status Banner -->
          <div
            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-pill border text-xs font-semibold"
            :class="stockStatus.color"
          >
            <CheckCircle2 v-if="stockStatus.status === 'in_stock'" class="w-3.5 h-3.5 text-emerald-600" />
            <AlertTriangle v-else-if="stockStatus.status === 'low_stock'" class="w-3.5 h-3.5 text-amber-600" />
            <XCircle v-else class="w-3.5 h-3.5 text-rose-600" />
            <span>{{ stockStatus.label }}</span>
          </div>

          <!-- Quantity Stepper & Main Add To Cart Action -->
          <div ref="addToCartBtnRef" class="space-y-3 pt-2">
            <div class="flex items-center gap-3">
              <!-- Stepper -->
              <div class="inline-flex items-center border border-line rounded-pill bg-white px-2 py-1 shadow-xs">
                <button
                  type="button"
                  @click="decrementQuantity"
                  :disabled="quantity <= 1 || !inStock"
                  class="w-7 h-7 rounded-full flex items-center justify-center text-ink-muted hover:text-ink disabled:opacity-30 disabled:cursor-not-allowed transition"
                  aria-label="Decrease quantity"
                >
                  <Minus class="w-3.5 h-3.5" />
                </button>

                <span class="w-10 text-center font-bold text-xs text-ink select-none font-mono">
                  {{ quantity }}
                </span>

                <button
                  type="button"
                  @click="incrementQuantity"
                  :disabled="!inStock || (product.track_stock && quantity >= stockQty)"
                  class="w-7 h-7 rounded-full flex items-center justify-center text-ink-muted hover:text-ink disabled:opacity-30 disabled:cursor-not-allowed transition"
                  aria-label="Increase quantity"
                >
                  <Plus class="w-3.5 h-3.5" />
                </button>
              </div>

              <!-- Main Add To Cart Pill Button -->
              <button
                type="button"
                @click="handleAddToCart"
                :disabled="!inStock || isAddingToCart || isInstantCheckingOut"
                class="flex-1 py-3 px-5 rounded-pill border border-line bg-white hover:bg-surface text-ink text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2 shadow-xs disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Loader2 v-if="isAddingToCart" class="w-4 h-4 animate-spin" />
                <ShoppingBag v-else class="w-4 h-4" />
                <span>{{ inStock ? 'Add to Bag' : 'Sold Out' }}</span>
              </button>

              <!-- Wishlist Heart Button -->
              <button
                type="button"
                @click="toggleWishlist"
                class="w-11 h-11 rounded-full border border-line bg-white flex items-center justify-center text-ink hover:text-brand hover:border-brand/40 transition shadow-xs focus:outline-none"
                :class="{ 'text-rose-600 border-rose-200 bg-rose-50/50': isWishlisted }"
                :aria-label="isWishlisted ? 'Remove from wishlist' : 'Save to wishlist'"
              >
                <Heart
                  class="w-4 h-4 transition-transform active:scale-125"
                  :class="{ 'fill-rose-600 text-rose-600': isWishlisted }"
                />
              </button>

              <!-- Share Button -->
              <button
                type="button"
                @click="shareProduct"
                class="w-11 h-11 rounded-full border border-line bg-white flex items-center justify-center text-ink-muted hover:text-ink hover:border-ink-muted transition shadow-xs focus:outline-none"
                aria-label="Share product"
                title="Share product link"
              >
                <Share2 class="w-4 h-4" />
              </button>
            </div>

            <!-- Instant Checkout / Buy Now Button -->
            <button
              v-if="inStock"
              type="button"
              @click="handleInstantCheckout"
              :disabled="isInstantCheckingOut || isAddingToCart"
              class="w-full py-3.5 px-6 rounded-pill bg-brand hover:opacity-90 text-white text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed group"
            >
              <Loader2 v-if="isInstantCheckingOut" class="w-4 h-4 animate-spin" />
              <Zap v-else class="w-4 h-4 text-amber-300 fill-amber-300 transition-transform group-hover:scale-110" />
              <span>Instant Checkout • Buy Now (${{ (currentPrice * quantity).toFixed(2) }})</span>
            </button>

            <!-- Back-in-Stock Notify Me Box (shown when out of stock) -->
            <div
              v-if="!inStock"
              class="p-4 rounded-2xl bg-surface border border-line space-y-2.5 mt-3"
            >
              <p class="text-xs font-semibold text-ink">
                Notify me when back in stock
              </p>
              <p v-if="notifySubmitted" class="text-xs text-emerald-700 font-medium">
                You're on the waitlist! We'll email you the moment this piece returns.
              </p>
              <form v-else class="flex items-center gap-2" @submit.prevent="subscribeBackInStock">
                <input
                  v-model="notifyEmail"
                  type="email"
                  required
                  placeholder="Enter your email address..."
                  class="flex-1 px-3.5 py-2 text-xs bg-white border border-line rounded-pill focus:outline-none focus:border-ink"
                />
                <button
                  type="submit"
                  :disabled="notifyLoading"
                  class="px-4 py-2 rounded-pill bg-ink text-white text-xs font-semibold hover:opacity-90 disabled:opacity-50"
                >
                  {{ notifyLoading ? 'Saving...' : 'Notify Me' }}
                </button>
              </form>
            </div>
          </div>

          <!-- Trust & Delivery Perks -->
          <div class="pt-4 border-t border-line space-y-2.5 text-xs text-ink-muted">
            <div class="flex items-center gap-2.5">
              <Truck class="w-4 h-4 text-brand flex-shrink-0" />
              <span>
                {{ settingsStore.freeShippingThreshold > 0 ? `Free standard shipping over $${settingsStore.freeShippingThreshold}.` : 'Fast, reliable shipping worldwide.' }}
                Estimated delivery in <strong>3–5 business days</strong>.
              </span>
            </div>

            <div class="flex items-center gap-2.5">
              <RotateCcw class="w-4 h-4 text-brand flex-shrink-0" />
              <span>Complimentary 30-day returns and exchanges on unworn pieces.</span>
            </div>

            <div class="flex items-center gap-2.5">
              <ShieldCheck class="w-4 h-4 text-brand flex-shrink-0" />
              <span>Guaranteed authentic craftsmanship with full lifetime warranty.</span>
            </div>
          </div>

          <!-- Product Details & Specs Accordions -->
          <ProductAccordions
            :sections="product.sections || []"
            :specs="product.specs || []"
          />
        </div>
      </div>

      <!-- Reviews Section -->
      <ProductReviews
        :product-id="product.id"
        :rating-summary="product.rating_summary || { average: 5.0, count: 0, breakdown: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 } }"
        :initial-reviews="product.reviews || []"
      />

      <!-- Related Products Grid -->
      <section
        v-if="relatedProducts && relatedProducts.length > 0"
        class="py-14 border-t border-line"
        aria-label="Related Products"
      >
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
              Complementary Pieces
            </h2>
            <p class="text-xs sm:text-sm text-ink-muted mt-0.5">
              Curated items that pair harmoniously with this design.
            </p>
          </div>

          <RouterLink
            v-if="product.category"
            :to="`/category/${product.category.slug}`"
            class="text-xs font-semibold text-brand hover:underline inline-flex items-center gap-1"
          >
            <span>View All</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </RouterLink>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
          <ProductCard
            v-for="rel in relatedProducts"
            :key="'related-' + rel.id"
            :product="rel"
          />
        </div>
      </section>

      <!-- Recently Viewed Products -->
      <section
        v-if="recentlyViewed && recentlyViewed.length > 0"
        class="py-12 border-t border-line"
        aria-label="Recently Viewed Products"
      >
        <div class="mb-8">
          <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
            Recently Viewed
          </h2>
          <p class="text-xs sm:text-sm text-ink-muted mt-0.5">
            Pieces you recently examined during this session.
          </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
          <ProductCard
            v-for="rv in recentlyViewed"
            :key="'recent-' + rv.id"
            :product="rv"
          />
        </div>
      </section>
    </div>

    <!-- Mobile Sticky Quick-Add Bar (Scroll Triggered) -->
    <MobileStickyBar
      v-if="product"
      :product="product"
      :current-price="currentPrice"
      :in-stock="inStock"
      :is-adding="isAddingToCart"
      :is-instant-checking-out="isInstantCheckingOut"
      :visible="showMobileStickyBar"
      @add-to-cart="handleAddToCart"
      @instant-checkout="handleInstantCheckout"
    />
  </div>
</template>
