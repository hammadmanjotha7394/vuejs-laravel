<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { Eye, EyeOff, Lock, Mail, AlertCircle, ArrowRight, ShieldCheck } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)
const errorMessage = ref('')
const isSubmitting = ref(false)

const quickAccounts = [
  { label: 'Super Admin', email: 'admin@shop.test', role: 'Full control' },
  { label: 'Product Lead', email: 'product@shop.test', role: 'Products only' },
  { label: 'Order Manager', email: 'order@shop.test', role: 'Orders only' },
  { label: 'Customer', email: 'customer@shop.test', role: 'Storefront' },
]

const fillAccount = (email) => {
  form.email = email
  form.password = 'password123'
  errorMessage.value = ''
}

const handleLogin = async () => {
  errorMessage.value = ''
  if (!form.email || !form.password) {
    errorMessage.value = 'Please provide both email and password.'
    return
  }

  isSubmitting.value = true
  try {
    await authStore.login({
      email: form.email,
      password: form.password,
      remember: form.remember,
    })

    const redirectPath = route.query.redirect || (authStore.isStaff ? '/admin' : '/')
    router.push(redirectPath)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Invalid email or password.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-page flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <RouterLink to="/" class="inline-flex items-center gap-2 text-2xl font-bold tracking-tight text-ink">
        <span class="w-9 h-9 rounded-full bg-brand text-white flex items-center justify-center text-sm font-semibold">B</span>
        <span>Boutique</span>
      </RouterLink>
      <h2 class="mt-4 text-2xl font-semibold tracking-tight text-ink">Sign in to your account</h2>
      <p class="mt-1 text-sm text-ink-muted">
        Or
        <RouterLink to="/register" class="font-medium text-brand hover:underline">
          create a new account
        </RouterLink>
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-surface border border-line py-8 px-6 shadow-soft rounded-card sm:px-10">
        <!-- Error Alert -->
        <div v-if="errorMessage" class="mb-5 p-3 rounded-input bg-red-50 border border-red-200 text-error flex items-start gap-2.5 text-sm">
          <AlertCircle class="w-4 h-4 flex-shrink-0 mt-0.5" />
          <span>{{ errorMessage }}</span>
        </div>

        <form class="space-y-5" @submit.prevent="handleLogin">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">Email address</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-muted">
                <Mail class="w-4 h-4" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                placeholder="name@example.com"
                class="block w-full pl-10 pr-3 py-2.5 text-sm bg-page border border-line rounded-input focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition"
              />
            </div>
          </div>

          <!-- Password Field -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">Password</label>
              <RouterLink to="/forgot-password" class="text-xs font-medium text-brand hover:underline">
                Forgot password?
              </RouterLink>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-muted">
                <Lock class="w-4 h-4" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="••••••••"
                class="block w-full pl-10 pr-10 py-2.5 text-sm bg-page border border-line rounded-input focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-ink-muted hover:text-ink transition"
                tabindex="-1"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center">
            <input
              id="remember-me"
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 text-brand focus:ring-brand border-line rounded"
            />
            <label for="remember-me" class="ml-2 block text-xs text-ink-muted">
              Remember me for 30 days
            </label>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-pill font-medium text-sm text-white bg-brand hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition disabled:opacity-50"
            >
              <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Sign in</span>
              <ArrowRight v-if="!isSubmitting" class="w-4 h-4" />
            </button>
          </div>
        </form>

        <!-- Quick Demo Switcher -->
        <div class="mt-8 pt-6 border-t border-line">
          <div class="flex items-center gap-1.5 text-xs font-semibold text-ink-muted uppercase tracking-wider mb-3">
            <ShieldCheck class="w-3.5 h-3.5 text-brand" />
            <span>Test Role Accounts (Click to Fill)</span>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <button
              v-for="acc in quickAccounts"
              :key="acc.email"
              type="button"
              @click="fillAccount(acc.email)"
              class="text-left p-2.5 rounded-input border border-line bg-page hover:border-brand/40 hover:bg-brand/5 transition"
            >
              <div class="font-semibold text-ink">{{ acc.label }}</div>
              <div class="text-[11px] text-ink-muted truncate">{{ acc.role }}</div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
