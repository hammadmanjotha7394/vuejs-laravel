<script setup>
import { X, Loader2 } from 'lucide-vue-next'

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  submitText: {
    type: String,
    default: 'Save Changes',
  },
  cancelText: {
    type: String,
    default: 'Cancel',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  widthClass: {
    type: String,
    default: 'max-w-lg',
  },
})

const emit = defineEmits(['close', 'submit'])
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs"
        @click.self="emit('close')"
      >
        <div
          class="bg-white w-full h-full flex flex-col shadow-2xl border-l border-line"
          :class="widthClass"
        >
          <!-- Header -->
          <div class="px-6 py-4 border-b border-line flex items-center justify-between gap-4 shrink-0">
            <div>
              <h2 class="text-base font-bold text-ink">{{ title }}</h2>
              <p v-if="subtitle" class="text-xs text-ink-muted mt-0.5">{{ subtitle }}</p>
            </div>
            <button
              type="button"
              @click="emit('close')"
              class="p-2 rounded-full text-ink-muted hover:text-ink hover:bg-surface transition"
              aria-label="Close panel"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Form Body -->
          <form
            @submit.prevent="emit('submit')"
            class="flex-1 flex flex-col min-h-0"
          >
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
              <slot />
            </div>

            <!-- Sticky Footer -->
            <div class="px-6 py-4 border-t border-line bg-surface/50 flex items-center justify-end gap-2.5 shrink-0">
              <slot name="footer">
                <button
                  type="button"
                  :disabled="loading"
                  @click="emit('close')"
                  class="px-4 py-2 rounded-pill border border-line bg-white text-xs font-semibold text-ink-muted hover:text-ink transition"
                >
                  {{ cancelText }}
                </button>
                <button
                  type="submit"
                  :disabled="loading"
                  class="inline-flex items-center gap-1.5 px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark disabled:opacity-50 transition"
                >
                  <Loader2 v-if="loading" class="w-3.5 h-3.5 animate-spin" />
                  <span>{{ submitText }}</span>
                </button>
              </slot>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
