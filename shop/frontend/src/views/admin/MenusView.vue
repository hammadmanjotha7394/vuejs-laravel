<script setup>
import { ref, computed, onMounted } from 'vue'
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
  ArrowUp,
  ArrowDown,
  Eye,
  ArrowRight,
} from 'lucide-vue-next'
import { useMenuStore } from '@/stores/menu'

const menuStore = useMenuStore()

const menus = ref([])
const categories = ref([])
const cmsPages = ref([])
const selectedMenuId = ref(null)
const currentMenu = ref(null)
const loading = ref(true)
const saving = ref(false)
const uploading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const isItemModalOpen = ref(false)
const editingItem = ref(null)
const previewTopItemId = ref(null)

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

const previewTopItem = computed(() => {
  const topItems = getTopLevelItems()
  if (!topItems.length) return null
  if (previewTopItemId.value) {
    const found = topItems.find((i) => i.id === previewTopItemId.value)
    if (found) return found
  }
  return topItems[0]
})

async function fetchMenusAndTargets() {
  loading.value = true
  try {
    const [menusRes, catsRes, pagesRes] = await Promise.allSettled([
      axios.get('/api/admin/menus'),
      axios.get('/api/categories'),
      axios.get('/api/admin/pages'),
    ])

    if (menusRes.status === 'fulfilled') {
      menus.value = menusRes.value.data.data || []
      if (menus.value.length > 0 && !selectedMenuId.value) {
        selectedMenuId.value = menus.value[0].id
      }
      if (selectedMenuId.value) {
        await loadMenuDetails(selectedMenuId.value)
      }
    }
    if (catsRes.status === 'fulfilled') {
      categories.value = catsRes.value.data.data || []
    }
    if (pagesRes.status === 'fulfilled') {
      cmsPages.value = pagesRes.value.data.data || []
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
    const top = getTopLevelItems()
    if (top.length > 0 && !top.some((i) => i.id === previewTopItemId.value)) {
      previewTopItemId.value = top[0].id
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load menu details.'
  }
}

onMounted(() => {
  fetchMenusAndTargets()
})

function getTopLevelItems() {
  if (!currentMenu.value?.all_items) return []
  return [...currentMenu.value.all_items]
    .filter((i) => !i.parent_id)
    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
}

function getChildren(parentId) {
  if (!currentMenu.value?.all_items) return []
  return [...currentMenu.value.all_items]
    .filter((i) => i.parent_id === parentId)
    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
}

function onTargetCategorySelect(catId) {
  const cat = categories.value.find((c) => Number(c.id) === Number(catId))
  if (cat) {
    itemForm.value.target_id = cat.id
    if (!itemForm.value.label) itemForm.value.label = cat.name
    itemForm.value.url = `/category/${cat.slug}`
  }
}

function onTargetPageSelect(pageId) {
  const pg = cmsPages.value.find((p) => Number(p.id) === Number(pageId))
  if (pg) {
    itemForm.value.target_id = pg.id
    if (!itemForm.value.label) itemForm.value.label = pg.title
    itemForm.value.url = `/page/${pg.slug}`
  }
}

function openCreateItem(parentId = null) {
  editingItem.value = null
  const allItems = currentMenu.value?.all_items || []
  const parentItem = parentId
    ? allItems.find((i) => Number(i.id) === Number(parentId))
    : null
  const isChildOfTop = Boolean(parentItem && !parentItem.parent_id)
  const isSubLink = Boolean(parentItem && parentItem.parent_id)

  let defaultCol = 0
  if (isChildOfTop) {
    const existingCols = getChildren(parentItem.id).length
    defaultCol = Math.min(existingCols + 1, 5)
  } else if (isSubLink) {
    defaultCol = parentItem.column_index || 1
  }

  itemForm.value = {
    label: '',
    type: isChildOfTop ? 'mega_group' : 'category',
    parent_id: parentId || '',
    target_id: null,
    url: '',
    open_in_new_tab: false,
    image: '',
    featured_title: '',
    featured_button_text: '',
    featured_button_url: '',
    badge_text: '',
    sort_order: allItems.length + 1,
    column_index: defaultCol,
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

async function moveItem(siblings, index, direction) {
  const targetIdx = index + direction
  if (targetIdx < 0 || targetIdx >= siblings.length) return

  const reordered = [...siblings]
  const [moved] = reordered.splice(index, 1)
  reordered.splice(targetIdx, 0, moved)

  const payload = reordered.map((item, idx) => ({
    id: item.id,
    sort_order: idx + 1,
    parent_id: item.parent_id || null,
    column_index: item.column_index || 0,
  }))

  try {
    await axios.post(`/api/admin/menus/${selectedMenuId.value}/reorder`, { items: payload })
    await loadMenuDetails(selectedMenuId.value)
    await menuStore.fetchMenu(currentMenu.value?.slug || 'main-header', true)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to reorder items.'
  }
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
      successMessage.value = 'Menu item updated and storefront cache cleared.'
    } else {
      await axios.post(`/api/admin/menus/${selectedMenuId.value}/items`, payload)
      successMessage.value = 'Menu item added and storefront cache cleared.'
    }

    isItemModalOpen.value = false
    await loadMenuDetails(selectedMenuId.value)
    await menuStore.fetchMenu(currentMenu.value?.slug || 'main-header', true)
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
    await menuStore.fetchMenu(currentMenu.value?.slug || 'main-header', true)
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
          Visual tree editor for Main Header & Footer navigation with up to 5 mega-menu columns and featured banner cards.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Separate Editor Tabs for Main Menu & Footer Menu -->
        <div class="inline-flex items-center p-1 rounded-full bg-gray-100 border border-gray-200">
          <button
            v-for="menu in menus"
            :key="menu.id"
            type="button"
            class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition"
            :class="
              selectedMenuId === menu.id
                ? 'bg-white text-[#2F5D50] shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            "
            @click="loadMenuDetails(menu.id)"
          >
            {{ menu.name }}
          </button>
        </div>

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

    <div v-else class="space-y-6">
      <!-- LIVE MEGA MENU PREVIEW PANEL -->
      <div
        v-if="getTopLevelItems().length > 0"
        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
      >
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-700">
            <Eye class="w-4 h-4 text-[#2F5D50]" />
            <span>Live Mega Menu Preview ({{ currentMenu.name }})</span>
          </div>
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs text-gray-500 mr-1">Preview top item:</span>
            <button
              v-for="tItem in getTopLevelItems()"
              :key="'prev-btn-' + tItem.id"
              type="button"
              @click="previewTopItemId = tItem.id"
              class="px-3 py-1 rounded-full text-xs font-medium transition"
              :class="
                previewTopItem?.id === tItem.id
                  ? 'bg-[#2F5D50] text-white'
                  : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100'
              "
            >
              {{ tItem.label }}
            </button>
          </div>
        </div>

        <div v-if="previewTopItem" class="p-6 bg-[#FAF9F6]">
          <div
            v-if="getChildren(previewTopItem.id).length === 0 && !previewTopItem.image && !previewTopItem.featured_title"
            class="text-center py-6 text-xs text-gray-500"
          >
            "{{ previewTopItem.label }}" is a direct link (<span class="font-mono">{{ previewTopItem.url || '/' }}</span>) with no mega-menu columns yet. Click <strong>+ Add Child/Column</strong> below to turn it into a multi-column mega menu.
          </div>

          <div v-else class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
              <!-- Columns Area -->
              <div
                :class="
                  previewTopItem.image || previewTopItem.featured_title
                    ? 'md:col-span-8 lg:col-span-9 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6'
                    : 'md:col-span-12 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6'
                "
              >
                <div
                  v-for="colItem in getChildren(previewTopItem.id)"
                  :key="'prev-col-' + colItem.id"
                  class="space-y-2.5"
                >
                  <template v-if="getChildren(colItem.id).length > 0 || colItem.type === 'mega_group'">
                    <div class="text-xs font-bold uppercase tracking-wider text-gray-900 border-b border-gray-100 pb-1.5 flex items-center gap-1.5">
                      <span>{{ colItem.label }}</span>
                      <span
                        v-if="colItem.badge_text"
                        class="px-1.5 py-0.5 rounded-full text-[9px] bg-emerald-50 text-emerald-700"
                      >
                        {{ colItem.badge_text }}
                      </span>
                    </div>
                    <ul class="space-y-1.5 text-xs text-gray-600">
                      <li
                        v-for="subLink in getChildren(colItem.id)"
                        :key="'prev-sub-' + subLink.id"
                        class="hover:text-[#2F5D50] flex items-center gap-1.5"
                      >
                        <span>{{ subLink.label }}</span>
                        <span
                          v-if="subLink.badge_text"
                          class="px-1.5 py-0.2 rounded-full text-[9px] bg-amber-50 text-amber-700 font-semibold"
                        >
                          {{ subLink.badge_text }}
                        </span>
                      </li>
                    </ul>
                  </template>
                  <template v-else>
                    <div class="text-xs font-medium text-gray-700 hover:text-[#2F5D50] flex items-center gap-1.5 py-1">
                      <span>{{ colItem.label }}</span>
                      <span
                        v-if="colItem.badge_text"
                        class="px-1.5 py-0.5 rounded-full text-[9px] bg-emerald-50 text-emerald-700"
                      >
                        {{ colItem.badge_text }}
                      </span>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Featured Card Preview -->
              <div
                v-if="previewTopItem.image || previewTopItem.featured_title"
                class="md:col-span-4 lg:col-span-3 rounded-xl overflow-hidden border border-gray-100 bg-gray-50 flex flex-col justify-between"
              >
                <img
                  v-if="previewTopItem.image"
                  :src="previewTopItem.image"
                  :alt="previewTopItem.featured_title || previewTopItem.label"
                  class="w-full h-36 object-cover"
                />
                <div class="p-4 space-y-2">
                  <h4 class="text-sm font-bold text-gray-900">
                    {{ previewTopItem.featured_title || previewTopItem.label }}
                  </h4>
                  <span
                    v-if="previewTopItem.featured_button_text"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#2F5D50]"
                  >
                    <span>{{ previewTopItem.featured_button_text }}</span>
                    <ArrowRight class="w-3 h-3" />
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TREE BUILDER CARDS -->
      <div
        v-for="(topItem, topIdx) in getTopLevelItems()"
        :key="topItem.id"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4"
      >
        <!-- Top Level Item Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="flex flex-col gap-0.5">
              <button
                type="button"
                :disabled="topIdx === 0"
                class="p-0.5 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                title="Move Up"
                @click="moveItem(getTopLevelItems(), topIdx, -1)"
              >
                <ArrowUp class="w-3.5 h-3.5" />
              </button>
              <button
                type="button"
                :disabled="topIdx === getTopLevelItems().length - 1"
                class="p-0.5 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                title="Move Down"
                @click="moveItem(getTopLevelItems(), topIdx, 1)"
              >
                <ArrowDown class="w-3.5 h-3.5" />
              </button>
            </div>
            <span class="text-xs font-mono font-bold text-gray-400">#{{ topIdx + 1 }}</span>
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
            v-for="(child, childIdx) in getChildren(topItem.id)"
            :key="child.id"
            class="p-3 rounded-xl border border-gray-100 bg-gray-50/50 space-y-2"
          >
            <div class="flex items-center justify-between text-xs">
              <span class="font-semibold text-gray-900 truncate">{{ child.label }}</span>
              <div class="flex items-center gap-0.5">
                <button
                  type="button"
                  :disabled="childIdx === 0"
                  class="p-0.5 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                  @click="moveItem(getChildren(topItem.id), childIdx, -1)"
                >
                  <ArrowUp class="w-3 h-3" />
                </button>
                <button
                  type="button"
                  :disabled="childIdx === getChildren(topItem.id).length - 1"
                  class="p-0.5 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                  @click="moveItem(getChildren(topItem.id), childIdx, 1)"
                >
                  <ArrowDown class="w-3 h-3" />
                </button>
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
                v-for="(sub, subIdx) in getChildren(child.id)"
                :key="sub.id"
                class="flex items-center justify-between text-[11px] text-gray-600 bg-white p-1.5 rounded-lg border border-gray-100"
              >
                <span class="truncate">{{ sub.label }}</span>
                <div class="flex items-center gap-1">
                  <button
                    type="button"
                    :disabled="subIdx === 0"
                    class="text-gray-400 hover:text-gray-700 disabled:opacity-30"
                    @click="moveItem(getChildren(child.id), subIdx, -1)"
                  >
                    <ArrowUp class="w-2.5 h-2.5" />
                  </button>
                  <button
                    type="button"
                    :disabled="subIdx === getChildren(child.id).length - 1"
                    class="text-gray-400 hover:text-gray-700 disabled:opacity-30"
                    @click="moveItem(getChildren(child.id), subIdx, 1)"
                  >
                    <ArrowDown class="w-2.5 h-2.5" />
                  </button>
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
          <!-- Parent Item Selector -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Parent Item / Placement</label>
            <select
              v-model="itemForm.parent_id"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
            >
              <option value="">None (Top-Level Navbar Item)</option>
              <template v-for="top in getTopLevelItems()" :key="'p-top-' + top.id">
                <option
                  v-if="!editingItem || editingItem.id !== top.id"
                  :value="top.id"
                >
                  ↳ Under "{{ top.label }}" (Mega Menu Column / Link)
                </option>
                <template v-for="child in getChildren(top.id)" :key="'p-child-' + child.id">
                  <option
                    v-if="!editingItem || editingItem.id !== child.id"
                    :value="child.id"
                  >
                    &nbsp;&nbsp;&nbsp;&nbsp;↳ Inside Column "{{ top.label }} › {{ child.label }}"
                  </option>
                </template>
              </template>
            </select>
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
                <option :value="0">None / Auto</option>
                <option :value="1">Column 1</option>
                <option :value="2">Column 2</option>
                <option :value="3">Column 3</option>
                <option :value="4">Column 4</option>
                <option :value="5">Column 5</option>
              </select>
            </div>
          </div>

          <!-- Quick Picker for Category or CMS Page -->
          <div v-if="itemForm.type === 'category' && categories.length > 0">
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Select Category</label>
            <select
              :value="itemForm.target_id || ''"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              @change="onTargetCategorySelect($event.target.value)"
            >
              <option value="">-- Choose a category to auto-fill URL --</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }} (/category/{{ cat.slug }})
              </option>
            </select>
          </div>

          <div v-if="itemForm.type === 'page' && cmsPages.length > 0">
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Select CMS Page</label>
            <select
              :value="itemForm.target_id || ''"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              @change="onTargetPageSelect($event.target.value)"
            >
              <option value="">-- Choose a CMS page to auto-fill URL --</option>
              <option v-for="pg in cmsPages" :key="pg.id" :value="pg.id">
                {{ pg.title }} (/page/{{ pg.slug }})
              </option>
            </select>
          </div>

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
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Target URL</label>
              <input
                v-model="itemForm.url"
                type="text"
                placeholder="/category/women or /page/about"
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

          <!-- Featured Card Section (Optional, for Top-Level Mega Menu items) -->
          <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/70 space-y-3">
            <span class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
              Mega Menu Featured Banner Card (Optional)
            </span>

            <div>
              <label class="block text-[11px] font-medium text-gray-600 mb-1">Banner Image URL</label>
              <input
                v-model="itemForm.image"
                type="text"
                placeholder="https://images.unsplash.com/... or /storage/menus/..."
                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-xs font-mono focus:outline-none focus:ring-2 focus:ring-[#2F5D50]"
              />
            </div>

            <div class="flex items-center gap-3">
              <div v-if="itemForm.image" class="relative">
                <img
                  :src="itemForm.image"
                  class="w-16 h-12 object-cover rounded-lg border border-gray-200"
                />
                <button
                  type="button"
                  title="Remove image"
                  class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-rose-500 text-white text-[10px] flex items-center justify-center shadow"
                  @click="itemForm.image = ''"
                >
                  ×
                </button>
              </div>
              <label
                class="cursor-pointer px-3 py-1.5 rounded-xl border border-gray-200 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 inline-flex items-center gap-1.5"
              >
                <Upload class="w-3.5 h-3.5" />
                <span>{{ uploading ? 'Uploading...' : 'Or Upload Image File' }}</span>
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

          <!-- Active & New Tab Toggles -->
          <div class="flex items-center justify-between pt-1">
            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700 cursor-pointer">
              <input
                v-model="itemForm.is_active"
                type="checkbox"
                class="rounded border-gray-300 text-[#2F5D50] focus:ring-[#2F5D50]"
              />
              <span>Visible on Storefront</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700 cursor-pointer">
              <input
                v-model="itemForm.open_in_new_tab"
                type="checkbox"
                class="rounded border-gray-300 text-[#2F5D50] focus:ring-[#2F5D50]"
              />
              <span>Open link in new tab</span>
            </label>
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
