<script setup>
import { RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import { ArrowRight, Sparkles, ShieldCheck, Truck, RotateCcw, HeartHandshake } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

useHead({
  title: `${settingsStore.siteName} — Thoughtfully Designed Essentials`,
  meta: [
    {
      name: 'description',
      content:
        'Conscious apparel, tactile homeware, and everyday accessories made in European and Japanese artisan workshops.',
    },
    { property: 'og:title', content: `${settingsStore.siteName} — Thoughtfully Designed Essentials` },
    {
      property: 'og:description',
      content: 'Minimalist apparel and everyday homeware designed to outlast fleeting trends.',
    },
  ],
})
</script>

<template>
  <div class="space-y-16 pb-16">
    <!-- Hero Banner Preview Section -->
    <section class="relative bg-surface border-b border-line overflow-hidden">
      <div class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 lg:py-32 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-7 space-y-6 text-left">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-pill bg-brand/10 text-brand text-xs font-semibold">
            <Sparkles class="w-3.5 h-3.5" />
            <span>New Season Architectural Collection</span>
          </div>

          <h1 class="text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-ink leading-[1.1]">
            Mindfully Crafted Goods For Quiet Living.
          </h1>

          <p class="text-sm sm:text-base text-ink-muted leading-relaxed max-w-xl">
            Pure organic linen, vegetable-tanned leather, and wheel-thrown ceramic objects built to age with graceful character.
          </p>

          <div class="pt-2 flex flex-wrap items-center gap-3">
            <RouterLink
              to="/category/apparel"
              class="px-6 py-3 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition inline-flex items-center gap-2 shadow-sm"
            >
              <span>Explore Collection</span>
              <ArrowRight class="w-4 h-4" />
            </RouterLink>

            <RouterLink
              to="/about"
              class="px-6 py-3 rounded-pill border border-line bg-white hover:bg-surface text-ink text-xs sm:text-sm font-semibold transition"
            >
              <span>Our Philosophy</span>
            </RouterLink>
          </div>
        </div>

        <div class="lg:col-span-5 relative">
          <div class="aspect-[4/5] rounded-card overflow-hidden shadow-soft border border-line bg-white">
            <img
              src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=80"
              alt="Artisanal goods preview"
              class="w-full h-full object-cover"
              loading="eager"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Trust Points Strip -->
    <section class="max-w-container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-6 sm:p-8 bg-surface/50 border border-line rounded-card">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-white border border-line text-brand flex items-center justify-center flex-shrink-0 shadow-sm">
            <Truck class="w-5 h-5" />
          </div>
          <div>
            <h4 class="text-xs font-bold text-ink">Carbon-Neutral Delivery</h4>
            <p class="text-[11px] text-ink-muted">Free standard shipping over $75</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-white border border-line text-brand flex items-center justify-center flex-shrink-0 shadow-sm">
            <RotateCcw class="w-5 h-5" />
          </div>
          <div>
            <h4 class="text-xs font-bold text-ink">30-Day Calm Return</h4>
            <p class="text-[11px] text-ink-muted">Prepaid recyclable packaging</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-white border border-line text-brand flex items-center justify-center flex-shrink-0 shadow-sm">
            <ShieldCheck class="w-5 h-5" />
          </div>
          <div>
            <h4 class="text-xs font-bold text-ink">Honest Provenance</h4>
            <p class="text-[11px] text-ink-muted">Direct artisan collaborations</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-white border border-line text-brand flex items-center justify-center flex-shrink-0 shadow-sm">
            <HeartHandshake class="w-5 h-5" />
          </div>
          <div>
            <h4 class="text-xs font-bold text-ink">Dedicated Support</h4>
            <p class="text-[11px] text-ink-muted">Real human customer care</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
