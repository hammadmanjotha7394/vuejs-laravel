<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useHead } from '@unhead/vue'
import axios from 'axios'
import {
  Lock,
  ShoppingBag,
  Truck,
  Tag,
  CheckCircle2,
  MapPin,
  ArrowLeft,
  ArrowRight,
  ShieldCheck,
  Loader2,
  X,
  AlertCircle,
} from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const toastStore = useToastStore()

const savedAddresses = ref([])
const selectedAddressId = ref(null)
const saveAddress = ref(false)
const selectedShippingRateId = ref(null)
const isSubmitting = ref(false)
const formErrors = ref({})

// Coupon input state
const couponInput = ref('')
const isApplyingCoupon = ref(false)

// Checkout form state
const form = ref({
  email: '',
  first_name: '',
  last_name: '',
  line1: '',
  line2: '',
  city: '',
  state: '',
  postal_code: '',
  country: 'US',
  phone: '',
  notes: '',
})

// Pre-fill user details if authenticated
const prefillUser = () => {
  if (authStore.user) {
    form.value.email = authStore.user.email || ''
    if (authStore.user.name) {
      const parts = authStore.user.name.trim().split(' ')
      form.value.first_name = parts[0] || ''
      form.value.last_name = parts.slice(1).join(' ') || ''
    }
    if (authStore.user.phone) {
      form.value.phone = authStore.user.phone
    }
  }
}

// Fetch saved addresses for logged-in customer
const fetchSavedAddresses = async () => {
  if (!authStore.isAuthenticated) return
  try {
    const res = await axios.get('/api/checkout/addresses')
    savedAddresses.value = res.data.data || []
    if (savedAddresses.value.length > 0) {
      const defaultAddr = savedAddresses.value.find((a) => a.is_default_shipping) || savedAddresses.value[0]
      applySavedAddress(defaultAddr)
    }
  } catch (e) {
    // Non-fatal
  }
}

const applySavedAddress = (addr) => {
  selectedAddressId.value = addr.id
  const nameParts = (addr.name || '').trim().split(' ')
  form.value.first_name = nameParts[0] || form.value.first_name
  form.value.last_name = nameParts.slice(1).join(' ') || form.value.last_name
  form.value.line1 = addr.line1 || ''
  form.value.line2 = addr.line2 || ''
  form.value.city = addr.city || ''
  form.value.state = addr.state || ''
  form.value.postal_code = addr.postal_code || ''
  form.value.country = addr.country || 'US'
  form.value.phone = addr.phone || form.value.phone
}

// Shipping Rate selection
const selectedShippingRate = computed(() => {
  const rates = cartStore.shipping.rates || []
  if (rates.length === 0) return null
  if (selectedShippingRateId.value) {
    return rates.find((r) => r.id === selectedShippingRateId.value) || rates[0]
  }
  return rates[0]
})

const shippingCost = computed(() => {
  if (!selectedShippingRate.value) return cartStore.shipping.is_free ? 0.0 : 8.0
  return Number(selectedShippingRate.value.cost || 0)
})

const estimatedTax = computed(() => {
  const taxRate = Number(settingsStore.settings?.tax_rate_percent || 0)
  const pricesIncludeTax = Boolean(settingsStore.settings?.prices_include_tax)
  if (pricesIncludeTax || taxRate <= 0) return 0.0
  const taxable = Math.max(0, cartStore.subtotal - cartStore.discount)
  return Math.round(((taxable * taxRate) / 100) * 100) / 100
})

const grandTotal = computed(() => {
  return Math.max(0, cartStore.subtotal - cartStore.discount + shippingCost.value + estimatedTax.value)
})

// Coupon handlers
const handleApplyCoupon = async () => {
  if (!couponInput.value.trim()) return
  isApplyingCoupon.value = true
  const ok = await cartStore.applyCoupon(couponInput.value)
  isApplyingCoupon.value = false
  if (ok) {
    couponInput.value = ''
  }
}

const handleRemoveCoupon = async () => {
  await cartStore.removeCoupon()
}

