<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Users,
  Search,
  Download,
  RotateCcw,
  Eye,
  Mail,
  Phone,
  MapPin,
  ShoppingBag,
  CheckCircle2,
  Calendar,
  ExternalLink,
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import DataTable from '@/components/admin/DataTable.vue'
import SlideOverForm from '@/components/admin/SlideOverForm.vue'
import ToggleSwitch from '@/components/admin/ToggleSwitch.vue'

const route = useRoute()
const router = useRouter()

const customers = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})
const isLoading = ref(false)
const toastMessage = ref('')
const errorMessage = ref('')

const filters = reactive({
  search: '',
  is_active: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
})

// Customer Profile Slide-Over state
const isDrawerOpen = ref(false)
const isLoadingProfile = ref(false)
const isSavingProfile = ref(false)
const selectedCustomer = ref(null)
const profileForm = reactive({
  name: '',
  phone: '',
  is_active: true,
  admin_notes: '',
})

const columns = [
  { key: 'name', label: 'Customer', sortable: true },
  { key: 'phone', label: 'Phone' },
  { key: 'orders_count', label: 'Orders', sortable: true },
  { key: 'total_spent', label: 'Lifetime Spend', sortable: true },
  { key: 'last_order_at', label: 'Last Order' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const showToast = (msg) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

const fetchCustomers = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    }
    if (filters.search) params.search = filters.search
    if (filters.is_active !== '') params.is_active = filters.is_active

    const res = await apiClient.get('/admin/customers', { params })
    customers.value = res.data.data || []
    pagination.value = {
      current_page: res.data.current_page || 1,
      last_page: res.data.last_page || 1,
      per_page: res.data.per_page || 20,
      total: res.data.total || 0,
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load customers.'
  } finally {
    isLoading.value = false
  }
}

const openCustomerProfile = async (customerId) => {
  isDrawerOpen.value = true
  isLoadingProfile.value = true
  selectedCustomer.value = null
  try {
    const res = await apiClient.get(`/admin/customers/${customerId}`)
    selectedCustomer.value = res.data.data
    profileForm.name = selectedCustomer.value.name || ''
    profileForm.phone = selectedCustomer.value.phone || ''
    profileForm.is_active = Boolean(selectedCustomer.value.is_active)
    profileForm.admin_notes = selectedCustomer.value.admin_notes || ''
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load customer profile.'
    isDrawerOpen.value = false
  } finally {
    isLoadingProfile.value = false
  }
}

const saveCustomerProfile = async () => {
  if (!selectedCustomer.value) return
  isSavingProfile.value = true
  try {
    await apiClient.put(`/admin/customers/${selectedCustomer.value.id}`, {
      name: profileForm.name,
      phone: profileForm.phone || null,
      is_active: profileForm.is_active,
      admin_notes: profileForm.admin_notes || null,
    })
    showToast(`Profile updated for ${profileForm.name}.`)
    isDrawerOpen.value = false
    fetchCustomers(pagination.value.current_page)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save customer profile.'
  } finally {
    isSavingProfile.value = false
  }
}

const quickToggleCustomerStatus = async (row, newVal) => {
  const prev = row.is_active
  row.is_active = newVal
  try {
    await apiClient.put(`/admin/customers/${row.id}`, {
      is_active: newVal,
    })
    showToast(`${row.name} account ${newVal ? 'enabled' : 'disabled'}.`)
  } catch (err) {
    row.is_active = prev
    errorMessage.value = err.response?.data?.message || 'Failed to update customer status.'
  }
}

