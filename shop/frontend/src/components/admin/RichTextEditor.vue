<script setup>
import { ref, watch, onMounted } from 'vue'
import {
  Bold,
  Italic,
  Heading2,
  Heading3,
  List,
  ListOrdered,
  Link as LinkIcon,
  Code,
  RemoveFormatting,
} from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Write formatted content here...',
  },
  minHeight: {
    type: String,
    default: '160px',
  },
})

const emit = defineEmits(['update:modelValue'])

const editorRef = ref(null)
const sourceMode = ref(false)
const rawHtml = ref(props.modelValue || '')

watch(
  () => props.modelValue,
  (val) => {
    rawHtml.value = val || ''
    if (editorRef.value && editorRef.value.innerHTML !== (val || '')) {
      editorRef.value.innerHTML = val || ''
    }
  }
)

onMounted(() => {
  if (editorRef.value) {
    editorRef.value.innerHTML = props.modelValue || ''
  }
})

const syncFromVisual = () => {
  if (!editorRef.value) return
  const html = editorRef.value.innerHTML
  rawHtml.value = html
  emit('update:modelValue', html)
}

const syncFromSource = () => {
  emit('update:modelValue', rawHtml.value)
}

const exec = (command, value = null) => {
  if (sourceMode.value) return
  editorRef.value?.focus()
  document.execCommand(command, false, value)
  syncFromVisual()
}

const insertLink = () => {
  if (sourceMode.value) return
  const url = window.prompt('Enter link URL (https://...):', 'https://')
  if (url) {
    exec('createLink', url)
  }
}

const toggleSource = () => {
  sourceMode.value = !sourceMode.value
  if (!sourceMode.value && editorRef.value) {
    editorRef.value.innerHTML = rawHtml.value
  }
}
</script>

<template>
  <div class="rounded-xl border border-line bg-white overflow-hidden focus-within:border-brand transition">
    <!-- Formatting Toolbar -->
    <div class="px-2.5 py-1.5 bg-surface border-b border-line flex flex-wrap items-center justify-between gap-1">
      <div class="flex flex-wrap items-center gap-0.5">
        <button
          type="button"
          @click="exec('bold')"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Bold"
        >
          <Bold class="w-3.5 h-3.5" />
        </button>
        <button
          type="button"
          @click="exec('italic')"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Italic"
        >
          <Italic class="w-3.5 h-3.5" />
        </button>
        <button
          type="button"
          @click="exec('formatBlock', 'H2')"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Heading 2"
        >
          <Heading2 class="w-3.5 h-3.5" />
        </button>
        <button
          type="button"
          @click="exec('formatBlock', 'H3')"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Heading 3"
        >
          <Heading3 class="w-3.5 h-3.5" />
        </button>
        <span class="w-px h-4 bg-line mx-1" />
        <button
          type="button"
          @click="exec('insertUnorderedList')"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Bullet List"
        >
          <List class="w-3.5 h-3.5" />
        </button>
        <button
          type="button"
          @click="exec('insertOrderedList')"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Numbered List"
        >
          <ListOrdered class="w-3.5 h-3.5" />
        </button>
        <button
          type="button"
          @click="insertLink"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Insert Link"
        >
          <LinkIcon class="w-3.5 h-3.5" />
        </button>
        <button
          type="button"
          @click="exec('removeFormat')"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-white transition"
          title="Clear Formatting"
        >
          <RemoveFormatting class="w-3.5 h-3.5" />
        </button>
      </div>

      <button
        type="button"
        @click="toggleSource"
        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold transition"
        :class="sourceMode ? 'bg-brand text-white' : 'text-ink-muted hover:text-ink hover:bg-white'"
        title="Toggle HTML Source"
      >
        <Code class="w-3 h-3" />
        <span>HTML</span>
      </button>
    </div>

    <!-- Visual Editable Area -->
    <div
      v-show="!sourceMode"
      ref="editorRef"
      contenteditable="true"
      :style="{ minHeight }"
      class="p-3.5 text-xs text-ink prose prose-sm max-w-none focus:outline-none leading-relaxed"
      @input="syncFromVisual"
      @blur="syncFromVisual"
    />

    <!-- Raw HTML Source Textarea -->
    <textarea
      v-if="sourceMode"
      v-model="rawHtml"
      :style="{ minHeight }"
      :placeholder="placeholder"
      class="w-full p-3.5 text-xs font-mono text-ink bg-surface/30 focus:outline-none"
      @input="syncFromSource"
    />
  </div>
</template>
