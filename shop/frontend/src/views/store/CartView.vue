<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useHead } from '@unhead/vue'
import {
  ShoppingBag,
  Trash2,
  Plus,
  Minus,
  Tag,
  ArrowRight,
  Truck,
  CheckCircle2,
  Lock,
  Loader2,
  X,
  ShieldCheck,
  RotateCcw,
} from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useSettingsStore } from '@/stores/settings'

const router = useRouter()
const cartStore = useCartStore()
const settingsStore = useSettingsStore()

const couponInput = ref('')
const isApplyingCoupon = ref(false)
const selectedShippingRateId = ref(null)

// Initialize or select lowest/first shipping rate
const selectedShippingRate = computed(() => {
  if (!cartStore.shipping.rates || cartStore.shipping.rates.length === 0) return null
  if (selectedShippingRateId.value) {
    return cartStore.shipping.rates.find((r) => r.id === selectedShippingRateId.value) || cartStore.shipping.rates[0]
  }
  return cartStore.shipping.rates[0]
})

const estimatedShippingCost = computed(() => {
  if (cartStore.shipping.is_free) return 0.0
  return selectedShippingRate.value ? Number(selectedShippingRate.value.cost || 0) : 0.0
})

const estimatedOrderTotal = computed(() => {
  return Math.max(0, cartStore.grandTotal + estimatedShippingCost.value)
})

const handleApplyCoupon = async () => {
  if (!couponInput.value.trim()) return
  isApplyingCoupon.value = true
  const success = await cartStore.applyCoupon(couponInput.value)
  isApplyingCoupon.value = false
  if (success) {
    couponInput.value = ''
  }
}

const handleRemoveCoupon = async () => {
  await cartStore.removeCoupon()
}

useHead({
  title: computed(() => `Shopping Bag (${cartStore.itemCount}) | ${settingsStore.siteName}`),
  meta: [
    { name: 'description', content: 'Review the items in your shopping bag before proceeding to checkout.' },
  ],
})
</script>

