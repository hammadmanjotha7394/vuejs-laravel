<script setup>
import { AlertTriangle, X, Loader2 } from 'lucide-vue-next'

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirm Action',
  },
  message: {
    type: String,
    default: 'Are you sure you want to proceed with this action?',
  },
  confirmText: {
    type: String,
    default: 'Confirm',
  },
  cancelText: {
    type: String,
    default: 'Cancel',
  },
  variant: {
    type: String,
    default: 'danger', // 'danger' | 'primary'
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4"
        @click.self="emit('close')"
      >
        <div class="bg-white rounded-card border border-line max-w-md w-full p-6 shadow-xl">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div
                class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                :class="
                  variant === 'danger'
                    ? 'bg-red-50 text-error'
                    : 'bg-brand/10 text-brand'
                "
              >
                <AlertTriangle class="w-4 h-4" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-ink">{{ title }}</h3>
                <p class="text-xs text-ink-muted mt-1 leading-relaxed">
                  {{ message }}
                </p>
              </div>
            </div>

            <button
              type="button"
              @click="emit('close')"
              class="p-1 rounded-lg text-ink-muted hover:text-ink hover:bg-surface"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <div v-if="$slots.default" class="mt-4">
            <slot />
          </div>

          <div class="mt-6 pt-4 border-t border-line flex items-center justify-end gap-2.5">
            <button
              type="button"
              :disabled="loading"
              @click="emit('close')"
              class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted hover:text-ink transition"
            >
              {{ cancelText }}
            </button>

            <button
              type="button"
              :disabled="loading"
              @click="emit('confirm')"
              class="inline-flex items-center gap-1.5 px-5 py-2 rounded-pill text-xs font-semibold text-white transition disabled:opacity-50"
              :class="
                variant === 'danger'
                  ? 'bg-error hover:bg-red-700'
                  : 'bg-brand hover:bg-brand-dark'
              "
            >
              <Loader2 v-if="loading" class="w-3.5 h-3.5 animate-spin" />
              <span>{{ confirmText }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
