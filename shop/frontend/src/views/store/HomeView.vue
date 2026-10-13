<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import axios from 'axios'
import {
  ArrowRight,
  ChevronLeft,
  ChevronRight,
  Sparkles,
  Flame,
  CheckCircle2,
  Loader2,
} from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'
import { useMenuStore } from '@/stores/menu'
import { useToastStore } from '@/stores/toast'
import HeroSlider from '@/components/store/HeroSlider.vue'
import CircularCategories from '@/components/store/CircularCategories.vue'
import ProductCard from '@/components/store/ProductCard.vue'
import PromoBanners from '@/components/store/PromoBanners.vue'
import TrustPoints from '@/components/store/TrustPoints.vue'
import HomeReviewsStrip from '@/components/store/HomeReviewsStrip.vue'

const settingsStore = useSettingsStore()
const menuStore = useMenuStore()
const toastStore = useToastStore()

useHead({
  title: computed(() => `${settingsStore.siteName} — ${settingsStore.siteTagline || 'Thoughtfully Crafted Goods'}`),
  meta: [
    {
      name: 'description',
      content: computed(() => settingsStore.publicSettings?.seo?.description || 'Conscious apparel, tactile homeware, and everyday accessories made in European and Japanese artisan workshops.'),
    },
    {
      property: 'og:title',
      content: computed(() => `${settingsStore.siteName} — ${settingsStore.siteTagline || 'Thoughtfully Crafted Goods'}`),
    },
    {
      property: 'og:description',
      content: computed(() => settingsStore.publicSettings?.seo?.description || 'Minimalist apparel and everyday homeware designed to outlast fleeting trends.'),
    },
  ],
})

// Data States
const heroSlides = ref([])
const categories = ref([])
const featuredProducts = ref([])
const bestSellerProducts = ref([])

// Tabs for Section 3: Featured vs New Arrivals
const activeTab = ref('featured') // 'featured' | 'new'
const newArrivalProducts = ref([])

// Loading states
const loadingHero = ref(true)
const loadingProducts = ref(true)
const loadingBestSellers = ref(true)

// Best sellers carousel ref for horizontal scroll buttons
const bestSellersContainer = ref(null)

// Newsletter inline form state
const newsletterEmail = ref('')
const isSubscribing = ref(false)
const subscribedSuccess = ref(false)

const scrollBestSellers = (direction) => {
  if (!bestSellersContainer.value) return
  const scrollAmount = 340
  if (direction === 'left') {
    bestSellersContainer.value.scrollBy({ left: -scrollAmount, behavior: 'smooth' })
  } else {
    bestSellersContainer.value.scrollBy({ left: scrollAmount, behavior: 'smooth' })
  }
}

const handleNewsletterSubscribe = async () => {
  if (!newsletterEmail.value.trim()) return

  isSubscribing.value = true
  try {
    const res = await axios.post('/api/newsletter/subscribe', {
      email: newsletterEmail.value.trim(),
    })
    subscribedSuccess.value = true
    toastStore.success(res.data.message || 'Subscribed successfully!')
    newsletterEmail.value = ''
  } catch (err) {
    const msg =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Failed to subscribe.'
    toastStore.error(msg)
  } finally {
    isSubscribing.value = false
  }
}

