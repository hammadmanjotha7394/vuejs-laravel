<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import DataTable from '@/components/admin/DataTable.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'
import ToggleSwitch from '@/components/admin/ToggleSwitch.vue'
import {
  Plus,
  Download,
  Upload,
  Copy,
  Pencil,
  Trash2,
  Image as ImageIcon,
  ExternalLink,
  Loader2,
  X,
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const toast = useToastStore()

useHead({
  title: () => `Products Catalog | Admin | ${settingsStore.siteName}`,
})

const products = ref([])
const categories = ref([])
const brands = ref([])
const loading = ref(true)

const page = ref(1)
const total = ref(0)
const lastPage = ref(1)

const searchQuery = ref('')
const selectedCategory = ref('')
const selectedBrand = ref('')
const selectedStatus = ref('')
const selectedStockFilter = ref('')
const sortBy = ref('id')
const sortDir = ref('desc')

const selectedIds = ref([])
const bulkAction = ref('')
const bulkCategoryId = ref('')
const executingBulk = ref(false)

// Confirm Delete Dialog State
const deleteDialogOpen = ref(false)
const deletingProduct = ref(null)
const isBulkDelete = ref(false)
const deletingLoading = ref(false)

// CSV Import Modal State
const isImportModalOpen = ref(false)
const importFile = ref(null)
const importing = ref(false)

const columns = [
  { key: 'image', label: 'Image', sortable: false, class: 'w-16' },
  { key: 'name', label: 'Product / SKU', sortable: true },
  { key: 'category', label: 'Category', sortable: false },
  { key: 'base_price', label: 'Price', sortable: true },
  { key: 'stock_qty', label: 'Stock', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'toggles', label: 'Active / Featured', sortable: false, align: 'center' },
  { key: 'actions', label: 'Actions', sortable: false, align: 'right' },
]

const fetchMetadata = async () => {
  try {
    const [catRes, brandRes] = await Promise.all([
      apiClient.get('/admin/categories').catch(() => ({ data: { data: [] } })),
      apiClient.get('/admin/brands').catch(() => ({ data: { data: [] } })),
    ])
    categories.value = catRes.data?.data || []
    brands.value = brandRes.data?.data || []
  } catch (e) {
    // Handled gracefully
  }
}

const fetchProducts = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/admin/products', {
      params: {
        page: page.value,
        search: searchQuery.value || undefined,
        category_id: selectedCategory.value || undefined,
        brand_id: selectedBrand.value || undefined,
        status: selectedStatus.value || undefined,
        stock_filter: selectedStockFilter.value || undefined,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
      },
    })
    products.value = res.data?.data || []
    total.value = res.data?.total || 0
    lastPage.value = res.data?.last_page || 1
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load products.')
  } finally {
    loading.value = false
  }
}

let searchTimer = null
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    fetchProducts()
  }, 300)
})

watch([selectedCategory, selectedBrand, selectedStatus, selectedStockFilter], () => {
  page.value = 1
  fetchProducts()
})

const handleSort = ({ key, dir }) => {
  sortBy.value = key
  sortDir.value = dir
  page.value = 1
  fetchProducts()
}

const handlePageChange = (nextPage) => {
  page.value = nextPage
  fetchProducts()
}

const handleQuickToggleStatus = async (product, isActive) => {
  const nextStatus = isActive ? 'active' : 'draft'
  const prevStatus = product.status
  product.status = nextStatus

  try {
    await apiClient.patch(`/admin/products/${product.id}/quick-toggle`, {
      status: nextStatus,
    })
    toast.success(`"${product.name}" marked as ${nextStatus}.`)
  } catch (err) {
    product.status = prevStatus
    toast.error(err.response?.data?.message || 'Unable to update status.')
  }
}

const handleQuickToggleFeatured = async (product, isFeatured) => {
  const prev = product.is_featured
  product.is_featured = isFeatured

  try {
    await apiClient.patch(`/admin/products/${product.id}/quick-toggle`, {
      is_featured: isFeatured,
    })
    toast.success(
      isFeatured
        ? `"${product.name}" added to featured products.`
        : `"${product.name}" removed from featured products.`
    )
  } catch (err) {
    product.is_featured = prev
    toast.error(err.response?.data?.message || 'Unable to update featured status.')
  }
}