// Client-side validation before submitting
const validateForm = () => {
  const errs = {}
  if (!form.value.email.trim() || !form.value.email.includes('@')) {
    errs.email = 'Please enter a valid email address for order updates.'
  }
  if (!form.value.first_name.trim()) {
    errs.first_name = 'First name is required.'
  }
  if (!form.value.last_name.trim()) {
    errs.last_name = 'Last name is required.'
  }
  if (!form.value.line1.trim()) {
    errs.line1 = 'Street address is required.'
  }
  if (!form.value.city.trim()) {
    errs.city = 'City is required.'
  }
  if (!form.value.postal_code.trim()) {
    errs.postal_code = 'Postal / ZIP code is required.'
  }
  if (!form.value.country.trim()) {
    errs.country = 'Country is required.'
  }
  formErrors.value = errs
  return Object.keys(errs).length === 0
}

// Submit Checkout -> Create Pending Order & Redirect to Stripe Checkout
const handleSubmitCheckout = async () => {
  if (!validateForm()) {
    toastStore.error('Please complete all required shipping fields.')
    return
  }

  isSubmitting.value = true
  formErrors.value = {}

  try {
    cartStore.applyHeaders()
    const res = await axios.post('/api/checkout', {
      email: form.value.email.trim(),
      shipping_rate_id: selectedShippingRate.value?.id || null,
      shipping_address: {
        first_name: form.value.first_name.trim(),
        last_name: form.value.last_name.trim(),
        line1: form.value.line1.trim(),
        line2: form.value.line2.trim() || null,
        city: form.value.city.trim(),
        state: form.value.state.trim() || null,
        postal_code: form.value.postal_code.trim(),
        country: form.value.country.trim(),
        phone: form.value.phone.trim() || null,
      },
      notes: form.value.notes.trim() || null,
      save_address: saveAddress.value,
      origin: window.location.origin,
    })

    const checkoutUrl = res.data.data?.checkout_url
    if (checkoutUrl) {
      if (checkoutUrl.startsWith(window.location.origin)) {
        const relativePath = checkoutUrl.replace(window.location.origin, '')
        router.push(relativePath)
      } else {
        window.location.href = checkoutUrl
      }
    }
  } catch (err) {
    if (err.response?.status === 422 && err.response.data?.errors) {
      const serverErrs = err.response.data.errors
      const mapped = {}
      Object.keys(serverErrs).forEach((k) => {
        const cleanKey = k.replace('shipping_address.', '')
        mapped[cleanKey] = serverErrs[k][0]
      })
      formErrors.value = mapped
      toastStore.error(Object.values(mapped)[0] || 'Please check your checkout details.')
    } else {
      toastStore.error(err.response?.data?.message || 'Could not initialize payment session.')
    }
  } finally {
    isSubmitting.value = false
  }
}

watch(
  () => authStore.user,
  () => {
    prefillUser()
    fetchSavedAddresses()
  }
)

onMounted(async () => {
  prefillUser()
  await cartStore.fetchCart()
  await fetchSavedAddresses()
  if (cartStore.shipping.rates?.length > 0) {
    selectedShippingRateId.value = cartStore.shipping.rates[0].id
  }
})

useHead({
  title: computed(() => `Checkout | ${settingsStore.siteName}`),
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
})
</script>

