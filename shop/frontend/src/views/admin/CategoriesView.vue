<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import {
  FolderTree,
  Plus,
  Edit2,
  Trash2,
  Upload,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Image as ImageIcon,
  ArrowUp,
  ArrowDown,
  Sparkles,
} from 'lucide-vue-next'

const categories = ref([])
const loading = ref(true)
const saving = ref(false)
const uploading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const isModalOpen = ref(false)
const editingCategory = ref(null)

const form = ref({
  name: '',
  slug: '',
  parent_id: '',
  description: '',
  image: '',
  is_active: true,
  is_featured: false,
  seo_title: '',
  seo_description: '',
  sort_order: 0,
})

const rootCategories = computed(() => {
  return categories.value.filter((c) => !c.parent_id)
})

async function fetchCategories() {
  loading.value = true
  try {
    const res = await axios.get('/api/admin/categories')
    categories.value = res.data.data
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load categories.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCategories()
})

function openCreateModal() {
  editingCategory.value = null
  form.value = {
    name: '',
    slug: '',
    parent_id: '',
    description: '',
    image: '',
    is_active: true,
    is_featured: false,
    seo_title: '',
    seo_description: '',
    sort_order: categories.value.length + 1,
  }
  isModalOpen.value = true
}

function openEditModal(category) {
  editingCategory.value = category
  form.value = {
    name: category.name,
    slug: category.slug,
    parent_id: category.parent_id || '',
    description: category.description || '',
    image: category.image || '',
    is_active: Boolean(category.is_active),
    is_featured: Boolean(category.is_featured),
    seo_title: category.seo_title || '',
    seo_description: category.seo_description || '',
    sort_order: category.sort_order || 0,
  }
  isModalOpen.value = true
}

async function handleSave() {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      ...form.value,
      parent_id: form.value.parent_id ? Number(form.value.parent_id) : null,
    }

    if (editingCategory.value) {
      await axios.put(`/api/admin/categories/${editingCategory.value.id}`, payload)
      successMessage.value = 'Category updated successfully.'
    } else {
      await axios.post('/api/admin/categories', payload)
      successMessage.value = 'Category created successfully.'
    }

    isModalOpen.value = false
    await fetchCategories()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save category.'
  } finally {
    saving.value = false
  }
}

async function handleDelete(category) {
  if (!confirm(`Are you sure you want to delete category "${category.name}"? Sub-categories will be moved to root.`)) {
    return
  }

  try {
    await axios.delete(`/api/admin/categories/${category.id}`)
    successMessage.value = `Category "${category.name}" deleted.`
    await fetchCategories()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete category.'
  }
}

async function moveOrder(category, direction) {
  const currentIndex = categories.value.findIndex((c) => c.id === category.id)
  const targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1

  if (targetIndex < 0 || targetIndex >= categories.value.length) return

  const targetCategory = categories.value[targetIndex]
  const currentSort = category.sort_order
  const targetSort = targetCategory.sort_order

  try {
    await axios.post('/api/admin/categories/reorder', {
      items: [
        { id: category.id, sort_order: targetSort },
        { id: targetCategory.id, sort_order: currentSort },
      ],
    })
    await fetchCategories()
  } catch (err) {
    errorMessage.value = 'Failed to reorder categories.'
  }
}

