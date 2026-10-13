<script setup>
defineProps({
  label: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  hint: {
    type: String,
    default: '',
  },
  error: {
    type: [String, Array],
    default: '',
  },
})
</script>

<template>
  <div class="space-y-1.5">
    <div v-if="label || $slots.label" class="flex items-center justify-between gap-2">
      <label class="block text-xs font-semibold text-ink">
        <slot name="label">{{ label }}</slot>
        <span v-if="required" class="text-error ml-0.5">*</span>
      </label>
      <slot name="label-right" />
    </div>

    <slot />

    <p v-if="error" class="text-[11px] font-medium text-error">
      {{ Array.isArray(error) ? error[0] : error }}
    </p>
    <p v-else-if="hint" class="text-[11px] text-ink-muted">
      {{ hint }}
    </p>
  </div>
</template>
