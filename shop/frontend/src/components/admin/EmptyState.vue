<script setup>
import { Inbox } from 'lucide-vue-next'

defineProps({
  icon: {
    type: [Object, Function],
    default: () => Inbox,
  },
  title: {
    type: String,
    default: 'No records found',
  },
  description: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div class="bg-white rounded-card border border-line p-8 sm:p-12 text-center">
    <div class="w-12 h-12 rounded-2xl bg-surface border border-line text-ink-muted flex items-center justify-center mx-auto mb-3.5">
      <component :is="icon" class="w-5 h-5" />
    </div>
    <h3 class="text-sm font-bold text-ink">{{ title }}</h3>
    <p v-if="description" class="text-xs text-ink-muted max-w-sm mx-auto mt-1">
      {{ description }}
    </p>
    <div v-if="$slots.default || $slots.action" class="mt-4 flex items-center justify-center gap-2">
      <slot name="action" />
      <slot />
    </div>
  </div>
</template>