const handleDuplicate = async (product) => {
  try {
    const res = await apiClient.post(`/admin/products/${product.id}/duplicate`)
    toast.success(`Duplicated as "${res.data?.data?.name}".`)
    await fetchProducts()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to duplicate product.')
  }
}

const promptDeleteProduct = (product) => {
  isBulkDelete.value = false
  deletingProduct.value = product
  deleteDialogOpen.value = true
}

const handleBulkApply = async () => {
  if (!bulkAction.value || selectedIds.value.length === 0) return

  if (bulkAction.value === 'delete') {
    isBulkDelete.value = true
    deletingProduct.value = null
    deleteDialogOpen.value = true
    return
  }

  if (bulkAction.value === 'change_category' && !bulkCategoryId.value) {
    toast.error('Please choose a target category.')
    return
  }

  executingBulk.value = true
  try {
    await apiClient.post('/admin/products/bulk-action', {
      ids: selectedIds.value,
      action: bulkAction.value,
      category_id: bulkAction.value === 'change_category' ? Number(bulkCategoryId.value) : undefined,
    })
    toast.success('Bulk action applied successfully.')
    selectedIds.value = []
    bulkAction.value = ''
    bulkCategoryId.value = ''
    await fetchProducts()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Bulk action failed.')
  } finally {
    executingBulk.value = false
  }
}

const confirmDelete = async () => {
  deletingLoading.value = true
  try {
    if (isBulkDelete.value) {
      await apiClient.post('/admin/products/bulk-action', {
        ids: selectedIds.value,
        action: 'delete',
      })
      toast.success(`Deleted ${selectedIds.value.length} products.`)
      selectedIds.value = []
      bulkAction.value = ''
    } else if (deletingProduct.value) {
      await apiClient.delete(`/admin/products/${deletingProduct.value.id}`)
      toast.success(`Deleted "${deletingProduct.value.name}".`)
    }
    deleteDialogOpen.value = false
    await fetchProducts()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete product.')
  } finally {
    deletingLoading.value = false
  }
}

