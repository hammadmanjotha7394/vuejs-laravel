<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@unhead/vue'
import { useSettingsStore } from '@/stores/settings'
import { Layers, ArrowLeft } from 'lucide-vue-next'

const route = useRoute()
const settingsStore = useSettingsStore()

const moduleTitle = computed(() => route.meta?.title || 'Administration Module')
const moduleDescription = computed(
  () =>
    route.meta?.description ||
    'This administrative module is registered in the navigation shell and protected by role permissions.'
)

useHead({
  title: () => `${moduleTitle.value} | Admin | ${settingsStore.siteName}`,
})
</script>

<template>
  <div class="bg-white rounded-card border border-line p-8 sm:p-12 shadow-card text-center">
    <div class="w-12 h-12 rounded-2xl bg-brand/10 text-brand flex items-center justify-center mx-auto mb-4">
      <Layers class="w-6 h-6" />
    </div>
    <p class="text-[11px] font-bold uppercase tracking-wider text-brand">Admin Workspace</p>
    <h1 class="text-lg sm:text-xl font-bold text-ink mt-1">{{ moduleTitle }}</h1>
    <p class="text-xs text-ink-muted max-w-md mx-auto mt-1.5 mb-6 leading-relaxed">
      {{ moduleDescription }}
    </p>
    <RouterLink
      to="/admin"
      class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
    >
      <ArrowLeft class="w-3.5 h-3.5" />
      <span>Back to Dashboard</span>
    </RouterLink>
  </div>
</template>
