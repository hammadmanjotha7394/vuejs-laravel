<script setup>
import { ref, onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import { MapPin, Plus, Pencil, Trash2, CheckCircle2, X } from 'lucide-vue-next'

const settingsStore = useSettingsStore()
const toast = useToastStore()

useHead({
  title: () => `Saved Addresses | ${settingsStore.siteName}`,
})

const loading = ref(true)
const addresses = ref([])
const modalOpen = ref(false)
const saving = ref(false)
const editingId = ref(null)
const formError = ref('')

const emptyForm = () => ({
  label: 'Home',
  name: '',
  phone: '',
  line1: '',
  line2: '',
  city: '',
  state: '',
  postal_code: '',
  country: 'US',
  is_default_shipping: false,
})

const form = ref(emptyForm())

const fetchAddresses = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/account/addresses')
    addresses.value = res.data?.data || []
  } catch (e) {
    addresses.value = []
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  editingId.value = null
  form.value = emptyForm()
  formError.value = ''
  modalOpen.value = true
}

const openEditModal = (addr) => {
  editingId.value = addr.id
  form.value = {
    label: addr.label || 'Home',
    name: addr.name || '',
    phone: addr.phone || '',
    line1: addr.line1 || '',
    line2: addr.line2 || '',
    city: addr.city || '',
    state: addr.state || '',
    postal_code: addr.postal_code || '',
    country: addr.country || 'US',
    is_default_shipping: !!addr.is_default_shipping,
  }
  formError.value = ''
  modalOpen.value = true
}

const saveAddress = async () => {
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      ...form.value,
      is_default_billing: form.value.is_default_shipping,
    }
    if (editingId.value) {
      await apiClient.put(`/account/addresses/${editingId.value}`, payload)
      toast.success('Address updated.')
    } else {
      await apiClient.post('/account/addresses', payload)
      toast.success('New address saved.')
    }
    modalOpen.value = false
    await fetchAddresses()
  } catch (err) {
    formError.value =
      err.response?.data?.message || 'Please check the required address fields.'
  } finally {
    saving.value = false
  }
}

const setDefault = async (id) => {
  try {
    const res = await apiClient.post(`/account/addresses/${id}/default`)
    addresses.value = res.data?.data || addresses.value
    toast.success('Default address updated.')
  } catch (err) {
    toast.error('Failed to update default address.')
  }
}

const removeAddress = async (id) => {
  try {
    await apiClient.delete(`/account/addresses/${id}`)
    addresses.value = addresses.value.filter((a) => a.id !== id)
    toast.info('Address deleted.')
    await fetchAddresses()
  } catch (err) {
    toast.error('Failed to delete address.')
  }
}

onMounted(() => {
  fetchAddresses()
})
</script>

