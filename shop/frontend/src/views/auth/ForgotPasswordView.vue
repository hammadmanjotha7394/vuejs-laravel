<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'
import { Mail, ArrowLeft, ArrowRight, CheckCircle2, AlertCircle, KeyRound } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()

const email = ref('')
const isSubmitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const handleSubmit = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!email.value.trim()) {
    errorMessage.value = 'Please enter your email address.'
    return
  }

  isSubmitting.value = true
  try {
    const res = await axios.post('/api/forgot-password', {
      email: email.value.trim(),
    })
    successMessage.value =
      res.data?.message ||
      'If an account exists for this email, password reset instructions have been sent.'
  } catch (err) {
    errorMessage.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Unable to process request right now. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="bg-surface/40 py-12 sm:py-20 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <div class="w-12 h-12 rounded-full bg-brand/10 text-brand flex items-center justify-center mx-auto mb-3">
        <KeyRound class="w-5 h-5" />
      </div>
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">Reset your password</h1>
      <p class="mt-2 text-xs sm:text-sm text-ink-muted max-w-sm mx-auto">
        Enter the email address associated with your {{ settingsStore.siteName }} account and we will send you a password reset link.
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white border border-line py-8 px-6 shadow-soft rounded-card sm:px-10">
        <!-- Success Banner -->
        <div
          v-if="successMessage"
          class="mb-5 p-4 rounded-input bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-2.5 text-xs sm:text-sm"
        >
          <CheckCircle2 class="w-4 h-4 flex-shrink-0 mt-0.5 text-emerald-600" />
          <div>
            <p class="font-medium">{{ successMessage }}</p>
            <RouterLink to="/login" class="inline-flex items-center gap-1 mt-2 font-semibold text-brand hover:underline">
              <ArrowLeft class="w-3.5 h-3.5" />
              <span>Return to Sign In</span>
            </RouterLink>
          </div>
        </div>

        <!-- Error Banner -->
        <div
          v-if="errorMessage"
          class="mb-5 p-3 rounded-input bg-red-50 border border-red-200 text-error flex items-start gap-2.5 text-xs sm:text-sm"
        >
          <AlertCircle class="w-4 h-4 flex-shrink-0 mt-0.5" />
          <span>{{ errorMessage }}</span>
        </div>

        <form class="space-y-5" @submit.prevent="handleSubmit">
          <div>
            <label for="reset-email" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Email address
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-muted">
                <Mail class="w-4 h-4" />
              </div>
              <input
                id="reset-email"
                v-model="email"
                type="email"
                autocomplete="email"
                required
                placeholder="name@example.com"
                class="block w-full pl-10 pr-3 py-2.5 text-sm bg-page border border-line rounded-input focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-pill font-semibold text-xs sm:text-sm text-white bg-brand hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition disabled:opacity-50"
          >
            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Send Reset Instructions</span>
            <ArrowRight v-if="!isSubmitting" class="w-4 h-4" />
          </button>
        </form>

        <div class="mt-6 pt-5 border-t border-line flex items-center justify-between text-xs text-ink-muted">
          <RouterLink to="/login" class="inline-flex items-center gap-1.5 font-medium text-ink hover:text-brand transition">
            <ArrowLeft class="w-3.5 h-3.5" />
            <span>Back to Sign In</span>
          </RouterLink>
          <RouterLink to="/contact" class="font-medium text-brand hover:underline">
            Need help? Contact Support
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
