<script setup>
import { ref } from 'vue'
import { UploadCloud, Trash2, ArrowLeft, ArrowRight, Star, Loader2 } from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
    // [{ id, url, thumb_url, alt, is_primary }]
  },
  multiple: {
    type: Boolean,
    default: true,
  },
  uploading: {
    type: Boolean,
    default: false,
  },
  progress: {
    type: Number,
    default: 0,
  },
  accept: {
    type: String,
    default: 'image/jpeg,image/png,image/webp,image/svg+xml',
  },
})

const emit = defineEmits(['update:modelValue', 'files-selected', 'remove', 'set-primary'])

const isDragging = ref(false)
const fileInput = ref(null)

const handleFiles = (fileList) => {
  if (!fileList || !fileList.length) return
  const files = Array.from(fileList)
  emit('files-selected', props.multiple ? files : [files[0]])
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const onDrop = (e) => {
  isDragging.value = false
  handleFiles(e.dataTransfer?.files)
}

const moveItem = (index, direction) => {
  const target = index + direction
  if (target < 0 || target >= props.modelValue.length) return
  const copy = [...props.modelValue]
  const [moved] = copy.splice(index, 1)
  copy.splice(target, 0, moved)
  emit('update:modelValue', copy)
}

const removeItem = (index, item) => {
  const copy = props.modelValue.filter((_, idx) => idx !== index)
  emit('update:modelValue', copy)
  emit('remove', { index, item })
}

const markPrimary = (index) => {
  const copy = props.modelValue.map((item, idx) => ({
    ...item,
    is_primary: idx === index,
  }))
  emit('update:modelValue', copy)
  emit('set-primary', index)
}
</script>

<template>
  <div class="space-y-4">
    <!-- Dropzone -->
    <div
      class="relative rounded-card border-2 border-dashed p-6 text-center transition cursor-pointer"
      :class="
        isDragging
          ? 'border-brand bg-brand/5'
          : 'border-line bg-surface/50 hover:border-brand/50 hover:bg-surface'
      "
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      @click="fileInput?.click()"
    >
      <input
        ref="fileInput"
        type="file"
        :multiple="multiple"
        :accept="accept"
        class="hidden"
        @change="handleFiles($event.target.files)"
      />

      <div class="w-10 h-10 rounded-xl bg-white border border-line text-brand flex items-center justify-center mx-auto mb-2.5 shadow-2xs">
        <Loader2 v-if="uploading" class="w-5 h-5 animate-spin" />
        <UploadCloud v-else class="w-5 h-5" />
      </div>

      <p class="text-xs font-bold text-ink">
        {{ uploading ? 'Uploading media...' : 'Click to upload or drag & drop' }}
      </p>
      <p class="text-[11px] text-ink-muted mt-0.5">
        JPG, PNG, WebP or sanitized SVG (automatically converted to WebP sizes)
      </p>

      <!-- Upload Progress Bar -->
      <div
        v-if="uploading && progress > 0"
        class="max-w-xs mx-auto mt-3 h-1.5 rounded-full bg-gray-200 overflow-hidden"
      >
        <div
          class="h-full bg-brand transition-all duration-200"
          :style="{ width: `${Math.min(100, progress)}%` }"
        />
      </div>
    </div>

    <!-- Image Previews & Reorder Grid -->
    <div
      v-if="modelValue?.length"
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"
    >
      <div
        v-for="(img, idx) in modelValue"
        :key="img.id || img.url || idx"
        class="group relative rounded-xl border p-2 bg-white transition"
        :class="img.is_primary ? 'border-brand ring-1 ring-brand/30' : 'border-line'"
      >
        <div class="aspect-square rounded-lg bg-surface overflow-hidden mb-2 relative">
          <img
            :src="img.thumb_url || img.url"
            :alt="img.alt || 'Uploaded image'"
            class="w-full h-full object-cover"
          />
          <span
            v-if="img.is_primary"
            class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-pill bg-brand text-white text-[10px] font-bold shadow-xs"
          >
            Primary
          </span>
        </div>

        <div class="flex items-center justify-between gap-1">
          <div class="flex items-center gap-0.5">
            <button
              v-if="multiple"
              type="button"
              :disabled="idx === 0"
              @click="moveItem(idx, -1)"
              class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface disabled:opacity-30"
              title="Move left"
            >
              <ArrowLeft class="w-3.5 h-3.5" />
            </button>
            <button
              v-if="multiple"
              type="button"
              :disabled="idx === modelValue.length - 1"
              @click="moveItem(idx, 1)"
              class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface disabled:opacity-30"
              title="Move right"
            >
              <ArrowRight class="w-3.5 h-3.5" />
            </button>
            <button
              v-if="multiple && !img.is_primary"
              type="button"
              @click="markPrimary(idx)"
              class="p-1 rounded text-ink-muted hover:text-amber-600 hover:bg-amber-50"
              title="Set as primary"
            >
              <Star class="w-3.5 h-3.5" />
            </button>
          </div>

          <button
            type="button"
            @click="removeItem(idx, img)"
            class="p-1 rounded text-ink-muted hover:text-error hover:bg-red-50"
            title="Remove image"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