<template>
  <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-line mb-6">
      <div>
        <h2 class="text-base sm:text-lg font-bold text-ink">Saved Addresses</h2>
        <p class="text-xs text-ink-muted">
          Manage your shipping and billing addresses for faster checkout
        </p>
      </div>
      <button
        type="button"
        @click="openCreateModal"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition self-start sm:self-auto"
      >
        <Plus class="w-4 h-4" />
        <span>Add New Address</span>
      </button>
    </div>

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="i in 2" :key="i" class="h-40 rounded-xl bg-surface animate-pulse" />
    </div>

    <div v-else-if="addresses.length === 0" class="text-center py-12">
      <div class="w-12 h-12 rounded-full bg-surface text-ink-muted flex items-center justify-center mx-auto mb-3">
        <MapPin class="w-5 h-5" />
      </div>
      <p class="text-sm font-semibold text-ink">No saved addresses yet</p>
      <p class="text-xs text-ink-muted mt-1 mb-5">
        Save your home or office address to prefill checkout automatically.
      </p>
      <button
        type="button"
        @click="openCreateModal"
        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
      >
        <Plus class="w-4 h-4" />
        <span>Add Your First Address</span>
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="addr in addresses"
        :key="addr.id"
        class="rounded-card border p-5 flex flex-col justify-between transition"
        :class="
          addr.is_default_shipping
            ? 'border-brand bg-brand/[0.02]'
            : 'border-line hover:border-brand/40'
        "
      >
        <div>
          <div class="flex items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-pill bg-surface border border-line text-[11px] font-bold text-ink">
                {{ addr.label || 'Address' }}
              </span>
              <span
                v-if="addr.is_default_shipping"
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-pill bg-brand/10 text-brand text-[11px] font-bold"
              >
                <CheckCircle2 class="w-3 h-3" />
                <span>Default</span>
              </span>
            </div>

            <div class="flex items-center gap-1">
              <button
                type="button"
                @click="openEditModal(addr)"
                class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-surface transition"
                aria-label="Edit address"
              >
                <Pencil class="w-3.5 h-3.5" />
              </button>
              <button
                type="button"
                @click="removeAddress(addr.id)"
                class="p-1.5 rounded-lg text-ink-muted hover:text-error hover:bg-red-50 transition"
                aria-label="Delete address"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div class="text-xs text-ink-muted space-y-1">
            <p class="text-sm font-bold text-ink">{{ addr.name }}</p>
            <p>{{ addr.line1 }}<span v-if="addr.line2">, {{ addr.line2 }}</span></p>
            <p>{{ addr.city }}, {{ addr.state }} {{ addr.postal_code }}</p>
            <p>{{ addr.country }}</p>
            <p v-if="addr.phone" class="pt-1 text-ink-muted">Phone: {{ addr.phone }}</p>
          </div>
        </div>

        <div v-if="!addr.is_default_shipping" class="mt-4 pt-3 border-t border-line">
          <button
            type="button"
            @click="setDefault(addr.id)"
            class="text-xs font-semibold text-brand hover:underline"
          >
            Set as Default Address
          </button>
        </div>
      </div>
    </div>

    <!-- Add / Edit Address Modal -->
    <div
      v-if="modalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4"
    >
      <div class="bg-white rounded-card border border-line max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
          <h3 class="text-base font-bold text-ink">
            {{ editingId ? 'Edit Address' : 'Add New Address' }}
          </h3>
          <button
            type="button"
            @click="modalOpen = false"
            class="p-1.5 rounded-full text-ink-muted hover:text-ink hover:bg-surface"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="saveAddress" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block font-semibold text-ink mb-1">Label (e.g. Home, Office)</label>
              <input
                v-model="form.label"
                type="text"
                placeholder="Home"
                class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
              />
            </div>
            <div>
              <label class="block font-semibold text-ink mb-1">Full Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Jane Doe"
                class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block font-semibold text-ink mb-1">Phone Number</label>
              <input
                v-model="form.phone"
                type="text"
                placeholder="+1 (555) 234-5678"
                class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
              />
            </div>
            <div>
              <label class="block font-semibold text-ink mb-1">Country *</label>
              <input
                v-model="form.country"
                type="text"
                required
                placeholder="US"
                class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-ink mb-1">Street Address *</label>
            <input
              v-model="form.line1"
              type="text"
              required
              placeholder="123 Botanical Way"
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <div>
            <label class="block font-semibold text-ink mb-1">Apartment, Suite, etc. (Optional)</label>
            <input
              v-model="form.line2"
              type="text"
              placeholder="Apt 4B"
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div>
              <label class="block font-semibold text-ink mb-1">City *</label>
              <input
                v-model="form.city"
                type="text"
                required
                placeholder="Portland"
                class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
              />
            </div>
            <div>
              <label class="block font-semibold text-ink mb-1">State / Province</label>
              <input
                v-model="form.state"
                type="text"
                placeholder="OR"
                class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
              />
            </div>
            <div>
              <label class="block font-semibold text-ink mb-1">Postal Code *</label>
              <input
                v-model="form.postal_code"
                type="text"
                required
                placeholder="97201"
                class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
              />
            </div>
          </div>

          <label class="flex items-center gap-2 pt-1 cursor-pointer">
            <input
              v-model="form.is_default_shipping"
              type="checkbox"
              class="rounded border-line text-brand focus:ring-brand"
            />
            <span class="font-medium text-ink">Set as default shipping & billing address</span>
          </label>

          <p v-if="formError" class="text-xs text-error font-medium">
            {{ formError }}
          </p>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-line">
            <button
              type="button"
              @click="modalOpen = false"
              class="px-4 py-2 rounded-pill border border-line font-semibold text-ink-muted hover:text-ink"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 rounded-pill bg-brand text-white font-semibold hover:bg-brand-dark disabled:opacity-50 transition"
            >
              {{ saving ? 'Saving...' : 'Save Address' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
