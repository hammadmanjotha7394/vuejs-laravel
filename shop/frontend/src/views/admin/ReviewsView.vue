<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  Star,
  Plus,
  Upload,
  CheckCircle2,
  XCircle,
  MessageSquareReply,
  Edit2,
  Trash2,
  Search,
  AlertCircle,
  Loader2,
  ShieldAlert,
  FileSpreadsheet,
  X,
  Download,
} from 'lucide-vue-next'

const reviews = ref([])
const products = ref([])
const stats = ref({ total: 0, pending: 0, approved: 0, admin_created: 0 })
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const loading = ref(true)
const saving = ref(false)
const uploadingPhoto = ref(false)
const importingCsv = ref(false)

const successMessage = ref('')
const errorMessage = ref('')

// Filters
const filters = ref({
  status: '',
  rating: '',
  product_id: '',
  source: '',
  search: '',
  page: 1,
})

// Create / Edit Review Modal
const isModalOpen = ref(false)
const editingReview = ref(null)

const reviewForm = ref({
  product_ids: [],
  author_name: '',
  author_avatar: '',
  rating: 5,
  title: '',
  body: '',
  photos: [],
  review_date: new Date().toISOString().slice(0, 10),
  is_approved: true,
  admin_reply: '',
})

// Store Reply Modal
const isReplyModalOpen = ref(false)
const replyingReview = ref(null)
const replyText = ref('')

// CSV Import Modal
const isCsvModalOpen = ref(false)

async function fetchProducts() {
  try {
    const res = await axios.get('/api/admin/products?per_page=100')
    products.value = res.data.data || []
  } catch (e) {
    // Non-fatal
  }
}

async function fetchReviews(page = 1) {
  loading.value = true
  filters.value.page = page
  try {
    const params = {}
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.rating) params.rating = filters.value.rating
    if (filters.value.product_id) params.product_id = filters.value.product_id
    if (filters.value.source) params.source = filters.value.source
    if (filters.value.search.trim()) params.search = filters.value.search.trim()
    params.page = page

    const res = await axios.get('/api/admin/reviews', { params })
    reviews.value = res.data.data || []
    if (res.data.meta) pagination.value = res.data.meta
    if (res.data.stats) stats.value = res.data.stats
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load reviews.'
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  editingReview.value = null
  reviewForm.value = {
    product_ids: products.value.length > 0 ? [products.value[0].id] : [],
    author_name: '',
    author_avatar: '',
    rating: 5,
    title: '',
    body: '',
    photos: [],
    review_date: new Date().toISOString().slice(0, 10),
    is_approved: true,
    admin_reply: '',
  }
  isModalOpen.value = true
}

function openEditModal(rev) {
  editingReview.value = rev
  const dateStr = rev.review_date
    ? new Date(rev.review_date).toISOString().slice(0, 10)
    : new Date().toISOString().slice(0, 10)

  reviewForm.value = {
    product_ids: [rev.product_id],
    author_name: rev.author_name || '',
    author_avatar: rev.author_avatar || '',
    rating: Number(rev.rating) || 5,
    title: rev.title || '',
    body: rev.body || '',
    photos: Array.isArray(rev.photos_json) ? [...rev.photos_json] : [],
    review_date: dateStr,
    is_approved: Boolean(rev.is_approved),
    admin_reply: rev.admin_reply || '',
  }
  isModalOpen.value = true
}

function toggleProductSelection(pid) {
  const idx = reviewForm.value.product_ids.indexOf(pid)
  if (idx >= 0) {
    reviewForm.value.product_ids.splice(idx, 1)
  } else {
    reviewForm.value.product_ids.push(pid)
  }
}

async function handlePhotoUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return

  uploadingPhoto.value = true
  try {
    const fd = new FormData()
    fd.append('image', file)
    const res = await axios.post('/api/admin/reviews/upload-photo', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data.url) {
      reviewForm.value.photos.push(res.data.url)
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to upload photo.'
  } finally {
    uploadingPhoto.value = false
    e.target.value = ''
  }
}

