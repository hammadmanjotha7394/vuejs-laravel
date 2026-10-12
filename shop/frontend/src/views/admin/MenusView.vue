<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  Menu as MenuIcon,
  Plus,
  Trash2,
  Edit2,
  Columns,
  Image as ImageIcon,
  CheckCircle2,
  AlertCircle,
  Loader2,
  ExternalLink,
  Upload,
} from 'lucide-vue-next'

const menus = ref([])
const selectedMenuId = ref(null)
const currentMenu = ref(null)
const loading = ref(true)
const saving = ref(false)
const uploading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const isItemModalOpen = ref(false)
const editingItem = ref(null)

const itemForm = ref({
  label: '',
  type: 'category',
  parent_id: '',
  target_id: null,
  url: '',
  open_in_new_tab: false,
  image: '',
  featured_title: '',
  featured_button_text: '',
  featured_button_url: '',
  badge_text: '',
  sort_order: 0,
  column_index: 0,
  is_active: true,
})

async function fetchMenus() {
  loading.value = true
  try {
    const res = await axios.get('/api/admin/menus')
    menus.value = res.data.data
    if (menus.value.length > 0 && !selectedMenuId.value) {
      selectedMenuId.value = menus.value[0].id
    }
    if (selectedMenuId.value) {
      await loadMenuDetails(selectedMenuId.value)
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load menus.'
  } finally {
    loading.value = false
  }
}

async function loadMenuDetails(id) {
  selectedMenuId.value = id
  try {
    const res = await axios.get(`/api/admin/menus/${id}`)
    currentMenu.value = res.data.data
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load menu details.'
  }
}

onMounted(() => {
  fetchMenus()
})

function getTopLevelItems() {
  if (!currentMenu.value?.all_items) return []
  return currentMenu.value.all_items.filter((i) => !i.parent_id)
}

function getChildren(parentId) {
  if (!currentMenu.value?.all_items) return []
  return currentMenu.value.all_items.filter((i) => i.parent_id === parentId)
}

function openCreateItem(parentId = null) {
  editingItem.value = null
  itemForm.value = {
    label: '',
    type: parentId ? 'category' : 'category',
    parent_id: parentId || '',
    target_id: null,
    url: '',
    open_in_new_tab: false,
    image: '',
    featured_title: '',
    featured_button_text: '',
    featured_button_url: '',
    badge_text: '',
    sort_order: (currentMenu.value?.all_items?.length || 0) + 1,
    column_index: 0,
    is_active: true,
  }
  isItemModalOpen.value = true
}

function openEditItem(item) {
  editingItem.value = item
  itemForm.value = {
    label: item.label,
    type: item.type,
    parent_id: item.parent_id || '',
    target_id: item.target_id || null,
    url: item.url || '',
    open_in_new_tab: Boolean(item.open_in_new_tab),
    image: item.image || '',
    featured_title: item.featured_title || '',
    featured_button_text: item.featured_button_text || '',
    featured_button_url: item.featured_button_url || '',
    badge_text: item.badge_text || '',
    sort_order: item.sort_order || 0,
    column_index: item.column_index || 0,
    is_active: Boolean(item.is_active),
  }
  isItemModalOpen.value = true
}

async function handleSaveItem() {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      ...itemForm.value,
      parent_id: itemForm.value.parent_id ? Number(itemForm.value.parent_id) : null,
      column_index: Number(itemForm.value.column_index) || 0,
    }

    if (editingItem.value) {
      await axios.put(`/api/admin/menus/items/${editingItem.value.id}`, payload)
      successMessage.value = 'Menu item updated.'
    } else {
      await axios.post(`/api/admin/menus/${selectedMenuId.value}/items`, payload)
      successMessage.value = 'Menu item added.'
    }

    isItemModalOpen.value = false
    await loadMenuDetails(selectedMenuId.value)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save menu item.'
  } finally {
    saving.value = false
  }
}

async function handleDeleteItem(item) {
  if (!confirm(`Delete item "${item.label}" and any nested links?`)) return

  try {
    await axios.delete(`/api/admin/menus/items/${item.id}`)
    successMessage.value = `Item "${item.label}" deleted.`
    await loadMenuDetails(selectedMenuId.value)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete menu item.'
  }
}

