<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import { ChevronRight, Home } from 'lucide-vue-next'

const props = defineProps({
  items: {
    type: Array,
    required: true, // [ { label: 'Shop', url: '/shop' }, { label: 'Apparel', url: '/category/apparel' } ]
  },
})

// JSON-LD Structured Data for Google BreadcrumbList
const breadcrumbSchema = computed(() => {
  const itemListElement = [
    {
      '@type': 'ListItem',
      position: 1,
      name: 'Home',
      item: window.location.origin + '/',
    },
    ...props.items.map((item, idx) => ({
      '@type': 'ListItem',
      position: idx + 2,
      name: item.label,
      item: item.url ? (item.url.startsWith('http') ? item.url : window.location.origin + item.url) : undefined,
    })),
  ]

  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement,
  }
})

useHead({
  script: [
    {
      type: 'application/ld+json',
      innerHTML: computed(() => JSON.stringify(breadcrumbSchema.value)),
    },
  ],
})
</script>

<template>
  <nav aria-label="Breadcrumb" class="py-2.5">
    <ol class="flex items-center flex-wrap gap-1.5 text-xs text-ink-muted">
      <li>
        <RouterLink
          to="/"
          class="hover:text-brand transition flex items-center gap-1 focus:outline-none"
          aria-label="Home"
        >
          <Home class="w-3.5 h-3.5" />
          <span class="sr-only">Home</span>
        </RouterLink>
      </li>

      <li
        v-for="(item, index) in items"
        :key="'crumb-' + index"
        class="flex items-center gap-1.5"
      >
        <ChevronRight class="w-3.5 h-3.5 text-ink-muted/50 flex-shrink-0" />
        <RouterLink
          v-if="item.url && index < items.length - 1"
          :to="item.url"
          class="hover:text-brand transition max-w-[180px] truncate"
        >
          {{ item.label }}
        </RouterLink>
        <span
          v-else
          class="text-ink font-semibold max-w-[200px] truncate"
          aria-current="page"
        >
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>