async function handleSaveReview() {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    if (editingReview.value) {
      const res = await axios.put(`/api/admin/reviews/${editingReview.value.id}`, {
        author_name: reviewForm.value.author_name,
        author_avatar: reviewForm.value.author_avatar || null,
        rating: reviewForm.value.rating,
        title: reviewForm.value.title,
        body: reviewForm.value.body,
        photos: reviewForm.value.photos,
        review_date: reviewForm.value.review_date,
        is_approved: reviewForm.value.is_approved,
        admin_reply: reviewForm.value.admin_reply || null,
      })
      successMessage.value = res.data.message || 'Review updated.'
    } else {
      const res = await axios.post('/api/admin/reviews', {
        product_ids: reviewForm.value.product_ids,
        author_name: reviewForm.value.author_name,
        author_avatar: reviewForm.value.author_avatar || null,
        rating: reviewForm.value.rating,
        title: reviewForm.value.title,
        body: reviewForm.value.body,
        photos: reviewForm.value.photos,
        review_date: reviewForm.value.review_date,
        is_approved: reviewForm.value.is_approved,
        admin_reply: reviewForm.value.admin_reply || null,
      })
      successMessage.value = res.data.message || 'Review created.'
    }
    isModalOpen.value = false
    await fetchReviews(pagination.value.current_page)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save review.'
  } finally {
    saving.value = false
  }
}

async function handleToggleApproval(rev) {
  try {
    const res = await axios.patch(`/api/admin/reviews/${rev.id}/status`, {
      is_approved: !rev.is_approved,
    })
    rev.is_approved = res.data.data.is_approved
    successMessage.value = res.data.message
    await fetchReviews(pagination.value.current_page)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update status.'
  }
}

function openReplyModal(rev) {
  replyingReview.value = rev
  replyText.value = rev.admin_reply || ''
  isReplyModalOpen.value = true
}

async function handleSaveReply() {
  if (!replyingReview.value) return
  saving.value = true
  try {
    const res = await axios.post(`/api/admin/reviews/${replyingReview.value.id}/reply`, {
      admin_reply: replyText.value.trim() || null,
    })
    successMessage.value = res.data.message
    isReplyModalOpen.value = false
    await fetchReviews(pagination.value.current_page)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save reply.'
  } finally {
    saving.value = false
  }
}

async function handleDeleteReview(rev) {
  if (!window.confirm(`Delete review by "${rev.author_name}"?`)) return
  try {
    await axios.delete(`/api/admin/reviews/${rev.id}`)
    successMessage.value = 'Review deleted.'
    await fetchReviews(pagination.value.current_page)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete review.'
  }
}

async function handleCsvUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return

  importingCsv.value = true
  errorMessage.value = ''
  try {
    const fd = new FormData()
    fd.append('file', file)
    const res = await axios.post('/api/admin/reviews/import-csv', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    successMessage.value = res.data.message || 'CSV imported successfully.'
    isCsvModalOpen.value = false
    await fetchReviews(1)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to import CSV.'
  } finally {
    importingCsv.value = false
    e.target.value = ''
  }
}

function downloadSampleCsv() {
  const sampleSlug = products.value[0]?.slug || 'organic-linen-overshirt'
  const csvContent =
    'product_slug,author_name,author_avatar,rating,title,body,review_date,is_approved\n' +
    `${sampleSlug},Clara Lindqvist,CL,5,Beautiful weight and drape,The linen softens wonderfully after the first wash and fits true to size.,2026-09-15,true\n` +
    `${sampleSlug},Marcus Vance,MV,5,Timeless studio staple,Exceptional stitching and mother-of-pearl buttons. Highly recommended.,2026-09-28,true\n`

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'reviews-import-sample.csv'
  a.click()
  URL.revokeObjectURL(url)
}

