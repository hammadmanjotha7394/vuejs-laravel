<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import RichTextEditor from '@/components/admin/RichTextEditor.vue'
import ToggleSwitch from '@/components/admin/ToggleSwitch.vue'
import FormField from '@/components/admin/FormField.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'
import {
  ArrowLeft,
  Save,
  Plus,
  Trash2,
  Upload,
  Image as ImageIcon,
  Loader2,
  Sliders,
  Layers,
  Video,
  FileText,
  Tag,
  Globe,
  Sparkles,
  Truck,
  Link2,
  ExternalLink,
  ArrowUp,
  ArrowDown,
  Search,
  Star,
  AlertTriangle,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const settingsStore = useSettingsStore()
const toast = useToastStore()

const isEdit = computed(() => Boolean(route.params.id))
const productId = computed(() => route.params.id)

useHead({
  title: () =>
    `${isEdit.value ? 'Edit Product' : 'New Product'} | Admin | ${settingsStore.siteName}`,
})

const activeTab = ref('general')
const saving = ref(false)
const loading = ref(false)
const isDirty = ref(false)
const ignoreDirtyWatch = ref(false)

const uploadingImage = ref(false)
const imageUploadProgress = ref(0)
const uploadingVideo = ref(false)
const videoUploadProgress = ref(0)
const externalImageUrlInput = ref('')

const fieldErrors = ref({})
const categories = ref([])
const brands = ref([])
const catalogProducts = ref([])
const relatedSearch = ref('')

// Variant delete warning dialog state
const variantDeleteConfirmOpen = ref(false)
const variantToDeleteIndex = ref(null)
const variantToDeleteInfo = ref(null)

// Unsaved route leave dialog
const leaveConfirmOpen = ref(false)
let pendingNextRoute = null

const tabs = [
  { id: 'general', label: '1. General', icon: FileText },
  { id: 'description', label: '2. Description', icon: FileText },
  { id: 'pricing', label: '3. Pricing & Stock', icon: Sliders },
  { id: 'variations', label: '4. Variations', icon: Layers },
  { id: 'media', label: '5. Media & Video', icon: ImageIcon },
  { id: 'sections', label: '6. Item Details', icon: FileText },
  { id: 'specs', label: '7. Specifications', icon: Tag },
  { id: 'shipping', label: '8. Shipping', icon: Truck },
  { id: 'seo', label: '9. SEO Preview', icon: Globe },
  { id: 'related', label: '10. Related Items', icon: Link2 },
]

const form = ref({
  name: '',
  slug: '',
  sku: '',
  brand_id: '',
  category_id: '',
  category_ids: [],
  tags: [],
  status: 'active',
  is_featured: false,
  base_price: 0.0,
  compare_at_price: null,
  cost_price: null,
  track_stock: true,
  stock_qty: 15,
  low_stock_threshold: 5,
  allow_backorder: false,
  short_description: '',
  description: '',
  weight: null,
  length: null,
  width: null,
  height: null,
  tax_class: 'standard',
  video_url: '',
  video_path: '',
  video_poster: '',
  seo_title: '',
  seo_description: '',
  seo_social_image: '',
  related_products_json: [],
  frequently_bought_together_json: [],
  options: [],
  variants: [],
  images: [],
  sections: [
    { title: 'Product Details', content: '', is_open_by_default: true },
    { title: 'Materials & Care', content: '', is_open_by_default: false },
    { title: 'Shipping & Returns', content: '', is_open_by_default: false },
  ],
  specs: [],
})

const tagsInput = ref('')
const bulkActionType = ref('set_price')
const bulkActionValue = ref('')

// Track unsaved changes
watch(
  form,
  () => {
    if (!ignoreDirtyWatch.value) {
      isDirty.value = true
    }
  },
  { deep: true }
)

// Auto-generate slug for new product when typing name if slug is empty
const handleNameBlur = () => {
  if (!form.value.slug && form.value.name) {
    form.value.slug = form.value.name
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '')
  }
}

const primaryPreviewImage = computed(() => {
  const primary = form.value.images.find((i) => i.is_primary) || form.value.images[0]
  return primary ? primary.thumb_url || primary.url : null
})

// Google SEO Live Preview computed values
const seoPreviewTitle = computed(() => {
  const base = form.value.seo_title || form.value.name || 'Untitled Product'
  return `${base} | ${settingsStore.siteName || 'Boutique'}`
})

const seoPreviewUrl = computed(() => {
  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://store.example.com'
  const slug =
    form.value.slug ||
    (form.value.name
      ? form.value.name.toLowerCase().replace(/[^a-z0-9]+/g, '-')
      : 'product-slug')
  return `${origin}/products/${slug}`
})

const seoPreviewDescription = computed(() => {
  return (
    form.value.seo_description ||
    form.value.short_description ||
    'Discover handcrafted quality, sustainable materials, and timeless everyday design with fast shipping and easy returns.'
  )
})

const filteredCatalogForRelated = computed(() => {
  const q = relatedSearch.value.trim().toLowerCase()
  const currentId = Number(productId.value || 0)
  return catalogProducts.value
    .filter((p) => p.id !== currentId)
    .filter(
      (p) =>
        !q ||
        p.name.toLowerCase().includes(q) ||
        (p.sku && p.sku.toLowerCase().includes(q))
    )
    .slice(0, 12)
})

const fetchMetadata = async () => {
  try {
    const [catsRes, brandsRes, prodsRes] = await Promise.all([
      apiClient.get('/admin/categories').catch(() => ({ data: { data: [] } })),
      apiClient.get('/admin/brands').catch(() => ({ data: { data: [] } })),
      apiClient.get('/admin/products', { params: { per_page: 50 } }).catch(() => ({ data: { data: [] } })),
    ])
    categories.value = catsRes.data?.data || []
    brands.value = brandsRes.data?.data || []
    catalogProducts.value = prodsRes.data?.data || []
  } catch (err) {
    // Handled gracefully
  }
}