const fetchHomeData = async () => {
  // 1. Fetch Hero Slides
  try {
    const heroRes = await axios.get('/api/hero-slides')
    heroSlides.value = heroRes.data.data || []
  } catch (err) {
    console.warn('Failed to load hero slides:', err)
  } finally {
    loadingHero.value = false
  }

  // 2. Fetch Featured Categories
  try {
    const catRes = await axios.get('/api/categories')
    // Extract top-level or featured categories
    const allCats = catRes.data.data || []
    categories.value = allCats.filter((c) => c.is_featured || !c.parent_id).slice(0, 6)
  } catch (err) {
    console.warn('Failed to load categories:', err)
  }

  // 3. Fetch Featured Products & New Arrivals
  try {
    const [featRes, newRes] = await Promise.allSettled([
      axios.get('/api/products?featured=1&per_page=8'),
      axios.get('/api/products?sort=newest&per_page=8'),
    ])

    if (featRes.status === 'fulfilled') {
      featuredProducts.value = featRes.value.data.data || []
    }
    if (newRes.status === 'fulfilled') {
      newArrivalProducts.value = newRes.value.data.data || []
    }
  } catch (err) {
    console.warn('Failed to load featured products:', err)
  } finally {
    loadingProducts.value = false
  }

  // 4. Fetch Best Sellers
  try {
    const bestRes = await axios.get('/api/products?sort=popular&per_page=8')
    bestSellerProducts.value = bestRes.data.data || []
  } catch (err) {
    console.warn('Failed to load best sellers:', err)
  } finally {
    loadingBestSellers.value = false
  }
}

onMounted(() => {
  fetchHomeData()
})
</script>

