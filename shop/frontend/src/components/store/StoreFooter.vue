<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'
import {
  ArrowRight,
  CheckCircle2,
  Instagram,
  Facebook,
  Youtube,
  Twitter,
  MessageCircle,
  Loader2,
  Lock,
} from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'
import { useMenuStore } from '@/stores/menu'
import { useToastStore } from '@/stores/toast'

const settingsStore = useSettingsStore()
const menuStore = useMenuStore()
const toastStore = useToastStore()

const newsletterEmail = ref('')
const isSubscribing = ref(false)
const subscribedSuccess = ref(false)
const subscribeError = ref('')

const handleSubscribe = async () => {
  if (!newsletterEmail.value.trim()) return

  isSubscribing.value = true
  subscribeError.value = ''

  try {
    const response = await axios.post('/api/newsletter/subscribe', {
      email: newsletterEmail.value.trim(),
    })
    subscribedSuccess.value = true
    toastStore.success(response.data.message || 'Subscribed successfully!')
    newsletterEmail.value = ''
  } catch (err) {
    subscribeError.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Failed to subscribe. Please try again.'
    toastStore.error(subscribeError.value)
  } finally {
    isSubscribing.value = false
  }
}

const currentYear = new Date().getFullYear()

const copyrightText = computed(() => {
  return (
    settingsStore.headerFooter?.copyright_text ||
    `© ${currentYear} ${settingsStore.siteName}. Mindfully crafted for quiet living.`
  )
})

const footerText = computed(() => {
  return (
    settingsStore.headerFooter?.footer_text ||
    'Mindful apparel and artisanal home objects created in small, deliberate batches with European and Japanese heritage workshops.'
  )
})

const socialLinks = computed(() => settingsStore.socialLinks || {})

const footerMenuItems = computed(() => {
  return menuStore.footerMenu?.items || []
})
</script>

