<script setup>
defineProps({
  modelValue: {
    type: String,
    default: '#2F5D50',
  },
  presets: {
    type: Array,
    default: () => ['#2F5D50', '#1E3A8A', '#7C2D12', '#111827', '#047857', '#B45309'],
  },
})

const emit = defineEmits(['update:modelValue'])

const updateColor = (val) => {
  emit('update:modelValue', val)
}
</script>

<template>
  <div class="space-y-2">
    <div class="flex items-center gap-2.5">
      <label class="relative w-9 h-9 rounded-xl border border-line overflow-hidden cursor-pointer shadow-xs shrink-0">
        <input
          type="color"
          :value="modelValue || '#2F5D50'"
          @input="updateColor($event.target.value)"
          class="sr-only"
        />
        <span
          class="block w-full h-full"
          :style="{ backgroundColor: modelValue || '#2F5D50' }"
        />
      </label>

      <input
        type="text"
        :value="modelValue"
        @input="updateColor($event.target.value)"
        placeholder="#2F5D50"
        maxlength="7"
        class="w-32 rounded-xl border border-line px-3 py-2 text-xs font-mono text-ink uppercase focus:outline-none focus:border-brand"
      />

      <div v-if="presets?.length" class="flex items-center gap-1.5 pl-1">
        <button
          v-for="swatch in presets"
          :key="swatch"
          type="button"
          @click="updateColor(swatch)"
          class="w-5 h-5 rounded-full border border-black/10 transition hover:scale-110"
          :class="{ 'ring-2 ring-offset-1 ring-brand': modelValue?.toUpperCase() === swatch.toUpperCase() }"
          :style="{ backgroundColor: swatch }"
          :title="swatch"
        />
      </div>
    </div>
  </div>
</template>
