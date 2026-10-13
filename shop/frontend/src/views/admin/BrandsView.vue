<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Plus, Search, Edit2, Trash2, Tag, X } from 'lucide-vue-next'

const brands = ref([])
const loading = ref(false)
const search = ref('')

const showModal = ref(false)
const editingBrand = ref(null)
const saving = ref(false)
const form = ref({
  name: '',
  slug: '',
  logo: '',
  is_active: true,
})

const toastMsg = ref('')
function notify(msg) {
  toastMsg.value = msg
  setTimeout(() => {
    toastMsg.value = ''
  }, 3000)
}

async function fetchBrands() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/brands-manage', {
      params: { search: search.value || undefined },
    })
    brands.value = data.data || []
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingBrand.value = null
  form.value = { name: '', slug: '', logo: '', is_active: true }
  showModal.value = true
}

function openEdit(brand) {
  editingBrand.value = brand
  form.value = {
    name: brand.name,
    slug: brand.slug,
    logo: brand.logo || '',
    is_active: !!brand.is_active,
  }
  showModal.value = true
}

async function saveBrand() {
  if (!form.value.name.trim()) return
  saving.value = true
  try {
    if (editingBrand.value) {
      await axios.put(`/api/admin/brands-manage/${editingBrand.value.id}`, form.value)
      notify('Brand updated')
    } else {
      await axios.post('/api/admin/brands-manage', form.value)
      notify('Brand created')
    }
    showModal.value = false
    await fetchBrands()
  } catch (err) {
    notify(err.response?.data?.message || 'Could not save brand')
  } finally {
    saving.value = false
  }
}

async function removeBrand(brand) {
  if (!confirm(`Delete brand "${brand.name}"?`)) return
  try {
    await axios.delete(`/api/admin/brands-manage/${brand.id}`)
    notify('Brand deleted')
    await fetchBrands()
  } catch (err) {
    notify('Failed to delete brand')
  }
}

onMounted(fetchBrands)
</script>

<template>
  <div class="space-y-6">
    <div
      v-if="toastMsg"
      class="fixed bottom-6 right-6 z-50 px-4 py-2.5 rounded-xl bg-[#111111] text-white text-xs font-medium shadow-lg"
    >
      {{ toastMsg }}
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-[#111111] tracking-tight">Brands & Labels</h1>
        <p class="text-sm text-[#6B7280] mt-1">Manage product makers, studio labels, and brand logos.</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#111111] text-white text-xs font-medium hover:opacity-90"
        @click="openCreate"
      >
        <Plus class="w-4 h-4" />
        Add Brand
      </button>
    </div>

    <div class="bg-white border border-[#EAE8E3] rounded-xl p-4 flex items-center justify-between gap-4">
      <div class="relative flex-1 max-w-sm">
        <Search class="w-4 h-4 text-[#6B7280] absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          v-model="search"
          type="text"
          placeholder="Search brands..."
          class="w-full pl-9 pr-4 py-2 text-xs border border-[#EAE8E3] rounded-lg focus:outline-none focus:border-[#111111]"
          @input="fetchBrands"
        />
      </div>
      <span class="text-xs text-[#6B7280]">{{ brands.length }} brand(s)</span>
    </div>

    <div class="bg-white border border-[#EAE8E3] rounded-xl overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-[#EAE8E3] bg-[#FAF9F6] text-[11px] font-semibold uppercase tracking-wider text-[#6B7280]">
            <th class="py-3 px-4">Brand</th>
            <th class="py-3 px-4">Slug</th>
            <th class="py-3 px-4">Products</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#EAE8E3] text-xs">
          <tr v-if="!brands.length && !loading">
            <td colspan="5" class="py-10 text-center text-[#6B7280]">No brands found.</td>
          </tr>
          <tr v-for="b in brands" :key="b.id" class="hover:bg-[#FAF9F6]">
            <td class="py-3 px-4 font-medium text-[#111111] flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-center overflow-hidden">
                <img v-if="b.logo" :src="b.logo" :alt="b.name" class="w-full h-full object-contain" />
                <Tag v-else class="w-4 h-4 text-[#6B7280]" />
              </div>
              <span>{{ b.name }}</span>
            </td>
            <td class="py-3 px-4 font-mono text-[#6B7280]">{{ b.slug }}</td>
            <td class="py-3 px-4 tabular-nums">{{ b.products_count ?? 0 }}</td>
            <td class="py-3 px-4">
              <span
                class="px-2.5 py-0.5 rounded-full text-[11px] font-medium"
                :class="b.is_active ? 'bg-emerald-50 text-[#15803D]' : 'bg-gray-100 text-[#6B7280]'"
              >
                {{ b.is_active ? 'Active' : 'Hidden' }}
              </span>
            </td>
            <td class="py-3 px-4 text-right space-x-2">
              <button type="button" class="p-1.5 text-[#6B7280] hover:text-[#111111]" @click="openEdit(b)">
                <Edit2 class="w-4 h-4" />
              </button>
              <button type="button" class="p-1.5 text-[#6B7280] hover:text-[#DC2626]" @click="removeBrand(b)">
                <Trash2 class="w-4 h-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-[#EAE8E3] w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-[#EAE8E3] flex items-center justify-between">
          <h3 class="text-sm font-semibold text-[#111111]">{{ editingBrand ? 'Edit Brand' : 'Create Brand' }}</h3>
          <button type="button" @click="showModal = false"><X class="w-4 h-4 text-[#6B7280]" /></button>
        </div>
        <form class="p-6 space-y-4" @submit.prevent="saveBrand">
          <div>
            <label class="block text-xs font-medium text-[#111111] mb-1">Brand Name *</label>
            <input v-model="form.name" type="text" required class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
          </div>
          <div>
            <label class="block text-xs font-medium text-[#111111] mb-1">URL Slug</label>
            <input v-model="form.slug" type="text" placeholder="Auto-generated if blank" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
          </div>
          <div>
            <label class="block text-xs font-medium text-[#111111] mb-1">Logo URL (Optional)</label>
            <input v-model="form.logo" type="text" placeholder="https://..." class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
          </div>
          <label class="flex items-center gap-2 text-xs text-[#111111]">
            <input v-model="form.is_active" type="checkbox" class="rounded border-[#EAE8E3]" />
            Active on storefront
          </label>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2 rounded-full border border-[#EAE8E3] text-xs" @click="showModal = false">Cancel</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 rounded-full bg-[#111111] text-white text-xs font-medium">
              {{ saving ? 'Saving...' : 'Save Brand' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
