<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import {
  Package,
  Plus,
  Search,
  Filter,
  Download,
  Upload,
  Copy,
  Edit2,
  Trash2,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Image as ImageIcon,
  ExternalLink,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const products = ref([])
const categories = ref([])
const loading = ref(true)
const page = ref(1)
const total = ref(0)
const lastPage = ref(1)

const searchQuery = ref('')
const selectedCategory = ref('')
const selectedStatus = ref('')

const selectedIds = ref([])
const bulkAction = ref('')
const executingBulk = ref(false)

const isImportModalOpen = ref(false)
const importFile = ref(null)
const importing = ref(false)

const successMessage = ref('')
const errorMessage = ref('')

async function fetchCategories() {
  try {
    const res = await axios.get('/api/admin/categories')
    categories.value = res.data.data
  } catch (err) {
    console.error(err)
  }
}

async function fetchProducts() {
  loading.value = true
  try {
    const params = {
      page: page.value,
      search: searchQuery.value || undefined,
      category_id: selectedCategory.value || undefined,
      status: selectedStatus.value || undefined,
    }
    const res = await axios.get('/api/admin/products', { params })
    products.value = res.data.data
    total.value = res.data.total
    lastPage.value = res.data.last_page
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load products.'
  } finally {
    loading.value = false
  }
}

watch([selectedCategory, selectedStatus], () => {
  page.value = 1
  fetchProducts()
})

let searchTimeout = null
function handleSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchProducts()
  }, 350)
}

onMounted(async () => {
  await Promise.all([fetchCategories(), fetchProducts()])
})

function toggleSelectAll(e) {
  if (e.target.checked) {
    selectedIds.value = products.value.map((p) => p.id)
  } else {
    selectedIds.value = []
  }
}

async function handleBulkAction() {
  if (!bulkAction.value || selectedIds.value.length === 0) return
  if (bulkAction.value === 'delete' && !confirm(`Delete ${selectedIds.value.length} selected products?`)) return

  executingBulk.value = true
  try {
    await axios.post('/api/admin/products/bulk-action', {
      ids: selectedIds.value,
      action: bulkAction.value,
    })
    successMessage.value = 'Bulk action applied successfully.'
    selectedIds.value = []
    bulkAction.value = ''
    await fetchProducts()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Bulk action failed.'
  } finally {
    executingBulk.value = false
  }
}

async function handleDuplicate(product) {
  try {
    const res = await axios.post(`/api/admin/products/${product.id}/duplicate`)
    successMessage.value = `Duplicated as "${res.data.data.name}".`
    await fetchProducts()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to duplicate product.'
  }
}

async function handleDelete(product) {
  if (!confirm(`Are you sure you want to delete product "${product.name}"?`)) return

  try {
    await axios.delete(`/api/admin/products/${product.id}`)
    successMessage.value = `Product "${product.name}" deleted.`
    await fetchProducts()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete product.'
  }
}

