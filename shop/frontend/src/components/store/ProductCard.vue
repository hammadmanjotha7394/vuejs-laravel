<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Heart, Star, ShoppingBag, ArrowRight, Play, Zap, Loader2 } from 'lucide-vue-next'
import { useWishlistStore } from '@/stores/wishlist'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import { useSettingsStore } from '@/stores/settings'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const wishlistStore = useWishlistStore()
const cartStore = useCartStore()
const toastStore = useToastStore()
const settingsStore = useSettingsStore()
const router = useRouter()

const videoRef = ref(null)
const isVideoLoaded = ref(false)
const isHovered = ref(false)
const isInstantBuying = ref(false)

const isSaved = computed(() => wishlistStore.hasItem(props.product.id))

const handleMouseEnter = () => {
  isHovered.value = true
  if (props.product.video_url && videoRef.value) {
    if (!isVideoLoaded.value) {
      videoRef.value.src = props.product.video_url
      isVideoLoaded.value = true
    }
    videoRef.value.play().catch(() => {
      // Autoplay muted can fail gracefully
    })
  }
}

const handleMouseLeave = () => {
  isHovered.value = false
  if (videoRef.value && isVideoLoaded.value) {
    videoRef.value.pause()
    videoRef.value.currentTime = 0
  }
}

const toggleWishlist = (e) => {
  e.preventDefault()
  e.stopPropagation()
  wishlistStore.toggle(props.product)
}

const handleQuickAction = (e) => {
  e.preventDefault()
  e.stopPropagation()

  if (props.product.has_variants) {
    // Navigate to product page for option selection
    router.push(`/products/${props.product.slug}`)
  } else {
    // Quick Add to bag
    cartStore.addItem(props.product, null, 1).then((success) => {
      if (success) {
        cartStore.openDrawer()
      }
    })
  }
}

const handleInstantBuy = async (e) => {
  e.preventDefault()
  e.stopPropagation()

  if (props.product.has_variants) {
    router.push(`/products/${props.product.slug}`)
    return
  }

  isInstantBuying.value = true
  try {
    const success = await cartStore.addItem(props.product, null, 1)
    if (success) {
      toastStore.info('Proceeding directly to checkout...')
      await router.push('/checkout')
    }
  } finally {
    isInstantBuying.value = false
  }
}

const primaryImageUrl = computed(() => {
  return (
    props.product.primary_image?.medium_url ||
    props.product.primary_image?.url ||
    props.product.thumbnail ||
    'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80'
  )
})

const secondImageUrl = computed(() => {
  return (
    props.product.second_image?.medium_url ||
    props.product.second_image?.url ||
    null
  )
})
</script>