<template>
  <div class="min-h-screen bg-bg text-ink py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Top Back Link & Header -->
      <div class="mb-8">
        <RouterLink
          to="/cart"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-muted hover:text-brand transition mb-3"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Return to Shopping Bag</span>
        </RouterLink>

        <div class="flex items-center justify-between">
          <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
            Secure Checkout
          </h1>
          <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-pill">
            <Lock class="w-3.5 h-3.5" />
            <span>256-Bit SSL Encrypted</span>
          </div>
        </div>
      </div>

      <!-- Empty Cart Guard -->
      <div
        v-if="!cartStore.isLoading && !cartStore.hasItems"
        class="bg-white rounded-card border border-line p-12 text-center max-w-xl mx-auto shadow-xs space-y-4 my-8"
      >
        <div class="w-16 h-16 rounded-full bg-surface border border-line flex items-center justify-center text-ink-muted mx-auto">
          <ShoppingBag class="w-8 h-8 opacity-60" />
        </div>
        <h2 class="text-lg font-bold text-ink">Your shopping bag is empty</h2>
        <p class="text-xs sm:text-sm text-ink-muted max-w-md mx-auto">
          Add handcrafted pieces to your bag before proceeding to checkout.
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

      <!-- 2-Column Checkout Layout -->
      <form
        v-else
        @submit.prevent="handleSubmitCheckout"
        novalidate
        class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start"
      >
        <!-- LEFT COLUMN: Contact, Shipping Address, Method & Notes (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
          <!-- 1. Contact Information -->
          <section class="bg-white rounded-card border border-line p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-line pb-3">
              <h2 class="text-base font-bold text-ink">1. Contact Information</h2>
              <span v-if="!authStore.isAuthenticated" class="text-xs text-ink-muted">
                Already have an account?
                <RouterLink to="/login?redirect=/checkout" class="text-brand font-semibold hover:underline">
                  Sign in
                </RouterLink>
              </span>
            </div>

            <div>
              <label for="checkout-email" class="block text-xs font-semibold text-ink mb-1.5">
                Email Address <span class="text-rose-600">*</span>
              </label>
              <input
                id="checkout-email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                placeholder="you@example.com"
                class="w-full px-3.5 py-2.5 bg-surface border rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand transition"
                :class="formErrors.email ? 'border-rose-500' : 'border-line'"
              />
              <p v-if="formErrors.email" class="text-xs text-rose-600 mt-1 flex items-center gap-1">
                <AlertCircle class="w-3.5 h-3.5" />
                <span>{{ formErrors.email }}</span>
              </p>
              <p class="text-[11px] text-ink-muted mt-1">
                We'll send your order confirmation and real-time tracking updates to this address.
              </p>
            </div>
          </section>

          <!-- 2. Shipping Address -->
          <section class="bg-white rounded-card border border-line p-6 shadow-xs space-y-5">
            <div class="border-b border-line pb-3">
              <h2 class="text-base font-bold text-ink">2. Shipping Address</h2>
            </div>

            <!-- Saved Address Picker (for Logged-in Users) -->
            <div v-if="savedAddresses.length > 0" class="space-y-2.5">
              <label class="block text-xs font-semibold text-ink-muted uppercase tracking-wider">
                Saved Addresses
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button
                  v-for="addr in savedAddresses"
                  :key="'saved-addr-' + addr.id"
                  type="button"
                  @click="applySavedAddress(addr)"
                  class="text-left p-3.5 rounded-lg border transition flex items-start justify-between gap-2"
                  :class="
                    selectedAddressId === addr.id
                      ? 'border-brand bg-brand/5 ring-1 ring-brand'
                      : 'border-line hover:border-ink-muted bg-surface/40'
                  "
                >
                  <div class="text-xs space-y-0.5">
                    <div class="font-bold text-ink flex items-center gap-1.5">
                      <MapPin class="w-3.5 h-3.5 text-brand" />
                      <span>{{ addr.label || 'Saved Address' }}</span>
                    </div>
                    <div class="text-ink font-medium">{{ addr.name }}</div>
                    <div class="text-ink-muted truncate">{{ addr.line1 }}</div>
                    <div class="text-ink-muted">{{ addr.city }}, {{ addr.state }} {{ addr.postal_code }}</div>
                  </div>
                  <CheckCircle2
                    v-if="selectedAddressId === addr.id"
                    class="w-4 h-4 text-brand flex-shrink-0"
                  />
                </button>
              </div>
            </div>

            <!-- Address Input Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="shipping-fname" class="block text-xs font-semibold text-ink mb-1">
                  First Name <span class="text-rose-600">*</span>
                </label>
                <input
                  id="shipping-fname"
                  v-model="form.first_name"
                  type="text"
                  autocomplete="given-name"
                  required
                  class="w-full px-3.5 py-2.5 bg-surface border rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                  :class="formErrors.first_name ? 'border-rose-500' : 'border-line'"
                />
                <p v-if="formErrors.first_name" class="text-[11px] text-rose-600 mt-1">{{ formErrors.first_name }}</p>
              </div>

              <div>
                <label for="shipping-lname" class="block text-xs font-semibold text-ink mb-1">
                  Last Name <span class="text-rose-600">*</span>
                </label>
                <input
                  id="shipping-lname"
                  v-model="form.last_name"
                  type="text"
                  autocomplete="family-name"
                  required
                  class="w-full px-3.5 py-2.5 bg-surface border rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                  :class="formErrors.last_name ? 'border-rose-500' : 'border-line'"
                />
                <p v-if="formErrors.last_name" class="text-[11px] text-rose-600 mt-1">{{ formErrors.last_name }}</p>
              </div>

              <div class="sm:col-span-2">
                <label for="shipping-line1" class="block text-xs font-semibold text-ink mb-1">
                  Street Address <span class="text-rose-600">*</span>
                </label>
                <input
                  id="shipping-line1"
                  v-model="form.line1"
                  type="text"
                  autocomplete="address-line1"
                  required
                  placeholder="House number and street name"
                  class="w-full px-3.5 py-2.5 bg-surface border rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                  :class="formErrors.line1 ? 'border-rose-500' : 'border-line'"
                />
                <p v-if="formErrors.line1" class="text-[11px] text-rose-600 mt-1">{{ formErrors.line1 }}</p>
              </div>

              <div class="sm:col-span-2">
                <label for="shipping-line2" class="block text-xs font-semibold text-ink mb-1">
                  Apartment, suite, unit, etc. <span class="text-ink-muted font-normal">(optional)</span>
                </label>
                <input
                  id="shipping-line2"
                  v-model="form.line2"
                  type="text"
                  autocomplete="address-line2"
                  class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                />
              </div>

              <div>
                <label for="shipping-city" class="block text-xs font-semibold text-ink mb-1">
                  City <span class="text-rose-600">*</span>
                </label>
                <input
                  id="shipping-city"
                  v-model="form.city"
                  type="text"
                  autocomplete="address-level2"
                  required
                  class="w-full px-3.5 py-2.5 bg-surface border rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                  :class="formErrors.city ? 'border-rose-500' : 'border-line'"
                />
                <p v-if="formErrors.city" class="text-[11px] text-rose-600 mt-1">{{ formErrors.city }}</p>
              </div>

              <div>
                <label for="shipping-state" class="block text-xs font-semibold text-ink mb-1">
                  State / Province
                </label>
                <input
                  id="shipping-state"
                  v-model="form.state"
                  type="text"
                  autocomplete="address-level1"
                  class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                />
              </div>

              <div>
                <label for="shipping-zip" class="block text-xs font-semibold text-ink mb-1">
                  Postal / ZIP Code <span class="text-rose-600">*</span>
                </label>
                <input
                  id="shipping-zip"
                  v-model="form.postal_code"
                  type="text"
                  autocomplete="postal-code"
                  required
                  class="w-full px-3.5 py-2.5 bg-surface border rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                  :class="formErrors.postal_code ? 'border-rose-500' : 'border-line'"
                />
                <p v-if="formErrors.postal_code" class="text-[11px] text-rose-600 mt-1">{{ formErrors.postal_code }}</p>
              </div>

              <div>
                <label for="shipping-country" class="block text-xs font-semibold text-ink mb-1">
                  Country <span class="text-rose-600">*</span>
                </label>
                <select
                  id="shipping-country"
                  v-model="form.country"
                  autocomplete="country"
                  class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                >
                  <option value="US">United States</option>
                  <option value="CA">Canada</option>
                  <option value="GB">United Kingdom</option>
                  <option value="AU">Australia</option>
                  <option value="DE">Germany</option>
                  <option value="FR">France</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label for="shipping-phone" class="block text-xs font-semibold text-ink mb-1">
                  Phone Number <span class="text-ink-muted font-normal">(for delivery notifications)</span>
                </label>
                <input
                  id="shipping-phone"
                  v-model="form.phone"
                  type="tel"
                  autocomplete="tel"
                  placeholder="+1 (555) 000-0000"
                  class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
                />
              </div>
            </div>

            <!-- Save Address Checkbox -->
            <div v-if="authStore.isAuthenticated" class="pt-1">
              <label class="inline-flex items-center gap-2 text-xs text-ink cursor-pointer">
                <input
                  type="checkbox"
                  v-model="saveAddress"
                  class="rounded border-line text-brand focus:ring-brand"
                />
                <span>Save this address to my account for future orders</span>
              </label>
            </div>
          </section>

          <!-- 3. Shipping Method -->
          <section class="bg-white rounded-card border border-line p-6 shadow-xs space-y-4">
            <div class="border-b border-line pb-3">
              <h2 class="text-base font-bold text-ink">3. Shipping Method</h2>
            </div>

            <div v-if="cartStore.shipping.rates && cartStore.shipping.rates.length > 0" class="space-y-2.5">
              <label
                v-for="rate in cartStore.shipping.rates"
                :key="'checkout-rate-' + rate.id"
                class="flex items-center justify-between p-4 rounded-lg border cursor-pointer transition"
                :class="
                  selectedShippingRate?.id === rate.id
                    ? 'border-brand bg-brand/5 ring-1 ring-brand'
                    : 'border-line hover:border-ink-muted bg-surface/40'
                "
              >
                <div class="flex items-center gap-3">
                  <input
                    type="radio"
                    name="checkout_shipping_rate"
                    :value="rate.id"
                    v-model="selectedShippingRateId"
                    class="text-brand focus:ring-brand"
                  />
                  <div>
                    <div class="text-xs sm:text-sm font-bold text-ink">{{ rate.name }}</div>
                    <div class="text-[11px] text-ink-muted">Fully insured with carbon-neutral parcel tracking</div>
                  </div>
                </div>

                <span class="text-xs sm:text-sm font-extrabold font-mono" :class="rate.is_free ? 'text-emerald-700' : 'text-ink'">
                  {{ rate.is_free ? 'Free' : `$${Number(rate.cost).toFixed(2)}` }}
                </span>
              </label>
            </div>
          </section>

          <!-- 4. Order Notes -->
          <section class="bg-white rounded-card border border-line p-6 shadow-xs space-y-3">
            <h2 class="text-base font-bold text-ink">4. Order Notes <span class="text-xs font-normal text-ink-muted">(Optional)</span></h2>
            <textarea
              v-model="form.notes"
              rows="3"
              placeholder="Special delivery instructions, gate code, or complimentary gift note..."
              class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
            ></textarea>
          </section>
        </div>

        <!-- RIGHT COLUMN: Sticky Order Summary & Continue to Payment (5 cols) -->
        <div class="lg:col-span-5 lg:sticky lg:top-24 space-y-6">
          <div class="bg-white rounded-card border border-line p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-ink border-b border-line pb-3">
              Order Summary ({{ cartStore.itemCount }})
            </h2>

            <!-- Items List -->
            <div class="max-h-80 overflow-y-auto divide-y divide-line pr-1">
              <div
                v-for="item in cartStore.items"
                :key="'summary-item-' + item.id"
                class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="relative w-14 h-16 rounded-lg bg-surface border border-line overflow-hidden flex-shrink-0">
                    <img
                      v-if="item.image"
                      :src="item.image"
                      :alt="item.name"
                      class="w-full h-full object-cover"
                    />
                    <span class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full bg-ink text-white text-[10px] font-bold">
                      ×{{ item.quantity }}
                    </span>
                  </div>

                  <div class="min-w-0">
                    <h4 class="text-xs font-bold text-ink truncate">{{ item.name }}</h4>
                    <p v-if="item.variant_title" class="text-[11px] text-ink-muted truncate">
                      {{ item.variant_title }}
                    </p>
                    <p class="text-[11px] text-ink-muted font-mono">
                      ${{ item.unit_price.toFixed(2) }} each
                    </p>
                  </div>
                </div>

                <div class="text-xs font-bold font-mono text-ink flex-shrink-0">
                  ${{ item.line_total.toFixed(2) }}
                </div>
              </div>
            </div>

            <!-- Coupon Code Box -->
            <div class="pt-3 border-t border-line space-y-2">
              <div v-if="!cartStore.coupon" class="flex gap-2">
                <input
                  v-model="couponInput"
                  type="text"
                  placeholder="Promo code (e.g. WELCOME10)"
                  class="flex-1 px-3.5 py-2 bg-surface border border-line rounded-input text-xs uppercase text-ink focus:outline-none focus:border-brand"
                />
                <button
                  type="button"
                  @click="handleApplyCoupon"
                  :disabled="isApplyingCoupon || !couponInput.trim()"
                  class="px-4 py-2 rounded-pill bg-ink text-white text-xs font-semibold hover:opacity-90 transition disabled:opacity-40"
                >
                  <Loader2 v-if="isApplyingCoupon" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>Apply</span>
                </button>
              </div>

              <div
                v-else
                class="flex items-center justify-between bg-emerald-50 border border-emerald-200 px-3.5 py-2 rounded-pill text-xs text-emerald-800"
              >
                <div class="flex items-center gap-1.5">
                  <Tag class="w-3.5 h-3.5 text-emerald-600" />
                  <span class="font-bold">{{ cartStore.coupon.code }}</span>
                  <span>(-${{ cartStore.discount.toFixed(2) }})</span>
                </div>
                <button
                  type="button"
                  @click="handleRemoveCoupon"
                  class="text-emerald-700 hover:text-rose-600 p-0.5"
                  aria-label="Remove coupon"
                >
                  <X class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Totals Breakdown -->
            <div class="space-y-2.5 text-xs border-t border-line pt-4">
              <div class="flex items-center justify-between text-ink-muted">
                <span>Subtotal</span>
                <span class="font-mono text-ink font-semibold">${{ cartStore.subtotal.toFixed(2) }}</span>
              </div>

              <div v-if="cartStore.discount > 0" class="flex items-center justify-between text-emerald-700">
                <span>Discount</span>
                <span class="font-mono font-semibold">-${{ cartStore.discount.toFixed(2) }}</span>
              </div>

              <div class="flex items-center justify-between text-ink-muted">
                <span>Shipping ({{ selectedShippingRate?.name || 'Standard' }})</span>
                <span class="font-mono font-semibold" :class="shippingCost === 0 ? 'text-emerald-700' : 'text-ink'">
                  {{ shippingCost === 0 ? 'Free' : `$${shippingCost.toFixed(2)}` }}
                </span>
              </div>

              <div v-if="estimatedTax > 0" class="flex items-center justify-between text-ink-muted">
                <span>Estimated Tax</span>
                <span class="font-mono text-ink font-semibold">${{ estimatedTax.toFixed(2) }}</span>
              </div>

              <div class="pt-3 border-t border-line flex items-center justify-between text-base font-extrabold text-ink">
                <span>Total Due</span>
                <span class="font-mono text-xl">${{ grandTotal.toFixed(2) }}</span>
              </div>
            </div>

            <!-- Primary Submit CTA -->
            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full py-3.5 px-6 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition flex items-center justify-center gap-2 shadow-sm disabled:opacity-60"
            >
              <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
              <Lock v-else class="w-4 h-4" />
              <span>{{ isSubmitting ? 'Preparing Payment Session...' : 'Continue to payment' }}</span>
            </button>

            <div class="pt-2 text-center space-y-2 text-[11px] text-ink-muted">
              <div class="flex items-center justify-center gap-1.5">
                <ShieldCheck class="w-4 h-4 text-brand" />
                <span>Hosted Stripe Checkout • PCI-DSS Level 1 Compliant</span>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>
