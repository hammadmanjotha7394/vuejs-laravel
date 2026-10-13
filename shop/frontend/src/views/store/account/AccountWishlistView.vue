<script setup>
import { onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import { useWishlistStore } from '@/stores/wishlist'
import { useCartStore } from '@/stores/cart'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import { Heart, ShoppingBag, Trash2, ArrowRight, Package } from 'lucide-vue-next'

const wishlistStore = useWishlistStore()
const cartStore = useCartStore()
const settingsStore = useSettingsStore()
const toast = useToastStore()

useHead({
  title: () => `My Wishlist | ${settingsStore.siteName}`,
})

const handleMoveToCart = async (product) => {
  try {
    await cartStore.addItem({
      productId: product.id,
      variantId: null,
      qty: 1,
      openDrawer: true,
    })
    await wishlistStore.removeItem(product.id)
    toast.success(`Moved "${product.name}" to your bag.`)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Unable to add item to cart.')
  }
}

const handleRemove = async (product) => {
  await wishlistStore.removeItem(product.id)
  toast.info(`Removed "${product.name}" from wishlist.`)
}

onMounted(() => {
  wishlistStore.fetchWishlist()
})
</script>

<template>
  <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-5 border-b border-line mb-6">
      <div>
        <h2 class="text-base sm:text-lg font-bold text-ink">Saved Wishlist</h2>
        <p class="text-xs text-ink-muted">
          Items you've saved for later — move them to your bag anytime
        </p>
      </div>
      <span class="text-xs font-semibold text-ink-muted">
        {{ wishlistStore.count }} {{ wishlistStore.count === 1 ? 'item' : 'items' }}
      </span>
    </div>

    <div v-if="wishlistStore.loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i" class="h-64 rounded-xl bg-surface animate-pulse" />
    </div>

    <div v-else-if="wishlistStore.items.length === 0" class="text-center py-12">
      <div class="w-12 h-12 rounded-full bg-surface text-ink-muted flex items-center justify-center mx-auto mb-3">
        <Heart class="w-5 h-5" />
      </div>
      <p class="text-sm font-semibold text-ink">Your wishlist is empty</p>
      <p class="text-xs text-ink-muted mt-1 mb-5">
        Tap the heart icon on any product to save it to your personal wishlist.
      </p>
      <RouterLink
        to="/shop"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
      >
        <span>Explore Collection</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </RouterLink>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="product in wishlistStore.items"
        :key="product.id"
        class="rounded-card border border-line p-4 flex flex-col justify-between hover:border-brand/40 transition"
      >
        <div>
          <RouterLink
            :to="`/products/${product.slug}`"
            class="block aspect-square rounded-xl bg-surface overflow-hidden mb-3 relative"
          >
            <img
              v-if="product.primary_image"
              :src="product.primary_image"
              :alt="product.name"
              class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-ink-muted">
              <Package class="w-6 h-6" />
            </div>
          </RouterLink>

          <RouterLink
            :to="`/products/${product.slug}`"
            class="text-sm font-bold text-ink hover:text-brand transition line-clamp-1"
          >
            {{ product.name }}
          </RouterLink>

          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm font-bold text-ink">
              {{ settingsStore.formatPrice(product.effective_price ?? product.price) }}
            </span>
            <span
              v-if="product.sale_price && product.sale_price < product.price"
              class="text-xs text-ink-muted line-through"
            >
              {{ settingsStore.formatPrice(product.price) }}
            </span>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-line flex items-center gap-2">
          <button
            type="button"
            :disabled="!product.in_stock"
            @click="handleMoveToCart(product)"
            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark disabled:opacity-40 transition"
          >
            <ShoppingBag class="w-3.5 h-3.5" />
            <span>{{ product.in_stock ? 'Move to Bag' : 'Out of Stock' }}</span>
          </button>

          <button
            type="button"
            @click="handleRemove(product)"
            class="p-2 rounded-full border border-line text-ink-muted hover:text-error hover:border-red-200 hover:bg-red-50 transition"
            aria-label="Remove from wishlist"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
