<script setup>
import { ref, onErrorCaptured } from 'vue'
import { AlertCircle, RotateCcw } from 'lucide-vue-next'

const hasError = ref(false)
const errorMessage = ref('')

onErrorCaptured((err, instance, info) => {
  console.error('Unhandled render error captured by ErrorBoundary:', err, info)
  hasError.value = true
  errorMessage.value = err?.message || 'An unexpected display error occurred.'
  return false // stop propagation
})

const resetError = () => {
  hasError.value = false
  errorMessage.value = ''
  window.location.reload()
}
</script>

<template>
  <div v-if="hasError" class="min-h-[50vh] flex items-center justify-center p-6 text-center">
    <div class="max-w-md w-full bg-white border border-line rounded-card p-8 shadow-soft">
      <div class="w-12 h-12 rounded-full bg-red-50 text-error flex items-center justify-center mx-auto mb-4">
        <AlertCircle class="w-6 h-6" />
      </div>
      <h3 class="text-lg font-semibold text-ink">Something went wrong</h3>
      <p class="text-xs text-ink-muted mt-2 mb-6">
        {{ errorMessage }}
      </p>
      <button
        type="button"
        @click="resetError"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-medium hover:opacity-90 transition"
      >
        <RotateCcw class="w-4 h-4" />
        <span>Reload Page</span>
      </button>
    </div>
  </div>
  <slot v-else />
</template>
