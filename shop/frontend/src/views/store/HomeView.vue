<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { CheckCircle2, AlertCircle, RefreshCw, ShoppingBag, ArrowRight } from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const healthStatus = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')

const fetchHealth = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await apiClient.get('/health')
    healthStatus.value = response.data
  } catch (err) {
    errorMessage.value = err.response?.data?.message || err.message || 'Failed to connect to API'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchHealth()
})
</script>

<template>
  <div class="min-h-screen bg-page flex flex-col justify-between text-ink">
    <!-- Header -->
    <header class="border-b border-line bg-page">
      <div class="max-w-container mx-auto px-4 md:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-brand/10 text-brand flex items-center justify-center">
            <ShoppingBag class="w-5 h-5" />
          </div>
          <div>
            <span class="text-lg font-semibold tracking-tight">Shop</span>
            <span class="text-xs text-ink-muted ml-2 font-normal hidden sm:inline">Production-ready ecommerce</span>
          </div>
        </div>

        <div class="flex items-center gap-3 text-xs font-medium">
          <template v-if="authStore.isAuthenticated">
            <span class="text-ink font-semibold hidden sm:inline">{{ authStore.user?.name }}</span>
            <RouterLink
              v-if="authStore.isStaff"
              to="/admin"
              class="px-3.5 py-1.5 rounded-pill bg-brand text-white hover:opacity-90 transition font-medium"
            >
              Admin Dashboard
            </RouterLink>
            <button
              @click="authStore.logout()"
              class="px-3 py-1.5 rounded-pill border border-line hover:bg-surface text-ink-muted hover:text-ink transition"
            >
              Sign out
            </button>
          </template>
          <template v-else>
            <RouterLink
              to="/login"
              class="px-3.5 py-1.5 rounded-pill border border-line hover:bg-surface text-ink transition"
            >
              Sign in
            </RouterLink>
            <RouterLink
              to="/register"
              class="px-3.5 py-1.5 rounded-pill bg-brand text-white hover:opacity-90 transition"
            >
              Register
            </RouterLink>
          </template>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-container mx-auto px-4 md:px-8 py-16 flex-1 flex flex-col items-center justify-center">
      <div class="w-full max-w-xl bg-surface border border-line rounded-card p-8 shadow-soft">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand text-white mb-4">
            <ShoppingBag class="w-6 h-6" />
          </div>
          <h1 class="text-2xl font-semibold tracking-tight text-ink">Storefront Foundation</h1>
          <p class="text-ink-muted text-sm mt-2">Vue 3 + Vite + Tailwind CSS talking to Laravel Sanctum API</p>
        </div>

        <!-- Health Check Card -->
        <div class="bg-page border border-line rounded-card p-6 mb-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-ink-muted">API Health Check</h2>
            <button
              @click="fetchHealth"
              :disabled="isLoading"
              class="inline-flex items-center gap-1.5 text-xs text-brand hover:opacity-80 font-medium transition-opacity disabled:opacity-50"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
              <span>Refresh</span>
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="isLoading" class="flex items-center gap-3 py-2 text-ink-muted text-sm">
            <div class="w-4 h-4 rounded-full border-2 border-brand border-t-transparent animate-spin"></div>
            <span>Connecting to GET /api/health...</span>
          </div>

          <!-- Success state -->
          <div v-else-if="healthStatus" class="flex items-start gap-3 py-1">
            <CheckCircle2 class="w-5 h-5 text-success flex-shrink-0 mt-0.5" />
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-ink">API Status: Healthy</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-xs font-semibold bg-emerald-50 text-success border border-emerald-200">
                  200 OK
                </span>
              </div>
              <p class="text-xs text-ink-muted mt-1 font-mono">Response: {{ JSON.stringify(healthStatus) }}</p>
            </div>
          </div>

          <!-- Error state -->
          <div v-else class="flex items-start gap-3 py-1">
            <AlertCircle class="w-5 h-5 text-error flex-shrink-0 mt-0.5" />
            <div>
              <span class="text-sm font-semibold text-error">API Connection Failed</span>
              <p class="text-xs text-ink-muted mt-1">{{ errorMessage }}</p>
            </div>
          </div>
        </div>

        <!-- Specs summary -->
        <div class="space-y-2 text-xs text-ink-muted">
          <div class="flex justify-between py-1 border-b border-line">
            <span>Design tokens</span>
            <span class="font-medium text-ink">Inter font · Soft surfaces · Pill buttons · #2F5D50 Brand</span>
          </div>
          <div class="flex justify-between py-1 border-b border-line">
            <span>Stack</span>
            <span class="font-medium text-ink">Vue 3 · Pinia · Vue Router · Tailwind · Laravel 12 API</span>
          </div>
          <div class="flex justify-between py-1">
            <span>Backend proxy</span>
            <span class="font-medium text-ink">/api and /sanctum &rarr; http://127.0.0.1:8000</span>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-line bg-surface py-6">
      <div class="max-w-container mx-auto px-4 md:px-8 text-center text-xs text-ink-muted">
        E-commerce Web App Foundation &middot; Production Ready Architecture
      </div>
    </footer>
  </div>
</template>