<template>
  <div class="space-y-4 sm:space-y-8 pb-16">
    <!-- SECTION 1: HERO SLIDER -->
    <div v-if="loadingHero" class="w-full min-h-[500px] sm:min-h-[580px] lg:min-h-[640px] bg-surface animate-pulse flex items-center justify-center">
      <div class="w-8 h-8 rounded-full border-2 border-brand border-t-transparent animate-spin"></div>
    </div>
    <HeroSlider v-else :slides="heroSlides" />

    <!-- SECTION 2: CIRCULAR CATEGORIES ROW -->
    <CircularCategories :categories="categories" />

    <!-- SECTION 3: FEATURED / NEW ARRIVALS GRID -->
    <section class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 sm:mb-12">
        <div>
          <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
            Curated For Living
          </h2>
          <p class="text-xs sm:text-sm text-ink-muted mt-1.5">
            Distinctive essentials crafted in honest materials with timeless proportion.
          </p>
        </div>

        <!-- Filter Tab Buttons: Featured vs New Arrivals -->
        <div class="inline-flex items-center p-1 rounded-pill bg-surface border border-line self-start sm:self-auto text-xs">
          <button
            type="button"
            @click="activeTab = 'featured'"
            class="px-4 py-1.5 rounded-pill font-semibold transition"
            :class="activeTab === 'featured' ? 'bg-white text-brand shadow-sm' : 'text-ink-muted hover:text-ink'"
          >
            Featured
          </button>
          <button
            type="button"
            @click="activeTab = 'new'"
            class="px-4 py-1.5 rounded-pill font-semibold transition"
            :class="activeTab === 'new' ? 'bg-white text-brand shadow-sm' : 'text-ink-muted hover:text-ink'"
          >
            New Arrivals
          </button>
        </div>
      </div>

      <!-- Skeletons while loading -->
      <div
        v-if="loadingProducts"
        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6"
      >
        <div
          v-for="i in 8"
          :key="'skel-' + i"
          class="aspect-[4/5] bg-surface rounded-card animate-pulse border border-line"
        ></div>
      </div>

      <!-- Products Grid (2 mobile, 3 tablet, 4 desktop) -->
      <div
        v-else
        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6"
      >
        <ProductCard
          v-for="product in (activeTab === 'featured' ? (featuredProducts.length ? featuredProducts : newArrivalProducts) : newArrivalProducts)"
          :key="'prod-' + product.id"
          :product="product"
        />
      </div>

      <div class="mt-10 sm:mt-12 text-center">
        <RouterLink
          to="/shop"
          class="inline-flex items-center gap-2 px-7 py-3 rounded-pill border border-line bg-white hover:bg-surface text-ink text-xs sm:text-sm font-semibold transition shadow-sm"
        >
          <span>View Complete Catalog</span>
          <ArrowRight class="w-4 h-4" />
        </RouterLink>
      </div>
    </section>

    <!-- SECTION 4: PROMO BANNERS -->
    <PromoBanners />

    <!-- SECTION 5: BEST SELLERS CAROUSEL -->
    <section class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
      <div class="flex items-end justify-between gap-4 mb-8">
        <div>
          <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-pill bg-amber-50 text-amber-700 text-[11px] font-bold mb-1.5 border border-amber-200">
            <Flame class="w-3.5 h-3.5 fill-current" />
            <span>Customer Favorites</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
            Best Selling Essentials
          </h2>
        </div>

        <!-- Carousel Navigation Buttons -->
        <div class="hidden sm:flex items-center gap-2">
          <button
            type="button"
            @click="scrollBestSellers('left')"
            class="w-9 h-9 rounded-full bg-white border border-line flex items-center justify-center text-ink hover:text-brand hover:border-brand shadow-sm transition"
            aria-label="Scroll left"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>
          <button
            type="button"
            @click="scrollBestSellers('right')"
            class="w-9 h-9 rounded-full bg-white border border-line flex items-center justify-center text-ink hover:text-brand hover:border-brand shadow-sm transition"
            aria-label="Scroll right"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Horizontal scroll container -->
      <div
        ref="bestSellersContainer"
        class="flex gap-4 sm:gap-6 overflow-x-auto pb-6 pt-2 snap-x snap-mandatory scroll-smooth no-scrollbar"
      >
        <div
          v-for="product in bestSellerProducts"
          :key="'best-' + product.id"
          class="w-[240px] sm:w-[280px] lg:w-[300px] flex-shrink-0 snap-start"
        >
          <ProductCard :product="product" />
        </div>
      </div>
    </section>

    <!-- SECTION 6: WHY SHOP WITH US (TRUST POINTS) -->
    <TrustPoints />

    <!-- SECTION 7: CUSTOMER REVIEWS STRIP -->
    <HomeReviewsStrip />

    <!-- SECTION 8: NEWSLETTER SIGNUP -->
    <section class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
      <div class="bg-surface border border-line rounded-card p-8 sm:p-14 text-center max-w-3xl mx-auto shadow-sm">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand/10 text-brand mb-4">
          <Sparkles class="w-6 h-6" />
        </div>

        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
          Receive 10% Off Your First Order
        </h2>
        <p class="text-xs sm:text-sm text-ink-muted max-w-md mx-auto mt-2 leading-relaxed">
          Subscribe for quiet reflections on craftsmanship, early access to limited artisan drops, and invitations to private archive sales.
        </p>

        <!-- One-line Newsletter Form -->
        <div class="mt-8 max-w-md mx-auto">
          <div
            v-if="subscribedSuccess"
            class="p-3.5 bg-emerald-50 border border-emerald-200 text-xs font-medium text-success rounded-card flex items-center justify-center gap-2"
          >
            <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
            <span>Thank you! Your 10% welcome voucher code is on its way.</span>
          </div>

          <form
            v-else
            @submit.prevent="handleNewsletterSubscribe"
            class="flex items-center gap-2"
          >
            <input
              v-model="newsletterEmail"
              type="email"
              required
              placeholder="Enter your email address"
              class="flex-1 px-4 py-3 bg-white border border-line rounded-pill text-xs sm:text-sm text-ink placeholder:text-ink-muted focus:outline-none focus:border-brand shadow-sm transition"
            />
            <button
              type="submit"
              :disabled="isSubscribing"
              class="px-6 py-3 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition flex items-center gap-1.5 flex-shrink-0 disabled:opacity-60 shadow-sm"
            >
              <Loader2 v-if="isSubscribing" class="w-4 h-4 animate-spin" />
              <span v-else>Subscribe</span>
              <ArrowRight v-if="!isSubscribing" class="w-3.5 h-3.5" />
            </button>
          </form>
          <p class="text-[11px] text-ink-muted mt-2.5">
            We value your digital calm. Unsubscribe with one click anytime.
          </p>
        </div>
      </div>
    </section>
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