async function handleImageUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const res = await axios.post('/api/admin/categories/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    form.value.image = res.data.url
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to upload category image.'
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Categories & Hierarchy</h1>
        <p class="text-sm text-gray-500 mt-1">
          Manage root and sub-categories, circular tile images, and storefront ordering.
        </p>
      </div>
      <button
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#2F5D50] text-white text-sm font-medium hover:bg-[#264a40] transition-colors shadow-sm"
        @click="openCreateModal"
      >
        <Plus class="w-4 h-4" />
        <span>Add Category</span>
      </button>
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

    <!-- Category List Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-gray-400">
        <Loader2 class="w-6 h-6 animate-spin mx-auto mb-2 text-[#2F5D50]" />
        <span>Loading category tree...</span>
      </div>

      <div v-else-if="categories.length === 0" class="p-12 text-center text-gray-400">
        <FolderTree class="w-10 h-10 mx-auto mb-2 text-gray-300" />
        <p>No categories found. Create your first category above.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50/70 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-gray-100">
            <tr>
              <th class="px-6 py-3.5">Tile</th>
              <th class="px-6 py-3.5">Name / Slug</th>
              <th class="px-6 py-3.5">Parent Category</th>
              <th class="px-6 py-3.5">Products</th>
              <th class="px-6 py-3.5">Status</th>
              <th class="px-6 py-3.5">Order</th>
              <th class="px-6 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(cat, idx) in categories" :key="cat.id" class="hover:bg-gray-50/50 transition-colors">
              <!-- Circular Image Tile -->
              <td class="px-6 py-3.5">
                <div
                  class="w-10 h-10 rounded-full border border-gray-200 overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0"
                >
                  <img v-if="cat.image" :src="cat.image" :alt="cat.name" class="w-full h-full object-cover" />
                  <ImageIcon v-else class="w-4 h-4 text-gray-400" />
                </div>
              </td>

              <!-- Name & Slug -->
              <td class="px-6 py-3.5 font-medium text-gray-900">
                <div class="flex items-center gap-2">
                  <span v-if="cat.parent_id" class="text-gray-300 select-none">↳</span>
                  <span>{{ cat.name }}</span>
                  <span
                    v-if="cat.is_featured"
                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                  >
                    Featured
                  </span>
                </div>
                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ cat.slug }}</div>
              </td>

              <!-- Parent -->
              <td class="px-6 py-3.5 text-gray-500">
                <span v-if="cat.parent" class="px-2.5 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                  {{ cat.parent.name }}
                </span>
                <span v-else class="text-xs text-gray-400 italic">Root Category</span>
              </td>

              <!-- Products count -->
              <td class="px-6 py-3.5">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ cat.products_count ?? 0 }} products
                </span>
              </td>

              <!-- Status -->
              <td class="px-6 py-3.5">
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-medium inline-block"
                  :class="cat.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500'"
                >
                  {{ cat.is_active ? 'Active' : 'Disabled' }}
                </span>
              </td>

              <!-- Sort Order with Arrows -->
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-mono font-semibold text-gray-600 w-5 text-center">{{ cat.sort_order }}</span>
                  <button
                    :disabled="idx === 0"
                    class="p-1 rounded-lg hover:bg-gray-200 disabled:opacity-20 text-gray-600"
                    @click="moveOrder(cat, 'up')"
                  >
                    <ArrowUp class="w-3.5 h-3.5" />
                  </button>
                  <button
                    :disabled="idx === categories.length - 1"
                    class="p-1 rounded-lg hover:bg-gray-200 disabled:opacity-20 text-gray-600"
                    @click="moveOrder(cat, 'down')"
                  >
                    <ArrowDown class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>

              <!-- Actions -->
              <td class="px-6 py-3.5 text-right space-x-2">
                <button
                  class="p-1.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors"
                  title="Edit Category"
                  @click="openEditModal(cat)"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors"
                  title="Delete Category"
                  @click="handleDelete(cat)"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-5 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-lg font-bold text-gray-900">
            {{ editingCategory ? 'Edit Category' : 'Create Category' }}
          </h3>
          <button class="text-gray-400 hover:text-gray-600" @click="isModalOpen = false">✕</button>
        </div>

        <form class="space-y-4" @submit.prevent="handleSave">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Category Name *</label>
            <input
              v-model="form.name"
              required
              type="text"
              placeholder="e.g. Linen Apparel"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Slug (auto-generated if empty)</label>
            <input
              v-model="form.slug"
              type="text"
              placeholder="e.g. linen-apparel"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Parent Category</label>
            <select
              v-model="form.parent_id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
            >
              <option value="">None (Top-level Root Category)</option>
              <option
                v-for="parent in rootCategories.filter((r) => !editingCategory || r.id !== editingCategory.id)"
                :key="parent.id"
                :value="parent.id"
              >
                {{ parent.name }}
              </option>
            </select>
          </div>

          <!-- Circular Tile Image Upload -->
          <div class="space-y-2">
            <label class="block text-xs font-semibold text-gray-700 uppercase">Circular Tile Image</label>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                <img v-if="form.image" :src="form.image" class="w-full h-full object-cover" />
                <ImageIcon v-else class="w-5 h-5 text-gray-400" />
              </div>
              <div class="flex-1 flex items-center gap-2">
                <label
                  class="cursor-pointer px-3 py-1.5 rounded-xl border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-50 inline-flex items-center gap-1.5"
                >
                  <Upload class="w-3.5 h-3.5" />
                  <span>{{ uploading ? 'Uploading...' : 'Upload Image' }}</span>
                  <input
                    type="file"
                    class="hidden"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    @change="handleImageUpload"
                  />
                </label>
                <button
                  v-if="form.image"
                  type="button"
                  class="text-xs text-rose-600 hover:underline"
                  @click="form.image = ''"
                >
                  Remove
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="2"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <label class="flex items-center gap-2 p-3 rounded-xl border border-gray-200 cursor-pointer text-xs font-medium">
              <input v-model="form.is_active" type="checkbox" class="rounded text-[#2F5D50]" />
              <span>Active on Storefront</span>
            </label>
            <label class="flex items-center gap-2 p-3 rounded-xl border border-gray-200 cursor-pointer text-xs font-medium">
              <input v-model="form.is_featured" type="checkbox" class="rounded text-[#2F5D50]" />
              <span>Featured Tile (Home)</span>
            </label>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded-full border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50"
              @click="isModalOpen = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 rounded-full bg-[#2F5D50] text-white text-xs font-medium hover:bg-[#264a40] disabled:opacity-50 inline-flex items-center gap-2"
            >
              <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
              <span>Save Category</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
