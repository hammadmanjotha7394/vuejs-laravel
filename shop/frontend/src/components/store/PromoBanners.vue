<script setup>
import { RouterLink } from 'vue-router'
import { ArrowRight } from 'lucide-vue-next'

const props = defineProps({
  banners: {
    type: Array,
    default: () => [
      {
        tag: 'Artisan Textiles',
        title: 'Organic Flax Linen',
        description: 'Breathable shirting and washed duvet sets tailored for longevity.',
        button_text: 'Shop Collection',
        button_url: '/category/apparel',
        image: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1000&q=80',
      },
      {
        tag: 'Architectural Lighting',
        title: 'Warm Table Lamps',
        description: 'Cast solid brass with warm dimmed tones for tactile sanctuary spaces.',
        button_text: 'Explore Homeware',
        button_url: '/category/home-living',
        image: 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=1000&q=80',
      },
    ],
  },
})
</script>

<template>
  <section class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
      <div
        v-for="(banner, idx) in banners"
        :key="'promo-' + idx"
        class="group relative rounded-card overflow-hidden min-h-[340px] sm:min-h-[400px] flex items-end p-6 sm:p-10 shadow-soft border border-line"
      >
        <!-- Background Image -->
        <img
          :src="banner.image"
          :alt="banner.title"
          class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
          loading="lazy"
          width="800"
          height="600"
        />

        <!-- Soft Dark Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

        <!-- Text Content -->
        <div class="relative z-10 text-white space-y-3 max-w-md">
          <span
            v-if="banner.tag"
            class="inline-block text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-pill bg-white/20 backdrop-blur-md text-white border border-white/30"
          >
            {{ banner.tag }}
          </span>

          <h3 class="text-2xl sm:text-3xl font-bold tracking-tight leading-tight">
            {{ banner.title }}
          </h3>

          <p class="text-xs sm:text-sm text-white/80 line-clamp-2">
            {{ banner.description }}
          </p>

          <div class="pt-2">
            <RouterLink
              :to="banner.button_url || '/shop'"
              class="px-5 py-2.5 rounded-pill bg-white text-ink text-xs font-semibold hover:bg-brand hover:text-white transition inline-flex items-center gap-2 shadow-sm"
            >
              <span>{{ banner.button_text || 'Discover' }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
