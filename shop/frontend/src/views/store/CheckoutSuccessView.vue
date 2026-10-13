<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import axios from 'axios'
import {
  CheckCircle2,
  Package,
  MapPin,
  Mail,
  ArrowRight,
  ShoppingBag,
  UserPlus,
  Loader2,
  XCircle,
} from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const cartStore = useCartStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const toastStore = useToastStore()

const isLoading = ref(true)
const verifyError = ref(false)
const order = ref(null)

// Optional guest account creation form
const guestPassword = ref('')
const guestPasswordConfirmation = ref('')
const isCreatingAccount = ref(false)
const accountCreated = ref(false)

const fetchVerifiedOrder = async () => {
  const orderNumber = route.query.order
  const sessionId = route.query.session_id

  if (!orderNumber || !sessionId) {
    verifyError.value = true
    isLoading.value = false
    return
  }

  isLoading.value = true
  verifyError.value = false

  try {
    const res = await axios.get('/api/checkout/verify', {
      params: {
        order: orderNumber,
        session_id: sessionId,
      },
    })
    order.value = res.data.data
    // Refresh cart store state
    await cartStore.fetchCart()
  } catch (err) {
    verifyError.value = true
  } finally {
    isLoading.value = false
  }
}

const handleCreateGuestAccount = async () => {
  if (!guestPassword.value || guestPassword.value.length < 8) {
    toastStore.error('Password must be at least 8 characters.')
    return
  }
  if (guestPassword.value !== guestPasswordConfirmation.value) {
    toastStore.error('Passwords do not match.')
    return
  }

  isCreatingAccount.value = true
  try {
    const addr = order.value?.shipping_address || {}
    const fullName = `${addr.first_name || ''} ${addr.last_name || ''}`.trim() || 'Customer'

    await authStore.register({
      name: fullName,
      email: order.value.guest_email,
      password: guestPassword.value,
      password_confirmation: guestPasswordConfirmation.value,
    })

    accountCreated.value = true
    toastStore.success('Account created! You are now signed in.')
  } catch (err) {
    const msg =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Could not create account.'
    toastStore.error(msg)
  } finally {
    isCreatingAccount.value = false
  }
}

onMounted(() => {
  fetchVerifiedOrder()
})

useHead({
  title: computed(() => `Order Confirmed | ${settingsStore.siteName}`),
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
})
</script>

