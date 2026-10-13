<script setup>
import { ref, onMounted } from 'vue'
import { Cookie } from 'lucide-vue-next'

const visible = ref(false)

onMounted(() => {
  const choice = localStorage.getItem('shop_cookie_consent')
  if (!choice) {
    visible.value = true
  }
})

function setConsent(value) {
  localStorage.setItem('shop_cookie_consent', value)
  visible.value = false
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed bottom-3 inset-x-3 sm:inset-x-auto sm:bottom-4 sm:left-4 z-50 sm:max-w-sm bg-white border border-line rounded-2xl p-3.5 sm:p-4 shadow-xl"
  >
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 rounded-full bg-brand/10 border border-brand/15 flex items-center justify-center shrink-0 mt-0.5 text-brand">
        <Cookie class="w-4 h-4" />
      </div>
      <div class="space-y-2.5 flex-1 min-w-0">
        <p class="text-xs text-ink-muted leading-relaxed">
          We use essential cookies to keep your shopping bag working and optional analytics cookies to improve our store.
          <router-link to="/page/privacy" class="underline text-brand font-medium hover:opacity-85">Privacy Policy</router-link>.
        </p>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-full bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-xs"
            @click="setConsent('accepted')"
          >
            Accept All
          </button>
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-full border border-line text-xs font-medium text-ink-muted hover:text-ink hover:bg-surface transition"
            @click="setConsent('declined')"
          >
            Essential Only
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
