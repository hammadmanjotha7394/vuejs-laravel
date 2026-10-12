<script setup>
import { useToastStore } from '@/stores/toast'
import { CheckCircle2, AlertCircle, Info, AlertTriangle, X } from 'lucide-vue-next'

const toastStore = useToastStore()

const getIcon = (type) => {
  switch (type) {
    case 'success':
      return CheckCircle2
    case 'error':
      return AlertCircle
    case 'warning':
      return AlertTriangle
    default:
      return Info
  }
}

const getStyles = (type) => {
  switch (type) {
    case 'success':
      return {
        iconColor: 'text-success',
        badgeBg: 'bg-emerald-50',
      }
    case 'error':
      return {
        iconColor: 'text-error',
        badgeBg: 'bg-red-50',
      }
    case 'warning':
      return {
        iconColor: 'text-accent',
        badgeBg: 'bg-amber-50',
      }
    default:
      return {
        iconColor: 'text-brand',
        badgeBg: 'bg-brand/10',
      }
  }
}
</script>

<template>
  <div
    class="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0"
    aria-live="polite"
    aria-atomic="true"
  >
    <TransitionGroup
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        class="pointer-events-auto bg-white border border-line rounded-card p-4 shadow-soft flex items-start gap-3 relative overflow-hidden"
        role="alert"
      >
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
          :class="getStyles(toast.type).badgeBg"
        >
          <component
            :is="getIcon(toast.type)"
            class="w-4 h-4"
            :class="getStyles(toast.type).iconColor"
          />
        </div>

        <div class="flex-1 pr-6 min-w-0">
          <h4 v-if="toast.title" class="text-xs font-semibold text-ink">
            {{ toast.title }}
          </h4>
          <p class="text-xs text-ink-muted mt-0.5 break-words">
            {{ toast.message }}
          </p>
        </div>

        <button
          type="button"
          @click="toastStore.remove(toast.id)"
          class="absolute top-3 right-3 text-ink-muted hover:text-ink p-1 rounded-full hover:bg-surface transition"
          aria-label="Dismiss notification"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
