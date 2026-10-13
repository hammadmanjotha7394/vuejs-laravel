<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { Eye, EyeOff, Lock, Mail, AlertCircle, ArrowRight, ShieldCheck, Zap, ChevronDown } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)
const showDemoRoles = ref(true)
const errorMessage = ref('')
const isSubmitting = ref(false)

const quickAccounts = [
  { label: 'Super Admin', email: 'admin@shop.test', role: 'Full control & settings', badge: 'Admin' },
  { label: 'Product Lead', email: 'product@shop.test', role: 'Catalog, menus & pages', badge: 'Staff' },
  { label: 'Order Manager', email: 'order@shop.test', role: 'Orders & fulfillment', badge: 'Staff' },
  { label: 'Support Agent', email: 'support@shop.test', role: 'Live chat & reviews', badge: 'Staff' },
  { label: 'Finance Manager', email: 'finance@shop.test', role: 'Revenue & refunds', badge: 'Staff' },
  { label: 'Customer', email: 'customer@shop.test', role: 'Storefront & orders', badge: 'Shopper' },
]

const fillAccount = (email) => {
  form.email = email
  form.password = 'password123'
  errorMessage.value = ''
}

const quickLogin = async (email) => {
  fillAccount(email)
  await handleLogin()
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

    const redirectPath = route.query.redirect || (authStore.isStaff ? '/admin' : '/account')
    router.push(redirectPath)
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      errorMessage.value = Object.values(errors).flat().join(' ')
    } else {
      errorMessage.value = err.response?.data?.message || 'Invalid email or password.'
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
        Sign in to {{ settingsStore.siteName }}
      </h1>
      <p class="mt-1.5 text-xs sm:text-sm text-ink-muted">
        Don’t have an account yet?
        <RouterLink to="/register" class="font-semibold text-brand hover:underline">
          Create a free account
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

        <form class="space-y-5" @submit.prevent="handleLogin">
          <!-- Email Field -->
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
              <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted">
                Password
              </label>
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
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Remember Me & Track Order link -->
          <div class="flex items-center justify-between">
            <label for="remember-me" class="flex items-center cursor-pointer">
              <input
                id="remember-me"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-brand focus:ring-brand border-line rounded"
              />
              <span class="ml-2 block text-xs text-ink-muted">Remember me for 30 days</span>
            </label>
            <RouterLink to="/track-order" class="text-xs text-ink-muted hover:text-brand transition">
              Guest order lookup
            </RouterLink>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-pill font-semibold text-xs sm:text-sm text-white bg-brand hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition disabled:opacity-50"
            >
              <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Sign In</span>
              <ArrowRight v-if="!isSubmitting" class="w-4 h-4" />
            </button>
          </div>
        </form>

        <!-- Role & Demo Quick-Login Switcher -->
        <div class="mt-8 pt-6 border-t border-line">
          <button
            type="button"
            @click="showDemoRoles = !showDemoRoles"
            class="w-full flex items-center justify-between text-xs font-semibold text-ink-muted uppercase tracking-wider mb-3 hover:text-ink transition"
          >
            <span class="flex items-center gap-1.5">
              <ShieldCheck class="w-3.5 h-3.5 text-brand" />
              <span>Role Access & Quick Sign-In (6 Roles)</span>
            </span>
            <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showDemoRoles }" />
          </button>

          <div v-if="showDemoRoles" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            <div
              v-for="acc in quickAccounts"
              :key="acc.email"
              class="p-2.5 rounded-input border border-line bg-surface/60 hover:border-brand/40 transition flex flex-col justify-between gap-2"
            >
              <div @click="fillAccount(acc.email)" class="cursor-pointer">
                <div class="flex items-center justify-between gap-1">
                  <span class="font-semibold text-ink">{{ acc.label }}</span>
                  <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-white border border-line text-ink-muted">
                    {{ acc.badge }}
                  </span>
                </div>
                <div class="text-[11px] text-ink-muted truncate mt-0.5">{{ acc.role }}</div>
              </div>
              <div class="flex items-center gap-1.5 pt-1 border-t border-line/60">
                <button
                  type="button"
                  @click="fillAccount(acc.email)"
                  class="flex-1 py-1 px-2 rounded bg-white border border-line text-[11px] font-medium text-ink hover:border-brand transition"
                >
                  Fill
                </button>
                <button
                  type="button"
                  :disabled="isSubmitting"
                  @click="quickLogin(acc.email)"
                  class="flex-1 py-1 px-2 rounded bg-brand text-white text-[11px] font-semibold hover:opacity-90 transition inline-flex items-center justify-center gap-1"
                >
                  <Zap class="w-3 h-3" />
                  <span>Login</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
