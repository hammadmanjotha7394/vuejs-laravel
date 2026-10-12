<script setup>
import { ref, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { X, ArrowRight } from 'lucide-vue-next'

const settingsStore = useSettingsStore()
const isDismissed = ref(false)

const announcement = computed(() => settingsStore.announcement)

const isVisible = computed(() => {
  if (isDismissed.value) return false
  if (!announcement.value || !announcement.value.enabled) return false
  if (!announcement.value.text) return false

  // Date range check if defined
  const now = new Date().toISOString()
  if (announcement.value.start_date && announcement.value.start_date > now) return false
  if (announcement.value.end_date && announcement.value.end_date < now) return false

  // Check storage dismissal
  const textHash = btoa(unescape(encodeURIComponent(announcement.value.text))).slice(0, 16)
  if (sessionStorage.getItem(`announcement_dismissed_${textHash}`)) {
    return false
  }

  return true
})

const dismiss = () => {
  isDismissed.value = true
  if (announcement.value?.text) {
    const textHash = btoa(unescape(encodeURIComponent(announcement.value.text))).slice(0, 16)
    sessionStorage.setItem(`announcement_dismissed_${textHash}`, '1')
  }
}
</script>

<template>
  <aside
    v-if="isVisible"
    class="relative z-40 text-xs py-2 px-4 flex items-center justify-center transition-all duration-300"
    :style="{
      backgroundColor: announcement?.background_color || 'var(--brand, #2F5D50)',
      color: announcement?.text_color || '#FFFFFF',
    }"
    aria-label="Announcement"
  >
    <div class="max-w-container mx-auto flex items-center justify-center text-center gap-2 pr-6">
      <span class="font-medium tracking-tight">{{ announcement.text }}</span>
      <a
        v-if="announcement.link"
        :href="announcement.link"
        class="inline-flex items-center gap-1 underline underline-offset-4 hover:opacity-80 font-semibold transition"
      >
        <span>Shop Now</span>
        <ArrowRight class="w-3 h-3" />
      </a>
    </div>

    <button
      v-if="announcement.dismissible !== false"
      type="button"
      @click="dismiss"
      class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-full hover:bg-black/10 transition opacity-80 hover:opacity-100"
      aria-label="Dismiss announcement"
    >
      <X class="w-3.5 h-3.5" />
    </button>
  </aside>
</template>
