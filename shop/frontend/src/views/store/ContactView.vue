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
          <h1 class="text-2xl sm:text-3xl font-semibold text-[#111111] tracking-tight">Get in Touch</h1>
          <p class="text-sm text-[#6B7280] mt-2 leading-relaxed">
            Have a question about sizing, materials, or an existing order? Our studio team responds within one business day.
          </p>
        </div>

        <div class="space-y-4 pt-2">
          <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-full bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-center shrink-0">
              <Mail class="w-4 h-4 text-[#111111]" />
            </div>
            <div>
              <div class="text-xs font-semibold text-[#111111]">Customer Care Email</div>
              <div class="text-sm text-[#6B7280]">{{ settingsStore.general?.support_email || 'support@boutique.test' }}</div>
            </div>
          </div>

          <div v-if="settingsStore.general?.support_phone" class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-full bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-center shrink-0">
              <Phone class="w-4 h-4 text-[#111111]" />
            </div>
            <div>
              <div class="text-xs font-semibold text-[#111111]">Telephone</div>
              <div class="text-sm text-[#6B7280]">{{ settingsStore.general.support_phone }}</div>
            </div>
          </div>

          <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-full bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-center shrink-0">
              <MapPin class="w-4 h-4 text-[#111111]" />
            </div>
            <div>
              <div class="text-xs font-semibold text-[#111111]">Studio Address</div>
              <div class="text-sm text-[#6B7280]">{{ settingsStore.general?.store_address || '120 Mercer Street, New York, NY 10012' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Contact Form -->
      <div class="lg:col-span-7">
        <div class="bg-white border border-[#EAE8E3] rounded-2xl p-6 sm:p-8">
          <div v-if="submitted" class="py-8 text-center space-y-3">
            <CheckCircle2 class="w-10 h-10 text-[#15803D] mx-auto" />
            <h2 class="text-lg font-semibold text-[#111111]">Message Received</h2>
            <p class="text-sm text-[#6B7280] max-w-md mx-auto">
              Thank you for reaching out. Our team has received your inquiry and will reply to your email shortly.
            </p>
            <button
              type="button"
              class="mt-4 px-5 py-2.5 rounded-full border border-[#EAE8E3] text-xs font-medium text-[#111111]"
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
                <label class="block text-xs font-semibold text-[#111111] mb-1.5">Your Name *</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 text-sm border border-[#EAE8E3] rounded-xl focus:outline-none focus:border-[#111111]"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-[#111111] mb-1.5">Email Address *</label>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  class="w-full px-4 py-2.5 text-sm border border-[#EAE8E3] rounded-xl focus:outline-none focus:border-[#111111]"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-[#111111] mb-1.5">Subject *</label>
              <input
                v-model="form.subject"
                type="text"
                required
                placeholder="Order question, sizing advice, press inquiry..."
                class="w-full px-4 py-2.5 text-sm border border-[#EAE8E3] rounded-xl focus:outline-none focus:border-[#111111]"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-[#111111] mb-1.5">Message *</label>
              <textarea
                v-model="form.message"
                rows="5"
                required
                class="w-full px-4 py-2.5 text-sm border border-[#EAE8E3] rounded-xl focus:outline-none focus:border-[#111111]"
              ></textarea>
            </div>

            <p v-if="errorMsg" class="text-xs text-[#DC2626]">{{ errorMsg }}</p>

            <button
              type="submit"
              :disabled="sending"
              class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-[#111111] text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
            >
              <Send class="w-4 h-4" />
              {{ sending ? 'Sending...' : 'Send Message' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
