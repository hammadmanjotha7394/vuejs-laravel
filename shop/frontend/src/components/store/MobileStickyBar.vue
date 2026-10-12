<script setup>
import { ShoppingBag } from 'lucide-vue-next'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  currentPrice: {
    type: Number,
    required: true,
  },
  inStock: {
    type: Boolean,
    default: true,
  },
  isAdding: {
    type: Boolean,
    default: false,
  },
  visible: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['addToCart'])
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="visible"
      class="fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-line shadow-2xl px-4 py-3 sm:hidden"
      role="region"
      aria-label="Quick Add Sticky Bar"
    >
      <div class="flex items-center justify-between gap-3">
        <!-- Thumbnail and Title/Price -->
        <div class="flex items-center gap-2.5 min-w-0">
          <img
            v-if="product.images?.[0]"
            :src="product.images[0].thumb_url || product.images[0].url"
            :alt="product.name"
            class="w-10 h-10 rounded-lg object-cover border border-line flex-shrink-0"
          />
          <div class="min-w-0">
            <h4 class="text-xs font-semibold text-ink truncate leading-tight">
              {{ product.name }}
            </h4>
            <div class="text-xs font-bold text-ink mt-0.5">
              ${{ currentPrice.toFixed(2) }}
            </div>
          </div>
        </div>

        <!-- Add To Cart Pill Button -->
        <button
          type="button"
          @click="emit('addToCart')"
          :disabled="!inStock || isAdding"
          class="flex-shrink-0 px-4 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition inline-flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <ShoppingBag class="w-3.5 h-3.5" />
          <span>{{ inStock ? 'Add to Cart' : 'Sold Out' }}</span>
        </button>
      </div>
    </div>
  </Transition>
</template>
