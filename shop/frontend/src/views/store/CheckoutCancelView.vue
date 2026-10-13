<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import { AlertCircle, ShoppingBag, ArrowLeft, Lock } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

useHead({
  title: computed(() => `Payment Cancelled | ${settingsStore.siteName}`),
  meta: [{ name: 'robots', content: 'noindex, nofollow' }],
})
</script>

<template>
  <div class="min-h-screen bg-bg text-ink py-16 sm:py-24">
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-card border border-line p-8 sm:p-10 text-center shadow-xs space-y-5">
        <div class="w-16 h-16 rounded-full bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center mx-auto">
          <AlertCircle class="w-8 h-8" />
        </div>

        <div class="space-y-2">
          <h1 class="text-2xl font-extrabold text-ink tracking-tight">
            Payment Not Completed
          </h1>
          <p class="text-xs sm:text-sm text-ink-muted leading-relaxed">
            Your payment session was cancelled and no charges were made. All items in your shopping bag have been saved and are ready whenever you'd like to complete your order.
          </p>
        </div>

        <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
          <RouterLink
            to="/checkout"
            class="w-full sm:w-auto px-6 py-3 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition inline-flex items-center justify-center gap-2 shadow-sm"
          >
            <Lock class="w-4 h-4" />
            <span>Return to Checkout</span>
          </RouterLink>

          <RouterLink
            to="/cart"
            class="w-full sm:w-auto px-6 py-3 rounded-pill border border-line bg-white text-xs sm:text-sm font-semibold text-ink hover:bg-surface transition inline-flex items-center justify-center gap-2"
          >
            <ShoppingBag class="w-4 h-4" />
            <span>Review Shopping Bag</span>
          </RouterLink>
        </div>

        <div class="pt-2">
          <RouterLink
            to="/shop"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-muted hover:text-brand transition"
          >
            <ArrowLeft class="w-3.5 h-3.5" />
            <span>Continue browsing catalog</span>
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