<template>
  <div class="min-h-screen bg-bg text-ink py-10 sm:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div
        v-if="isLoading"
        class="bg-white rounded-card border border-line p-12 text-center shadow-xs space-y-4"
      >
        <Loader2 class="w-8 h-8 text-brand animate-spin mx-auto" />
        <p class="text-sm font-semibold text-ink">Verifying your order details...</p>
      </div>

      <!-- Verification Error State -->
      <div
        v-else-if="verifyError || !order"
        class="bg-white rounded-card border border-line p-12 text-center shadow-xs space-y-4"
      >
        <div class="w-14 h-14 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
          <XCircle class="w-7 h-7" />
        </div>
        <h1 class="text-xl font-extrabold text-ink">Order Verification Failed</h1>
        <p class="text-xs sm:text-sm text-ink-muted max-w-md mx-auto">
          We could not verify this order session reference. Please check your confirmation email or return to the store.
        </p>
        <div class="pt-2">
          <RouterLink
            to="/shop"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
          >
            <span>Return to Storefront</span>
            <ArrowRight class="w-4 h-4" />
          </RouterLink>
        </div>
      </div>

      <!-- Verified Order Summary -->
      <div v-else class="space-y-6">
        <!-- Confirmation Banner Card -->
        <div class="bg-white rounded-card border border-line p-6 sm:p-8 shadow-xs text-center space-y-3">
          <div class="w-16 h-16 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto">
            <CheckCircle2 class="w-8 h-8" />
          </div>

          <span class="inline-block text-[11px] font-bold uppercase tracking-widest text-brand">
            Thank You For Your Order
          </span>

          <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
            Order #{{ order.order_number }}
          </h1>

          <p class="text-xs sm:text-sm text-ink-muted max-w-lg mx-auto">
            We've received your order and sent a confirmation receipt to
            <strong class="text-ink">{{ order.guest_email }}</strong>.
          </p>

          <div class="pt-2 flex items-center justify-center gap-2 flex-wrap">
            <span class="px-3 py-1 rounded-pill bg-surface border border-line text-xs font-semibold text-ink">
              Placed: {{ order.placed_at }}
            </span>
            <span
              class="px-3 py-1 rounded-pill text-xs font-bold uppercase tracking-wider border"
              :class="
                order.payment_status === 'paid'
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-amber-50 text-amber-800 border-amber-200'
              "
            >
              Payment: {{ order.payment_status }}
            </span>
          </div>
        </div>

        <!-- Order Items & Totals Card -->
        <div class="bg-white rounded-card border border-line p-6 sm:p-8 shadow-xs space-y-6">
          <h2 class="text-base font-bold text-ink flex items-center gap-2 border-b border-line pb-3">
            <Package class="w-4 h-4 text-brand" />
            <span>Purchased Items ({{ order.items.length }})</span>
          </h2>

          <div class="divide-y divide-line">
            <div
              v-for="item in order.items"
              :key="'ord-item-' + item.id"
              class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4"
            >
              <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-14 h-16 rounded-lg bg-surface border border-line overflow-hidden flex-shrink-0">
                  <img
                    v-if="item.image"
                    :src="item.image"
                    :alt="item.name"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-ink-muted">
                    <ShoppingBag class="w-5 h-5 opacity-40" />
                  </div>
                </div>

                <div class="min-w-0">
                  <h4 class="text-xs sm:text-sm font-bold text-ink truncate">{{ item.name }}</h4>
                  <p v-if="item.sku" class="text-[11px] font-mono text-ink-muted">SKU: {{ item.sku }}</p>
                  <p class="text-xs text-ink-muted mt-0.5">
                    Qty: <strong>{{ item.qty }}</strong> × ${{ item.unit_price.toFixed(2) }}
                  </p>
                </div>
              </div>

              <div class="text-xs sm:text-sm font-extrabold font-mono text-ink flex-shrink-0">
                ${{ item.line_total.toFixed(2) }}
              </div>
            </div>
          </div>

          <!-- Totals Breakdown -->
          <div class="border-t border-line pt-4 space-y-2 text-xs">
            <div class="flex justify-between text-ink-muted">
              <span>Subtotal</span>
              <span class="font-mono font-semibold text-ink">${{ order.subtotal.toFixed(2) }}</span>
            </div>

            <div v-if="order.discount_total > 0" class="flex justify-between text-emerald-700">
              <span>Discount {{ order.coupon_code ? `(${order.coupon_code})` : '' }}</span>
              <span class="font-mono font-semibold">-${{ order.discount_total.toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-ink-muted">
              <span>Shipping</span>
              <span class="font-mono font-semibold text-ink">
                {{ order.shipping_total === 0 ? 'Free' : `$${order.shipping_total.toFixed(2)}` }}
              </span>
            </div>

            <div v-if="order.tax_total > 0" class="flex justify-between text-ink-muted">
              <span>Tax</span>
              <span class="font-mono font-semibold text-ink">${{ order.tax_total.toFixed(2) }}</span>
            </div>

            <div class="pt-2.5 border-t border-line flex justify-between text-sm sm:text-base font-extrabold text-ink">
              <span>Total</span>
              <span class="font-mono text-lg">${{ order.grand_total.toFixed(2) }} {{ order.currency }}</span>
            </div>
          </div>
        </div>

        <!-- Shipping Destination Card -->
        <div
          v-if="order.shipping_address"
          class="bg-white rounded-card border border-line p-6 shadow-xs space-y-2"
        >
          <h3 class="text-xs font-bold uppercase tracking-wider text-ink-muted flex items-center gap-1.5">
            <MapPin class="w-3.5 h-3.5 text-brand" />
            <span>Shipping Destination</span>
          </h3>
          <div class="text-xs sm:text-sm text-ink space-y-0.5">
            <p class="font-bold">{{ order.shipping_address.first_name }} {{ order.shipping_address.last_name }}</p>
            <p>{{ order.shipping_address.line1 }}</p>
            <p v-if="order.shipping_address.line2">{{ order.shipping_address.line2 }}</p>
            <p>
              {{ order.shipping_address.city }}, {{ order.shipping_address.state }}
              {{ order.shipping_address.postal_code }}
            </p>
            <p>{{ order.shipping_address.country }}</p>
          </div>
        </div>

        <!-- Guest Option to Create Account -->
        <div
          v-if="!authStore.isAuthenticated && !accountCreated"
          class="bg-surface/60 rounded-card border border-line p-6 sm:p-8 space-y-4"
        >
          <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-full bg-brand/10 text-brand flex items-center justify-center flex-shrink-0">
              <UserPlus class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-ink">Save your details for next time</h3>
              <p class="text-xs text-ink-muted mt-0.5">
                Create an account with <strong>{{ order.guest_email }}</strong> to track this parcel in real time and speed through future checkouts.
              </p>
            </div>
          </div>

          <form @submit.prevent="handleCreateGuestAccount" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <input
              v-model="guestPassword"
              type="password"
              required
              minlength="8"
              placeholder="Choose a password (min 8 chars)"
              class="px-3.5 py-2.5 bg-white border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
            />
            <input
              v-model="guestPasswordConfirmation"
              type="password"
              required
              minlength="8"
              placeholder="Confirm password"
              class="px-3.5 py-2.5 bg-white border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
            />
            <div class="sm:col-span-2 flex justify-end">
              <button
                type="submit"
                :disabled="isCreatingAccount"
                class="px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition inline-flex items-center gap-1.5 shadow-sm"
              >
                <Loader2 v-if="isCreatingAccount" class="w-3.5 h-3.5 animate-spin" />
                <span>Create Account</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Navigation Actions -->
        <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
          <RouterLink
            v-if="authStore.isAuthenticated && order?.order_number"
            :to="`/account/orders/${order.order_number}`"
            class="px-6 py-3 rounded-pill border border-line bg-white text-ink text-xs sm:text-sm font-semibold hover:border-brand hover:text-brand transition inline-flex items-center gap-2 shadow-xs"
          >
            <span>View Order & Invoice</span>
          </RouterLink>
          <RouterLink
            to="/shop"
            class="px-6 py-3 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition inline-flex items-center gap-2 shadow-sm"
          >
            <span>Continue Shopping</span>
            <ArrowRight class="w-4 h-4" />
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
