<script setup>
import { ref, reactive } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { Eye, EyeOff, Lock, Mail, User as UserIcon, AlertCircle, ArrowRight } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const errorMessage = ref('')
const isSubmitting = ref(false)

const handleRegister = async () => {
  errorMessage.value = ''
  if (!form.name || !form.email || !form.password) {
    errorMessage.value = 'Please complete all required fields.'
    return
  }
  if (form.password.length < 8) {
    errorMessage.value = 'Password must be at least 8 characters.'
    return
  }
  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Passwords do not match.'
    return
  }

  isSubmitting.value = true
  try {
    await authStore.register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })

    router.push('/account')
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      errorMessage.value = Object.values(errors).flat().join(' ')
    } else {
      errorMessage.value = err.response?.data?.message || 'Registration failed.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="bg-surface/40 py-12 sm:py-16 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
        Create your {{ settingsStore.siteName }} account
      </h1>
      <p class="mt-1.5 text-xs sm:text-sm text-ink-muted">
        Already have an account?
        <RouterLink to="/login" class="font-semibold text-brand hover:underline">
          Sign in here
        </RouterLink>
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white border border-line py-8 px-6 shadow-soft rounded-card sm:px-10">
        <!-- Error Alert -->
        <div
          v-if="errorMessage"
          class="mb-5 p-3 rounded-input bg-red-50 border border-red-200 text-error flex items-start gap-2.5 text-xs sm:text-sm"
        >
          <AlertCircle class="w-4 h-4 flex-shrink-0 mt-0.5" />
          <span>{{ errorMessage }}</span>
        </div>

        <form class="space-y-4" @submit.prevent="handleRegister">
          <!-- Full Name -->
          <div>
            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Full name
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-muted">
                <UserIcon class="w-4 h-4" />
              </div>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                placeholder="Jane Doe"
                class="block w-full pl-10 pr-3 py-2.5 text-sm bg-page border border-line rounded-input focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition"
              />
            </div>
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Email address
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-muted">
                <Mail class="w-4 h-4" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                placeholder="jane@example.com"
                class="block w-full pl-10 pr-3 py-2.5 text-sm bg-page border border-line rounded-input focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition"
              />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Password (8+ characters)
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-muted">
                <Lock class="w-4 h-4" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="block w-full pl-10 pr-10 py-2.5 text-sm bg-page border border-line rounded-input focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-ink-muted hover:text-ink transition"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Confirm Password -->
          <div>
            <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
              Confirm password
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-muted">
                <Lock class="w-4 h-4" />
              </div>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="block w-full pl-10 pr-3 py-2.5 text-sm bg-page border border-line rounded-input focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition"
              />
            </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-pill font-semibold text-xs sm:text-sm text-white bg-brand hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition disabled:opacity-50"
            >
              <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Create Account</span>
              <ArrowRight v-if="!isSubmitting" class="w-4 h-4" />
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
