<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  X,
  ShoppingBag,
  Plus,
  Minus,
  Trash2,
  Tag,
  ArrowRight,
  Truck,
  CheckCircle2,
  Lock,
  Loader2,
} from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'

const router = useRouter()
const cartStore = useCartStore()

const couponCodeInput = ref('')
const isApplyingCoupon = ref(false)
const showCouponInput = ref(false)

const handleApplyCoupon = async () => {
  if (!couponCodeInput.value.trim()) return
  isApplyingCoupon.value = true
  const success = await cartStore.applyCoupon(couponCodeInput.value)
  isApplyingCoupon.value = false
  if (success) {
    couponCodeInput.value = ''
    showCouponInput.value = false
  }
}

const handleRemoveCoupon = async () => {
  await cartStore.removeCoupon()
}

const navigateTo = (path) => {
  cartStore.closeDrawer()
  router.push(path)
}
</script>

<template>
  <div>
    <!-- Backdrop Overlay -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="cartStore.isOpen"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs"
        @click="cartStore.closeDrawer"
        aria-hidden="true"
      ></div>
    </Transition>

    <!-- Slide-in Drawer Panel (Right Side) -->
    <Transition
      enter-active-class="transition duration-300 ease-out transform"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition duration-200 ease-in transform"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <div
        v-if="cartStore.isOpen"
        class="fixed inset-y-0 right-0 z-50 w-full max-w-md bg-white shadow-2xl flex flex-col focus:outline-none"
        role="dialog"
        aria-modal="true"
        aria-label="Shopping Bag Drawer"
      >
        <!-- Header -->
        <div class="px-5 py-4 border-b border-line flex items-center justify-between bg-surface/50">
          <div class="flex items-center gap-2">
            <ShoppingBag class="w-5 h-5 text-brand" />
            <h2 class="font-bold text-sm sm:text-base text-ink tracking-tight">
              Your Bag ({{ cartStore.itemCount }})
            </h2>
          </div>

          <button
            type="button"
            @click="cartStore.closeDrawer"
            class="p-1.5 rounded-full text-ink-muted hover:text-ink hover:bg-surface transition"
            aria-label="Close drawer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Free Shipping Progress Meter -->
        <div class="px-5 py-3 bg-surface/40 border-b border-line">
          <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
            <span v-if="cartStore.shipping.is_free" class="text-emerald-700 flex items-center gap-1">
              <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
              <span>You've unlocked free standard shipping!</span>
            </span>
            <span v-else class="text-ink-muted flex items-center gap-1">
              <Truck class="w-3.5 h-3.5 text-brand" />
              <span>Add <strong>${{ cartStore.shipping.amount_needed.toFixed(2) }}</strong> more for free shipping</span>
            </span>
            <span class="font-mono text-ink text-[11px]">
              {{ cartStore.shipping.progress_percentage }}%
            </span>
          </div>

          <div class="w-full h-1.5 bg-line rounded-pill overflow-hidden">
            <div
              class="h-full bg-brand rounded-pill transition-all duration-300"
              :style="{ width: `${cartStore.shipping.progress_percentage}%` }"
            ></div>
          </div>
        </div>

        <!-- Drawer Content -->
        <!-- State A: Empty Bag -->
        <div
          v-if="!cartStore.hasItems"
          class="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-4"
        >
          <div class="w-16 h-16 rounded-full bg-surface border border-line flex items-center justify-center text-ink-muted">
            <ShoppingBag class="w-8 h-8 opacity-60" />
          </div>
          <div>
            <h3 class="text-base font-bold text-ink">Your bag is empty</h3>
            <p class="text-xs text-ink-muted mt-1 max-w-xs mx-auto">
              Discover our latest curated seasonal collections and handcrafted essentials.
            </p>
          </div>
          <button
            type="button"
            @click="navigateTo('/shop')"
            class="px-6 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm inline-flex items-center gap-1.5"
          >
            <span>Start Shopping</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- State B: Populated Bag -->
        <div v-else class="flex-1 flex flex-col min-h-0">
          <!-- Items List -->
          <div class="flex-1 overflow-y-auto px-5 py-4 divide-y divide-line">
            <div
              v-for="item in cartStore.items"
              :key="'drawer-item-' + item.id"
              class="py-4 first:pt-0 last:pb-0 flex gap-3.5"
            >
              <!-- Thumbnail -->
              <div class="w-16 h-20 rounded-lg bg-surface border border-line overflow-hidden flex-shrink-0">
                <img
                  v-if="item.image"
                  :src="item.image"
                  :alt="item.name"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-ink-muted">
                  <ShoppingBag class="w-5 h-5 opacity-40" />
                </div>
              </div>

              <!-- Item Info -->
              <div class="flex-1 min-w-0 flex flex-col justify-between">
                <div>
                  <div class="flex items-start justify-between gap-2">
                    <RouterLink
                      :to="`/products/${item.slug}`"
                      @click="cartStore.closeDrawer"
                      class="text-xs font-semibold text-ink hover:text-brand transition truncate leading-snug"
                    >
                      {{ item.name }}
                    </RouterLink>

                    <!-- Remove Item Button -->
                    <button
                      type="button"
                      @click="cartStore.removeItem(item.id)"
                      class="text-ink-muted hover:text-rose-600 transition p-0.5"
                      aria-label="Remove item"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>

                  <!-- Variant title -->
                  <div v-if="item.variant_title" class="text-[11px] text-ink-muted mt-0.5 truncate">
                    {{ item.variant_title }}
                  </div>

                  <!-- Price -->
                  <div class="text-xs font-bold text-ink mt-1">
                    ${{ item.unit_price.toFixed(2) }}
                  </div>
                </div>

                <!-- Quantity Stepper -->
                <div class="flex items-center justify-between pt-2">
                  <div class="inline-flex items-center border border-line rounded-pill bg-white px-1.5 py-0.5">
                    <button
                      type="button"
                      @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                      :disabled="item.quantity <= 1"
                      class="w-5 h-5 rounded-full flex items-center justify-center text-ink-muted hover:text-ink disabled:opacity-30 transition"
                      aria-label="Decrease quantity"
                    >
                      <Minus class="w-3 h-3" />
                    </button>

                    <span class="w-6 text-center text-xs font-bold font-mono text-ink">
                      {{ item.quantity }}
                    </span>

                    <button
                      type="button"
                      @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                      :disabled="item.quantity >= item.max_quantity"
                      class="w-5 h-5 rounded-full flex items-center justify-center text-ink-muted hover:text-ink disabled:opacity-30 transition"
                      aria-label="Increase quantity"
                    >
                      <Plus class="w-3 h-3" />
                    </button>
                  </div>

                  <span class="text-xs font-bold text-ink font-mono">
                    ${{ item.line_total.toFixed(2) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Summary Footer -->
          <div class="p-5 border-t border-line bg-surface/40 space-y-3.5">
            <!-- Promo Code Toggle / Input -->
            <div class="space-y-2">
              <div v-if="!cartStore.coupon" class="flex items-center justify-between">
                <button
                  type="button"
                  @click="showCouponInput = !showCouponInput"
                  class="text-xs font-semibold text-brand hover:underline inline-flex items-center gap-1"
                >
                  <Tag class="w-3.5 h-3.5" />
                  <span>{{ showCouponInput ? 'Cancel promo code' : 'Have a promo code?' }}</span>
                </button>
              </div>

              <!-- Coupon input form -->
              <form
                v-if="showCouponInput && !cartStore.coupon"
                @submit.prevent="handleApplyCoupon"
                class="flex gap-2"
              >
                <input
                  v-model="couponCodeInput"
                  type="text"
                  placeholder="e.g. WELCOME10"
                  class="flex-1 px-3 py-1.5 bg-white border border-line rounded-input text-xs uppercase text-ink focus:outline-none focus:border-brand"
                />
                <button
                  type="submit"
                  :disabled="isApplyingCoupon || !couponCodeInput.trim()"
                  class="px-4 py-1.5 rounded-pill bg-ink text-white text-xs font-semibold hover:opacity-90 transition disabled:opacity-40"
                >
                  <Loader2 v-if="isApplyingCoupon" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>Apply</span>
                </button>
              </form>

              <!-- Active coupon badge -->
              <div
                v-if="cartStore.coupon"
                class="flex items-center justify-between bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-pill text-xs text-emerald-800"
              >
                <div class="flex items-center gap-1.5">
                  <Tag class="w-3.5 h-3.5 text-emerald-600" />
                  <span class="font-bold">{{ cartStore.coupon.code }}</span>
                  <span class="text-[11px] text-emerald-700">(-${{ cartStore.discount.toFixed(2) }})</span>
                </div>

                <button
                  type="button"
                  @click="handleRemoveCoupon"
                  class="text-emerald-700 hover:text-rose-600 transition p-0.5"
                  aria-label="Remove coupon"
                >
                  <X class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Totals Breakdowns -->
            <div class="space-y-1.5 text-xs">
              <div class="flex items-center justify-between text-ink-muted">
                <span>Subtotal</span>
                <span class="font-mono text-ink font-semibold">${{ cartStore.subtotal.toFixed(2) }}</span>
              </div>

              <div v-if="cartStore.discount > 0" class="flex items-center justify-between text-emerald-700">
                <span>Promotional Discount</span>
                <span class="font-mono font-semibold">-${{ cartStore.discount.toFixed(2) }}</span>
              </div>

              <div class="flex items-center justify-between text-ink-muted">
                <span>Shipping</span>
                <span>{{ cartStore.shipping.is_free ? 'Free' : 'Calculated at checkout' }}</span>
              </div>

              <div class="pt-2 border-t border-line flex items-center justify-between text-sm font-extrabold text-ink">
                <span>Estimated Total</span>
                <span class="font-mono text-base">${{ cartStore.grandTotal.toFixed(2) }}</span>
              </div>
            </div>

            <!-- Primary CTAs -->
            <div class="space-y-2 pt-1">
              <button
                type="button"
                @click="navigateTo('/checkout')"
                class="w-full py-3 px-4 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition flex items-center justify-center gap-2 shadow-sm"
              >
                <Lock class="w-3.5 h-3.5" />
                <span>Proceed to Checkout</span>
              </button>

              <button
                type="button"
                @click="navigateTo('/cart')"
                class="w-full py-2.5 px-4 rounded-pill border border-line bg-white text-xs font-semibold text-ink hover:bg-surface transition flex items-center justify-center gap-1.5 shadow-2xs"
              >
                <span>View Full Bag</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
