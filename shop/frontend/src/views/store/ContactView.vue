<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useSettingsStore } from '@/stores/settings'
import { Mail, Phone, MapPin, CheckCircle2, Send } from 'lucide-vue-next'

const settingsStore = useSettingsStore()

const form = ref({
  name: '',
  email: '',
  subject: '',
  message: '',
  website: '', // Honeypot anti-spam field
})

const sending = ref(false)
const submitted = ref(false)
const errorMsg = ref('')

async function submitContact() {
  sending.value = true
  errorMsg.value = ''
  try {
    await axios.post('/api/contact', form.value)
    submitted.value = true
    form.value = { name: '', email: '', subject: '', message: '', website: '' }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Unable to send message right now. Please try again.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
      <!-- Left Contact Details -->
      <div class="lg:col-span-5 space-y-6">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-ink tracking-tight">Get in Touch</h1>
          <p class="text-sm text-ink-muted mt-2 leading-relaxed">
            Have a question about sizing, materials, or an existing order? Our concierge studio team responds within one business day.
          </p>
        </div>

        <div class="space-y-4 pt-2">
          <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-full bg-surface border border-line flex items-center justify-center shrink-0 text-brand">
              <Mail class="w-4 h-4" />
            </div>
            <div>
              <div class="text-xs font-semibold text-ink">Customer Care Email</div>
              <a
                :href="`mailto:${settingsStore.supportEmail || 'support@boutique.test'}`"
                class="text-sm text-ink-muted hover:text-brand transition"
              >
                {{ settingsStore.supportEmail || 'support@boutique.test' }}
              </a>
            </div>
          </div>

          <div v-if="settingsStore.supportPhone" class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-full bg-surface border border-line flex items-center justify-center shrink-0 text-brand">
              <Phone class="w-4 h-4" />
            </div>
            <div>
              <div class="text-xs font-semibold text-ink">Telephone Concierge</div>
              <a
                :href="`tel:${settingsStore.supportPhone}`"
                class="text-sm text-ink-muted hover:text-brand transition"
              >
                {{ settingsStore.supportPhone }}
              </a>
            </div>
          </div>

          <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-full bg-surface border border-line flex items-center justify-center shrink-0 text-brand">
              <MapPin class="w-4 h-4" />
            </div>
            <div>
              <div class="text-xs font-semibold text-ink">Studio Address</div>
              <div class="text-sm text-ink-muted leading-relaxed">
                {{ settingsStore.storeAddress || '120 Mercer Street, New York, NY 10012' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Contact Form -->
      <div class="lg:col-span-7">
        <div class="bg-white border border-line rounded-card p-6 sm:p-8 shadow-soft">
          <div v-if="submitted" class="py-8 text-center space-y-3">
            <CheckCircle2 class="w-10 h-10 text-success mx-auto" />
            <h2 class="text-lg font-bold text-ink">Message Received</h2>
            <p class="text-sm text-ink-muted max-w-md mx-auto">
              Thank you for reaching out. Our studio team has received your message and will reply to your email shortly.
            </p>
            <button
              type="button"
              class="mt-4 px-5 py-2.5 rounded-pill border border-line text-xs font-semibold text-ink hover:bg-surface transition"
              @click="submitted = false"
            >
              Send Another Message
            </button>
          </div>

          <form v-else class="space-y-4" @submit.prevent="submitContact">
            <!-- Honeypot field hidden from human visitors -->
            <div class="hidden" aria-hidden="true">
              <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-ink mb-1.5">Your Name *</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  placeholder="Jane Doe"
                  class="w-full px-4 py-2.5 text-sm bg-surface/40 border border-line rounded-input focus:outline-none focus:border-brand focus:bg-white transition"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-ink mb-1.5">Email Address *</label>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="jane@example.com"
                  class="w-full px-4 py-2.5 text-sm bg-surface/40 border border-line rounded-input focus:outline-none focus:border-brand focus:bg-white transition"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Subject *</label>
              <input
                v-model="form.subject"
                type="text"
                required
                placeholder="Order question, sizing advice, press inquiry..."
                class="w-full px-4 py-2.5 text-sm bg-surface/40 border border-line rounded-input focus:outline-none focus:border-brand focus:bg-white transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Message *</label>
              <textarea
                v-model="form.message"
                rows="5"
                required
                placeholder="How may our studio team assist you today?"
                class="w-full px-4 py-2.5 text-sm bg-surface/40 border border-line rounded-input focus:outline-none focus:border-brand focus:bg-white transition"
              ></textarea>
            </div>

            <p v-if="errorMsg" class="text-xs text-error font-medium">{{ errorMsg }}</p>

            <button
              type="submit"
              :disabled="sending"
              class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-pill bg-brand text-white text-xs sm:text-sm font-semibold hover:opacity-90 transition shadow-sm disabled:opacity-50"
            >
              <Send class="w-4 h-4" />
              <span>{{ sending ? 'Sending Message...' : 'Send Message' }}</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
