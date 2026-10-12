<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import {
  Heart,
  ShoppingBag,
  Trash2,
  ArrowRight,
  Star,
  CheckCircle2,
} from 'lucide-vue-next'
import { useWishlistStore } from '@/stores/wishlist'
import { useCartStore } from '@/stores/cart'
import { useSettingsStore } from '@/stores/settings'

const wishlistStore = useWishlistStore()
const cartStore = useCartStore()
const settingsStore = useSettingsStore()

const handleMoveToBag = (product) => {
  cartStore.addItem(product, null, 1)
  cartStore.openDrawer()
}

useHead({
  title: computed(() => `My Wishlist (${wishlistStore.count}) | ${settingsStore.siteName}`),
  meta: [
    { name: 'description', content: 'View and manage your saved favorite pieces.' },
  ],
})
</script>

<template>
  <div class="min-h-screen bg-bg text-ink py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="flex items-center justify-between pb-6 border-b border-line mb-8">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight flex items-center gap-2.5">
            <span>My Wishlist</span>
            <span class="text-sm font-bold text-ink-muted px-2.5 py-0.5 rounded-pill bg-surface border border-line">
              {{ wishlistStore.count }}
            </span>
          </h1>
          <p class="text-xs sm:text-sm text-ink-muted mt-1">
            Curated pieces saved to your personal boutique archive.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            v-if="wishlistStore.count > 0"
            type="button"
            @click="wishlistStore.clear"
            class="text-xs text-ink-muted hover:text-rose-600 transition underline underline-offset-2"
          >
            Clear All
          </button>

          <RouterLink
            to="/shop"
            class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:underline"
          >
            <span>Continue Shopping</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </RouterLink>
        </div>
      </div>

      <!-- State A: Empty Wishlist -->
      <div
        v-if="wishlistStore.count === 0"
        class="bg-white rounded-card border border-line p-12 text-center max-w-xl mx-auto shadow-xs space-y-4 my-8"
      >
        <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center mx-auto">
          <Heart class="w-8 h-8 opacity-70" />
        </div>
        <h2 class="text-lg font-bold text-ink">Your wishlist is empty</h2>
        <p class="text-xs sm:text-sm text-ink-muted leading-relaxed max-w-md mx-auto">
          Save your favorite artisanal creations while exploring our catalog by tapping the heart icon.
        </p>
        <div class="pt-2">
          <RouterLink
            to="/shop"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
          >
            <span>Explore Catalog</span>
            <ArrowRight class="w-4 h-4" />
          </RouterLink>
        </div>
      </div>

      <!-- State B: Wishlist Grid -->
      <div
        v-else
        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6"
      >
        <div
          v-for="item in wishlistStore.items"
          :key="'wishlist-' + item.id"
          class="bg-white rounded-card border border-line overflow-hidden shadow-xs group flex flex-col justify-between"
        >
          <!-- Thumbnail & Quick Actions -->
          <div class="relative aspect-[4/5] bg-surface overflow-hidden">
            <RouterLink :to="`/products/${item.slug}`" class="block w-full h-full">
              <img
                :src="item.primary_image?.medium_url || item.primary_image?.url || item.image || 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80'"
                :alt="item.name"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
              />
            </RouterLink>

            <!-- Remove Button -->
            <button
              type="button"
              @click="wishlistStore.remove(item.id)"
              class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-white/90 backdrop-blur-xs text-rose-600 flex items-center justify-center shadow-sm hover:bg-white hover:scale-110 transition"
              aria-label="Remove from wishlist"
            >
              <Heart class="w-4 h-4 fill-rose-600" />
            </button>
          </div>

          <!-- Product Details -->
          <div class="p-4 space-y-3 flex-1 flex flex-col justify-between">
            <div class="space-y-1">
              <RouterLink
                :to="`/products/${item.slug}`"
                class="text-xs sm:text-sm font-bold text-ink hover:text-brand transition line-clamp-1"
              >
                {{ item.name }}
              </RouterLink>

              <div class="flex items-baseline gap-2">
                <span class="text-sm font-extrabold text-ink font-mono">
                  ${{ Number(item.price || 0).toFixed(2) }}
                </span>

                <span
                  v-if="item.compare_at_price && item.compare_at_price > item.price"
                  class="text-xs text-ink-muted line-through font-mono"
                >
                  ${{ Number(item.compare_at_price).toFixed(2) }}
                </span>
              </div>
            </div>

            <!-- Move To Bag Button -->
            <button
              type="button"
              @click="handleMoveToBag(item)"
              class="w-full py-2 px-3 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition flex items-center justify-center gap-1.5 shadow-2xs"
            >
              <ShoppingBag class="w-3.5 h-3.5" />
              <span>Move to Bag</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