async function handleImageUpload(e) {
  const file = e.target.files[0]
  if (!file) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const res = await axios.post('/api/admin/menus/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    itemForm.value.image = res.data.url
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to upload image.'
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
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Mega Menu & Navigation Builder</h1>
        <p class="text-sm text-gray-500 mt-1">
          Build rich multi-column mega menus (up to 5 columns with groups and featured image banners).
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Switch Menu Select -->
        <select
          v-model="selectedMenuId"
          class="px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium focus:ring-2 focus:ring-[#2F5D50]"
          @change="loadMenuDetails(selectedMenuId)"
        >
          <option v-for="menu in menus" :key="menu.id" :value="menu.id">
            {{ menu.name }} ({{ menu.slug }})
          </option>
        </select>

        <button
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#2F5D50] text-white text-xs font-medium hover:bg-[#264a40] transition-colors shadow-sm"
          @click="openCreateItem(null)"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Add Top Item</span>
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

    <!-- Menu Items Hierarchy View -->
    <div v-if="loading" class="p-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
      <Loader2 class="w-6 h-6 animate-spin mx-auto mb-2 text-[#2F5D50]" />
      <span>Loading menu items...</span>
    </div>

    <div v-else-if="!currentMenu" class="p-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
      No menu selected.
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="topItem in getTopLevelItems()"
        :key="topItem.id"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4"
      >
        <!-- Top Level Item Header -->
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-3">
            <span class="text-xs font-mono font-bold text-gray-400">#{{ topItem.sort_order }}</span>
            <span class="text-base font-bold text-gray-900">{{ topItem.label }}</span>
            <span
              v-if="topItem.badge_text"
              class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
            >
              {{ topItem.badge_text }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[10px] uppercase font-mono bg-gray-100 text-gray-600">
              {{ topItem.type }}
            </span>
            <span v-if="topItem.url" class="text-xs text-gray-400 font-mono">
              {{ topItem.url }}
            </span>
          </div>

          <div class="flex items-center gap-2">
            <button
              class="px-2.5 py-1 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors inline-flex items-center gap-1"
              @click="openCreateItem(topItem.id)"
            >
              <Plus class="w-3 h-3" />
              <span>Add Child/Column</span>
            </button>
            <button
              class="p-1.5 rounded-lg text-gray-600 hover:bg-gray-100"
              title="Edit Top Item"
              @click="openEditItem(topItem)"
            >
              <Edit2 class="w-4 h-4" />
            </button>
            <button
              class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50"
              title="Delete Top Item"
              @click="handleDeleteItem(topItem)"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Featured Banner Preview (if set on top item) -->
        <div
          v-if="topItem.image || topItem.featured_title"
          class="p-3 rounded-xl bg-gray-50 border border-gray-100 flex items-center gap-4 text-xs"
        >
          <img
            v-if="topItem.image"
            :src="topItem.image"
            alt="Featured card"
            class="w-16 h-12 object-cover rounded-lg border border-gray-200"
          />
          <div class="flex-1">
            <span class="font-bold text-gray-800">Featured Card: </span>
            <span>{{ topItem.featured_title || 'Untitled Banner' }}</span>
            <span v-if="topItem.featured_button_text" class="text-gray-500">
              · Button: "{{ topItem.featured_button_text }}" ({{ topItem.featured_button_url }})
            </span>
          </div>
        </div>

        <!-- Children / Mega Columns Grid -->
        <div v-if="getChildren(topItem.id).length > 0" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-3 pt-1">
          <div
            v-for="child in getChildren(topItem.id)"
            :key="child.id"
            class="p-3 rounded-xl border border-gray-100 bg-gray-50/50 space-y-2"
          >
            <div class="flex items-center justify-between text-xs">
              <span class="font-semibold text-gray-900 truncate">{{ child.label }}</span>
              <div class="flex items-center gap-1">
                <button class="p-1 text-gray-400 hover:text-gray-700" @click="openEditItem(child)">
                  <Edit2 class="w-3 h-3" />
                </button>
                <button class="p-1 text-rose-400 hover:text-rose-700" @click="handleDeleteItem(child)">
                  <Trash2 class="w-3 h-3" />
                </button>
              </div>
            </div>

            <div class="text-[11px] text-gray-500 flex items-center justify-between">
              <span>Col: {{ child.column_index || 'Auto' }}</span>
              <span class="font-mono text-gray-400 truncate max-w-[80px]">{{ child.url || '—' }}</span>
            </div>

            <!-- Nested Sub-items (if group) -->
            <div v-if="getChildren(child.id).length > 0" class="pt-2 border-t border-gray-200/60 space-y-1">
              <div
                v-for="sub in getChildren(child.id)"
                :key="sub.id"
                class="flex items-center justify-between text-[11px] text-gray-600 bg-white p-1.5 rounded-lg border border-gray-100"
              >
                <span class="truncate">{{ sub.label }}</span>
                <div class="flex items-center gap-1">
                  <button class="text-gray-400 hover:text-gray-700" @click="openEditItem(sub)">
                    <Edit2 class="w-2.5 h-2.5" />
                  </button>
                  <button class="text-rose-400 hover:text-rose-700" @click="handleDeleteItem(sub)">
                    <Trash2 class="w-2.5 h-2.5" />
                  </button>
                </div>
              </div>
            </div>

            <button
              v-if="child.type === 'mega_group'"
              class="w-full text-center py-1 rounded text-[10px] font-medium text-gray-500 hover:bg-gray-200/50"
              @click="openCreateItem(child.id)"
            >
              + Add Sub-link
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit Item Modal -->
    <div
      v-if="isItemModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-lg font-bold text-gray-900">
            {{ editingItem ? 'Edit Menu Item' : 'Add Menu Item' }}
          </h3>
          <button class="text-gray-400 hover:text-gray-600" @click="isItemModalOpen = false">✕</button>
        </div>

        <form class="space-y-4" @submit.prevent="handleSaveItem">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Item Label *</label>
            <input
              v-model="itemForm.label"
              required
              type="text"
              placeholder="e.g. Women, Clothing, Tops"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Type</label>
              <select
                v-model="itemForm.type"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="category">Category Link</option>
                <option value="page">CMS Page</option>
                <option value="custom_url">Custom URL</option>
                <option value="mega_group">Mega Group (Column Header)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Column (1 - 5)</label>
              <select
                v-model="itemForm.column_index"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option :value="0">None / Full</option>
                <option :value="1">Column 1</option>
                <option :value="2">Column 2</option>
                <option :value="3">Column 3</option>
                <option :value="4">Column 4</option>
                <option :value="5">Column 5</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Target URL</label>
              <input
                v-model="itemForm.url"
                type="text"
                placeholder="/category/women or https://..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Badge Text (Optional)</label>
              <input
                v-model="itemForm.badge_text"
                type="text"
                placeholder="New, Sale, 20% Off"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
          </div>

          <!-- Featured Card Section (Optional) -->
          <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/70 space-y-3">
            <span class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
              Mega Menu Featured Banner (Optional)
            </span>
            <div class="flex items-center gap-3">
              <img
                v-if="itemForm.image"
                :src="itemForm.image"
                class="w-12 h-10 object-cover rounded-lg border border-gray-200"
              />
              <label
                class="cursor-pointer px-3 py-1.5 rounded-xl border border-gray-200 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 inline-flex items-center gap-1.5"
              >
                <Upload class="w-3.5 h-3.5" />
                <span>{{ uploading ? 'Uploading...' : 'Upload Banner Image' }}</span>
                <input
                  type="file"
                  class="hidden"
                  accept="image/png,image/jpeg,image/webp"
                  @change="handleImageUpload"
                />
              </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
              <input
                v-model="itemForm.featured_title"
                type="text"
                placeholder="Banner Title"
                class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white text-xs"
              />
              <input
                v-model="itemForm.featured_button_text"
                type="text"
                placeholder="Button Text"
                class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white text-xs"
              />
              <input
                v-model="itemForm.featured_button_url"
                type="text"
                placeholder="Button URL"
                class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-mono"
              />
            </div>
          </div>

          <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded-full border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50"
              @click="isItemModalOpen = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 rounded-full bg-[#2F5D50] text-white text-xs font-medium hover:bg-[#264a40] disabled:opacity-50 inline-flex items-center gap-2"
            >
              <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
              <span>Save Item</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
