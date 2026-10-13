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
    class="fixed bottom-4 left-4 z-40 max-w-sm bg-white border border-[#EAE8E3] rounded-2xl p-4 shadow-lg"
  >
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 rounded-full bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-center shrink-0 mt-0.5">
        <Cookie class="w-4 h-4 text-[#111111]" />
      </div>
      <div class="space-y-2.5">
        <p class="text-xs text-[#6B7280] leading-relaxed">
          We use essential cookies to keep your shopping bag working and optional analytics cookies to improve our store.
          <router-link to="/page/privacy" class="underline text-[#111111]">Privacy Policy</router-link>.
        </p>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-full bg-[#111111] text-white text-xs font-medium hover:opacity-90"
            @click="setConsent('accepted')"
          >
            Accept All
          </button>
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-full border border-[#EAE8E3] text-xs font-medium text-[#6B7280] hover:text-[#111111]"
            @click="setConsent('declined')"
          >
            Essential Only
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
