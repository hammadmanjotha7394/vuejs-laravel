<script setup>
defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  tabs: {
    type: Array,
    required: true,
    // [{ key: 'general', label: 'General', icon: Component, badge: 3 }]
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const selectTab = (key) => {
  emit('update:modelValue', key)
  emit('change', key)
}
</script>

<template>
  <div class="flex items-center gap-1.5 overflow-x-auto bg-surface p-1 rounded-xl border border-line">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      type="button"
      @click="selectTab(tab.key)"
      class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all"
      :class="
        modelValue === tab.key
          ? 'bg-white text-ink shadow-xs border border-line/60'
          : 'text-ink-muted hover:text-ink'
      "
    >
      <component
        v-if="tab.icon"
        :is="tab.icon"
        class="w-3.5 h-3.5 shrink-0"
        :class="modelValue === tab.key ? 'text-brand' : 'text-ink-muted'"
      />
      <span>{{ tab.label }}</span>
      <span
        v-if="tab.badge !== undefined && tab.badge !== null"
        class="px-1.5 py-0.2 rounded-pill text-[10px] font-bold"
        :class="
          modelValue === tab.key
            ? 'bg-brand/10 text-brand'
            : 'bg-white text-ink-muted border border-line'
        "
      >
        {{ tab.badge }}
      </span>
    </button>
  </div>
</template>
