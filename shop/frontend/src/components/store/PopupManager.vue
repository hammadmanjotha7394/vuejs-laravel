<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { X, ArrowRight, Loader2, CheckCircle2 } from 'lucide-vue-next'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const toastStore = useToastStore()

const popups = ref([])
const activePopup = ref(null)
const emailInput = ref('')
const isSubscribing = ref(false)
const subscribed = ref(false)

let delayTimer = null
let scrollHandler = null
let exitIntentHandler = null

const isEligibleByPage = (popup) => {
  if (route.path.startsWith('/checkout') || route.path.startsWith('/admin')) return false

  const pages = popup.show_on_pages_json
  if (!Array.isArray(pages) || pages.length === 0 || pages.includes('all')) {
    return true
  }
  if (pages.includes('home') && route.path === '/') {
    return true
  }
  return pages.some((p) => {
    const clean = String(p).trim()
    return clean && (route.path === clean || route.path.startsWith(clean))
  })
}

const isEligibleByFrequency = (popup) => {
  const id = popup.id
  const freq = popup.frequency || 'once_per_session'

  if (freq === 'always') return true

  if (freq === 'once_per_session') {
    return !sessionStorage.getItem(`boutique_popup_session_${id}`)
  }

  if (freq === 'once_per_day') {
    const lastSeen = localStorage.getItem(`boutique_popup_day_${id}`)
    if (!lastSeen) return true
    const diffHours = (Date.now() - Number(lastSeen)) / (1000 * 60 * 60)
    return diffHours >= 24
  }

  if (freq === 'once_ever') {
    return !localStorage.getItem(`boutique_popup_ever_${id}`)
  }

  return true
}

const markPopupAsSeen = (popup) => {
  const id = popup.id
  const freq = popup.frequency || 'once_per_session'

  if (freq === 'once_per_session') {
    sessionStorage.setItem(`boutique_popup_session_${id}`, '1')
  } else if (freq === 'once_per_day') {
    localStorage.setItem(`boutique_popup_day_${id}`, String(Date.now()))
  } else if (freq === 'once_ever') {
    localStorage.setItem(`boutique_popup_ever_${id}`, '1')
  }
}

const trackPopupEvent = async (popupId, action) => {
  if (!popupId) return
  try {
    await axios.post(`/api/popups/${popupId}/track`, { action })
  } catch (e) {
    // Non-fatal
  }
}

const showPopup = (popup) => {
  if (!isEligibleByPage(popup)) return
  if (activePopup.value) return

  if (isEligibleByFrequency(popup)) {
    activePopup.value = popup
    markPopupAsSeen(popup)
    trackPopupEvent(popup.id, 'view')
  }
}

const closePopup = () => {
  activePopup.value = null
  subscribed.value = false
  emailInput.value = ''
}

const handleCtaClick = () => {
  if (activePopup.value) {
    trackPopupEvent(activePopup.value.id, 'click')
  }
  closePopup()
}

const handleNewsletterSubmit = async () => {
  if (!emailInput.value.trim()) return

  isSubscribing.value = true
  try {
    const response = await axios.post('/api/newsletter/subscribe', {
      email: emailInput.value.trim(),
      popup_id: activePopup.value?.id || null,
    })
    subscribed.value = true
    toastStore.success(response.data.message || 'Subscribed successfully!')
    setTimeout(() => {
      closePopup()
    }, 2200)
  } catch (err) {
    const msg =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Failed to subscribe.'
    toastStore.error(msg)
  } finally {
    isSubscribing.value = false
  }
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && activePopup.value) {
    closePopup()
  }
}

const setupTriggers = () => {
  if (popups.value.length === 0) return

  const candidate = popups.value.find((p) => isEligibleByPage(p) && isEligibleByFrequency(p))
  if (!candidate) return

  const trigger = candidate.trigger || 'delay'
  const triggerVal = Number(candidate.trigger_value) || 5

  if (trigger === 'on_load') {
    showPopup(candidate)
  } else if (trigger === 'delay') {
    delayTimer = setTimeout(() => {
      showPopup(candidate)
    }, triggerVal * 1000)
  } else if (trigger === 'scroll') {
    const scrollTarget = triggerVal || 40
    scrollHandler = () => {
      const scrollY = window.scrollY
      const docHeight = document.documentElement.scrollHeight - window.innerHeight
      if (docHeight > 0) {
        const scrolledPercent = (scrollY / docHeight) * 100
        if (scrolledPercent >= scrollTarget) {
          showPopup(candidate)
          window.removeEventListener('scroll', scrollHandler)
        }
      }
    }
    window.addEventListener('scroll', scrollHandler, { passive: true })
  } else if (trigger === 'exit_intent') {
    exitIntentHandler = (e) => {
      if (e.clientY <= 10) {
        showPopup(candidate)
        document.removeEventListener('mouseleave', exitIntentHandler)
      }
    }
    document.addEventListener('mouseleave', exitIntentHandler)
  }
}

