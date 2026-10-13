<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import axios from 'axios'
import { ChevronRight, Loader2, FileText, ArrowLeft } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const route = useRoute()
const settingsStore = useSettingsStore()

const page = ref(null)
const loading = ref(true)
const notFound = ref(false)

const pageTitle = computed(() => {
  if (!page.value) return settingsStore.siteName || 'Atelier Store'
  return page.value.seo_title || `${page.value.title} — ${settingsStore.siteName || 'Atelier Store'}`
})

const pageDescription = computed(() => {
  if (!page.value) return ''
  return page.value.seo_description || page.value.title || ''
})

useHead({
  title: pageTitle,
  meta: [
    { name: 'description', content: pageDescription },
    { property: 'og:title', content: pageTitle },
    { property: 'og:description', content: pageDescription },
  ],
})

async function fetchPage(slug) {
  if (!slug) return
  loading.value = true
  notFound.value = false
  try {
    const res = await axios.get(`/api/pages/${encodeURIComponent(slug)}`)
    page.value = res.data.data
  } catch (err) {
    page.value = null
    notFound.value = true
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.slug,
  (newSlug) => {
    if (newSlug) fetchPage(newSlug)
  }
)

onMounted(() => {
  fetchPage(route.params.slug)
})
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-ink-muted mb-8" aria-label="Breadcrumb">
      <RouterLink to="/" class="hover:text-ink transition">Home</RouterLink>
      <ChevronRight class="w-3.5 h-3.5" />
      <span class="text-ink font-medium truncate">{{ page?.title || route.params.slug }}</span>
    </nav>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="bg-white rounded-card border border-line p-8 sm:p-12 text-center space-y-3">
      <Loader2 class="w-6 h-6 animate-spin mx-auto text-brand" />
      <p class="text-xs text-ink-muted">Loading page content...</p>
    </div>

    <!-- 404 Not Found State -->
    <div
      v-else-if="notFound || !page"
      class="bg-white rounded-card border border-line p-10 sm:p-16 text-center space-y-4 shadow-sm"
    >
      <div class="w-12 h-12 rounded-full bg-surface border border-line flex items-center justify-center mx-auto text-ink-muted">
        <FileText class="w-6 h-6 stroke-[1.5]" />
      </div>
      <h1 class="text-xl sm:text-2xl font-bold text-ink">Page Not Found</h1>
      <p class="text-xs sm:text-sm text-ink-muted max-w-md mx-auto">
        The informational page you are looking for is not published yet or has moved to a new address.
      </p>
      <div class="pt-2">
        <RouterLink
          to="/"
          class="inline-flex items-center gap-2 px-6 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 transition shadow-sm"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Return to Storefront</span>
        </RouterLink>
      </div>
    </div>

    <!-- Published CMS Page Content -->
    <article
      v-else
      class="bg-white rounded-card border border-line p-6 sm:p-12 shadow-sm space-y-8"
    >
      <header class="border-b border-line pb-6">
        <h1 class="text-2xl sm:text-4xl font-bold tracking-tight text-ink">
          {{ page.title }}
        </h1>
        <p v-if="page.seo_description" class="text-xs sm:text-sm text-ink-muted mt-2.5 leading-relaxed">
          {{ page.seo_description }}
        </p>
      </header>

      <!-- Sanitized HTML Content -->
      <div
        class="prose prose-sm sm:prose-base max-w-none text-ink leading-relaxed prose-headings:font-bold prose-headings:text-ink prose-a:text-brand"
        v-html="page.content_html"
      />
    </article>
  </div>
</template>