const handleImportCsv = async () => {
  if (!importFile.value) return
  importing.value = true

  const formData = new FormData()
  formData.append('csv_file', importFile.value)

  try {
    const res = await apiClient.post('/admin/products/import-csv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    toast.success(res.data?.message || 'CSV imported successfully.')
    isImportModalOpen.value = false
    importFile.value = null
    await fetchProducts()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to import CSV.')
  } finally {
    importing.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchMetadata(), fetchProducts()])
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">Products Catalog</h1>
        <p class="text-xs text-ink-muted mt-0.5">
          Manage inventory, variations, pricing, media galleries, and SEO metadata
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a
          href="/api/admin/products/export-csv"
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-white text-ink hover:border-brand hover:text-brand text-xs font-semibold transition"
        >
          <Download class="w-3.5 h-3.5" />
          <span>Export CSV</span>
        </a>

        <button
          v-if="authStore.hasPermission('products.create')"
          type="button"
          @click="isImportModalOpen = true"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-white text-ink hover:border-brand hover:text-brand text-xs font-semibold transition"
        >
          <Upload class="w-3.5 h-3.5" />
          <span>Import CSV</span>
        </button>

        <RouterLink
          v-if="authStore.hasPermission('products.create')"
          to="/admin/products/create"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition shadow-2xs"
        >
          <Plus class="w-4 h-4" />
          <span>Add Product</span>
        </RouterLink>
      </div>
    </div>

    <!-- Products DataTable -->
    <DataTable
      :columns="columns"
      :rows="products"
      :loading="loading"
      searchable
      v-model:search="searchQuery"
      search-placeholder="Search by product name or SKU..."
      selectable
      v-model:selectedIds="selectedIds"
      :sort-by="sortBy"
      :sort-dir="sortDir"
      :page="page"
      :last-page="lastPage"
      :total="total"
      empty-title="No products match your filters"
      empty-description="Try clearing your search or add a new product to the catalog."
      @sort="handleSort"
      @page-change="handlePageChange"
    >
      <!-- Filter Dropdowns -->
      <template #filters>
        <select
          v-model="selectedCategory"
          class="px-3 py-2 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
        >
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>

        <select
          v-if="brands.length"
          v-model="selectedBrand"
          class="px-3 py-2 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
        >
          <option value="">All Brands</option>
          <option v-for="b in brands" :key="b.id" :value="b.id">
            {{ b.name }}
          </option>
        </select>

        <select
          v-model="selectedStatus"
          class="px-3 py-2 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="draft">Draft</option>
          <option value="archived">Archived</option>
        </select>

        <select
          v-model="selectedStockFilter"
          class="px-3 py-2 rounded-xl border border-line bg-white text-xs text-ink focus:outline-none focus:border-brand"
        >
          <option value="">All Stock Levels</option>
          <option value="low">Low Stock Alert</option>
          <option value="out">Out of Stock (0)</option>
        </select>
      </template>

      <!-- Bulk Actions Slot -->
      <template #bulk-actions>
        <select
          v-model="bulkAction"
          class="px-3 py-1.5 rounded-xl border border-line bg-white text-xs text-ink"
        >
          <option value="">Choose Bulk Action...</option>
          <option value="publish">Set Active (Publish)</option>
          <option value="archive">Archive Selected</option>
          <option value="change_category">Move to Category...</option>
          <option v-if="authStore.hasPermission('products.delete')" value="delete">
            Delete Selected
          </option>
        </select>

        <select
          v-if="bulkAction === 'change_category'"
          v-model="bulkCategoryId"
          class="px-3 py-1.5 rounded-xl border border-line bg-white text-xs text-ink"
        >
          <option value="">Select Category...</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>

        <button
          type="button"
          :disabled="!bulkAction || executingBulk"
          @click="handleBulkApply"
          class="px-4 py-1.5 rounded-pill bg-ink text-white text-xs font-semibold hover:bg-ink/90 disabled:opacity-40 transition"
        >
          {{ executingBulk ? 'Applying...' : 'Apply' }}
        </button>
      </template>

      <!-- Image Cell -->
      <template #cell-image="{ row }">
        <div class="w-11 h-11 rounded-xl bg-surface border border-line overflow-hidden flex items-center justify-center shrink-0">
          <img
            v-if="row.primary_image"
            :src="row.primary_image.thumb_url || row.primary_image.url"
            :alt="row.name"
            class="w-full h-full object-cover"
          />
          <ImageIcon v-else class="w-4 h-4 text-ink-muted" />
        </div>
      </template>

      <!-- Name & SKU Cell -->
      <template #cell-name="{ row }">
        <div class="min-w-[180px]">
          <RouterLink
            :to="`/admin/products/${row.id}/edit`"
            class="font-bold text-ink hover:text-brand transition block leading-snug"
          >
            {{ row.name }}
          </RouterLink>
          <div class="flex items-center gap-2 mt-0.5 text-[11px] text-ink-muted font-mono">
            <span>{{ row.sku }}</span>
            <span
              v-if="row.variants_count > 0"
              class="px-1.5 py-0.2 rounded bg-brand/10 text-brand font-sans font-semibold text-[10px]"
            >
              {{ row.variants_count }} variants
            </span>
          </div>
        </div>
      </template>

      <!-- Category & Brand Cell -->
      <template #cell-category="{ row }">
        <div>
          <p class="font-semibold text-ink">{{ row.category?.name || 'Uncategorized' }}</p>
          <p v-if="row.brand" class="text-[11px] text-ink-muted">{{ row.brand.name }}</p>
        </div>
      </template>

      <!-- Price Cell -->
      <template #cell-base_price="{ row }">
        <div class="font-mono">
          <p class="font-bold text-ink">{{ settingsStore.formatPrice(row.base_price) }}</p>
          <p
            v-if="row.compare_at_price && Number(row.compare_at_price) > Number(row.base_price)"
            class="text-[11px] text-ink-muted line-through"
          >
            {{ settingsStore.formatPrice(row.compare_at_price) }}
          </p>
        </div>
      </template>

      <!-- Stock Cell (Red if low) -->
      <template #cell-stock_qty="{ row }">
        <span
          class="inline-flex items-center px-2.5 py-0.5 rounded-pill text-[11px] font-bold border"
          :class="
            row.stock_qty <= 0
              ? 'bg-red-50 text-error border-red-200'
              : row.stock_qty <= (row.low_stock_threshold ?? 5)
                ? 'bg-rose-50 text-rose-700 border-rose-200'
                : 'bg-emerald-50 text-emerald-800 border-emerald-200'
          "
        >
          {{ row.stock_qty }} in stock
        </span>
      </template>

      <!-- Status Pill Cell -->
      <template #cell-status="{ row }">
        <span
          class="px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border capitalize"
          :class="
            row.status === 'active' || row.status === 'published'
              ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
              : row.status === 'draft'
                ? 'bg-amber-50 text-amber-800 border-amber-200'
                : 'bg-gray-100 text-gray-700 border-gray-300'
          "
        >
          {{ row.status }}
        </span>
      </template>

      <!-- Quick Toggles Cell (Active / Featured) -->
      <template #cell-toggles="{ row }">
        <div
          v-if="authStore.hasPermission('products.update')"
          class="inline-flex items-center gap-4"
          @click.stop
        >
          <div class="flex flex-col items-center gap-1" title="Toggle Active / Draft">
            <ToggleSwitch
              :model-value="row.status === 'active' || row.status === 'published'"
              @change="(val) => handleQuickToggleStatus(row, val)"
            />
            <span class="text-[10px] text-ink-muted">Active</span>
          </div>

          <div class="flex flex-col items-center gap-1" title="Toggle Featured on Homepage">
            <ToggleSwitch
              :model-value="Boolean(row.is_featured)"
              @change="(val) => handleQuickToggleFeatured(row, val)"
            />
            <span class="text-[10px] text-ink-muted">Featured</span>
          </div>
        </div>
      </template>

      <!-- Row Actions Cell -->
      <template #cell-actions="{ row }">
        <div class="inline-flex items-center justify-end gap-1" @click.stop>
          <a
            :href="`/products/${row.slug}`"
            target="_blank"
            rel="noopener noreferrer"
            class="p-1.5 rounded-lg text-ink-muted hover:text-brand hover:bg-surface transition"
            title="Preview on Storefront"
          >
            <ExternalLink class="w-4 h-4" />
          </a>

          <button
            v-if="authStore.hasPermission('products.update')"
            type="button"
            @click="router.push(`/admin/products/${row.id}/edit`)"
            class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-surface transition"
            title="Edit Product"
          >
            <Pencil class="w-4 h-4" />
          </button>

          <button
            v-if="authStore.hasPermission('products.create')"
            type="button"
            @click="handleDuplicate(row)"
            class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-surface transition"
            title="Duplicate Product"
          >
            <Copy class="w-4 h-4" />
          </button>

          <button
            v-if="authStore.hasPermission('products.delete')"
            type="button"
            @click="promptDeleteProduct(row)"
            class="p-1.5 rounded-lg text-ink-muted hover:text-error hover:bg-red-50 transition"
            title="Delete Product"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      :open="deleteDialogOpen"
      :title="isBulkDelete ? `Delete ${selectedIds.length} Products` : 'Delete Product'"
      :message="
        isBulkDelete
          ? `Are you sure you want to delete the ${selectedIds.length} selected products? They will be removed from the active storefront.`
          : `Are you sure you want to delete &quot;${deletingProduct?.name}&quot;?`
      "
      confirm-text="Delete"
      variant="danger"
      :loading="deletingLoading"
      @close="deleteDialogOpen = false"
      @confirm="confirmDelete"
    />

    <!-- CSV Import Modal -->
    <div
      v-if="isImportModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
      @click.self="isImportModalOpen = false"
    >
      <div class="bg-white rounded-card border border-line max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-line pb-3">
          <h3 class="text-base font-bold text-ink">Import Products from CSV</h3>
          <button
            type="button"
            class="p-1 rounded-full text-ink-muted hover:text-ink"
            @click="isImportModalOpen = false"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <p class="text-xs text-ink-muted leading-relaxed">
          Upload a standard product CSV file (<span class="font-mono text-ink">ID, Name, SKU, Slug, Price, Compare Price, Stock Qty</span>). Matching SKUs will be updated and new SKUs created.
        </p>

        <input
          type="file"
          accept=".csv,text/csv"
          class="w-full text-xs text-ink-muted file:mr-3 file:py-2 file:px-4 file:rounded-pill file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-ink hover:file:bg-gray-200 cursor-pointer"
          @change="importFile = $event.target.files[0]"
        />

        <div class="pt-3 border-t border-line flex justify-end gap-2.5">
          <button
            type="button"
            class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted hover:text-ink"
            @click="isImportModalOpen = false"
          >
            Cancel
          </button>
          <button
            type="button"
            :disabled="!importFile || importing"
            class="px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark disabled:opacity-50 inline-flex items-center gap-1.5 transition"
            @click="handleImportCsv"
          >
            <Loader2 v-if="importing" class="w-3.5 h-3.5 animate-spin" />
            <span>Upload & Import</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
