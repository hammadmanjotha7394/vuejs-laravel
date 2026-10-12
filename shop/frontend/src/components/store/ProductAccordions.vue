<script setup>
import { ref } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  sections: {
    type: Array,
    default: () => [],
  },
  specs: {
    type: Array,
    default: () => [],
  },
})

// Initialize open set with sections marked 'is_open_by_default'
const openSections = ref(new Set())

// Open sections that have is_open_by_default
props.sections.forEach((s) => {
  if (s.is_open_by_default) {
    openSections.value.add(`sec-${s.id}`)
  }
})

// Specifications accordion can be open by default if present
if (props.specs.length > 0) {
  openSections.value.add('specs')
}

const toggle = (id) => {
  if (openSections.value.has(id)) {
    openSections.value.delete(id)
  } else {
    openSections.value.add(id)
  }
}

const isOpen = (id) => openSections.value.has(id)
</script>

<template>
  <div class="divide-y divide-line border-t border-b border-line my-8" aria-label="Product Information">
    <!-- Specifications Accordion Section -->
    <div v-if="specs && specs.length > 0" class="py-4">
      <button
        type="button"
        @click="toggle('specs')"
        class="w-full flex items-center justify-between py-1 text-left font-bold text-sm sm:text-base text-ink hover:text-brand transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded"
        :aria-expanded="isOpen('specs')"
        aria-controls="accordion-specs"
      >
        <span>Specifications & Dimensions</span>
        <ChevronDown
          class="w-4 h-4 text-ink-muted transition-transform duration-200"
          :class="{ 'rotate-180': isOpen('specs') }"
        />
      </button>

      <div
        v-show="isOpen('specs')"
        id="accordion-specs"
        class="pt-3 pb-1"
      >
        <div class="rounded-lg bg-surface/60 border border-line/60 overflow-hidden">
          <table class="w-full text-xs">
            <tbody>
              <tr
                v-for="(sp, idx) in specs"
                :key="'spec-' + idx"
                class="border-b border-line/40 last:border-b-0 hover:bg-white/50 transition"
              >
                <td class="py-2.5 px-4 font-semibold text-ink-muted w-1/3 sm:w-1/4">
                  {{ sp.label }}
                </td>
                <td class="py-2.5 px-4 text-ink font-medium">
                  {{ sp.value }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Product Details Sections Accordions -->
    <div
      v-for="section in sections"
      :key="'section-' + section.id"
      class="py-4"
    >
      <button
        type="button"
        @click="toggle(`sec-${section.id}`)"
        class="w-full flex items-center justify-between py-1 text-left font-bold text-sm sm:text-base text-ink hover:text-brand transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded"
        :aria-expanded="isOpen(`sec-${section.id}`)"
        :aria-controls="`accordion-sec-${section.id}`"
      >
        <span>{{ section.title }}</span>
        <ChevronDown
          class="w-4 h-4 text-ink-muted transition-transform duration-200"
          :class="{ 'rotate-180': isOpen(`sec-${section.id}`) }"
        />
      </button>

      <div
        v-show="isOpen(`sec-${section.id}`)"
        :id="`accordion-sec-${section.id}`"
        class="pt-3 pb-1 text-xs sm:text-sm text-ink-muted leading-relaxed prose prose-sm max-w-none"
        v-html="section.content"
      ></div>
    </div>
  </div>
</template>
