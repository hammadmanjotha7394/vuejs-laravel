<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import {
  ArrowLeft,
  Save,
  Plus,
  Trash2,
  Upload,
  Image as ImageIcon,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Sliders,
  Layers,
  Video,
  FileText,
  Tag,
  Globe,
  Sparkles,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => Boolean(route.params.id))
const productId = computed(() => route.params.id)

const activeTab = ref('basics')
const saving = ref(false)
const loading = ref(false)
const uploadingImage = ref(false)
const uploadingVideo = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const categories = ref([])
const brands = ref([])

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
  stock_qty: 10,
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
  video_poster: '',
  seo_title: '',
  seo_description: '',
  seo_social_image: '',
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

// Bulk variant edit state
const bulkActionType = ref('set_price')
const bulkActionValue = ref('')

async function fetchMetadata() {
  try {
    const [catsRes, brandsRes] = await Promise.all([
      axios.get('/api/admin/categories'),
      axios.get('/api/admin/brands').catch(() => ({ data: { data: [] } })),
    ])
    categories.value = catsRes.data.data
    brands.value = brandsRes.data.data || []
  } catch (err) {
    console.error(err)
  }
}

async function fetchProduct() {
  if (!isEdit.value) return
  loading.value = true
  try {
    const res = await axios.get(`/api/admin/products/${productId.value}`)
    const p = res.data.data

    form.value = {
      name: p.name,
      slug: p.slug,
      sku: p.sku,
      brand_id: p.brand_id || '',
      category_id: p.category_id || '',
      category_ids: p.categories ? p.categories.map((c) => c.id) : [],
      tags: p.tags ? p.tags.map((t) => t.name) : [],
      status: p.status,
      is_featured: Boolean(p.is_featured),
      base_price: Number(p.base_price),
      compare_at_price: p.compare_at_price ? Number(p.compare_at_price) : null,
      cost_price: p.cost_price ? Number(p.cost_price) : null,
      track_stock: Boolean(p.track_stock),
      stock_qty: p.stock_qty || 0,
      low_stock_threshold: p.low_stock_threshold || 5,
      allow_backorder: Boolean(p.allow_backorder),
      short_description: p.short_description || '',
      description: p.description || '',
      weight: p.weight,
      length: p.length,
      width: p.width,
      height: p.height,
      tax_class: p.tax_class || 'standard',
      video_url: p.video_url || '',
      video_poster: p.video_poster || '',
      seo_title: p.seo_title || '',
      seo_description: p.seo_description || '',
      seo_social_image: p.seo_social_image || '',
      options: p.option_types
        ? p.option_types.map((ot) => ({
            name: ot.name,
            values: ot.values ? ot.values.map((v) => ({ name: v.name, hex_color: v.hex_color })) : [],
          }))
        : [],
      variants: p.variants
        ? p.variants.map((v) => ({
            sku: v.sku,
            price: Number(v.price),
            compare_at_price: v.compare_at_price ? Number(v.compare_at_price) : null,
            stock_qty: v.stock_qty,
            is_active: Boolean(v.is_active),
            option_values_json: v.option_values_json || {},
          }))
        : [],
      images: p.images
        ? p.images.map((img) => ({
            url: img.url,
            path: img.path,
            thumb_path: img.thumb_path,
            medium_path: img.medium_path,
            large_path: img.large_path,
            alt: img.alt,
            is_primary: Boolean(img.is_primary),
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
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load product details.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchMetadata()
  await fetchProduct()
})

// Option & Variant tools
function addOptionType() {
  form.value.options.push({ name: '', values: [] })
}

function removeOptionType(index) {
  form.value.options.splice(index, 1)
}

function addOptionValue(option) {
  option.values.push({ name: '', hex_color: null })
}

function removeOptionValue(option, valIndex) {
  option.values.splice(valIndex, 1)
}

function generateVariantCombinations() {
  if (form.value.options.length === 0) return

  const validOptions = form.value.options.filter(
    (o) => o.name && o.values.some((v) => v.name)
  )
  if (validOptions.length === 0) return

  // Cartesian product
  const cartesian = (arrays) => {
    return arrays.reduce(
      (acc, curr) => acc.flatMap((d) => curr.map((e) => [...d, e])),
      [[]]
    )
  }

  const valuesArrays = validOptions.map((opt) =>
    opt.values.filter((v) => v.name).map((v) => ({ type: opt.name, value: v.name }))
  )

  const combinations = cartesian(valuesArrays)
  const baseSku = form.value.sku || form.value.name.replace(/\s+/g, '-').toUpperCase()

  form.value.variants = combinations.map((combo, idx) => {
    const comboMap = {}
    let skuSuffix = ''
    combo.forEach((c) => {
      comboMap[c.type] = c.value
      skuSuffix += `-${c.value.substring(0, 3).toUpperCase()}`
    })

    return {
      sku: `${baseSku}${skuSuffix}`,
      price: form.value.base_price,
      compare_at_price: form.value.compare_at_price,
      stock_qty: 10,
      is_active: true,
      option_values_json: comboMap,
    }
  })
}

function applyBulkVariantEdit() {
  const val = Number(bulkActionValue.value)
  if (isNaN(val)) return

  form.value.variants.forEach((v) => {
    if (bulkActionType.value === 'set_price') {
      v.price = val
    } else if (bulkActionType.value === 'increase_price_percent') {
      v.price = Number((v.price * (1 + val / 100)).toFixed(2))
    } else if (bulkActionType.value === 'set_stock') {
      v.stock_qty = Math.round(val)
    }
  })
  bulkActionValue.value = ''
}

// Media uploads
async function handleImageUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  uploadingImage.value = true
  const formData = new FormData()
  formData.append('image', file)

  try {
    const res = await axios.post('/api/admin/products/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    form.value.images.push({
      url: res.data.url,
      path: res.data.path,
      thumb_path: res.data.thumb_path,
      medium_path: res.data.medium_path,
      large_path: res.data.large_path,
      alt: form.value.name || 'Product Image',
      is_primary: form.value.images.length === 0,
    })
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Image upload failed.'
  } finally {
    uploadingImage.value = false
    e.target.value = ''
  }
}

function addExternalImageUrl(url) {
  if (!url) return
  form.value.images.push({
    url: url,
    alt: form.value.name || 'Product Image',
    is_primary: form.value.images.length === 0,
  })
}

function setPrimaryImage(index) {
  form.value.images.forEach((img, idx) => {
    img.is_primary = idx === index
  })
}

function removeImage(index) {
  form.value.images.splice(index, 1)
  if (form.value.images.length > 0 && !form.value.images.some((i) => i.is_primary)) {
    form.value.images[0].is_primary = true
  }
}

// Section & Spec tools
function addSection() {
  form.value.sections.push({ title: '', content: '', is_open_by_default: false })
}
function removeSection(index) {
  form.value.sections.splice(index, 1)
}

function addSpec() {
  form.value.specs.push({ label: '', value: '' })
}
function removeSpec(index) {
  form.value.specs.splice(index, 1)
}

// Save product
async function handleSave() {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const tags = tagsInput.value
      ? tagsInput.value.split(',').map((t) => t.trim()).filter(Boolean)
      : []

    const payload = {
      ...form.value,
      tags,
      brand_id: form.value.brand_id ? Number(form.value.brand_id) : null,
      category_id: form.value.category_id ? Number(form.value.category_id) : null,
    }

    if (isEdit.value) {
      await axios.put(`/api/admin/products/${productId.value}`, payload)
      successMessage.value = 'Product updated successfully.'
    } else {
      const res = await axios.post('/api/admin/products', payload)
      successMessage.value = 'Product created successfully.'
      setTimeout(() => {
        router.push(`/admin/products/${res.data.data.id}/edit`)
      }, 700)
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save product.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-3">
        <button
          class="p-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 transition-colors"
          @click="router.push('/admin/products')"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            {{ isEdit ? 'Edit Product' : 'Add New Product' }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ form.name || 'Untitled Product' }} · {{ form.sku || 'No SKU' }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          :disabled="saving"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#2F5D50] text-white text-xs font-semibold hover:bg-[#264a40] disabled:opacity-50 transition-colors shadow-sm"
          @click="handleSave"
        >
          <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
          <Save v-else class="w-4 h-4" />
          <span>{{ isEdit ? 'Save Changes' : 'Publish Product' }}</span>
        </button>
      </div>
    </div>

    <!-- Alerts -->
    <div
      v-if="successMessage"
      class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between"
    >
      <div class="flex items-center gap-2 text-sm font-medium">
        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
        <span>{{ successMessage }}</span>
      </div>
      <button class="text-xs text-emerald-700 hover:underline" @click="successMessage = ''">Dismiss</button>
    </div>

    <div
      v-if="errorMessage"
      class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between"
    >
      <div class="flex items-center gap-2 text-sm font-medium">
        <AlertCircle class="w-4 h-4 text-rose-600" />
        <span>{{ errorMessage }}</span>
      </div>
      <button class="text-xs text-rose-700 hover:underline" @click="errorMessage = ''">Dismiss</button>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-gray-200 gap-1 overflow-x-auto text-xs font-medium">
      <button
        v-for="tab in [
          { id: 'basics', label: '1. Basics & Description', icon: FileText },
          { id: 'pricing', label: '2. Pricing & Inventory', icon: Sliders },
          { id: 'variations', label: '3. Variations & Options', icon: Layers },
          { id: 'media', label: '4. Images & Video', icon: ImageIcon },
          { id: 'sections', label: '5. Detail Accordions', icon: FileText },
          { id: 'specs', label: '6. Specifications', icon: Tag },
          { id: 'seo', label: '7. SEO & Meta', icon: Globe },
        ]"
        :key="tab.id"
        class="flex items-center gap-2 px-4 py-3 border-b-2 transition-colors whitespace-nowrap"
        :class="[
          activeTab === tab.id
            ? 'border-[#2F5D50] text-[#2F5D50] font-bold'
            : 'border-transparent text-gray-500 hover:text-gray-900',
        ]"
        @click="activeTab = tab.id"
      >
        <component :is="tab.icon" class="w-3.5 h-3.5" />
        <span>{{ tab.label }}</span>
      </button>
    </div>

    <!-- Form Content Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
      <!-- 1. BASICS & DESCRIPTIONS -->
      <div v-if="activeTab === 'basics'" class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Product Title *</label>
            <input
              v-model="form.name"
              required
              type="text"
              placeholder="e.g. Organic Washed Linen Shirt"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-[#2F5D50]"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">SKU (Stock Keeping Unit)</label>
            <input
              v-model="form.sku"
              type="text"
              placeholder="AUTO-GENERATED"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm font-mono"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Slug (URL identifier)</label>
            <input
              v-model="form.slug"
              type="text"
              placeholder="auto-generated-from-name"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm font-mono"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Primary Category</label>
            <select
              v-model="form.category_id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white"
            >
              <option value="">Select Category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Brand</label>
            <select
              v-model="form.brand_id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white"
            >
              <option value="">No Brand (In-house)</option>
              <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Publication Status</label>
            <select
              v-model="form.status"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white"
            >
              <option value="active">Active (Visible)</option>
              <option value="draft">Draft (Hidden)</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <div class="flex items-center pt-6">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold uppercase text-gray-700">
              <input v-model="form.is_featured" type="checkbox" class="rounded text-[#2F5D50]" />
              <span>Mark as Featured Product</span>
            </label>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tags (Comma-separated)</label>
            <input
              v-model="tagsInput"
              type="text"
              placeholder="e.g. linen, summer, relaxed fit, sustainable"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Short Description</label>
            <textarea
              v-model="form.short_description"
              rows="2"
              placeholder="Brief 1-2 sentence hook for product card and search snippets"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm"
            ></textarea>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Full Rich Description (HTML allowed)</label>
            <textarea
              v-model="form.description"
              rows="6"
              placeholder="Full product editorial story, craftsmanship details, styling suggestions..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm font-mono"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- 2. PRICING & INVENTORY -->
      <div v-if="activeTab === 'pricing'" class="space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Base Price ($) *</label>
            <input
              v-model="form.base_price"
              type="number"
              step="0.01"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm font-mono font-bold"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Compare-at Price ($) (Sale)</label>
            <input
              v-model="form.compare_at_price"
              type="number"
              step="0.01"
              placeholder="Leave empty if not on sale"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm font-mono"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Cost Price ($) (Internal)</label>
            <input
              v-model="form.cost_price"
              type="number"
              step="0.01"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm font-mono"
            />
          </div>
        </div>

        <div class="pt-4 border-t border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Stock Quantity</label>
            <input
              v-model="form.stock_qty"
              type="number"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Low Stock Alert Threshold</label>
            <input
              v-model="form.low_stock_threshold"
              type="number"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm"
            />
          </div>
          <div class="flex items-center pt-6">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold uppercase text-gray-700">
              <input v-model="form.allow_backorder" type="checkbox" class="rounded text-[#2F5D50]" />
              <span>Allow Backorders When Out of Stock</span>
            </label>
          </div>
        </div>
      </div>

      <!-- 3. VARIATIONS & OPTIONS -->
      <div v-if="activeTab === 'variations'" class="space-y-6">
        <!-- Define Option Types -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Product Option Types (e.g. Size, Color)</h3>
            <button
              type="button"
              class="text-xs font-semibold text-[#2F5D50] hover:underline flex items-center gap-1"
              @click="addOptionType"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Add Option Type</span>
            </button>
          </div>

          <div v-for="(opt, oIdx) in form.options" :key="oIdx" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-3">
            <div class="flex items-center justify-between">
              <input
                v-model="opt.name"
                type="text"
                placeholder="Option name (e.g. Color, Size, Material)"
                class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-bold w-64 bg-white"
              />
              <button class="text-rose-500 hover:text-rose-700 text-xs" @click="removeOptionType(oIdx)">Remove</button>
            </div>

            <!-- Option Values -->
            <div class="flex flex-wrap items-center gap-2">
              <div
                v-for="(val, vIdx) in opt.values"
                :key="vIdx"
                class="flex items-center gap-1.5 p-1.5 rounded-lg bg-white border border-gray-200 text-xs"
              >
                <input
                  v-if="opt.name.toLowerCase().includes('color')"
                  v-model="val.hex_color"
                  type="color"
                  class="w-5 h-5 rounded cursor-pointer border-0"
                />
                <input
                  v-model="val.name"
                  type="text"
                  placeholder="Value"
                  class="w-20 px-1 py-0.5 text-xs border-0 focus:ring-0"
                />
                <button class="text-gray-400 hover:text-gray-600 px-1" @click="removeOptionValue(opt, vIdx)">✕</button>
              </div>
              <button
                type="button"
                class="px-2.5 py-1 rounded-lg border border-dashed border-gray-300 text-xs text-gray-500 hover:bg-gray-100"
                @click="addOptionValue(opt)"
              >
                + Add Value
              </button>
            </div>
          </div>

          <button
            v-if="form.options.length > 0"
            type="button"
            class="px-4 py-2 rounded-full bg-gray-900 text-white text-xs font-semibold hover:bg-black inline-flex items-center gap-1.5"
            @click="generateVariantCombinations"
          >
            <Sparkles class="w-3.5 h-3.5" />
            <span>Generate Variant Combinations</span>
          </button>
        </div>

        <!-- Variants Table & Bulk Edit Tool -->
        <div v-if="form.variants.length > 0" class="pt-6 border-t border-gray-100 space-y-4">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50 p-3 rounded-xl">
            <span class="text-xs font-bold text-gray-700">{{ form.variants.length }} Generated Variants</span>

            <!-- Bulk Edit Tool -->
            <div class="flex items-center gap-2">
              <select v-model="bulkActionType" class="text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white">
                <option value="set_price">Set Price for All</option>
                <option value="increase_price_percent">Increase Price by %</option>
                <option value="set_stock">Set Stock for All</option>
              </select>
              <input
                v-model="bulkActionValue"
                type="number"
                placeholder="Value..."
                class="w-24 text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white font-mono"
              />
              <button
                type="button"
                class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-xs font-semibold"
                @click="applyBulkVariantEdit"
              >
                Apply
              </button>
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-xs text-left">
              <thead class="bg-gray-50 text-gray-500 uppercase font-semibold">
                <tr>
                  <th class="px-4 py-2.5">Variant / SKU</th>
                  <th class="px-4 py-2.5">Price ($)</th>
                  <th class="px-4 py-2.5">Compare Price ($)</th>
                  <th class="px-4 py-2.5">Stock</th>
                  <th class="px-4 py-2.5">Active</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(v, idx) in form.variants" :key="idx">
                  <td class="px-4 py-2">
                    <input v-model="v.sku" type="text" class="w-full px-2 py-1 rounded border border-gray-200 font-mono text-xs" />
                  </td>
                  <td class="px-4 py-2">
                    <input v-model="v.price" type="number" step="0.01" class="w-24 px-2 py-1 rounded border border-gray-200 font-mono text-xs" />
                  </td>
                  <td class="px-4 py-2">
                    <input v-model="v.compare_at_price" type="number" step="0.01" class="w-24 px-2 py-1 rounded border border-gray-200 font-mono text-xs" />
                  </td>
                  <td class="px-4 py-2">
                    <input v-model="v.stock_qty" type="number" class="w-20 px-2 py-1 rounded border border-gray-200 text-xs" />
                  </td>
                  <td class="px-4 py-2">
                    <input v-model="v.is_active" type="checkbox" class="rounded text-[#2F5D50]" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 4. MEDIA (IMAGES & VIDEO) -->
      <div v-if="activeTab === 'media'" class="space-y-6">
        <!-- Images Grid -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Product Images (Converted to WebP: 300px, 800px, 1600px)</h3>
            <label
              class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#2F5D50] text-white text-xs font-semibold hover:bg-[#264a40]"
            >
              <Upload class="w-3.5 h-3.5" />
              <span>{{ uploadingImage ? 'Processing...' : 'Upload Image' }}</span>
              <input type="file" class="hidden" accept="image/*" @change="handleImageUpload" />
            </label>
          </div>

          <div v-if="form.images.length === 0" class="p-8 text-center text-gray-400 border border-dashed rounded-xl">
            No images uploaded yet. Upload a photo or paste a URL below.
          </div>

          <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
              v-for="(img, idx) in form.images"
              :key="idx"
              class="relative rounded-xl border border-gray-200 overflow-hidden bg-gray-50 group"
            >
              <img :src="img.thumb_url || img.url" class="w-full h-36 object-cover" />
              <div class="p-2 space-y-1">
                <input
                  v-model="img.alt"
                  type="text"
                  placeholder="Alt text"
                  class="w-full text-[10px] px-2 py-1 rounded border border-gray-200"
                />
                <div class="flex items-center justify-between pt-1">
                  <button
                    type="button"
                    class="text-[10px] font-semibold"
                    :class="img.is_primary ? 'text-emerald-700' : 'text-gray-400 hover:text-gray-700'"
                    @click="setPrimaryImage(idx)"
                  >
                    {{ img.is_primary ? '★ Primary' : 'Set Primary' }}
                  </button>
                  <button type="button" class="text-rose-500 hover:text-rose-700 text-xs" @click="removeImage(idx)">
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Video Settings -->
        <div class="pt-6 border-t border-gray-100 space-y-3">
          <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
            <Video class="w-4 h-4 text-[#2F5D50]" />
            <span>Product Hover / Gallery Video</span>
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Video Direct URL (MP4)</label>
              <input
                v-model="form.video_url"
                type="text"
                placeholder="https://.../video.mp4"
                class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs font-mono"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Poster Image URL (Optional)</label>
              <input
                v-model="form.video_poster"
                type="text"
                placeholder="https://.../poster.jpg"
                class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs font-mono"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 5. DETAIL SECTIONS (ACCORDIONS) -->
      <div v-if="activeTab === 'sections'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-gray-900">Click-to-Open Accordion Sections</h3>
          <button
            type="button"
            class="text-xs font-semibold text-[#2F5D50] hover:underline flex items-center gap-1"
            @click="addSection"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Accordion Section</span>
          </button>
        </div>

        <div v-for="(sec, sIdx) in form.sections" :key="sIdx" class="p-4 rounded-xl border border-gray-200 space-y-2">
          <div class="flex items-center justify-between">
            <input
              v-model="sec.title"
              type="text"
              placeholder="Section Title (e.g. Materials & Care)"
              class="w-72 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-bold"
            />
            <div class="flex items-center gap-3">
              <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                <input v-model="sec.is_open_by_default" type="checkbox" class="rounded text-[#2F5D50]" />
                <span>Open by default</span>
              </label>
              <button type="button" class="text-rose-500 text-xs" @click="removeSection(sIdx)">Remove</button>
            </div>
          </div>
          <textarea
            v-model="sec.content"
            rows="3"
            placeholder="Detailed text / bullet points for this accordion block..."
            class="w-full px-3 py-2 rounded-lg border border-gray-200 text-xs"
          ></textarea>
        </div>
      </div>

      <!-- 6. SPECIFICATIONS -->
      <div v-if="activeTab === 'specs'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-gray-900">Technical Specifications</h3>
          <button
            type="button"
            class="text-xs font-semibold text-[#2F5D50] hover:underline flex items-center gap-1"
            @click="addSpec"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Specification</span>
          </button>
        </div>

        <div v-for="(sp, idx) in form.specs" :key="idx" class="flex items-center gap-3">
          <input
            v-model="sp.label"
            type="text"
            placeholder="Label (e.g. Weight, Material, Origin)"
            class="w-48 px-3 py-2 rounded-xl border border-gray-200 text-xs font-medium"
          />
          <input
            v-model="sp.value"
            type="text"
            placeholder="Value (e.g. 185 GSM, 100% Linen)"
            class="flex-1 px-3 py-2 rounded-xl border border-gray-200 text-xs"
          />
          <button type="button" class="text-rose-500 text-xs" @click="removeSpec(idx)">✕</button>
        </div>
      </div>

      <!-- 7. SEO & META -->
      <div v-if="activeTab === 'seo'" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Custom SEO Title</label>
          <input
            v-model="form.seo_title"
            type="text"
            placeholder="Product Page Title"
            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">SEO Meta Description</label>
          <textarea
            v-model="form.seo_description"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm"
          ></textarea>
        </div>
      </div>
    </div>
  </div>
</template>