const fetchProduct = async () => {
  if (!isEdit.value) return
  loading.value = true
  ignoreDirtyWatch.value = true
  try {
    const res = await apiClient.get(`/admin/products/${productId.value}`)
    const p = res.data?.data

    form.value = {
      name: p.name || '',
      slug: p.slug || '',
      sku: p.sku || '',
      brand_id: p.brand_id || '',
      category_id: p.category_id || '',
      category_ids: p.categories ? p.categories.map((c) => c.id) : [],
      tags: p.tags ? p.tags.map((t) => t.name) : [],
      status: p.status || 'active',
      is_featured: Boolean(p.is_featured),
      base_price: Number(p.base_price ?? 0),
      compare_at_price: p.compare_at_price ? Number(p.compare_at_price) : null,
      cost_price: p.cost_price ? Number(p.cost_price) : null,
      track_stock: p.track_stock !== undefined ? Boolean(p.track_stock) : true,
      stock_qty: Number(p.stock_qty ?? 0),
      low_stock_threshold: Number(p.low_stock_threshold ?? 5),
      allow_backorder: Boolean(p.allow_backorder),
      short_description: p.short_description || '',
      description: p.description || '',
      weight: p.weight,
      length: p.length,
      width: p.width,
      height: p.height,
      tax_class: p.tax_class || 'standard',
      video_url: p.video_url || '',
      video_path: p.video_path || '',
      video_poster: p.video_poster || '',
      seo_title: p.seo_title || '',
      seo_description: p.seo_description || '',
      seo_social_image: p.seo_social_image || '',
      related_products_json: Array.isArray(p.related_products_json)
        ? p.related_products_json.map(Number)
        : [],
      frequently_bought_together_json: Array.isArray(p.frequently_bought_together_json)
        ? p.frequently_bought_together_json.map(Number)
        : [],
      options: p.option_types
        ? p.option_types.map((ot) => ({
            name: ot.name,
            values: ot.values
              ? ot.values.map((v) => ({
                  name: v.value || v.name,
                  hex_color: v.color_hex || v.hex_color || null,
                }))
              : [],
          }))
        : [],
      variants: p.variants
        ? p.variants.map((v) => ({
            id: v.id,
            sku: v.sku,
            price: Number(v.price),
            compare_at_price: v.compare_at_price ? Number(v.compare_at_price) : null,
            stock_qty: Number(v.stock_qty ?? 0),
            image_id: v.image_id || null,
            is_active: Boolean(v.is_active),
            orders_count: Number(v.orders_count ?? 0),
            option_values_json: v.option_values_json || {},
          }))
        : [],
      images: p.images
        ? p.images.map((img) => ({
            id: img.id,
            url: img.url,
            thumb_url: img.thumb_url,
            path: img.path,
            thumb_path: img.thumb_path,
            medium_path: img.medium_path,
            large_path: img.large_path,
            alt: img.alt || p.name,
            is_primary: Boolean(img.is_primary),
            variant_id: img.variant_id || null,
          }))
        : [],
      sections: p.details_sections?.length
        ? p.details_sections.map((s) => ({
            title: s.title,
            content: s.content,
            is_open_by_default: Boolean(s.is_open_by_default),
          }))
        : form.value.sections,
      specs: p.specs?.length
        ? p.specs.map((sp) => ({ label: sp.label, value: sp.value }))
        : [],
    }
    tagsInput.value = form.value.tags.join(', ')
    setTimeout(() => {
      isDirty.value = false
      ignoreDirtyWatch.value = false
    }, 50)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load product details.')
    ignoreDirtyWatch.value = false
  } finally {
    loading.value = false
  }
}

// 4. Variations & Option Types
const addOptionType = () => {
  form.value.options.push({
    name: '',
    values: [{ name: '', hex_color: null }],
  })
}

const removeOptionType = (index) => {
  form.value.options.splice(index, 1)
}

const addOptionValue = (option) => {
  option.values.push({
    name: '',
    hex_color: option.name?.toLowerCase().includes('color') ? '#2F5D50' : null,
  })
}

const removeOptionValue = (option, valIndex) => {
  option.values.splice(valIndex, 1)
}

const generateVariantCombinations = () => {
  const validOptions = form.value.options.filter(
    (o) => o.name && o.values.some((v) => v.name && v.name.trim())
  )
  if (validOptions.length === 0) {
    toast.error('Please add at least one option type and value first.')
    return
  }

  const cartesian = (arrays) =>
    arrays.reduce(
      (acc, curr) => acc.flatMap((d) => curr.map((e) => [...d, e])),
      [[]]
    )

  const valuesArrays = validOptions.map((opt) =>
    opt.values
      .filter((v) => v.name && v.name.trim())
      .map((v) => ({ type: opt.name.trim(), value: v.name.trim() }))
  )

  const combinations = cartesian(valuesArrays)
  const baseSku =
    form.value.sku ||
    (form.value.name
      ? form.value.name.replace(/[^a-zA-Z0-9]+/g, '-').toUpperCase()
      : 'PROD')

  form.value.variants = combinations.map((combo) => {
    const comboMap = {}
    let skuSuffix = ''
    combo.forEach((c) => {
      comboMap[c.type] = c.value
      skuSuffix += `-${c.value.replace(/[^a-zA-Z0-9]/g, '').substring(0, 3).toUpperCase()}`
    })

    const candidateSku = `${baseSku}${skuSuffix}`
    const existing = form.value.variants.find((v) => v.sku === candidateSku)

    return {
      id: existing?.id || null,
      sku: candidateSku,
      price: existing ? existing.price : Number(form.value.base_price || 0),
      compare_at_price: existing ? existing.compare_at_price : form.value.compare_at_price,
      stock_qty: existing ? existing.stock_qty : Number(form.value.stock_qty || 10),
      image_id: existing?.image_id || null,
      is_active: existing ? existing.is_active : true,
      orders_count: existing?.orders_count || 0,
      option_values_json: comboMap,
    }
  })

  toast.info(`Generated ${form.value.variants.length} variant combinations.`)
}

const applyBulkVariantEdit = () => {
  const val = Number(bulkActionValue.value)
  if (isNaN(val) || bulkActionValue.value === '') return

  form.value.variants.forEach((v) => {
    if (bulkActionType.value === 'set_price') {
      v.price = Number(val.toFixed(2))
    } else if (bulkActionType.value === 'increase_price_percent') {
      v.price = Number((v.price * (1 + val / 100)).toFixed(2))
    } else if (bulkActionType.value === 'set_stock') {
      v.stock_qty = Math.max(0, Math.round(val))
    }
  })
  bulkActionValue.value = ''
  toast.success('Updated all variant rows.')
}

const requestRemoveVariant = (idx) => {
  const v = form.value.variants[idx]
  if (v && v.orders_count > 0) {
    variantToDeleteIndex.value = idx
    variantToDeleteInfo.value = v
    variantDeleteConfirmOpen.value = true
    return
  }
  form.value.variants.splice(idx, 1)
}

const confirmRemoveVariantWithOrders = () => {
  if (variantToDeleteIndex.value !== null) {
    form.value.variants.splice(variantToDeleteIndex.value, 1)
  }
  variantDeleteConfirmOpen.value = false
  variantToDeleteIndex.value = null
  variantToDeleteInfo.value = null
}

