<script setup>
import { computed } from 'vue'
import { Truck, RotateCcw, ShieldCheck, Headphones, Sparkles, Award } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

const iconMap = {
  Truck,
  RotateCcw,
  ShieldCheck,
  Headphones,
  Sparkles,
  Award,
}

const defaultPoints = [
  {
    icon: Truck,
    title: 'Carbon-Neutral Delivery',
    desc: 'Free standard shipping on all domestic orders over $75.',
  },
  {
    icon: RotateCcw,
    title: '30-Day Calm Return',
    desc: 'Prepaid recyclable labels and effortless exchange processing.',
  },
  {
    icon: ShieldCheck,
    title: 'Direct Artisan Provenance',
    desc: 'Collaborations with small family-owned European & Japanese workshops.',
  },
  {
    icon: Headphones,
    title: 'Dedicated Human Support',
    desc: 'Reach our studio concierge 7 days a week for honest guidance.',
  },
]

const trustPoints = computed(() => {
  const custom = settingsStore.publicSettings?.trust_points
  if (Array.isArray(custom) && custom.length > 0) {
    return custom.map((pt, idx) => ({
      icon: typeof pt.icon === 'string' ? iconMap[pt.icon] || defaultPoints[idx % 4].icon : pt.icon || defaultPoints[idx % 4].icon,
      title: pt.title || defaultPoints[idx % 4].title,
      desc: pt.desc || pt.description || defaultPoints[idx % 4].desc,
    }))
  }
  return defaultPoints
})
</script>

<template>
  <section class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
    <div class="bg-surface/70 border border-line rounded-card p-8 sm:p-12 shadow-sm">
      <div class="text-center max-w-xl mx-auto mb-10">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
          Why Shop With Us
        </h2>
        <p class="text-xs sm:text-sm text-ink-muted mt-2">
          Thoughtful practices guiding every creation from the studio bench to your home.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <div
          v-for="(point, idx) in trustPoints"
          :key="'trust-' + idx"
          class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-3"
        >
          <div class="w-12 h-12 rounded-full bg-white border border-line flex items-center justify-center text-brand shadow-sm">
            <component :is="point.icon" class="w-6 h-6" />
          </div>

          <h3 class="text-sm font-bold text-ink">
            {{ point.title }}
          </h3>

          <p class="text-xs text-ink-muted leading-relaxed">
            {{ point.desc }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
