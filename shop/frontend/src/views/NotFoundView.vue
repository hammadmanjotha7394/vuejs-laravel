<script setup>
import { RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import { ArrowLeft, Home, Compass } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

useHead({
  title: `Page Not Found | ${settingsStore.siteName}`,
  meta: [
    { name: 'description', content: 'The requested page could not be located on the store.' },
    { name: 'robots', content: 'noindex, follow' },
  ],
})
</script>

<template>
  <div class="min-h-[65vh] flex items-center justify-center px-4 py-16 text-center">
    <div class="max-w-lg w-full bg-white border border-line rounded-card p-8 sm:p-12 shadow-soft space-y-6">
      <div class="w-16 h-16 rounded-full bg-brand/10 text-brand flex items-center justify-center mx-auto">
        <Compass class="w-8 h-8" />
      </div>

      <div class="space-y-2">
        <span class="text-xs font-bold uppercase tracking-widest text-brand">404 Error</span>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
          Page Not Found
        </h1>
        <p class="text-xs sm:text-sm text-ink-muted leading-relaxed">
          The piece you are searching for may have been archived, renamed, or temporarily relocated to another collection.
        </p>
      </div>

      <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
        <RouterLink
          to="/"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
        >
          <Home class="w-4 h-4" />
          <span>Return Home</span>
        </RouterLink>

        <RouterLink
          to="/shop"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-pill border border-line bg-white hover:bg-surface text-ink text-xs font-semibold transition"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Browse Catalog</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