function getInitials(name, avatar) {
  if (avatar && avatar.length <= 3 && !avatar.startsWith('http') && !avatar.startsWith('/')) {
    return avatar.toUpperCase()
  }
  return String(name || 'C')
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

onMounted(() => {
  fetchProducts()
  fetchReviews(1)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Product Reviews Manager</h1>
        <p class="text-xs sm:text-sm text-ink-muted mt-1">
          Moderate customer reviews, publish public store replies, create custom storefront reviews, or bulk-import via CSV.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <button
          type="button"
          @click="isCsvModalOpen = true"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-pill border border-line bg-white hover:bg-surface text-xs font-semibold text-ink transition shadow-sm"
        >
          <FileSpreadsheet class="w-4 h-4 text-brand" />
          <span>Import CSV</span>
        </button>

        <button
          type="button"
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 transition shadow-sm"
        >
          <Plus class="w-4 h-4" />
          <span>Create Review</span>
        </button>
      </div>
    </div>

    <!-- Summary KPI Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-card border border-line p-4 shadow-sm">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted">Total Reviews</span>
        <p class="text-2xl font-bold text-ink mt-1">{{ stats.total }}</p>
      </div>
      <div class="bg-white rounded-card border border-line p-4 shadow-sm">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Pending Approval</span>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ stats.pending }}</p>
      </div>
      <div class="bg-white rounded-card border border-line p-4 shadow-sm">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Published</span>
        <p class="text-2xl font-bold text-emerald-700 mt-1">{{ stats.approved }}</p>
      </div>
      <div class="bg-white rounded-card border border-line p-4 shadow-sm">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted">Admin Created (Internal)</span>
        <p class="text-2xl font-bold text-ink mt-1">{{ stats.admin_created }}</p>
      </div>
    </div>

    <!-- Alerts -->
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

    <!-- Filters Bar -->
    <div class="bg-white rounded-card border border-line p-4 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="relative">
        <Search class="w-4 h-4 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search author, text, product..."
          class="w-full pl-9 pr-3 py-2 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
          @keyup.enter="fetchReviews(1)"
        />
      </div>

      <select
        v-model="filters.status"
        class="px-3 py-2 rounded-xl border border-line text-xs text-ink bg-white focus:outline-none focus:border-brand"
        @change="fetchReviews(1)"
      >
        <option value="">All Statuses</option>
        <option value="pending">Pending Moderation</option>
        <option value="approved">Approved / Published</option>
      </select>

      <select
        v-model="filters.rating"
        class="px-3 py-2 rounded-xl border border-line text-xs text-ink bg-white focus:outline-none focus:border-brand"
        @change="fetchReviews(1)"
      >
        <option value="">All Star Ratings</option>
        <option :value="5">5 Stars Only</option>
        <option :value="4">4 Stars Only</option>
        <option :value="3">3 Stars Only</option>
        <option :value="2">2 Stars Only</option>
        <option :value="1">1 Star Only</option>
      </select>

      <select
        v-model="filters.product_id"
        class="px-3 py-2 rounded-xl border border-line text-xs text-ink bg-white focus:outline-none focus:border-brand"
        @change="fetchReviews(1)"
      >
        <option value="">All Products</option>
        <option v-for="prod in products" :key="prod.id" :value="prod.id">
          {{ prod.name }}
        </option>
      </select>

      <select
        v-model="filters.source"
        class="px-3 py-2 rounded-xl border border-line text-xs text-ink bg-white focus:outline-none focus:border-brand"
        @change="fetchReviews(1)"
      >
        <option value="">All Sources</option>
        <option value="customer">Customer Submitted</option>
        <option value="admin">Admin Created (Hidden Flag)</option>
      </select>
    </div>

    <!-- Reviews Table / Cards -->
    <div v-if="loading" class="p-12 text-center bg-white rounded-card border border-line text-ink-muted">
      <Loader2 class="w-6 h-6 animate-spin mx-auto mb-2 text-brand" />
      <span class="text-xs">Loading reviews...</span>
    </div>

    <div
      v-else-if="reviews.length === 0"
      class="p-12 text-center bg-white rounded-card border border-line space-y-3"
    >
      <Star class="w-8 h-8 text-ink-muted mx-auto stroke-[1.5]" />
      <h3 class="text-sm font-bold text-ink">No reviews match your filters</h3>
      <p class="text-xs text-ink-muted max-w-sm mx-auto">
        Use "Create Review" or "Import CSV" to add product reviews, or clear your search filters.
      </p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="rev in reviews"
        :key="rev.id"
        class="bg-white rounded-card border border-line p-5 shadow-sm space-y-3"
      >
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-3">
          <div class="flex items-center gap-3">
            <!-- Avatar or Initials -->
            <img
              v-if="rev.author_avatar && (rev.author_avatar.startsWith('http') || rev.author_avatar.startsWith('/'))"
              :src="rev.author_avatar"
              :alt="rev.author_name"
              class="w-10 h-10 rounded-full object-cover border border-line"
            />
            <div
              v-else
              class="w-10 h-10 rounded-full bg-brand/10 text-brand font-bold text-xs flex items-center justify-center border border-brand/20"
            >
              {{ getInitials(rev.author_name, rev.author_avatar) }}
            </div>

            <div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-bold text-ink">{{ rev.author_name }}</span>

                <!-- Admin-Only Hidden Flag Badge -->
                <span
                  v-if="rev.is_admin_created"
                  class="px-2 py-0.5 rounded-pill text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200"
                  title="Visible only in Admin Panel. Hidden from public API & storefront."
                >
                  Admin Created (Hidden on Storefront)
                </span>

                <span
                  class="px-2 py-0.5 rounded-pill text-[10px] font-semibold border"
                  :class="
                    rev.is_approved
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                      : 'bg-amber-50 text-amber-700 border-amber-200'
                  "
                >
                  {{ rev.is_approved ? 'Approved' : 'Pending' }}
                </span>
              </div>

              <div class="text-xs text-ink-muted mt-0.5 flex flex-wrap items-center gap-2">
                <span v-if="rev.product" class="font-medium text-ink">
                  Product: {{ rev.product.name }}
                </span>
                <span>·</span>
                <span>Review Date: {{ formatDate(rev.review_date) }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap items-center gap-1.5">
            <button
              type="button"
              @click="handleToggleApproval(rev)"
              class="px-3 py-1.5 rounded-pill text-xs font-semibold border transition inline-flex items-center gap-1"
              :class="
                rev.is_approved
                  ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100'
                  : 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'
              "
            >
              <CheckCircle2 v-if="!rev.is_approved" class="w-3.5 h-3.5" />
              <XCircle v-else class="w-3.5 h-3.5" />
              <span>{{ rev.is_approved ? 'Unapprove' : 'Approve' }}</span>
            </button>

            <button
              type="button"
              @click="openReplyModal(rev)"
              class="px-3 py-1.5 rounded-pill text-xs font-semibold border border-line bg-surface hover:bg-white text-ink transition inline-flex items-center gap-1"
            >
              <MessageSquareReply class="w-3.5 h-3.5 text-brand" />
              <span>{{ rev.admin_reply ? 'Edit Reply' : 'Reply as Store' }}</span>
            </button>

            <button
              type="button"
              @click="openEditModal(rev)"
              class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-surface"
              title="Edit Review"
            >
              <Edit2 class="w-4 h-4" />
            </button>

            <button
              type="button"
              @click="handleDeleteReview(rev)"
              class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50"
              title="Delete Review"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Rating + Headline + Body -->
        <div class="space-y-1.5">
          <div class="flex items-center gap-1 text-amber-400">
            <Star
              v-for="s in 5"
              :key="s"
              class="w-3.5 h-3.5"
              :class="s <= rev.rating ? 'fill-current' : 'text-gray-200'"
            />
            <span v-if="rev.title" class="ml-2 text-xs font-bold text-ink">{{ rev.title }}</span>
          </div>

          <p class="text-xs sm:text-sm text-ink-muted leading-relaxed">
            {{ rev.body }}
          </p>

          <!-- Review Photos -->
          <div
            v-if="Array.isArray(rev.photos_json) && rev.photos_json.length > 0"
            class="flex flex-wrap gap-2 pt-2"
          >
            <img
              v-for="(photo, pIdx) in rev.photos_json"
              :key="pIdx"
              :src="photo"
              class="w-16 h-16 rounded-xl object-cover border border-line"
            />
          </div>

          <!-- Public Store Reply -->
          <div
            v-if="rev.admin_reply"
            class="mt-3 p-3.5 rounded-xl bg-surface border-l-4 border-brand text-xs space-y-1"
          >
            <span class="font-bold text-ink block">Public Reply from Store:</span>
            <p class="text-ink-muted">{{ rev.admin_reply }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- CREATE / EDIT REVIEW MODAL -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-card max-w-2xl w-full p-6 shadow-2xl border border-line space-y-4 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-line pb-3">
          <h3 class="text-lg font-bold text-ink">
            {{ editingReview ? 'Edit Review' : 'Create Custom Storefront Review' }}
          </h3>
          <button
            type="button"
            @click="isModalOpen = false"
            class="p-1.5 rounded-full text-ink-muted hover:text-ink"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Mandatory Consumer-Protection & Advertising Law Reminder Note -->
        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-3 text-xs text-amber-900">
          <ShieldAlert class="w-4 h-4 text-amber-700 flex-shrink-0 mt-0.5" />
          <div>
            <strong class="font-bold">Legal & Consumer-Protection Reminder:</strong>
            Please ensure that any reviews created or imported here comply with your local consumer-protection and advertising laws (such as FTC Endorsement Guides or EU Omnibus Directive rules) regarding authentic customer testimonials, disclosures, and migrated reviews.
          </div>
        </div>

        <form class="space-y-4" @submit.prevent="handleSaveReview">
          <!-- Multi-Product Selector (when creating) -->
          <div v-if="!editingReview">
            <label class="block text-xs font-semibold text-ink uppercase mb-1.5">
              Select Product(s) * ({{ reviewForm.product_ids.length }} selected)
            </label>
            <div class="max-h-36 overflow-y-auto p-3 rounded-xl border border-line bg-surface/40 grid grid-cols-1 sm:grid-cols-2 gap-2">
              <label
                v-for="prod in products"
                :key="prod.id"
                class="flex items-center gap-2 text-xs text-ink cursor-pointer p-1.5 rounded-lg hover:bg-white"
              >
                <input
                  type="checkbox"
                  :checked="reviewForm.product_ids.includes(prod.id)"
                  class="rounded text-brand focus:ring-brand"
                  @change="toggleProductSelection(prod.id)"
                />
                <span class="truncate">{{ prod.name }}</span>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-semibold text-ink uppercase mb-1">Reviewer Name *</label>
              <input
                v-model="reviewForm.author_name"
                required
                type="text"
                placeholder="e.g. Sophie Laurent"
                class="w-full px-3.5 py-2 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink uppercase mb-1">Avatar URL or Initials</label>
              <input
                v-model="reviewForm.author_avatar"
                type="text"
                placeholder="SL or https://..."
                class="w-full px-3.5 py-2 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink uppercase mb-1">Review Date *</label>
              <input
                v-model="reviewForm.review_date"
                required
                type="date"
                class="w-full px-3.5 py-2 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-ink uppercase mb-1">Star Rating *</label>
              <div class="flex items-center gap-1.5 pt-1">
                <button
                  v-for="s in 5"
                  :key="s"
                  type="button"
                  @click="reviewForm.rating = s"
                  class="p-1 focus:outline-none"
                >
                  <Star
                    class="w-5 h-5"
                    :class="s <= reviewForm.rating ? 'text-amber-400 fill-amber-400' : 'text-gray-200'"
                  />
                </button>
                <span class="text-xs font-bold text-ink ml-1">{{ reviewForm.rating }} / 5</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink uppercase mb-1">Review Headline</label>
              <input
                v-model="reviewForm.title"
                type="text"
                placeholder="e.g. Effortless drape and heirloom quality"
                class="w-full px-3.5 py-2 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-ink uppercase mb-1">Review Text *</label>
            <textarea
              v-model="reviewForm.body"
              required
              rows="4"
              placeholder="Write the customer review body..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <!-- Optional Review Photos -->
          <div>
            <label class="block text-xs font-semibold text-ink uppercase mb-1.5">Review Photos (Optional)</label>
            <div class="flex flex-wrap items-center gap-3">
              <div
                v-for="(pUrl, idx) in reviewForm.photos"
                :key="idx"
                class="relative w-16 h-16 rounded-xl overflow-hidden border border-line"
              >
                <img :src="pUrl" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click="reviewForm.photos.splice(idx, 1)"
                  class="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full p-0.5"
                >
                  <X class="w-3 h-3" />
                </button>
              </div>

              <label class="cursor-pointer px-3.5 py-2 rounded-xl border border-line bg-surface hover:bg-white text-xs font-medium text-ink inline-flex items-center gap-1.5">
                <Upload class="w-3.5 h-3.5 text-brand" />
                <span>{{ uploadingPhoto ? 'Uploading...' : 'Add Photo' }}</span>
                <input type="file" class="hidden" accept="image/*" @change="handlePhotoUpload" />
              </label>
            </div>
          </div>

          <!-- Optional Public Store Reply -->
          <div>
            <label class="block text-xs font-semibold text-ink uppercase mb-1">Public Store Reply (Optional)</label>
            <textarea
              v-model="reviewForm.admin_reply"
              rows="2"
              placeholder="Optional public response from the studio..."
              class="w-full px-3.5 py-2 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <div class="flex items-center justify-between pt-3 border-t border-line">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input
                v-model="reviewForm.is_approved"
                type="checkbox"
                class="rounded text-brand focus:ring-brand"
              />
              <span class="text-xs font-semibold text-ink">Approved & visible on storefront immediately</span>
            </label>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="isModalOpen = false"
                class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 disabled:opacity-50 inline-flex items-center gap-1.5 shadow-sm"
              >
                <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                <span>{{ editingReview ? 'Save Changes' : 'Publish Review' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- STORE REPLY MODAL -->
    <div
      v-if="isReplyModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-card max-w-md w-full p-6 shadow-2xl border border-line space-y-4">
        <div class="flex items-center justify-between border-b border-line pb-3">
          <h3 class="text-base font-bold text-ink">Public Reply as Store</h3>
          <button type="button" @click="isReplyModalOpen = false" class="text-ink-muted hover:text-ink">
            <X class="w-4 h-4" />
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="handleSaveReply">
          <textarea
            v-model="replyText"
            rows="4"
            placeholder="Write a warm public reply from the store (leave blank to remove reply)..."
            class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
          />
          <div class="flex justify-end gap-2">
            <button
              type="button"
              @click="isReplyModalOpen = false"
              class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95"
            >
              Save Reply
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- BULK CSV IMPORT MODAL -->
    <div
      v-if="isCsvModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-card max-w-md w-full p-6 shadow-2xl border border-line space-y-4">
        <div class="flex items-center justify-between border-b border-line pb-3">
          <h3 class="text-base font-bold text-ink">Bulk Import Reviews from CSV</h3>
          <button type="button" @click="isCsvModalOpen = false" class="text-ink-muted hover:text-ink">
            <X class="w-4 h-4" />
          </button>
        </div>

        <p class="text-xs text-ink-muted leading-relaxed">
          Upload a CSV file with columns: <code class="font-mono text-ink">product_slug, author_name, author_avatar, rating, title, body, review_date, is_approved</code>.
        </p>

        <button
          type="button"
          @click="downloadSampleCsv"
          class="w-full py-2 px-3 rounded-xl border border-line bg-surface hover:bg-white text-xs font-semibold text-ink inline-flex items-center justify-center gap-1.5"
        >
          <Download class="w-3.5 h-3.5 text-brand" />
          <span>Download Sample CSV Template</span>
        </button>

        <label class="w-full py-6 px-4 rounded-xl border-2 border-dashed border-line hover:border-brand bg-surface/40 cursor-pointer flex flex-col items-center justify-center gap-2 text-xs text-ink-muted">
          <Upload class="w-5 h-5 text-brand" />
          <span class="font-semibold text-ink">{{ importingCsv ? 'Importing CSV...' : 'Choose CSV File to Upload' }}</span>
          <input type="file" class="hidden" accept=".csv,text/csv" @change="handleCsvUpload" />
        </label>
      </div>
    </div>
  </div>
</template>
