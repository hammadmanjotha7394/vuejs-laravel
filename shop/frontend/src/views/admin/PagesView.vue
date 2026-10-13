<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import {
  FileText,
  Plus,
  Edit2,
  Trash2,
  Search,
  ExternalLink,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Globe,
  Eye,
  X,
} from 'lucide-vue-next'
import RichTextEditor from '@/components/admin/RichTextEditor.vue'

const pages = ref([])
const loading = ref(true)
const saving = ref(false)
const searchQuery = ref('')
const successMessage = ref('')
const errorMessage = ref('')

const isModalOpen = ref(false)
const editingPage = ref(null)

const pageForm = ref({
  title: '',
  slug: '',
  content_html: '',
  seo_title: '',
  seo_description: '',
  is_published: true,
})

const quickTemplates = [
  {
    label: 'About Us',
    title: 'About Our Studio',
    slug: 'about',
    seo_title: 'About Our Studio — Craftsmanship & Provenance',
    seo_description: 'Discover the independent workshops, natural materials, and timeless design philosophy behind our collection.',
    content_html:
      '<h2>Thoughtfully Crafted For Everyday Living</h2><p>Founded with a belief in fewer, better objects, our studio partners directly with family-run textile mills, ceramicists, and metalworkers across Europe and Japan.</p><h3>Our Material Philosophy</h3><ul><li>100% organic long-staple linen and GOTS-certified cotton</li><li>Small-batch production to eliminate overstock waste</li><li>Repairable hardware and timeless silhouettes</li></ul>',
  },
  {
    label: 'FAQ',
    title: 'Frequently Asked Questions',
    slug: 'faq',
    seo_title: 'Frequently Asked Questions — Orders, Sizing & Care',
    seo_description: 'Find quick answers about shipping times, international duties, garment care, and returns.',
    content_html:
      '<h2>Orders & Shipping</h2><h3>When will my order ship?</h3><p>In-stock items dispatch from our studio warehouse within 1–2 business days.</p><h3>Do you ship internationally?</h3><p>Yes, we offer tracked express delivery to over 35 countries with duties calculated at checkout.</p><h2>Returns & Exchanges</h2><h3>What is your return window?</h3><p>Unworn, unwashed items in original packaging may be returned within 30 days of delivery.</p>',
  },
  {
    label: 'Shipping',
    title: 'Shipping & Delivery',
    slug: 'shipping',
    seo_title: 'Shipping & Delivery Information',
    seo_description: 'Domestic and international delivery options, dispatch timelines, and carbon-neutral packaging.',
    content_html:
      '<h2>Delivery Options & Transit Times</h2><p>Every order is packed in plastic-free, biodegradable mailers or FSC-certified boxes.</p><ul><li><strong>Standard Domestic Shipping:</strong> 3–5 business days (Free on orders over $75)</li><li><strong>Express Studio Courier:</strong> 1–2 business days</li><li><strong>International Tracked:</strong> 5–8 business days</li></ul>',
  },
  {
    label: 'Returns',
    title: 'Returns & Exchanges',
    slug: 'returns',
    seo_title: '30-Day Calm Returns & Exchanges',
    seo_description: 'Simple, paperless returns and size exchanges within 30 days of receiving your order.',
    content_html:
      '<h2>30-Day Calm Return Policy</h2><p>We want you to love every piece you bring into your home. If an item is not quite right, you may request a return or size exchange within 30 days of delivery.</p><h3>How to Initiate a Return</h3><ol><li>Sign in to your account or visit our Order Tracking page.</li><li>Select the items you wish to return and download your prepaid return label.</li><li>Drop the parcel at any authorized courier point. Refunds are issued within 3–5 business days of inspection.</li></ol>',
  },
  {
    label: 'Privacy',
    title: 'Privacy Policy',
    slug: 'privacy',
    seo_title: 'Privacy Policy & Data Protection',
    seo_description: 'How we collect, safeguard, and respect your personal data and privacy rights.',
    content_html:
      '<h2>Your Privacy Matters</h2><p>We collect only the information necessary to fulfill your orders, provide customer support, and improve your shopping experience. We never sell or rent your personal data to third parties.</p><h3>Payment Security</h3><p>All transactions are encrypted via TLS and processed directly by Stripe. We never store raw credit card numbers on our servers.</p>',
  },
  {
    label: 'Terms',
    title: 'Terms of Service',
    slug: 'terms',
    seo_title: 'Terms of Service & Conditions of Sale',
    seo_description: 'Terms governing the use of our website, product availability, pricing, and intellectual property.',
    content_html:
      '<h2>Conditions of Use</h2><p>By placing an order through our online store, you agree to the terms outlined below regarding pricing, payment verification, shipping, and limited product warranties.</p>',
  },
  {
    label: 'Contact',
    title: 'Contact Our Studio',
    slug: 'contact',
    seo_title: 'Contact Us — Customer Care & Studio Inquiries',
    seo_description: 'Reach our customer concierge Monday through Sunday for sizing advice, order questions, or press inquiries.',
    content_html:
      '<h2>We Would Love To Hear From You</h2><p>Our studio concierge team is available seven days a week from 9:00 AM to 6:00 PM EST.</p><ul><li><strong>Customer Care:</strong> support@atelierstore.com</li><li><strong>Trade & Press:</strong> press@atelierstore.com</li><li><strong>Live Chat:</strong> Click the chat button in the bottom-right corner for instant assistance.</li></ul>',
  },
]