const fetchPopups = async () => {
  try {
    const res = await axios.get('/api/popups')
    popups.value = res.data.data || []
    setupTriggers()
  } catch (e) {
    // Non-fatal if popups fail to load
  }
}

watch(
  () => route.path,
  (newPath) => {
    if (newPath.startsWith('/checkout') && activePopup.value) {
      closePopup()
    }
  }
)

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
  fetchPopups()
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
  clearTimeout(delayTimer)
  if (scrollHandler) window.removeEventListener('scroll', scrollHandler)
  if (exitIntentHandler) document.removeEventListener('mouseleave', exitIntentHandler)
})
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="activePopup"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      :aria-label="activePopup.title || activePopup.name"
      @click.self="closePopup"
    >
      <div
        class="relative w-full max-w-lg bg-white rounded-card shadow-2xl border border-line overflow-hidden flex flex-col md:flex-row transition-all"
        @click.stop
      >
        <!-- Close button -->
        <button
          type="button"
          @click="closePopup"
          class="absolute top-3 right-3 z-20 p-1.5 rounded-full bg-white/80 hover:bg-white text-ink-muted hover:text-ink shadow-sm transition"
          aria-label="Close popup"
        >
          <X class="w-4 h-4" />
        </button>

        <!-- Image-Only Popup Mode -->
        <div v-if="activePopup.type === 'image_only' && activePopup.image" class="w-full">
          <a
            v-if="activePopup.button_url"
            :href="activePopup.button_url"
            @click="handleCtaClick"
            class="block w-full"
          >
            <img
              :src="activePopup.image"
              :alt="activePopup.title || activePopup.name"
              class="w-full max-h-[460px] object-cover"
            />
          </a>
          <img
            v-else
            :src="activePopup.image"
            :alt="activePopup.title || activePopup.name"
            class="w-full max-h-[460px] object-cover"
          />
        </div>

        <template v-else>
          <!-- Optional side image -->
          <div
            v-if="activePopup.image"
            class="w-full md:w-5/12 aspect-[4/3] md:aspect-auto relative bg-surface overflow-hidden flex-shrink-0"
          >
            <img
              :src="activePopup.image"
              :alt="activePopup.title"
              class="w-full h-full object-cover"
            />
          </div>

          <!-- Popup content -->
          <div class="p-6 md:p-8 flex-1 flex flex-col justify-between">
            <div>
              <span
                v-if="activePopup.type === 'newsletter'"
                class="inline-block text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-pill bg-brand/10 text-brand mb-2"
              >
                Exclusive Invitation
              </span>

              <h3 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
                {{ activePopup.title }}
              </h3>

              <p class="text-xs text-ink-muted leading-relaxed mt-2.5">
                {{ activePopup.body }}
              </p>
            </div>

            <!-- Newsletter Form -->
            <div v-if="activePopup.type === 'newsletter'" class="mt-6">
              <div
                v-if="subscribed"
                class="flex items-center gap-2 p-3 bg-emerald-50 rounded-lg border border-emerald-200 text-xs text-success font-medium"
              >
                <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
                <span>Thank you! Your private voucher is in your inbox.</span>
              </div>
              <form v-else @submit.prevent="handleNewsletterSubmit" class="space-y-2">
                <input
                  v-model="emailInput"
                  type="email"
                  required
                  placeholder="Enter your email address"
                  class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs text-ink placeholder:text-ink-muted focus:outline-none focus:border-brand transition"
                />
                <button
                  type="submit"
                  :disabled="isSubscribing"
                  class="w-full py-2.5 px-4 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition flex items-center justify-center gap-2 disabled:opacity-60 shadow-sm"
                >
                  <Loader2 v-if="isSubscribing" class="w-3.5 h-3.5 animate-spin" />
                  <span>{{ activePopup.button_text || 'Claim Your Voucher' }}</span>
                  <ArrowRight v-if="!isSubscribing" class="w-3.5 h-3.5" />
                </button>
              </form>
            </div>

            <!-- Standard CTA Button -->
            <div v-else-if="activePopup.button_text" class="mt-6">
              <a
                v-if="activePopup.button_url"
                :href="activePopup.button_url"
                @click="handleCtaClick"
                class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
              >
                <span>{{ activePopup.button_text }}</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </a>
            </div>
          </div>
        </template>
      </div>
    </div>
  </Transition>
</template>