const exportCustomersCsv = async () => {
  try {
    const res = await apiClient.get('/admin/customers/export-csv', {
      params: {
        search: filters.search || undefined,
        is_active: filters.is_active !== '' ? filters.is_active : undefined,
      },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `customers-${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    errorMessage.value = 'Failed to export customers CSV.'
  }
}

const handleSort = ({ key, dir }) => {
  filters.sort_by = key
  filters.sort_dir = dir
  fetchCustomers(1)
}

const resetFilters = () => {
  filters.search = ''
  filters.is_active = ''
  filters.sort_by = 'created_at'
  filters.sort_dir = 'desc'
  fetchCustomers(1)
}

const getInitials = (name) => {
  if (!name) return 'C'
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

const formatDate = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

let searchTimer = null
watch(
  () => filters.search,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchCustomers(1), 280)
  }
)

watch(
  () => filters.is_active,
  () => fetchCustomers(1)
)

onMounted(async () => {
  await fetchCustomers(1)
  if (route.query.customer_id) {
    openCustomerProfile(route.query.customer_id)
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Customers Directory</h1>
        <p class="text-xs text-ink-muted mt-1">
          View customer profiles, lifetime order history, saved addresses, internal notes, and export CSV records.
        </p>
      </div>

      <button
        type="button"
        @click="exportCustomersCsv"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:bg-surface transition self-start"
      >
        <Download class="w-3.5 h-3.5 text-brand" />
        <span>Export Customers CSV</span>
      </button>
    </div>

    <!-- Toasts / Alerts -->
    <div
      v-if="toastMessage"
      class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-success px-4 py-3 rounded-card text-xs font-medium"
    >
      <CheckCircle2 class="w-4 h-4 shrink-0" />
      <span>{{ toastMessage }}</span>
    </div>

    <div
      v-if="errorMessage"
      class="flex items-center justify-between bg-red-50 border border-red-200 text-error px-4 py-3 rounded-card text-xs font-medium"
    >
      <span>{{ errorMessage }}</span>
      <button @click="errorMessage = ''" class="text-[11px] underline">Dismiss</button>
    </div>

    <!-- Customers DataTable -->
    <DataTable
      :columns="columns"
      :rows="customers"
      :loading="isLoading"
      :pagination="pagination"
      :sort-by="filters.sort_by"
      :sort-dir="filters.sort_dir"
      empty-title="No customers found"
      empty-description="No registered customer accounts match your current search or filter."
      @sort="handleSort"
      @page-change="fetchCustomers"
    >
      <template #filters>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search customers by name, email, or phone..."
              class="w-full pl-9 pr-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <select
            v-model="filters.is_active"
            class="px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
          >
            <option value="">All Statuses</option>
            <option value="1">Active Accounts</option>
            <option value="0">Disabled Accounts</option>
          </select>

          <button
            type="button"
            @click="resetFilters"
            class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-line bg-surface text-xs font-medium text-ink-muted hover:text-ink transition"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Reset</span>
          </button>
        </div>
      </template>

      <template #cell-name="{ row }">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-brand-soft text-brand font-bold text-xs flex items-center justify-center shrink-0">
            {{ getInitials(row.name) }}
          </div>
          <div class="min-w-0">
            <button
              type="button"
              @click="openCustomerProfile(row.id)"
              class="text-xs font-bold text-ink hover:text-brand truncate block text-left"
            >
              {{ row.name }}
            </button>
            <p class="text-[11px] text-ink-muted truncate">{{ row.email }}</p>
          </div>
        </div>
      </template>

      <template #cell-phone="{ row }">
        <span class="text-xs text-ink-muted">{{ row.phone || '—' }}</span>
      </template>

      <template #cell-orders_count="{ row }">
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-pill text-xs font-semibold bg-surface text-ink border border-line">
          {{ row.orders_count || 0 }}
        </span>
      </template>

      <template #cell-total_spent="{ row }">
        <span class="text-xs font-bold text-ink">
          ${{ Number(row.total_spent || 0).toFixed(2) }}
        </span>
      </template>

      <template #cell-last_order_at="{ row }">
        <span class="text-xs text-ink-muted">{{ formatDate(row.last_order_at) }}</span>
      </template>

      <template #cell-is_active="{ row }">
        <div class="flex items-center gap-2">
          <ToggleSwitch
            :model-value="Boolean(row.is_active)"
            @update:model-value="(val) => quickToggleCustomerStatus(row, val)"
          />
          <span class="text-[11px] font-medium" :class="row.is_active ? 'text-success' : 'text-ink-muted'">
            {{ row.is_active ? 'Active' : 'Disabled' }}
          </span>
        </div>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end">
          <button
            type="button"
            @click="openCustomerProfile(row.id)"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:border-brand hover:text-brand transition"
          >
            <Eye class="w-3.5 h-3.5" />
            <span>Profile</span>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Customer Profile Slide-Over -->
    <SlideOverForm
      :open="isDrawerOpen"
      :title="selectedCustomer ? selectedCustomer.name : 'Customer Profile'"
      subtitle="Customer details, order history, addresses, and internal staff notes"
      width="xl"
      :saving="isSavingProfile"
      save-label="Save Customer Changes"
      @close="isDrawerOpen = false"
      @save="saveCustomerProfile"
    >
      <div v-if="isLoadingProfile" class="py-12 text-center text-xs text-ink-muted">
        Loading customer profile...
      </div>

      <div v-else-if="selectedCustomer" class="space-y-6">
        <!-- Summary Metrics -->
        <div class="grid grid-cols-3 gap-3">
          <div class="bg-surface border border-line rounded-xl p-3.5">
            <span class="text-[11px] text-ink-muted block">Total Orders</span>
            <span class="text-lg font-bold text-ink">{{ selectedCustomer.orders_count }}</span>
          </div>
          <div class="bg-surface border border-line rounded-xl p-3.5">
            <span class="text-[11px] text-ink-muted block">Lifetime Spend</span>
            <span class="text-lg font-bold text-ink">${{ Number(selectedCustomer.total_spent).toFixed(2) }}</span>
          </div>
          <div class="bg-surface border border-line rounded-xl p-3.5">
            <span class="text-[11px] text-ink-muted block">Avg Order Value</span>
            <span class="text-lg font-bold text-ink">${{ Number(selectedCustomer.average_order_value).toFixed(2) }}</span>
          </div>
        </div>

        <!-- Account Info & Status -->
        <div class="bg-surface border border-line rounded-xl p-4 space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xs font-bold text-ink">Account Status</h3>
              <p class="text-[11px] text-ink-muted">Disable account to prevent customer login</p>
            </div>
            <ToggleSwitch v-model="profileForm.is_active" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-line">
            <div>
              <label class="block text-[11px] font-semibold text-ink-muted mb-1">Full Name</label>
              <input
                v-model="profileForm.name"
                type="text"
                class="w-full px-3 py-2 text-xs bg-page border border-line rounded-lg text-ink focus:outline-none focus:border-brand"
              />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-ink-muted mb-1">Phone Number</label>
              <input
                v-model="profileForm.phone"
                type="text"
                class="w-full px-3 py-2 text-xs bg-page border border-line rounded-lg text-ink focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-4 text-[11px] text-ink-muted pt-1">
            <span class="inline-flex items-center gap-1">
              <Mail class="w-3.5 h-3.5" />
              {{ selectedCustomer.email }}
            </span>
            <span class="inline-flex items-center gap-1">
              <Calendar class="w-3.5 h-3.5" />
              Joined {{ formatDate(selectedCustomer.created_at) }}
            </span>
          </div>
        </div>

        <!-- Internal Admin Notes -->
        <div>
          <label class="block text-xs font-bold text-ink mb-1.5">Internal Staff Notes</label>
          <textarea
            v-model="profileForm.admin_notes"
            rows="3"
            placeholder="Add private staff notes about this customer (VIP preferences, sizing notes, support history)..."
            class="w-full px-3.5 py-2.5 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
          ></textarea>
        </div>

        <!-- Order History -->
        <div class="space-y-2.5">
          <h3 class="text-xs font-bold text-ink flex items-center gap-1.5">
            <ShoppingBag class="w-3.5 h-3.5 text-brand" />
            <span>Order History ({{ selectedCustomer.orders?.length || 0 }})</span>
          </h3>

          <div v-if="!selectedCustomer.orders?.length" class="text-xs text-ink-muted bg-surface rounded-xl p-4 border border-line">
            This customer has not placed any orders yet.
          </div>

          <div v-else class="divide-y divide-line border border-line rounded-xl overflow-hidden bg-page">
            <div
              v-for="ord in selectedCustomer.orders"
              :key="ord.id"
              class="p-3.5 flex items-center justify-between gap-3 hover:bg-surface transition text-xs"
            >
              <div>
                <button
                  type="button"
                  @click="isDrawerOpen = false; router.push(`/admin/orders/${ord.id}`)"
                  class="font-mono font-bold text-brand hover:underline inline-flex items-center gap-1"
                >
                  <span>{{ ord.order_number }}</span>
                  <ExternalLink class="w-3 h-3" />
                </button>
                <p class="text-[11px] text-ink-muted mt-0.5">
                  {{ formatDate(ord.placed_at || ord.created_at) }} · {{ ord.items_count || 0 }} item(s)
                </p>
              </div>
              <div class="text-right">
                <p class="font-bold text-ink">${{ Number(ord.grand_total).toFixed(2) }}</p>
                <span class="text-[10px] font-semibold uppercase text-ink-muted">{{ ord.status }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Saved Addresses -->
        <div v-if="selectedCustomer.addresses?.length" class="space-y-2.5">
          <h3 class="text-xs font-bold text-ink flex items-center gap-1.5">
            <MapPin class="w-3.5 h-3.5 text-brand" />
            <span>Saved Addresses ({{ selectedCustomer.addresses.length }})</span>
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div
              v-for="addr in selectedCustomer.addresses"
              :key="addr.id"
              class="bg-surface border border-line rounded-xl p-3 text-xs space-y-0.5"
            >
              <p class="font-semibold text-ink">
                {{ addr.first_name }} {{ addr.last_name }}
                <span v-if="addr.is_default_shipping" class="ml-1 text-[10px] text-brand font-bold">(Default)</span>
              </p>
              <p class="text-ink-muted">{{ addr.line1 }}</p>
              <p class="text-ink-muted">{{ addr.city }}, {{ addr.state }} {{ addr.postal_code }}</p>
              <p class="text-ink-muted">{{ addr.country }}</p>
            </div>
          </div>
        </div>
      </div>
    </SlideOverForm>
  </div>
</template>
