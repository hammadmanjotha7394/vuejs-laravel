<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const toggle = (current, disabled) => {
  if (disabled) return
  const next = !current
  emit('update:modelValue', next)
  emit('change', next)
}
</script>

<template>
  <div
    class="inline-flex items-center justify-between gap-3"
    :class="{ 'opacity-50 cursor-not-allowed': disabled, 'cursor-pointer': !disabled }"
    @click="toggle(modelValue, disabled)"
  >
    <div v-if="label || description" class="min-w-0">
      <p v-if="label" class="text-xs font-semibold text-ink">{{ label }}</p>
      <p v-if="description" class="text-[11px] text-ink-muted mt-0.5">{{ description }}</p>
    </div>

    <button
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :disabled="disabled"
      class="relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
      :class="modelValue ? 'bg-brand' : 'bg-gray-200'"
    >
      <span
        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out"
        :class="modelValue ? 'translate-x-4' : 'translate-x-0'"
      />
    </button>
  </div>
</template>
