<script setup>
import { ref, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { X, ArrowRight } from 'lucide-vue-next'

const settingsStore = useSettingsStore()
const isDismissed = ref(false)

const announcement = computed(() => settingsStore.announcement)

const displayText = computed(() => {
  if (announcement.value?.text && announcement.value.text.trim()) {
    return announcement.value.text.trim()
  }
  if (settingsStore.freeShippingThreshold > 0) {
    return `Complimentary worldwide shipping on orders over ${settingsStore.currencySymbol}${settingsStore.freeShippingThreshold} • Use code WELCOME10 for 10% off`
  }
  return ''
})

const isVisible = computed(() => {
  if (isDismissed.value) return false
  if (!announcement.value || announcement.value.enabled === false) return false
  if (!displayText.value) return false

  // Date range check if defined
  const now = new Date().toISOString()
  if (announcement.value.start_date && announcement.value.start_date > now) return false
  if (announcement.value.end_date && announcement.value.end_date < now) return false

  // Check storage dismissal
  const textHash = btoa(unescape(encodeURIComponent(displayText.value))).slice(0, 16)
  if (sessionStorage.getItem(`announcement_dismissed_${textHash}`)) {
    return false
  }

  return true
})

const dismiss = () => {
  isDismissed.value = true
  if (displayText.value) {
    const textHash = btoa(unescape(encodeURIComponent(displayText.value))).slice(0, 16)
    sessionStorage.setItem(`announcement_dismissed_${textHash}`, '1')
  }
}
</script>

<template>
  <aside
    v-if="isVisible"
    class="relative z-40 text-[11px] sm:text-xs py-2 px-3 sm:px-4 flex items-center justify-center transition-all duration-300 font-medium"
    :style="{
      backgroundColor: announcement?.background_color || announcement?.bg_color || 'var(--brand, #2F5D50)',
      color: announcement?.text_color || '#FFFFFF',
    }"
    aria-label="Announcement"
  >
    <div class="max-w-container mx-auto flex flex-wrap items-center justify-center text-center gap-x-2 gap-y-0.5 px-7 sm:px-10 leading-tight">
      <span class="tracking-tight">{{ displayText }}</span>
      <RouterLink
        v-if="announcement?.link || !announcement?.text"
        :to="announcement?.link || '/shop'"
        class="inline-flex items-center gap-1 underline underline-offset-4 hover:opacity-85 font-semibold transition text-white shrink-0"
      >
        <span>Shop Now</span>
        <ArrowRight class="w-3 h-3" />
      </RouterLink>
    </div>

    <button
      v-if="announcement.dismissible !== false"
      type="button"
      @click="dismiss"
      class="absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-full hover:bg-black/10 transition opacity-80 hover:opacity-100"
      aria-label="Dismiss announcement"
    >
      <X class="w-3.5 h-3.5" />
    </button>
  </aside>
</template>