const filteredPages = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return pages.value
  return pages.value.filter(
    (p) =>
      p.title.toLowerCase().includes(q) ||
      p.slug.toLowerCase().includes(q) ||
      (p.seo_title || '').toLowerCase().includes(q)
  )
})

async function fetchPages() {
  loading.value = true
  try {
    const res = await axios.get('/api/admin/pages')
    pages.value = res.data.data || []
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load CMS pages.'
  } finally {
    loading.value = false
  }
}

function generateSlug(val) {
  return String(val || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

function onTitleInput() {
  if (!editingPage.value) {
    pageForm.value.slug = generateSlug(pageForm.value.title)
  }
}

function openCreateModal(template = null) {
  editingPage.value = null
  if (template) {
    pageForm.value = {
      title: template.title,
      slug: template.slug,
      content_html: template.content_html,
      seo_title: template.seo_title,
      seo_description: template.seo_description,
      is_published: true,
    }
  } else {
    pageForm.value = {
      title: '',
      slug: '',
      content_html: '',
      seo_title: '',
      seo_description: '',
      is_published: true,
    }
  }
  isModalOpen.value = true
}

function openEditModal(page) {
  editingPage.value = page
  pageForm.value = {
    title: page.title || '',
    slug: page.slug || '',
    content_html: page.content_html || '',
    seo_title: page.seo_title || '',
    seo_description: page.seo_description || '',
    is_published: Boolean(page.is_published),
  }
  isModalOpen.value = true
}

async function handleSavePage() {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = { ...pageForm.value }
    if (editingPage.value) {
      await axios.put(`/api/admin/pages/${editingPage.value.id}`, payload)
      successMessage.value = `Page "${payload.title}" updated and cache cleared.`
    } else {
      await axios.post('/api/admin/pages', payload)
      successMessage.value = `Page "${payload.title}" created.`
    }
    isModalOpen.value = false
    await fetchPages()
  } catch (err) {
    errorMessage.value =
      err.response?.data?.message || 'Failed to save CMS page. Check slug uniqueness.'
  } finally {
    saving.value = false
  }
}

async function handleDeletePage(page) {
  if (!window.confirm(`Delete page "${page.title}" (/page/${page.slug})?`)) return

  try {
    await axios.delete(`/api/admin/pages/${page.id}`)
    successMessage.value = `Page "${page.title}" deleted.`
    await fetchPages()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete page.'
  }
}

onMounted(() => {
  fetchPages()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">CMS Pages</h1>
        <p class="text-xs sm:text-sm text-ink-muted mt-1">
          Create and manage informational pages (About, FAQ, Shipping, Returns, Privacy, Terms, Contact) with rich text and SEO metadata.
        </p>
      </div>

      <button
        type="button"
        @click="openCreateModal()"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 transition shadow-sm self-start sm:self-auto"
      >
        <Plus class="w-4 h-4" />
        <span>Create Page</span>
      </button>
    </div>

    <!-- Quick Starter Templates Bar -->
    <div class="bg-white rounded-card border border-line p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
      <div class="text-xs text-ink-muted font-medium">
        Quick-start standard store pages:
      </div>
      <div class="flex flex-wrap items-center gap-1.5">
        <button
          v-for="tpl in quickTemplates"
          :key="tpl.slug"
          type="button"
          @click="openCreateModal(tpl)"
          class="px-3 py-1.5 rounded-pill border border-line bg-surface hover:bg-brand hover:text-white hover:border-brand text-xs font-medium text-ink transition"
        >
          + {{ tpl.label }}
        </button>
      </div>
    </div>

    <!-- Status Banners -->
    <div
      v-if="successMessage"
      class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm font-medium"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 text-emerald-600 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>
      <button type="button" class="text-xs underline" @click="successMessage = ''">Dismiss</button>
    </div>

    <div
      v-if="errorMessage"
      class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between text-xs sm:text-sm font-medium"
    >
      <div class="flex items-center gap-2">
        <AlertCircle class="w-4 h-4 text-rose-600 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <button type="button" class="text-xs underline" @click="errorMessage = ''">Dismiss</button>
    </div>

    <!-- Search Bar -->
    <div class="relative max-w-md">
      <Search class="w-4 h-4 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search pages by title or slug..."
        class="w-full pl-10 pr-4 py-2.5 rounded-pill border border-line bg-white text-xs sm:text-sm text-ink focus:outline-none focus:border-brand"
      />
    </div>

    <!-- Loading / Empty / Table -->
    <div v-if="loading" class="p-12 text-center bg-white rounded-card border border-line text-ink-muted">
      <Loader2 class="w-6 h-6 animate-spin mx-auto mb-2 text-brand" />
      <span class="text-xs">Loading pages...</span>
    </div>

    <div
      v-else-if="filteredPages.length === 0"
      class="p-12 text-center bg-white rounded-card border border-line space-y-3"
    >
      <FileText class="w-8 h-8 text-ink-muted mx-auto stroke-[1.5]" />
      <h3 class="text-sm font-bold text-ink">No CMS pages found</h3>
      <p class="text-xs text-ink-muted max-w-sm mx-auto">
        Click "Create Page" or pick one of the quick-start templates above to publish your policy and story pages.
      </p>
    </div>

    <div v-else class="bg-white rounded-card border border-line shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-line bg-surface/60 text-[11px] font-semibold uppercase tracking-wider text-ink-muted">
              <th class="py-3.5 px-4">Title</th>
              <th class="py-3.5 px-4">URL Slug</th>
              <th class="py-3.5 px-4">SEO Title</th>
              <th class="py-3.5 px-4">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line text-xs sm:text-sm">
            <tr
              v-for="page in filteredPages"
              :key="page.id"
              class="hover:bg-surface/40 transition"
            >
              <td class="py-3.5 px-4 font-semibold text-ink">
                {{ page.title }}
              </td>
              <td class="py-3.5 px-4 font-mono text-xs text-ink-muted">
                /page/{{ page.slug }}
              </td>
              <td class="py-3.5 px-4 text-xs text-ink-muted max-w-xs truncate">
                {{ page.seo_title || page.title }}
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-pill text-[11px] font-semibold"
                  :class="
                    page.is_published
                      ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                      : 'bg-gray-100 text-gray-600 border border-gray-200'
                  "
                >
                  {{ page.is_published ? 'Published' : 'Draft' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="inline-flex items-center gap-1.5">
                  <a
                    :href="`/page/${page.slug}`"
                    target="_blank"
                    rel="noopener"
                    class="p-1.5 rounded-lg text-ink-muted hover:text-brand hover:bg-surface transition"
                    title="View on Storefront"
                  >
                    <ExternalLink class="w-4 h-4" />
                  </a>
                  <button
                    type="button"
                    @click="openEditModal(page)"
                    class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-surface transition"
                    title="Edit Page"
                  >
                    <Edit2 class="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    @click="handleDeletePage(page)"
                    class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 transition"
                    title="Delete Page"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Page Modal -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-card max-w-3xl w-full p-6 shadow-2xl border border-line space-y-5 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-line pb-3">
          <h3 class="text-lg font-bold text-ink">
            {{ editingPage ? `Edit Page: ${editingPage.title}` : 'Create CMS Page' }}
          </h3>
          <button
            type="button"
            @click="isModalOpen = false"
            class="p-1.5 rounded-full text-ink-muted hover:text-ink hover:bg-surface"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="handleSavePage">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-ink uppercase mb-1">Page Title *</label>
              <input
                v-model="pageForm.title"
                required
                type="text"
                placeholder="e.g. Shipping & Returns"
                class="w-full px-3.5 py-2.5 rounded-xl border border-line text-sm text-ink focus:outline-none focus:border-brand"
                @input="onTitleInput"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink uppercase mb-1">URL Slug *</label>
              <div class="flex items-center rounded-xl border border-line bg-surface/50 overflow-hidden focus-within:border-brand">
                <span class="px-3 text-xs text-ink-muted font-mono border-r border-line py-2.5">/page/</span>
                <input
                  v-model="pageForm.slug"
                  required
                  type="text"
                  placeholder="shipping-returns"
                  class="w-full px-3 py-2.5 bg-white text-sm font-mono text-ink focus:outline-none"
                />
              </div>
            </div>
          </div>

          <!-- Rich Text Editor -->
          <div>
            <label class="block text-xs font-semibold text-ink uppercase mb-1">Page Body Content</label>
            <RichTextEditor
              v-model="pageForm.content_html"
              min-height="260px"
              placeholder="Write page content using headings, lists, and paragraphs..."
            />
          </div>

          <!-- SEO Section + Google Snippet Preview -->
          <div class="p-4 rounded-xl bg-surface/60 border border-line space-y-4">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink">
              <Globe class="w-4 h-4 text-brand" />
              <span>Search Engine Optimization (SEO)</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-ink mb-1">SEO Meta Title</label>
                <input
                  v-model="pageForm.seo_title"
                  type="text"
                  maxlength="255"
                  placeholder="Defaults to page title"
                  class="w-full px-3 py-2 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-ink mb-1">SEO Meta Description</label>
                <input
                  v-model="pageForm.seo_description"
                  type="text"
                  maxlength="500"
                  placeholder="150-160 character summary for search engines"
                  class="w-full px-3 py-2 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
                />
              </div>
            </div>

            <!-- Live Google Search Snippet Preview -->
            <div class="p-3.5 rounded-xl bg-white border border-line space-y-1">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-ink-muted block">
                Google Search Result Preview
              </span>
              <div class="text-sm font-semibold text-[#1a0dab] truncate">
                {{ pageForm.seo_title || pageForm.title || 'Page Title' }}
              </div>
              <div class="text-xs text-[#006621] font-mono truncate">
                https://yourdomain.com/page/{{ pageForm.slug || 'page-slug' }}
              </div>
              <div class="text-xs text-[#545454] line-clamp-2">
                {{
                  pageForm.seo_description ||
                  'Provide a clear meta description so searchers understand what this page covers.'
                }}
              </div>
            </div>
          </div>

          <!-- Published Toggle & Submit -->
          <div class="flex items-center justify-between pt-3 border-t border-line">
            <label class="inline-flex items-center gap-2.5 cursor-pointer">
              <input
                v-model="pageForm.is_published"
                type="checkbox"
                class="w-4 h-4 rounded text-brand focus:ring-brand"
              />
              <span class="text-xs font-semibold text-ink">Published & visible on storefront</span>
            </label>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="isModalOpen = false"
                class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted hover:text-ink"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 disabled:opacity-50 inline-flex items-center gap-2 shadow-sm"
              >
                <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                <span>{{ editingPage ? 'Save Changes' : 'Publish Page' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