// 5. Media Uploads (Multi-upload with progress, reorder, external URL, video)
const handleImageFilesUpload = async (e) => {
  const files = Array.from(e.target.files || [])
  if (!files.length) return

  uploadingImage.value = true
  imageUploadProgress.value = 0

  try {
    for (let i = 0; i < files.length; i++) {
      const formData = new FormData()
      formData.append('image', files[i])

      const res = await apiClient.post('/admin/products/upload-image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (evt) => {
          if (evt.total) {
            imageUploadProgress.value = Math.round((evt.loaded * 100) / evt.total)
          }
        },
      })

      form.value.images.push({
        url: res.data.url,
        thumb_url: res.data.thumb_url,
        path: res.data.path,
        thumb_path: res.data.thumb_path,
        medium_path: res.data.medium_path,
        large_path: res.data.large_path,
        alt: form.value.name || 'Product Image',
        is_primary: form.value.images.length === 0,
        variant_id: null,
      })
    }
    toast.success(`Uploaded ${files.length} ${files.length === 1 ? 'image' : 'images'} (WebP 300/800/1600px).`)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Image upload failed.')
  } finally {
    uploadingImage.value = false
    imageUploadProgress.value = 0
    e.target.value = ''
  }
}

const addExternalImageByUrl = () => {
  const url = externalImageUrlInput.value.trim()
  if (!url) return
  form.value.images.push({
    url,
    thumb_url: url,
    alt: form.value.name || 'Product Image',
    is_primary: form.value.images.length === 0,
    variant_id: null,
  })
  externalImageUrlInput.value = ''
  toast.info('Added external image URL.')
}

const moveImage = (idx, dir) => {
  const target = idx + dir
  if (target < 0 || target >= form.value.images.length) return
  const copy = [...form.value.images]
  const [moved] = copy.splice(idx, 1)
  copy.splice(target, 0, moved)
  form.value.images = copy
}

const setPrimaryImage = (index) => {
  form.value.images.forEach((img, idx) => {
    img.is_primary = idx === index
  })
}

const removeImage = (index) => {
  form.value.images.splice(index, 1)
  if (form.value.images.length > 0 && !form.value.images.some((i) => i.is_primary)) {
    form.value.images[0].is_primary = true
  }
}

const handleVideoUpload = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  uploadingVideo.value = true
  videoUploadProgress.value = 0
  const formData = new FormData()
  formData.append('video', file)

  try {
    const res = await apiClient.post('/admin/products/upload-video', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (evt) => {
        if (evt.total) {
          videoUploadProgress.value = Math.round((evt.loaded * 100) / evt.total)
        }
      },
    })
    form.value.video_path = res.data.path
    form.value.video_url = res.data.url
    toast.success('Product video uploaded.')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Video upload failed.')
  } finally {
    uploadingVideo.value = false
    videoUploadProgress.value = 0
    e.target.value = ''
  }
}

// 6. Accordion Detail Sections (Add, Remove, Reorder)
const addSection = () => {
  form.value.sections.push({
    title: '',
    content: '',
    is_open_by_default: false,
  })
}

const moveSection = (idx, dir) => {
  const target = idx + dir
  if (target < 0 || target >= form.value.sections.length) return
  const copy = [...form.value.sections]
  const [moved] = copy.splice(idx, 1)
  copy.splice(target, 0, moved)
  form.value.sections = copy
}

const removeSection = (index) => {
  form.value.sections.splice(index, 1)
}

// 7. Specifications Rows
const addSpec = () => {
  form.value.specs.push({ label: '', value: '' })
}

const moveSpec = (idx, dir) => {
  const target = idx + dir
  if (target < 0 || target >= form.value.specs.length) return
  const copy = [...form.value.specs]
  const [moved] = copy.splice(idx, 1)
  copy.splice(target, 0, moved)
  form.value.specs = copy
}

const removeSpec = (index) => {
  form.value.specs.splice(index, 1)
}

// 10. Related Products Picker
const toggleRelatedProduct = (id) => {
  const list = form.value.related_products_json || []
  if (list.includes(id)) {
    form.value.related_products_json = list.filter((x) => x !== id)
  } else {
    form.value.related_products_json = [...list, id]
  }
}

const toggleFbtProduct = (id) => {
  const list = form.value.frequently_bought_together_json || []
  if (list.includes(id)) {
    form.value.frequently_bought_together_json = list.filter((x) => x !== id)
  } else {
    form.value.frequently_bought_together_json = [...list, id]
  }
}

// Save Product
const handleSave = async () => {
  fieldErrors.value = {}
  if (!form.value.name.trim()) {
    fieldErrors.value.name = 'Product name is required.'
    activeTab.value = 'general'
    toast.error('Please enter a product name.')
    return
  }

  saving.value = true
  try {
    const tags = tagsInput.value
      ? tagsInput.value
          .split(',')
          .map((t) => t.trim())
          .filter(Boolean)
      : []

    const payload = {
      ...form.value,
      tags,
      brand_id: form.value.brand_id ? Number(form.value.brand_id) : null,
      category_id: form.value.category_id ? Number(form.value.category_id) : null,
    }

    if (isEdit.value) {
      await apiClient.put(`/admin/products/${productId.value}`, payload)
      isDirty.value = false
      toast.success('Product saved successfully.')
      await fetchProduct()
    } else {
      const res = await apiClient.post('/admin/products', payload)
      isDirty.value = false
      toast.success('Product created and published!')
      router.push(`/admin/products/${res.data.data.id}/edit`)
    }
  } catch (err) {
    if (err.response?.data?.errors) {
      fieldErrors.value = err.response.data.errors
    }
    toast.error(err.response?.data?.message || 'Failed to save product.')
  } finally {
    saving.value = false
  }
}

// Keyboard Ctrl+S Save Shortcut & BeforeUnload Guard
const handleKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
    e.preventDefault()
    if (!saving.value) {
      handleSave()
    }
  }
}

const handleBeforeUnload = (e) => {
  if (isDirty.value) {
    e.preventDefault()
    e.returnValue = ''
  }
}

onBeforeRouteLeave((to, from, next) => {
  if (isDirty.value && !saving.value) {
    pendingNextRoute = next
    leaveConfirmOpen.value = true
    return
  }
  next()
})

const confirmLeaveWithoutSaving = () => {
  leaveConfirmOpen.value = false
  isDirty.value = false
  if (pendingNextRoute) {
    pendingNextRoute()
    pendingNextRoute = null
  }
}