async function handleImport() {
  if (!importFile.value) return
  importing.value = true

  const formData = new FormData()
  formData.append('csv_file', importFile.value)

  try {
    const res = await axios.post('/api/admin/products/import-csv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    successMessage.value = res.data.message
    isImportModalOpen.value = false
    importFile.value = null
    await fetchProducts()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to import CSV.'
  } finally {
    importing.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Products Catalog</h1>
        <p class="text-sm text-gray-500 mt-1">
          Manage inventory, variations, pricing, media, and detail specifications.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a
          href="/api/admin/products/export-csv"
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 text-xs font-medium transition-colors"
        >
          <Download class="w-3.5 h-3.5" />
          <span>Export CSV</span>
        </a>

        <button
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 text-xs font-medium transition-colors"
          @click="isImportModalOpen = true"
        >
          <Upload class="w-3.5 h-3.5" />
          <span>Import CSV</span>
        </button>

        <button
          v-if="authStore.hasPermission('products.create')"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#2F5D50] text-white text-xs font-medium hover:bg-[#264a40] transition-colors shadow-sm"
          @click="router.push('/admin/products/create')"
        >
          <Plus class="w-4 h-4" />
          <span>Add Product</span>
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

    <!-- Filters & Bulk Action Bar -->
    <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
      <!-- Search & Category Filters -->
      <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
        <div class="relative w-full sm:w-64">
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name, SKU..."
            class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-[#2F5D50]"
            @input="handleSearchInput"
          />
        </div>

        <select
          v-model="selectedCategory"
          class="w-full sm:w-44 px-3 py-2 rounded-xl border border-gray-200 text-xs bg-white"
        >
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>

        <select
          v-model="selectedStatus"
          class="w-full sm:w-36 px-3 py-2 rounded-xl border border-gray-200 text-xs bg-white"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="draft">Draft</option>
          <option value="archived">Archived</option>
        </select>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedIds.length > 0" class="flex items-center gap-2 w-full md:w-auto justify-end">
        <span class="text-xs text-gray-500 font-medium">{{ selectedIds.length }} selected</span>
        <select
          v-model="bulkAction"
          class="px-3 py-1.5 rounded-xl border border-gray-200 text-xs bg-white"
        >
          <option value="">Select Bulk Action</option>
          <option value="publish">Set as Active</option>
          <option value="archive">Archive Selected</option>
          <option value="delete">Delete Selected</option>
        </select>
        <button
          :disabled="!bulkAction || executingBulk"
          class="px-3.5 py-1.5 rounded-full bg-gray-900 text-white text-xs font-medium hover:bg-black disabled:opacity-50"
          @click="handleBulkAction"
        >
          Apply
        </button>
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-gray-400">
        <Loader2 class="w-6 h-6 animate-spin mx-auto mb-2 text-[#2F5D50]" />
        <span>Loading products...</span>
      </div>

      <div v-else-if="products.length === 0" class="p-12 text-center text-gray-400">
        <Package class="w-10 h-10 mx-auto mb-2 text-gray-300" />
        <p>No products match your criteria.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50/70 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3.5 w-10">
                <input
                  type="checkbox"
                  class="rounded text-[#2F5D50]"
                  :checked="selectedIds.length === products.length && products.length > 0"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="px-4 py-3.5">Image</th>
              <th class="px-4 py-3.5">Product / SKU</th>
              <th class="px-4 py-3.5">Category</th>
              <th class="px-4 py-3.5">Price</th>
              <th class="px-4 py-3.5">Stock</th>
              <th class="px-4 py-3.5">Status</th>
              <th class="px-4 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-4 py-3.5">
                <input v-model="selectedIds" type="checkbox" :value="product.id" class="rounded text-[#2F5D50]" />
              </td>

              <!-- Thumbnail -->
              <td class="px-4 py-3.5">
                <div class="w-12 h-12 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center flex-shrink-0">
                  <img
                    v-if="product.primary_image"
                    :src="product.primary_image.thumb_url || product.primary_image.url"
                    :alt="product.name"
                    class="w-full h-full object-cover"
                  />
                  <ImageIcon v-else class="w-4 h-4 text-gray-400" />
                </div>
              </td>

              <!-- Name & SKU -->
              <td class="px-4 py-3.5">
                <div class="font-medium text-gray-900 leading-tight">{{ product.name }}</div>
                <div class="text-xs text-gray-400 font-mono mt-0.5 flex items-center gap-2">
                  <span>{{ product.sku }}</span>
                  <span v-if="product.variants_count > 0" class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded text-[10px]">
                    {{ product.variants_count }} variants
                  </span>
                </div>
              </td>

              <!-- Category & Brand -->
              <td class="px-4 py-3.5 text-xs text-gray-500">
                <div class="font-medium text-gray-700">{{ product.category?.name || 'Uncategorized' }}</div>
                <div v-if="product.brand" class="text-gray-400">{{ product.brand.name }}</div>
              </td>

              <!-- Pricing -->
              <td class="px-4 py-3.5 font-mono text-xs">
                <div class="font-bold text-gray-900">${{ Number(product.base_price).toFixed(2) }}</div>
                <div v-if="product.compare_at_price" class="text-rose-500 line-through">
                  ${{ Number(product.compare_at_price).toFixed(2) }}
                </div>
              </td>

              <!-- Stock -->
              <td class="px-4 py-3.5 text-xs">
                <span
                  class="px-2 py-0.5 rounded-full font-medium"
                  :class="[
                    product.stock_qty <= product.low_stock_threshold
                      ? 'bg-rose-50 text-rose-700 border border-rose-200'
                      : 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                  ]"
                >
                  {{ product.stock_qty }} in stock
                </span>
              </td>

              <!-- Status -->
              <td class="px-4 py-3.5">
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-medium uppercase"
                  :class="[
                    product.status === 'active' || product.status === 'published'
                      ? 'bg-emerald-50 text-emerald-700'
                      : product.status === 'draft'
                      ? 'bg-amber-50 text-amber-700'
                      : 'bg-gray-100 text-gray-500',
                  ]"
                >
                  {{ product.status }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3.5 text-right space-x-1">
                <button
                  class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg"
                  title="Edit Product"
                  @click="router.push(`/admin/products/${product.id}/edit`)"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg"
                  title="Duplicate"
                  @click="handleDuplicate(product)"
                >
                  <Copy class="w-4 h-4" />
                </button>
                <button
                  class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg"
                  title="Delete"
                  @click="handleDelete(product)"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-3.5 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span>Showing page {{ page }} of {{ lastPage }} ({{ total }} total products)</span>
          <div class="flex items-center gap-2">
            <button
              :disabled="page <= 1"
              class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-40"
              @click="page--; fetchProducts()"
            >
              Previous
            </button>
            <button
              :disabled="page >= lastPage"
              class="px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-40"
              @click="page++; fetchProducts()"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- CSV Import Modal -->
    <div
      v-if="isImportModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-base font-bold text-gray-900">Import Products from CSV</h3>
          <button class="text-gray-400 hover:text-gray-600" @click="isImportModalOpen = false">✕</button>
        </div>

        <p class="text-xs text-gray-500">
          Upload a CSV file containing columns: Name, SKU, Price, Stock Qty. New items will be created and matching SKUs updated.
        </p>

        <input
          type="file"
          accept=".csv,text/csv"
          class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-gray-100 hover:file:bg-gray-200 cursor-pointer"
          @change="importFile = $event.target.files[0]"
        />

        <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
          <button
            class="px-4 py-2 rounded-full border border-gray-200 text-xs text-gray-600"
            @click="isImportModalOpen = false"
          >
            Cancel
          </button>
          <button
            :disabled="!importFile || importing"
            class="px-5 py-2 rounded-full bg-[#2F5D50] text-white text-xs font-medium hover:bg-[#264a40] disabled:opacity-50 inline-flex items-center gap-2"
            @click="handleImport"
          >
            <Loader2 v-if="importing" class="w-3.5 h-3.5 animate-spin" />
            <span>Upload & Import</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