<template>
  <div class="min-h-screen bg-bg text-ink py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Page Header -->
      <div class="flex items-center justify-between pb-6 border-b border-line mb-8">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
            Shopping Bag
          </h1>
          <p class="text-xs sm:text-sm text-ink-muted mt-0.5">
            {{ cartStore.itemCount }} {{ cartStore.itemCount === 1 ? 'handcrafted piece' : 'pieces' }} ready for your wardrobe.
          </p>
        </div>

        <RouterLink
          to="/shop"
          class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:underline"
        >
          <span>Continue Shopping</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </RouterLink>
      </div>

      <!-- State A: Empty Bag -->
      <div
        v-if="!cartStore.hasItems"
        class="bg-white rounded-card border border-line p-12 text-center max-w-xl mx-auto shadow-xs space-y-4 my-8"
      >
        <div class="w-16 h-16 rounded-full bg-surface border border-line flex items-center justify-center text-ink-muted mx-auto">
          <ShoppingBag class="w-8 h-8 opacity-60" />
        </div>
        <h2 class="text-lg font-bold text-ink">Your shopping bag is empty</h2>
        <p class="text-xs sm:text-sm text-ink-muted leading-relaxed max-w-md mx-auto">
          Looks like you haven't added anything yet. Explore our latest arrivals, timeless ceramics, and artisan apparel.
        </p>
        <div class="pt-2">
          <RouterLink
            to="/shop"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
          >
            <span>Explore Collections</span>
            <ArrowRight class="w-4 h-4" />
          </RouterLink>
        </div>
      </div>

      <!-- State B: Populated Cart -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        <!-- LEFT: Items List (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          <!-- Free Shipping Progress Card -->
          <div class="bg-surface/50 border border-line rounded-card p-4 sm:p-5">
            <div class="flex items-center justify-between text-xs sm:text-sm font-semibold mb-2">
              <span v-if="cartStore.shipping.is_free" class="text-emerald-700 flex items-center gap-1.5">
                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                <span>You've unlocked complimentary standard shipping!</span>
              </span>
              <span v-else class="text-ink flex items-center gap-1.5">
                <Truck class="w-4 h-4 text-brand" />
                <span>Add <strong>${{ cartStore.shipping.amount_needed.toFixed(2) }}</strong> more to unlock Free Standard Shipping</span>
              </span>
              <span class="font-mono text-xs text-ink-muted">
                {{ cartStore.shipping.progress_percentage }}%
              </span>
            </div>

            <div class="w-full h-2 bg-line rounded-pill overflow-hidden">
              <div
                class="h-full bg-brand rounded-pill transition-all duration-300"
                :style="{ width: `${cartStore.shipping.progress_percentage}%` }"
              ></div>
            </div>
          </div>

          <!-- Items Table Card -->
          <div class="bg-white rounded-card border border-line overflow-hidden shadow-xs divide-y divide-line">
            <div
              v-for="item in cartStore.items"
              :key="'cart-item-' + item.id"
              class="p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
              <!-- Thumbnail & Info -->
              <div class="flex items-center gap-4 min-w-0">
                <div class="w-20 h-24 rounded-lg bg-surface border border-line overflow-hidden flex-shrink-0">
                  <img
                    v-if="item.image"
                    :src="item.image"
                    :alt="item.name"
                    class="w-full h-full object-cover"
                    loading="lazy"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-ink-muted">
                    <ShoppingBag class="w-6 h-6 opacity-40" />
                  </div>
                </div>

                <div class="min-w-0 space-y-1">
                  <RouterLink
                    :to="`/products/${item.slug}`"
                    class="text-sm font-bold text-ink hover:text-brand transition truncate block"
                  >
                    {{ item.name }}
                  </RouterLink>

                  <div v-if="item.variant_title" class="text-xs text-ink-muted">
                    {{ item.variant_title }}
                  </div>

                  <div class="text-xs font-mono text-ink-muted">
                    SKU: {{ item.sku }}
                  </div>

                  <div class="text-xs sm:text-sm font-bold text-ink sm:hidden pt-1">
                    ${{ item.unit_price.toFixed(2) }}
                  </div>
                </div>
              </div>

              <!-- Price, Stepper, Line Total, Remove -->
              <div class="w-full sm:w-auto flex items-center justify-between sm:justify-end gap-6 pt-3 sm:pt-0 border-t sm:border-t-0 border-line/50">
                <!-- Unit Price (Desktop) -->
                <div class="hidden sm:block text-right">
                  <div class="text-xs text-ink-muted">Price</div>
                  <div class="text-sm font-semibold text-ink font-mono">
                    ${{ item.unit_price.toFixed(2) }}
                  </div>
                </div>

                <!-- Quantity Stepper -->
                <div class="inline-flex items-center border border-line rounded-pill bg-white px-2 py-1 shadow-2xs">
                  <button
                    type="button"
                    @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                    :disabled="item.quantity <= 1"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-ink-muted hover:text-ink disabled:opacity-30 transition"
                    aria-label="Decrease quantity"
                  >
                    <Minus class="w-3.5 h-3.5" />
                  </button>

                  <span class="w-8 text-center text-xs font-bold font-mono text-ink">
                    {{ item.quantity }}
                  </span>

                  <button
                    type="button"
                    @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                    :disabled="item.quantity >= item.max_quantity"
                    class="w-6 h-6 rounded-full flex items-center justify-center text-ink-muted hover:text-ink disabled:opacity-30 transition"
                    aria-label="Increase quantity"
                  >
                    <Plus class="w-3.5 h-3.5" />
                  </button>
                </div>

                <!-- Line Total -->
                <div class="text-right min-w-[70px]">
                  <div class="hidden sm:block text-xs text-ink-muted">Total</div>
                  <div class="text-sm font-extrabold text-ink font-mono">
                    ${{ item.line_total.toFixed(2) }}
                  </div>
                </div>

                <!-- Remove Button -->
                <button
                  type="button"
                  @click="cartStore.removeItem(item.id)"
                  class="p-2 text-ink-muted hover:text-rose-600 transition rounded-full hover:bg-rose-50"
                  aria-label="Remove item from bag"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Bottom Actions: Clear bag & Return to catalog -->
          <div class="flex items-center justify-between text-xs text-ink-muted pt-2">
            <button
              type="button"
              @click="cartStore.clearCart"
              class="hover:text-rose-600 transition underline underline-offset-2"
            >
              Clear entire bag
            </button>

            <RouterLink
              to="/shop"
              class="hover:text-brand transition flex items-center gap-1 font-semibold"
            >
              <span>Explore more pieces</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </RouterLink>
          </div>
        </div>

        <!-- RIGHT: Order Summary Card (4 cols) -->
        <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
          <div class="bg-white rounded-card border border-line p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-ink border-b border-line pb-3">
              Order Summary
            </h2>

            <!-- Promo Coupon Form -->
            <div class="space-y-2">
              <label class="block text-xs font-semibold text-ink">
                Promotional Coupon Code
              </label>

              <!-- Input Form (if no coupon applied) -->
              <form
                v-if="!cartStore.coupon"
                @submit.prevent="handleApplyCoupon"
                class="flex gap-2"
              >
                <input
                  v-model="couponInput"
                  type="text"
                  placeholder="e.g. WELCOME10"
                  class="flex-1 px-3.5 py-2 bg-surface border border-line rounded-input text-xs uppercase text-ink focus:outline-none focus:border-brand"
                />
                <button
                  type="submit"
                  :disabled="isApplyingCoupon || !couponInput.trim()"
                  class="px-4 py-2 rounded-pill bg-ink text-white text-xs font-semibold hover:opacity-90 transition disabled:opacity-40"
                >
                  <Loader2 v-if="isApplyingCoupon" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>Apply</span>
                </button>
              </form>

              <!-- Active coupon badge -->
              <div
                v-else
                class="flex items-center justify-between bg-emerald-50 border border-emerald-200 px-3.5 py-2 rounded-pill text-xs text-emerald-800"
              >
                <div class="flex items-center gap-2">
                  <Tag class="w-4 h-4 text-emerald-600" />
                  <span class="font-bold">{{ cartStore.coupon.code }}</span>
                  <span class="text-emerald-700">(-${{ cartStore.discount.toFixed(2) }})</span>
                </div>

                <button
                  type="button"
                  @click="handleRemoveCoupon"
                  class="text-emerald-700 hover:text-rose-600 transition p-0.5"
                  aria-label="Remove coupon"
                >
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Shipping Rate Selector -->
            <div v-if="cartStore.shipping.rates && cartStore.shipping.rates.length > 0" class="space-y-2">
              <label class="block text-xs font-semibold text-ink">
                Delivery Method
              </label>
              <div class="space-y-2">
                <label
                  v-for="rate in cartStore.shipping.rates"
                  :key="'rate-' + rate.id"
                  class="flex items-center justify-between p-3 rounded-lg border text-xs cursor-pointer transition"
                  :class="[
                    (selectedShippingRateId === rate.id || (!selectedShippingRateId && rate.id === selectedShippingRate?.id))
                      ? 'border-brand bg-brand/5 ring-1 ring-brand'
                      : 'border-line hover:border-ink-muted bg-surface/40',
                  ]"
                >
                  <div class="flex items-center gap-2">
                    <input
                      type="radio"
                      name="shipping_rate"
                      :value="rate.id"
                      v-model="selectedShippingRateId"
                      class="text-brand focus:ring-brand"
                    />
                    <span class="font-medium text-ink">{{ rate.name }}</span>
                  </div>
                  <span class="font-mono font-bold" :class="rate.is_free ? 'text-emerald-700' : 'text-ink'">
                    {{ rate.is_free ? 'Free' : `$${Number(rate.cost).toFixed(2)}` }}
                  </span>
                </label>
              </div>
            </div>

            <!-- Calculations Breakdown -->
            <div class="space-y-2.5 text-xs border-t border-line pt-4">
              <div class="flex items-center justify-between text-ink-muted">
                <span>Subtotal</span>
                <span class="font-mono text-ink font-semibold">${{ cartStore.subtotal.toFixed(2) }}</span>
              </div>

              <div v-if="cartStore.discount > 0" class="flex items-center justify-between text-emerald-700">
                <span>Promotional Discount</span>
                <span class="font-mono font-semibold">-${{ cartStore.discount.toFixed(2) }}</span>
              </div>

              <div class="flex items-center justify-between text-ink-muted">
                <span>Estimated Shipping</span>
                <span class="font-mono font-semibold">
                  {{ estimatedShippingCost === 0 ? 'Free' : `$${estimatedShippingCost.toFixed(2)}` }}
                </span>
              </div>

              <div class="pt-3 border-t border-line flex items-center justify-between text-base font-extrabold text-ink">
                <span>Estimated Total</span>
                <span class="font-mono text-xl">${{ estimatedOrderTotal.toFixed(2) }}</span>
              </div>
            </div>

            <!-- Checkout CTA Button -->
            <div class="pt-2">
              <RouterLink
                to="/checkout"
                class="w-full py-3.5 px-6 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition flex items-center justify-center gap-2 shadow-sm text-center"
              >
                <Lock class="w-4 h-4" />
                <span>Proceed to Checkout</span>
              </RouterLink>
            </div>

            <!-- Trust Points -->
            <div class="pt-4 border-t border-line/60 space-y-2 text-[11px] text-ink-muted">
              <div class="flex items-center gap-2">
                <ShieldCheck class="w-3.5 h-3.5 text-brand flex-shrink-0" />
                <span>Encrypted 256-bit SSL transaction via Stripe.</span>
              </div>
              <div class="flex items-center gap-2">
                <RotateCcw class="w-3.5 h-3.5 text-brand flex-shrink-0" />
                <span>Complimentary 30-day hassle-free returns.</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