onMounted(async () => {
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('beforeunload', handleBeforeUnload)
  await fetchMetadata()
  await fetchProduct()
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('beforeunload', handleBeforeUnload)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="p-2 rounded-full border border-line bg-white hover:bg-surface text-ink-muted hover:text-ink transition"
          @click="router.push('/admin/products')"
          aria-label="Back to products"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
        <div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
              {{ isEdit ? 'Edit Product' : 'Create New Product' }}
            </h1>
            <span
              v-if="isDirty"
              class="px-2.5 py-0.5 rounded-pill bg-amber-50 text-amber-800 border border-amber-200 text-[11px] font-semibold"
            >
              Unsaved changes
            </span>
          </div>
          <p class="text-xs text-ink-muted mt-0.5">
            {{ form.name || 'Untitled Product' }} · SKU:
            <span class="font-mono text-ink">{{ form.sku || 'Auto-generated' }}</span>
          </p>
        </div>
      </div>

      <!-- Mobile Quick Save -->
      <div class="flex lg:hidden items-center gap-2">
        <a
          v-if="isEdit && form.slug"
          :href="`/products/${form.slug}`"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-white text-xs font-semibold text-ink"
        >
          <ExternalLink class="w-3.5 h-3.5" />
          <span>Preview</span>
        </a>
        <button
          type="button"
          :disabled="saving"
          @click="handleSave"
          class="inline-flex items-center gap-1.5 px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark disabled:opacity-50 transition"
        >
          <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
          <Save v-else class="w-3.5 h-3.5" />
          <span>{{ isEdit ? 'Save Changes' : 'Publish' }}</span>
        </button>
      </div>
    </div>

    <!-- Section Navigation Tabs -->
    <div class="bg-white rounded-card border border-line p-1.5 shadow-card flex items-center gap-1 overflow-x-auto">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        @click="activeTab = tab.id"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all"
        :class="
          activeTab === tab.id
            ? 'bg-brand text-white shadow-2xs'
            : 'text-ink-muted hover:text-ink hover:bg-surface'
        "
      >
        <component :is="tab.icon" class="w-3.5 h-3.5 shrink-0" />
        <span>{{ tab.label }}</span>
      </button>
    </div>

    <!-- Main 2-Column Layout: Form Content + Sticky Right-Side Summary Card -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Left Column: Active Form Section (lg:col-span-8) -->
      <div class="lg:col-span-8 bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
        <div v-if="loading" class="py-12 text-center space-y-3">
          <Loader2 class="w-6 h-6 animate-spin text-brand mx-auto" />
          <p class="text-xs text-ink-muted">Loading product data...</p>
        </div>

        <template v-else>
          <!-- 1. GENERAL (name, slug, brand, categories, tags, status) -->
          <div v-if="activeTab === 'general'" class="space-y-5">
            <h2 class="text-base font-bold text-ink pb-3 border-b border-line">
              1. General Information
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <FormField label="Product Name" required :error="fieldErrors.name">
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="e.g. Organic Washed Linen Relaxed Shirt"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
                    @blur="handleNameBlur"
                  />
                </FormField>
              </div>

              <FormField
                label="URL Slug"
                hint="Leave blank to auto-generate from name"
                :error="fieldErrors.slug"
              >
                <input
                  v-model="form.slug"
                  type="text"
                  placeholder="organic-washed-linen-shirt"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField
                label="SKU (Stock Keeping Unit)"
                hint="Unique identifier for inventory tracking"
                :error="fieldErrors.sku"
              >
                <input
                  v-model="form.sku"
                  type="text"
                  placeholder="LIN-SHIRT-001"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField label="Primary Category" :error="fieldErrors.category_id">
                <select
                  v-model="form.category_id"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
                >
                  <option value="">Select Primary Category...</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </FormField>

              <FormField label="Brand" :error="fieldErrors.brand_id">
                <select
                  v-model="form.brand_id"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
                >
                  <option value="">In-House / No Brand</option>
                  <option v-for="b in brands" :key="b.id" :value="b.id">
                    {{ b.name }}
                  </option>
                </select>
              </FormField>

              <!-- Additional Categories Multi-Select -->
              <div class="sm:col-span-2">
                <FormField label="Additional Categories (Multi-select)">
                  <div class="flex flex-wrap gap-2 p-3 rounded-xl border border-line bg-surface/40 max-h-36 overflow-y-auto">
                    <label
                      v-for="cat in categories"
                      :key="'mc-' + cat.id"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-white border border-line text-xs cursor-pointer hover:border-brand transition"
                    >
                      <input
                        v-model="form.category_ids"
                        type="checkbox"
                        :value="cat.id"
                        class="rounded border-line text-brand focus:ring-brand"
                      />
                      <span>{{ cat.name }}</span>
                    </label>
                  </div>
                </FormField>
              </div>

              <div class="sm:col-span-2">
                <FormField
                  label="Product Tags"
                  hint="Comma-separated tags (e.g. linen, breathable, summer, sustainable)"
                >
                  <input
                    v-model="tagsInput"
                    type="text"
                    placeholder="linen, summer, organic, relaxed fit"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
                  />
                </FormField>
              </div>
            </div>
          </div>

          <!-- 2. DESCRIPTION (short description + RichTextEditor) -->
          <div v-if="activeTab === 'description'" class="space-y-5">
            <h2 class="text-base font-bold text-ink pb-3 border-b border-line">
              2. Editorial & Rich Description
            </h2>

            <FormField
              label="Short Description"
              hint="1–2 sentence summary shown near the top of the product detail page"
            >
              <textarea
                v-model="form.short_description"
                rows="3"
                placeholder="Tailored from 100% European flax linen, pre-washed for a soft, lived-in drape from day one."
                class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
              />
            </FormField>

            <FormField
              label="Full Product Description"
              hint="Formatted editorial copy, craftsmanship notes, and styling guidance"
            >
              <RichTextEditor
                v-model="form.description"
                min-height="240px"
                placeholder="Write detailed product story..."
              />
            </FormField>
          </div>

          <!-- 3. PRICING & INVENTORY -->
          <div v-if="activeTab === 'pricing'" class="space-y-6">
            <h2 class="text-base font-bold text-ink pb-3 border-b border-line">
              3. Pricing & Inventory Control
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <FormField label="Base Price ($)" required :error="fieldErrors.base_price">
                <input
                  v-model="form.base_price"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono font-bold text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField
                label="Compare-at Price ($)"
                hint="Shows strikethrough price & sale badge"
              >
                <input
                  v-model="form.compare_at_price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="Optional"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField
                label="Cost per Item ($)"
                hint="Internal only — customers never see this"
              >
                <input
                  v-model="form.cost_price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="Optional"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>
            </div>

            <div class="pt-5 border-t border-line space-y-4">
              <div class="flex flex-wrap items-center justify-between gap-4 p-4 rounded-xl bg-surface/60 border border-line">
                <ToggleSwitch
                  v-model="form.track_stock"
                  label="Track Inventory Stock Quantity"
                  description="Automatically deduct stock when Stripe confirms an order"
                />
                <ToggleSwitch
                  v-model="form.allow_backorder"
                  label="Continue Selling When Out of Stock"
                  description="Allow customers to purchase on backorder when stock reaches 0"
                />
              </div>

              <div v-if="form.track_stock" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label="Available Stock Quantity">
                  <input
                    v-model="form.stock_qty"
                    type="number"
                    min="0"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                  />
                </FormField>

                <FormField
                  label="Low-Stock Alert Threshold"
                  hint="Triggers low-stock badge on dashboard when stock falls to or below this number"
                >
                  <input
                    v-model="form.low_stock_threshold"
                    type="number"
                    min="0"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                  />
                </FormField>
              </div>
            </div>
          </div>

          <!-- 4. VARIATIONS (Step 1: Option Types, Step 2: Variants Table + Bulk Toolbar + Order Delete Warning) -->
          <div v-if="activeTab === 'variations'" class="space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-line">
              <div>
                <h2 class="text-base font-bold text-ink">4. Product Variations</h2>
                <p class="text-xs text-ink-muted">
                  Step 1: Define option types & values · Step 2: Edit generated variants
                </p>
              </div>
              <button
                type="button"
                @click="addOptionType"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-pill bg-brand/10 text-brand text-xs font-semibold hover:bg-brand/20 transition"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Add Option Type</span>
              </button>
            </div>

            <!-- Step 1: Option Types -->
            <div v-if="form.options.length === 0" class="p-6 rounded-xl border border-dashed border-line text-center text-xs text-ink-muted">
              This product has no variations yet. Click <strong>Add Option Type</strong> (e.g., Size, Color) to create variants.
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(opt, oIdx) in form.options"
                :key="oIdx"
                class="p-4 rounded-xl border border-line bg-surface/40 space-y-3"
              >
                <div class="flex items-center justify-between gap-3">
                  <input
                    v-model="opt.name"
                    type="text"
                    placeholder="Option Name (e.g. Color, Size, Fit)"
                    class="px-3 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink w-64 focus:outline-none focus:border-brand"
                  />
                  <button
                    type="button"
                    class="text-xs font-semibold text-error hover:underline"
                    @click="removeOptionType(oIdx)"
                  >
                    Remove Option
                  </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                  <div
                    v-for="(val, vIdx) in opt.values"
                    :key="vIdx"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-line text-xs"
                  >
                    <input
                      v-if="opt.name && opt.name.toLowerCase().includes('color')"
                      v-model="val.hex_color"
                      type="color"
                      class="w-4 h-4 rounded cursor-pointer border-0 p-0"
                      title="Pick swatch color"
                    />
                    <input
                      v-model="val.name"
                      type="text"
                      placeholder="Value (e.g. Sage, M)"
                      class="w-24 text-xs text-ink focus:outline-none"
                    />
                    <button
                      type="button"
                      class="text-ink-muted hover:text-error"
                      @click="removeOptionValue(opt, vIdx)"
                    >
                      ✕
                    </button>
                  </div>

                  <button
                    type="button"
                    @click="addOptionValue(opt)"
                    class="px-2.5 py-1 rounded-lg border border-dashed border-line bg-white text-xs font-semibold text-brand hover:border-brand transition"
                  >
                    + Add Value
                  </button>
                </div>
              </div>

              <button
                type="button"
                @click="generateVariantCombinations"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-ink text-white text-xs font-semibold hover:bg-ink/90 transition"
              >
                <Sparkles class="w-3.5 h-3.5" />
                <span>Generate / Sync Variant Matrix</span>
              </button>
            </div>

            <!-- Step 2: Variants Matrix Table & Bulk Edit Toolbar -->
            <div v-if="form.variants.length > 0" class="pt-5 border-t border-line space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-surface border border-line">
                <span class="text-xs font-bold text-ink">
                  Step 2: {{ form.variants.length }} Variants
                </span>

                <div class="flex flex-wrap items-center gap-2">
                  <select
                    v-model="bulkActionType"
                    class="px-2.5 py-1.5 rounded-lg border border-line bg-white text-xs text-ink"
                  >
                    <option value="set_price">Set Price ($) for All</option>
                    <option value="increase_price_percent">Adjust Price by %</option>
                    <option value="set_stock">Set Stock Qty for All</option>
                  </select>
                  <input
                    v-model="bulkActionValue"
                    type="number"
                    step="0.01"
                    placeholder="Value..."
                    class="w-24 px-2.5 py-1.5 rounded-lg border border-line bg-white text-xs font-mono text-ink"
                  />
                  <button
                    type="button"
                    @click="applyBulkVariantEdit"
                    class="px-3.5 py-1.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
                  >
                    Apply to All
                  </button>
                </div>
              </div>

              <div class="overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-xs text-left border-collapse">
                  <thead class="bg-surface border-b border-line text-[11px] font-bold uppercase text-ink-muted">
                    <tr>
                      <th class="py-2.5 px-3">Options / SKU</th>
                      <th class="py-2.5 px-3">Price ($)</th>
                      <th class="py-2.5 px-3">Compare ($)</th>
                      <th class="py-2.5 px-3">Stock</th>
                      <th class="py-2.5 px-3">Linked Image</th>
                      <th class="py-2.5 px-3 text-center">Active</th>
                      <th class="py-2.5 px-3 text-right">Remove</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-line">
                    <tr v-for="(v, idx) in form.variants" :key="idx" class="hover:bg-surface/40">
                      <td class="py-2.5 px-3 min-w-[160px]">
                        <div class="flex flex-wrap gap-1 mb-1">
                          <span
                            v-for="(val, key) in v.option_values_json"
                            :key="key"
                            class="px-1.5 py-0.2 rounded bg-brand/10 text-brand text-[10px] font-semibold"
                          >
                            {{ key }}: {{ val }}
                          </span>
                          <span
                            v-if="v.orders_count > 0"
                            class="px-1.5 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-semibold"
                            title="This variant has historical customer orders"
                          >
                            {{ v.orders_count }} orders
                          </span>
                        </div>
                        <input
                          v-model="v.sku"
                          type="text"
                          class="w-full px-2 py-1 rounded border border-line font-mono text-xs text-ink"
                        />
                      </td>

                      <td class="py-2.5 px-3">
                        <input
                          v-model="v.price"
                          type="number"
                          step="0.01"
                          class="w-24 px-2 py-1 rounded border border-line font-mono text-xs text-ink"
                        />
                      </td>

                      <td class="py-2.5 px-3">
                        <input
                          v-model="v.compare_at_price"
                          type="number"
                          step="0.01"
                          placeholder="—"
                          class="w-24 px-2 py-1 rounded border border-line font-mono text-xs text-ink"
                        />
                      </td>

                      <td class="py-2.5 px-3">
                        <input
                          v-model="v.stock_qty"
                          type="number"
                          min="0"
                          class="w-20 px-2 py-1 rounded border border-line font-mono text-xs text-ink"
                        />
                      </td>

                      <td class="py-2.5 px-3">
                        <select
                          v-model="v.image_id"
                          class="w-32 px-2 py-1 rounded border border-line bg-white text-xs text-ink"
                        >
                          <option :value="null">Default Image</option>
                          <option
                            v-for="(img, imgIdx) in form.images"
                            :key="img.id || imgIdx"
                            :value="img.id || null"
                          >
                            Image #{{ imgIdx + 1 }} ({{ img.alt || 'Photo' }})
                          </option>
                        </select>
                      </td>

                      <td class="py-2.5 px-3 text-center">
                        <input
                          v-model="v.is_active"
                          type="checkbox"
                          class="rounded border-line text-brand focus:ring-brand cursor-pointer"
                        />
                      </td>

                      <td class="py-2.5 px-3 text-right">
                        <button
                          type="button"
                          @click="requestRemoveVariant(idx)"
                          class="p-1.5 rounded text-ink-muted hover:text-error hover:bg-red-50 transition"
                          title="Delete variant"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 5. MEDIA (Multi-upload, Reorder, Set Primary, Alt Text, Add by URL, Link to Variant, Video Upload/URL) -->
          <div v-if="activeTab === 'media'" class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-line">
              <div>
                <h2 class="text-base font-bold text-ink">5. Product Media & Video</h2>
                <p class="text-xs text-ink-muted">
                  Uploaded photos automatically generate 300px, 800px, and 1600px WebP derivatives
                </p>
              </div>

              <label class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition self-start sm:self-auto">
                <Loader2 v-if="uploadingImage" class="w-3.5 h-3.5 animate-spin" />
                <Upload v-else class="w-3.5 h-3.5" />
                <span>{{ uploadingImage ? `Uploading (${imageUploadProgress}%)` : 'Upload Images' }}</span>
                <input
                  type="file"
                  multiple
                  accept="image/jpeg,image/png,image/webp,image/svg+xml"
                  class="hidden"
                  @change="handleImageFilesUpload"
                />
              </label>
            </div>

            <!-- Add Image by External URL -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-3.5 rounded-xl bg-surface/60 border border-line">
              <input
                v-model="externalImageUrlInput"
                type="url"
                placeholder="Paste external image URL (https://...)..."
                class="flex-1 px-3 py-2 rounded-lg border border-line bg-white text-xs font-mono text-ink focus:outline-none focus:border-brand"
                @keydown.enter.prevent="addExternalImageByUrl"
              />
              <button
                type="button"
                @click="addExternalImageByUrl"
                class="px-4 py-2 rounded-pill bg-ink text-white text-xs font-semibold hover:bg-ink/90 transition shrink-0"
              >
                Add by URL
              </button>
            </div>

            <!-- Images Gallery Grid -->
            <div v-if="form.images.length === 0" class="p-8 rounded-xl border border-dashed border-line text-center text-xs text-ink-muted">
              No product images added yet. Upload files above or add an image URL.
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="(img, idx) in form.images"
                :key="idx"
                class="rounded-xl border p-3 bg-white space-y-2.5 transition"
                :class="img.is_primary ? 'border-brand ring-1 ring-brand/30' : 'border-line'"
              >
                <div class="aspect-square rounded-lg bg-surface overflow-hidden relative">
                  <img
                    :src="img.thumb_url || img.url"
                    :alt="img.alt || 'Product image'"
                    class="w-full h-full object-cover"
                  />
                  <span
                    v-if="img.is_primary"
                    class="absolute top-2 left-2 px-2 py-0.5 rounded-pill bg-brand text-white text-[10px] font-bold shadow-xs"
                  >
                    Primary
                  </span>
                </div>

                <input
                  v-model="img.alt"
                  type="text"
                  placeholder="Alt text for SEO & accessibility"
                  class="w-full px-2.5 py-1.5 rounded-lg border border-line text-xs text-ink"
                />

                <!-- Link Image to Specific Variant -->
                <div v-if="form.variants.length > 0">
                  <select
                    v-model="img.variant_id"
                    class="w-full px-2.5 py-1.5 rounded-lg border border-line bg-surface/50 text-[11px] text-ink"
                  >
                    <option :value="null">All Variants (Shared Image)</option>
                    <option
                      v-for="v in form.variants"
                      :key="v.sku"
                      :value="v.id || null"
                    >
                      Variant: {{ v.sku }}
                    </option>
                  </select>
                </div>

                <!-- Reorder, Primary & Remove Toolbar -->
                <div class="flex items-center justify-between pt-1 border-t border-line">
                  <div class="flex items-center gap-1">
                    <button
                      type="button"
                      :disabled="idx === 0"
                      @click="moveImage(idx, -1)"
                      class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface disabled:opacity-30"
                      title="Move earlier"
                    >
                      <ArrowUp class="w-3.5 h-3.5 -rotate-90" />
                    </button>
                    <button
                      type="button"
                      :disabled="idx === form.images.length - 1"
                      @click="moveImage(idx, 1)"
                      class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface disabled:opacity-30"
                      title="Move later"
                    >
                      <ArrowDown class="w-3.5 h-3.5 -rotate-90" />
                    </button>
                    <button
                      v-if="!img.is_primary"
                      type="button"
                      @click="setPrimaryImage(idx)"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold text-ink-muted hover:text-brand hover:bg-brand/5"
                    >
                      <Star class="w-3 h-3" />
                      <span>Set Primary</span>
                    </button>
                  </div>

                  <button
                    type="button"
                    @click="removeImage(idx)"
                    class="p-1 rounded text-ink-muted hover:text-error hover:bg-red-50"
                    title="Remove image"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Video Upload & URL Section -->
            <div class="pt-6 border-t border-line space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                  <h3 class="text-sm font-bold text-ink flex items-center gap-2">
                    <Video class="w-4 h-4 text-brand" />
                    <span>Product Hover & Gallery Video</span>
                  </h3>
                  <p class="text-xs text-ink-muted">
                    Plays muted on product card hover and inside the product detail gallery
                  </p>
                </div>

                <label class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-pill border border-line bg-white text-ink hover:border-brand hover:text-brand text-xs font-semibold transition self-start sm:self-auto">
                  <Loader2 v-if="uploadingVideo" class="w-3.5 h-3.5 animate-spin" />
                  <Upload v-else class="w-3.5 h-3.5" />
                  <span>{{ uploadingVideo ? `Uploading (${videoUploadProgress}%)` : 'Upload MP4/WebM' }}</span>
                  <input
                    type="file"
                    accept="video/mp4,video/webm,video/quicktime"
                    class="hidden"
                    @change="handleVideoUpload"
                  />
                </label>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label="Video URL (MP4 / WebM)">
                  <input
                    v-model="form.video_url"
                    type="text"
                    placeholder="https://.../product-motion.mp4"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                  />
                </FormField>

                <FormField label="Video Poster Image URL (Optional)">
                  <input
                    v-model="form.video_poster"
                    type="text"
                    placeholder="https://.../poster.webp"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                  />
                </FormField>
              </div>
            </div>
          </div>

          <!-- 6. ITEM DETAILS SECTIONS (Accordion blocks with Rich Text & Reorder) -->
          <div v-if="activeTab === 'sections'" class="space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-line">
              <div>
                <h2 class="text-base font-bold text-ink">6. Item Details Accordion Blocks</h2>
                <p class="text-xs text-ink-muted">
                  Click-to-open accordion sections displayed on the product page
                </p>
              </div>
              <button
                type="button"
                @click="addSection"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-pill bg-brand/10 text-brand text-xs font-semibold hover:bg-brand/20 transition"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Add Section</span>
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(sec, sIdx) in form.sections"
                :key="sIdx"
                class="p-4 rounded-xl border border-line bg-surface/30 space-y-3"
              >
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <input
                    v-model="sec.title"
                    type="text"
                    placeholder="Accordion Title (e.g. Materials & Care, Fit Guide)"
                    class="flex-1 min-w-[200px] px-3 py-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:outline-none focus:border-brand"
                  />

                  <div class="flex items-center gap-2">
                    <ToggleSwitch
                      v-model="sec.is_open_by_default"
                      label="Open by default"
                    />
                    <button
                      type="button"
                      :disabled="sIdx === 0"
                      @click="moveSection(sIdx, -1)"
                      class="p-1.5 rounded-lg border border-line bg-white text-ink-muted hover:text-ink disabled:opacity-30"
                      title="Move up"
                    >
                      <ArrowUp class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      :disabled="sIdx === form.sections.length - 1"
                      @click="moveSection(sIdx, 1)"
                      class="p-1.5 rounded-lg border border-line bg-white text-ink-muted hover:text-ink disabled:opacity-30"
                      title="Move down"
                    >
                      <ArrowDown class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      @click="removeSection(sIdx)"
                      class="p-1.5 rounded-lg border border-line bg-white text-ink-muted hover:text-error"
                      title="Delete section"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>

                <RichTextEditor
                  v-model="sec.content"
                  min-height="120px"
                  placeholder="Write accordion content..."
                />
              </div>
            </div>
          </div>

          <!-- 7. SPECIFICATIONS ROWS -->
          <div v-if="activeTab === 'specs'" class="space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-line">
              <div>
                <h2 class="text-base font-bold text-ink">7. Technical Specifications</h2>
                <p class="text-xs text-ink-muted">
                  Structured key-value rows shown in the Specifications table
                </p>
              </div>
              <button
                type="button"
                @click="addSpec"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-pill bg-brand/10 text-brand text-xs font-semibold hover:bg-brand/20 transition"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Add Row</span>
              </button>
            </div>

            <div v-if="form.specs.length === 0" class="p-8 rounded-xl border border-dashed border-line text-center text-xs text-ink-muted">
              No specification rows added yet. Click <strong>Add Row</strong> to add fabric weight, origin, dimensions, etc.
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="(sp, idx) in form.specs"
                :key="idx"
                class="flex items-center gap-2.5"
              >
                <input
                  v-model="sp.label"
                  type="text"
                  placeholder="Label (e.g. Fabric Weight)"
                  class="w-1/3 px-3 py-2 rounded-xl border border-line text-xs font-semibold text-ink focus:outline-none focus:border-brand"
                />
                <input
                  v-model="sp.value"
                  type="text"
                  placeholder="Value (e.g. 185 GSM Belgian Flax)"
                  class="flex-1 px-3 py-2 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
                />
                <button
                  type="button"
                  :disabled="idx === 0"
                  @click="moveSpec(idx, -1)"
                  class="p-2 rounded-lg border border-line text-ink-muted hover:text-ink disabled:opacity-30"
                >
                  <ArrowUp class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  :disabled="idx === form.specs.length - 1"
                  @click="moveSpec(idx, 1)"
                  class="p-2 rounded-lg border border-line text-ink-muted hover:text-ink disabled:opacity-30"
                >
                  <ArrowDown class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  @click="removeSpec(idx)"
                  class="p-2 rounded-lg border border-line text-ink-muted hover:text-error"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- 8. SHIPPING -->
          <div v-if="activeTab === 'shipping'" class="space-y-5">
            <h2 class="text-base font-bold text-ink pb-3 border-b border-line">
              8. Shipping Dimensions & Tax Class
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <FormField label="Weight (kg)">
                <input
                  v-model="form.weight"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.45"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField label="Length (cm)">
                <input
                  v-model="form.length"
                  type="number"
                  step="0.1"
                  min="0"
                  placeholder="32.0"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField label="Width (cm)">
                <input
                  v-model="form.width"
                  type="number"
                  step="0.1"
                  min="0"
                  placeholder="24.0"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField label="Height (cm)">
                <input
                  v-model="form.height"
                  type="number"
                  step="0.1"
                  min="0"
                  placeholder="5.0"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>
            </div>

            <div class="max-w-xs pt-2">
              <FormField label="Tax Class">
                <select
                  v-model="form.tax_class"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
                >
                  <option value="standard">Standard Rate</option>
                  <option value="reduced">Reduced Rate</option>
                  <option value="zero">Zero Rated</option>
                  <option value="exempt">Tax Exempt</option>
                </select>
              </FormField>
            </div>
          </div>

          <!-- 9. SEO WITH LIVE GOOGLE-STYLE PREVIEW -->
          <div v-if="activeTab === 'seo'" class="space-y-6">
            <h2 class="text-base font-bold text-ink pb-3 border-b border-line">
              9. Search Engine Optimization (SEO)
            </h2>

            <!-- Live Google Snippet Preview Card -->
            <div class="p-5 rounded-card border border-line bg-surface/50 space-y-1.5">
              <p class="text-[10px] font-bold uppercase tracking-wider text-ink-muted mb-2">
                Live Google Search Result Preview
              </p>
              <p class="text-xs text-emerald-800 truncate font-mono">
                {{ seoPreviewUrl }}
              </p>
              <p class="text-base font-semibold text-[#1a0dab] hover:underline cursor-pointer line-clamp-1">
                {{ seoPreviewTitle }}
              </p>
              <p class="text-xs text-ink-muted line-clamp-2 leading-relaxed max-w-2xl">
                {{ seoPreviewDescription }}
              </p>
            </div>

            <div class="space-y-4">
              <FormField
                label="Custom SEO Meta Title"
                :hint="`${(form.seo_title || form.name || '').length} / 60 recommended characters`"
              >
                <input
                  v-model="form.seo_title"
                  type="text"
                  maxlength="150"
                  :placeholder="form.name || 'Product SEO Title'"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField
                label="SEO Meta Description"
                :hint="`${(form.seo_description || '').length} / 160 recommended characters`"
              >
                <textarea
                  v-model="form.seo_description"
                  rows="3"
                  maxlength="500"
                  placeholder="Write a compelling summary for search engines and social shares..."
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
                />
              </FormField>

              <FormField label="Open Graph / Social Share Image URL">
                <input
                  v-model="form.seo_social_image"
                  type="text"
                  placeholder="Defaults to primary product image if left blank"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-line text-xs font-mono text-ink focus:outline-none focus:border-brand"
                />
              </FormField>
            </div>
          </div>

          <!-- 10. RELATED PRODUCTS PICKER -->
          <div v-if="activeTab === 'related'" class="space-y-6">
            <div class="pb-3 border-b border-line">
              <h2 class="text-base font-bold text-ink">
                10. Related Products & Cross-Sells
              </h2>
              <p class="text-xs text-ink-muted">
                Curate manual "You May Also Like" and "Frequently Bought Together" recommendations
              </p>
            </div>

            <div class="relative max-w-md">
              <Search class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="relatedSearch"
                type="search"
                placeholder="Search catalog products by name or SKU..."
                class="w-full pl-9 pr-4 py-2 rounded-pill border border-line bg-surface/50 text-xs text-ink focus:bg-white focus:outline-none focus:border-brand"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Related Products ("You May Also Like") -->
              <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-ink">
                  Related Products ({{ form.related_products_json?.length || 0 }} selected)
                </h3>
                <div class="divide-y divide-line rounded-xl border border-line max-h-80 overflow-y-auto">
                  <label
                    v-for="item in filteredCatalogForRelated"
                    :key="'rel-' + item.id"
                    class="p-3 flex items-center justify-between gap-3 text-xs cursor-pointer hover:bg-surface/60 transition"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <input
                        type="checkbox"
                        :checked="form.related_products_json?.includes(item.id)"
                        @change="toggleRelatedProduct(item.id)"
                        class="rounded border-line text-brand focus:ring-brand"
                      />
                      <span class="font-semibold text-ink truncate">{{ item.name }}</span>
                    </div>
                    <span class="font-mono text-ink-muted shrink-0">
                      {{ settingsStore.formatPrice(item.base_price) }}
                    </span>
                  </label>
                </div>
              </div>

              <!-- Frequently Bought Together -->
              <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-ink">
                  Frequently Bought Together ({{ form.frequently_bought_together_json?.length || 0 }} selected)
                </h3>
                <div class="divide-y divide-line rounded-xl border border-line max-h-80 overflow-y-auto">
                  <label
                    v-for="item in filteredCatalogForRelated"
                    :key="'fbt-' + item.id"
                    class="p-3 flex items-center justify-between gap-3 text-xs cursor-pointer hover:bg-surface/60 transition"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <input
                        type="checkbox"
                        :checked="form.frequently_bought_together_json?.includes(item.id)"
                        @change="toggleFbtProduct(item.id)"
                        class="rounded border-line text-brand focus:ring-brand"
                      />
                      <span class="font-semibold text-ink truncate">{{ item.name }}</span>
                    </div>
                    <span class="font-mono text-ink-muted shrink-0">
                      {{ settingsStore.formatPrice(item.base_price) }}
                    </span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Right Column: Sticky Summary Card (lg:col-span-4) -->
      <aside class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
        <div class="bg-white rounded-card border border-line p-5 shadow-card space-y-5">
          <div class="flex items-center justify-between pb-3 border-b border-line">
            <h3 class="text-sm font-bold text-ink">Publishing & Summary</h3>
            <span
              class="px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border capitalize"
              :class="
                form.status === 'active'
                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                  : form.status === 'draft'
                    ? 'bg-amber-50 text-amber-800 border-amber-200'
                    : 'bg-gray-100 text-gray-700 border-gray-300'
              "
            >
              {{ form.status }}
            </span>
          </div>

          <!-- Product Thumbnail & Quick Summary -->
          <div class="flex items-center gap-3.5 p-3 rounded-xl bg-surface/60 border border-line">
            <div class="w-14 h-14 rounded-xl bg-white border border-line overflow-hidden flex items-center justify-center shrink-0">
              <img
                v-if="primaryPreviewImage"
                :src="primaryPreviewImage"
                :alt="form.name"
                class="w-full h-full object-cover"
              />
              <ImageIcon v-else class="w-5 h-5 text-ink-muted" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-bold text-ink truncate">
                {{ form.name || 'Untitled Product' }}
              </p>
              <p class="text-sm font-bold text-brand font-mono mt-0.5">
                {{ settingsStore.formatPrice(form.base_price || 0) }}
              </p>
              <p class="text-[11px] text-ink-muted">
                {{ form.variants.length }} variants · {{ form.images.length }} photos
              </p>
            </div>
          </div>

          <!-- Status Selector -->
          <FormField label="Visibility Status">
            <select
              v-model="form.status"
              class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-white text-xs font-semibold text-ink focus:outline-none focus:border-brand"
            >
              <option value="active">Active (Visible on Storefront)</option>
              <option value="draft">Draft (Hidden)</option>
              <option value="archived">Archived</option>
            </select>
          </FormField>

          <!-- Featured Toggle -->
          <div class="p-3.5 rounded-xl bg-surface/50 border border-line">
            <ToggleSwitch
              v-model="form.is_featured"
              label="Featured Product"
              description="Highlight in Featured Collection on homepage"
            />
          </div>

          <!-- Primary Save Button & Preview Link -->
          <div class="space-y-2.5 pt-2 border-t border-line">
            <button
              type="button"
              :disabled="saving"
              @click="handleSave"
              class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-pill bg-brand text-white text-xs font-bold hover:bg-brand-dark disabled:opacity-50 transition shadow-sm"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <Save v-else class="w-4 h-4" />
              <span>{{ isEdit ? 'Save Product Changes' : 'Publish Product' }}</span>
            </button>

            <a
              v-if="isEdit && form.slug"
              :href="`/products/${form.slug}`"
              target="_blank"
              rel="noopener noreferrer"
              class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-pill border border-line bg-white text-xs font-semibold text-ink hover:border-brand hover:text-brand transition"
            >
              <span>Preview on Storefront</span>
              <ExternalLink class="w-3.5 h-3.5" />
            </a>

            <p class="text-[11px] text-center text-ink-muted">
              Tip: Press <kbd class="px-1.5 py-0.5 rounded bg-surface border border-line font-mono text-[10px]">Ctrl+S</kbd> anytime to save
            </p>
          </div>
        </div>
      </aside>
    </div>

    <!-- Warn Before Deleting a Variant That Has Orders -->
    <ConfirmDialog
      :open="variantDeleteConfirmOpen"
      title="Variant Has Existing Orders"
      :message="`Variant &quot;${variantToDeleteInfo?.sku}&quot; is linked to ${variantToDeleteInfo?.orders_count} historical customer order(s). Are you sure you want to remove this variant from the product?`"
      confirm-text="Remove Variant Anyway"
      variant="danger"
      @close="variantDeleteConfirmOpen = false"
      @confirm="confirmRemoveVariantWithOrders"
    />

    <!-- Unsaved Changes Route Leave Warning -->
    <ConfirmDialog
      :open="leaveConfirmOpen"
      title="Discard Unsaved Changes?"
      message="You have unsaved edits on this product form. Leaving this page now will discard your changes."
      confirm-text="Discard & Leave"
      cancel-text="Stay on Page"
      variant="danger"
      @close="leaveConfirmOpen = false"
      @confirm="confirmLeaveWithoutSaving"
    />
  </div>
</template>
