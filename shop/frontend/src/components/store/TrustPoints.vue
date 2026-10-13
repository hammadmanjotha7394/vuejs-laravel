<script setup>
import { computed } from 'vue'
import {
  Truck,
  RotateCcw,
  ShieldCheck,
  Headphones,
  Sparkles,
  Award,
  Heart,
  Leaf,
  CheckCircle2,
} from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

const iconMap = {
  Truck,
  RotateCcw,
  ShieldCheck,
  Headphones,
  Sparkles,
  Award,
  Heart,
  Leaf,
  truck: Truck,
  'rotate-ccw': RotateCcw,
  'shield-check': ShieldCheck,
  headphones: Headphones,
  sparkles: Sparkles,
  award: Award,
  heart: Heart,
  leaf: Leaf,
}

const defaultPoints = computed(() => [
  {
    icon: Truck,
    tag: 'Tracked & Insured',
    title: 'Carbon-Neutral Delivery',
    desc: `Complimentary standard shipping on all domestic orders over ${settingsStore.currencySymbol}${settingsStore.freeShippingThreshold || 75}.`,
  },
  {
    icon: RotateCcw,
    tag: 'Hassle-Free',
    title: '30-Day Calm Return',
    desc: 'Prepaid recyclable labels and effortless exchange processing with zero stress.',
  },
  {
    icon: ShieldCheck,
    tag: 'Authentic Provenance',
    title: 'Direct Artisan Provenance',
    desc: 'Collaborations with small family-owned European & Japanese heritage workshops.',
  },
  {
    icon: Headphones,
    tag: 'Concierge Care',
    title: 'Dedicated Human Support',
    desc: 'Reach our studio concierge 7 days a week for honest guidance & sizing advice.',
  },
])

const trustPoints = computed(() => {
  const custom = settingsStore.publicSettings?.trust_points
  if (Array.isArray(custom) && custom.length > 0) {
    return custom.map((pt, idx) => {
      const rawIcon = typeof pt.icon === 'string' ? pt.icon.toLowerCase() : pt.icon
      return {
        icon: iconMap[rawIcon] || defaultPoints.value[idx % 4].icon,
        tag: pt.tag || defaultPoints.value[idx % 4]?.tag || 'Boutique Standard',
        title: pt.title || defaultPoints.value[idx % 4].title,
        desc: pt.desc || pt.description || defaultPoints.value[idx % 4].desc,
      }
    })
  }
  return defaultPoints.value
})
</script>

<template>
  <section class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-16 lg:py-20" aria-label="Why Shop With Us">
    <div class="relative rounded-3xl bg-gradient-to-b from-surface/40 via-surface/80 to-surface border border-line/80 p-4 sm:p-10 lg:p-14 shadow-soft overflow-hidden">
      <!-- Ambient Decorative Blurred Glows -->
      <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-brand/5 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-brand/5 blur-3xl pointer-events-none"></div>

      <!-- Section Header -->
      <div class="relative z-10 text-center max-w-2xl mx-auto mb-6 sm:mb-14">
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-pill bg-brand/10 text-brand text-xs font-semibold tracking-wide mb-3 border border-brand/15">
          <Sparkles class="w-3.5 h-3.5" />
          <span>Craftsmanship & Peace of Mind</span>
        </div>

        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-ink">
          Why Shop With Us
        </h2>

        <p class="text-xs sm:text-sm md:text-base text-ink-muted mt-2.5 leading-relaxed">
          Thoughtful practices guiding every creation from the studio bench to your home.
        </p>
      </div>

      <!-- 4 Elevated Trust Cards -->
      <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 lg:gap-6">
        <div
          v-for="(point, idx) in trustPoints"
          :key="'trust-' + idx"
          class="group relative bg-white rounded-2xl p-4 sm:p-7 border border-line shadow-xs hover:shadow-soft hover:border-brand/40 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
        >
          <!-- Top subtle brand accent glow bar that animates on hover -->
          <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-brand to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-t-2xl"></div>

          <div class="flex items-start gap-3.5 sm:block">
            <!-- Header: Icon + Number indicator -->
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-brand/10 text-brand flex items-center justify-center shrink-0 transition-all duration-300 group-hover:bg-brand group-hover:text-white group-hover:scale-105 shadow-2xs ring-4 ring-brand/5 mb-0 sm:mb-5">
              <component :is="point.icon" class="w-5 h-5 sm:w-6 sm:h-6 transition-transform duration-300 group-hover:scale-110" />
            </div>

            <div class="flex-1 min-w-0">
              <!-- Top row for number and tag -->
              <div class="flex items-center justify-between gap-2 mb-1 sm:mb-2">
                <div v-if="point.tag">
                  <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-brand bg-brand/5 px-2 py-0.5 rounded-full border border-brand/10">
                    {{ point.tag }}
                  </span>
                </div>
                <div v-else class="hidden sm:block"></div>

                <span class="text-[11px] sm:text-xs font-mono font-bold px-2 py-0.5 rounded-full bg-surface border border-line text-ink-muted group-hover:text-brand group-hover:border-brand/30 transition-colors shrink-0">
                  0{{ idx + 1 }}
                </span>
              </div>

              <!-- Title -->
              <h3 class="text-sm sm:text-base font-bold text-ink group-hover:text-brand transition-colors tracking-tight mb-1 sm:mb-2">
                {{ point.title }}
              </h3>

              <!-- Description -->
              <p class="text-xs text-ink-muted leading-relaxed">
                {{ point.desc }}
              </p>
            </div>
          </div>

          <!-- Bottom check guarantee -->
          <div class="pt-3 sm:pt-4 mt-3 sm:mt-4 border-t border-line/60 flex items-center gap-1.5 text-[11px] font-semibold text-brand/80 group-hover:text-brand transition-colors">
            <CheckCircle2 class="w-3.5 h-3.5 shrink-0" />
            <span>Guaranteed Standard</span>
          </div>
        </div>
      </div>

      <!-- Bottom Reassurance Strip -->
      <div class="mt-10 pt-8 border-t border-line/80 relative z-10 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-ink-muted">
        <div class="flex items-center gap-6 sm:gap-8 flex-wrap justify-center text-center">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-4 h-4 text-brand flex-shrink-0" />
            <span class="font-medium text-ink">256-Bit SSL Encrypted Checkout</span>
          </div>
          <div class="flex items-center gap-2">
            <Award class="w-4 h-4 text-brand flex-shrink-0" />
            <span class="font-medium text-ink">Artisan Quality Guarantee</span>
          </div>
          <div class="flex items-center gap-2">
            <Leaf class="w-4 h-4 text-brand flex-shrink-0" />
            <span class="font-medium text-ink">100% Recyclable Packaging</span>
          </div>
        </div>

        <div class="text-[11px] text-ink-muted font-medium flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Verified Studio Excellence</span>
        </div>
      </div>
    </div>
  </section>
</template>