<template>
  <div
    class="group relative flex flex-col bg-white rounded-card border border-line overflow-hidden transition-all duration-300 hover:shadow-soft hover:border-brand/30"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <!-- Visual Image / Media Container (4:5 Aspect Ratio) -->
    <div class="relative aspect-[4/5] w-full bg-surface overflow-hidden">
      <RouterLink
        :to="'/products/' + product.slug"
        class="block w-full h-full focus:outline-none"
        :aria-label="product.name"
      >
        <!-- Primary Image -->
        <img
          :src="primaryImageUrl"
          :alt="product.name"
          class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
          loading="lazy"
          width="400"
          height="500"
        />

        <!-- Secondary Image (Fades in on hover) -->
        <img
          v-if="secondImageUrl && !product.video_url"
          :src="secondImageUrl"
          :alt="product.name + ' - alternate view'"
          class="absolute inset-0 w-full h-full object-cover opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
          loading="lazy"
          width="400"
          height="500"
        />

        <!-- Desktop Inline Video Preview (Plays on desktop hover, muted/loop) -->
        <video
          v-if="product.video_url"
          ref="videoRef"
          muted
          loop
          playsinline
          preload="none"
          :poster="product.video_poster || primaryImageUrl"
          class="hidden md:block absolute inset-0 w-full h-full object-cover transition-opacity duration-300 pointer-events-none"
          :class="isHovered && isVideoLoaded ? 'opacity-100' : 'opacity-0'"
        ></video>
      </RouterLink>

      <!-- Badges: Top Left -->
      <div class="absolute top-3 left-3 flex flex-col gap-1 z-10 pointer-events-none">
        <span
          v-if="product.is_on_sale"
          class="px-2.5 py-1 rounded-pill bg-error text-white text-[10px] font-bold tracking-tight shadow-sm"
        >
          -{{ product.sale_percentage }}%
        </span>
        <span
          v-else-if="product.is_featured"
          class="px-2.5 py-1 rounded-pill bg-white/90 backdrop-blur-sm border border-line text-ink text-[10px] font-semibold shadow-sm"
        >
          Curated
        </span>

        <!-- Mobile Video indicator pill (small play icon) -->
        <span
          v-if="product.video_url"
          class="md:hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-pill bg-black/60 backdrop-blur-sm text-white text-[9px] font-semibold"
        >
          <Play class="w-2.5 h-2.5 fill-current" />
          <span>Video</span>
        </span>
      </div>

      <!-- Wishlist Button: Top Right -->
      <button
        type="button"
        @click="toggleWishlist"
        class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm border border-line/80 flex items-center justify-center text-ink hover:text-error hover:scale-110 transition shadow-sm"
        :aria-label="isSaved ? 'Remove from wishlist' : 'Save to wishlist'"
      >
        <Heart
          class="w-4 h-4 transition-colors"
          :class="isSaved ? 'fill-error text-error' : 'text-ink-muted'"
        />
      </button>

      <!-- Quick Action Pill Buttons on Hover (Bottom of image) -->
      <div class="absolute inset-x-2.5 bottom-2.5 z-10 opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all duration-200 flex items-center gap-1.5">
        <button
          type="button"
          @click="handleQuickAction"
          class="flex-1 py-2 px-2.5 rounded-pill bg-white/95 backdrop-blur-md text-ink text-[11px] font-semibold shadow-md border border-line hover:bg-surface transition flex items-center justify-center gap-1"
          :title="product.has_variants ? 'Choose Options' : 'Add to Bag'"
        >
          <ShoppingBag v-if="!product.has_variants" class="w-3 h-3" />
          <ArrowRight v-else class="w-3 h-3" />
          <span class="truncate">{{ product.has_variants ? 'Options' : 'Add' }}</span>
        </button>

        <button
          type="button"
          @click="handleInstantBuy"
          :disabled="isInstantBuying"
          class="py-2 px-3 rounded-pill bg-brand text-white text-[11px] font-bold shadow-md hover:opacity-90 transition flex items-center justify-center gap-1 flex-shrink-0 disabled:opacity-60"
          title="Instant Checkout • Buy Now"
        >
          <Loader2 v-if="isInstantBuying" class="w-3 h-3 animate-spin" />
          <Zap v-else class="w-3 h-3 text-amber-300 fill-amber-300" />
          <span>Buy</span>
        </button>
      </div>
    </div>

    <!-- Product Details Content -->
    <div class="p-4 flex-1 flex flex-col justify-between">
      <div>
        <!-- Category or Brand -->
        <p
          v-if="product.brand?.name || product.category?.name"
          class="text-[11px] font-medium text-ink-muted uppercase tracking-wider mb-1 truncate"
        >
          {{ product.brand?.name || product.category?.name }}
        </p>

        <!-- Product Title Link -->
        <h3 class="text-xs sm:text-sm font-semibold text-ink line-clamp-1 group-hover:text-brand transition-colors">
          <RouterLink :to="'/products/' + product.slug">
            {{ product.name }}
          </RouterLink>
        </h3>
      </div>

      <!-- Price & Rating Row -->
      <div class="mt-2.5 pt-2 border-t border-line/40 flex items-center justify-between gap-2">
        <!-- Price Display -->
        <div class="flex items-baseline gap-1.5 flex-wrap">
          <span class="text-sm font-bold text-ink">
            {{ settingsStore.formatPrice(product.price) }}
          </span>
          <span
            v-if="product.compare_at_price && product.compare_at_price > product.price"
            class="text-xs text-ink-muted line-through font-normal"
          >
            {{ settingsStore.formatPrice(product.compare_at_price) }}
          </span>
        </div>

        <!-- Rating Stars -->
        <div
          v-if="product.rating"
          class="flex items-center gap-1 text-[11px] font-semibold text-ink-muted flex-shrink-0"
        >
          <Star class="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
          <span>{{ product.rating }}</span>
          <span v-if="product.reviews_count" class="text-[10px] text-gray-400 font-normal">
            ({{ product.reviews_count }})
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