<template>
  <footer class="bg-surface/60 border-t border-line text-ink pt-14 pb-10">
    <div class="max-w-container mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Top Section: Brand + Navigation Columns + Newsletter -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8 pb-12 border-b border-line">
        <!-- Col 1: Brand story & Socials (4 cols) -->
        <div class="lg:col-span-4 space-y-4">
          <RouterLink to="/" class="inline-block">
            <img
              v-if="settingsStore.logoUrl"
              :src="settingsStore.logoUrl"
              :alt="settingsStore.siteName"
              class="h-7 sm:h-8 object-contain"
            />
            <span v-else class="text-xl font-bold tracking-tight text-ink">
              {{ settingsStore.siteName }}
            </span>
          </RouterLink>

          <p class="text-xs sm:text-sm text-ink-muted leading-relaxed max-w-sm">
            {{ footerText }}
          </p>

          <!-- Social media links -->
          <div class="pt-2 flex items-center gap-3">
            <a
              v-if="socialLinks.instagram"
              :href="socialLinks.instagram"
              target="_blank"
              rel="noopener noreferrer"
              class="w-8 h-8 rounded-full bg-white border border-line flex items-center justify-center text-ink-muted hover:text-brand hover:border-brand transition"
              aria-label="Instagram"
            >
              <Instagram class="w-4 h-4" />
            </a>

            <a
              v-if="socialLinks.facebook"
              :href="socialLinks.facebook"
              target="_blank"
              rel="noopener noreferrer"
              class="w-8 h-8 rounded-full bg-white border border-line flex items-center justify-center text-ink-muted hover:text-brand hover:border-brand transition"
              aria-label="Facebook"
            >
              <Facebook class="w-4 h-4" />
            </a>

            <a
              v-if="socialLinks.youtube"
              :href="socialLinks.youtube"
              target="_blank"
              rel="noopener noreferrer"
              class="w-8 h-8 rounded-full bg-white border border-line flex items-center justify-center text-ink-muted hover:text-brand hover:border-brand transition"
              aria-label="YouTube"
            >
              <Youtube class="w-4 h-4" />
            </a>

            <a
              v-if="socialLinks.x || socialLinks.twitter"
              :href="socialLinks.x || socialLinks.twitter"
              target="_blank"
              rel="noopener noreferrer"
              class="w-8 h-8 rounded-full bg-white border border-line flex items-center justify-center text-ink-muted hover:text-brand hover:border-brand transition"
              aria-label="X / Twitter"
            >
              <Twitter class="w-4 h-4" />
            </a>

            <a
              v-if="socialLinks.whatsapp"
              :href="`https://wa.me/${socialLinks.whatsapp}`"
              target="_blank"
              rel="noopener noreferrer"
              class="w-8 h-8 rounded-full bg-white border border-line flex items-center justify-center text-ink-muted hover:text-brand hover:border-brand transition"
              aria-label="WhatsApp"
            >
              <MessageCircle class="w-4 h-4" />
            </a>
          </div>
        </div>

        <!-- Col 2: Navigation Columns (4 cols) -->
        <div class="lg:col-span-4 grid grid-cols-2 gap-6">
          <!-- Quick links column 1 -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-ink mb-3.5">
              Explore
            </h4>
            <ul class="space-y-2.5 text-xs text-ink-muted">
              <li>
                <RouterLink to="/shop" class="hover:text-brand transition">All Products</RouterLink>
              </li>
              <li>
                <RouterLink to="/category/apparel" class="hover:text-brand transition">Apparel</RouterLink>
              </li>
              <li>
                <RouterLink to="/category/home-living" class="hover:text-brand transition">Home & Living</RouterLink>
              </li>
              <li>
                <RouterLink to="/category/bags-leather" class="hover:text-brand transition">Bags & Leather</RouterLink>
              </li>
            </ul>
          </div>

          <!-- Quick links column 2 (from footer menu or CMS) -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-ink mb-3.5">
              Care & Help
            </h4>
            <ul class="space-y-2.5 text-xs text-ink-muted">
              <template v-if="footerMenuItems.length > 0">
                <li v-for="item in footerMenuItems" :key="'f-item-' + item.id">
                  <RouterLink :to="item.url || '#'" class="hover:text-brand transition">
                    {{ item.label }}
                  </RouterLink>
                </li>
              </template>
              <template v-else>
                <li>
                  <RouterLink to="/about" class="hover:text-brand transition">About Us</RouterLink>
                </li>
                <li>
                  <RouterLink to="/faq" class="hover:text-brand transition">FAQ</RouterLink>
                </li>
                <li>
                  <RouterLink to="/shipping" class="hover:text-brand transition">Shipping & Returns</RouterLink>
                </li>
                <li>
                  <RouterLink to="/terms" class="hover:text-brand transition">Terms of Service</RouterLink>
                </li>
              </template>
            </ul>
          </div>
        </div>

        <!-- Col 3: Newsletter Signup (4 cols) -->
        <div class="lg:col-span-4 space-y-3.5">
          <h4 class="text-xs font-bold uppercase tracking-wider text-ink">
            Newsletter
          </h4>
          <p class="text-xs text-ink-muted leading-relaxed">
            Join our mailing list to receive thoughtful narratives, private promotions, and early access to archival releases.
          </p>

          <form @submit.prevent="handleSubscribe" class="space-y-2">
            <div class="flex items-center gap-2">
              <input
                v-model="newsletterEmail"
                type="email"
                required
                placeholder="Enter your email address"
                class="flex-1 px-3.5 py-2.5 bg-white border border-line rounded-input text-xs text-ink placeholder:text-ink-muted focus:outline-none focus:border-brand transition shadow-sm"
              />
              <button
                type="submit"
                :disabled="isSubscribing"
                class="px-4 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition flex items-center gap-1.5 flex-shrink-0 disabled:opacity-60 shadow-sm"
              >
                <Loader2 v-if="isSubscribing" class="w-3.5 h-3.5 animate-spin" />
                <span v-else>Join</span>
                <ArrowRight v-if="!isSubscribing" class="w-3 h-3" />
              </button>
            </div>

            <!-- Inline Feedback message -->
            <div
              v-if="subscribedSuccess"
              class="flex items-center gap-2 text-xs text-success font-medium bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200"
            >
              <CheckCircle2 class="w-3.5 h-3.5 flex-shrink-0" />
              <span>Thank you! Your 10% voucher code has been queued.</span>
            </div>
            <div
              v-if="subscribeError"
              class="text-xs text-error font-medium px-1"
            >
              {{ subscribeError }}
            </div>
          </form>

          <p class="text-[11px] text-ink-muted flex items-center gap-1.5 pt-1">
            <Lock class="w-3 h-3 text-ink-muted" />
            <span>We honor your digital privacy. Unsubscribe anytime.</span>
          </p>
        </div>
      </div>

      <!-- Bottom Section: Payment Badges & Copyright -->
      <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-ink-muted">
        <div>
          {{ copyrightText }}
        </div>

        <!-- Payment methods badges -->
        <div
          v-if="settingsStore.headerFooter?.payment_icons !== false"
          class="flex items-center gap-2"
          aria-label="Accepted payment methods"
        >
          <span class="px-2 py-0.5 rounded bg-white border border-line text-[10px] font-bold text-gray-700">
            VISA
          </span>
          <span class="px-2 py-0.5 rounded bg-white border border-line text-[10px] font-bold text-gray-700">
            MC
          </span>
          <span class="px-2 py-0.5 rounded bg-white border border-line text-[10px] font-bold text-gray-700">
            AMEX
          </span>
          <span class="px-2 py-0.5 rounded bg-white border border-line text-[10px] font-bold text-gray-700">
            APPLE PAY
          </span>
          <span class="px-2 py-0.5 rounded bg-white border border-line text-[10px] font-bold text-gray-700">
            GPAY
          </span>
          <span class="px-2 py-0.5 rounded bg-white border border-line text-[10px] font-bold text-gray-700">
            STRIPE
          </span>
        </div>
      </div>
    </div>
  </footer>
</template>
