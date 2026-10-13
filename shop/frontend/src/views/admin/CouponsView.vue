<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Plus, Search, Edit2, Trash2, TicketPercent, X } from 'lucide-vue-next'

const coupons = ref([])
const loading = ref(false)
const search = ref('')

const showModal = ref(false)
const editingCoupon = ref(null)
const saving = ref(false)
const form = ref({
  code: '',
  type: 'percent',
  value: 10,
  min_subtotal: '',
  min_qty: '',
  max_uses: '',
  per_user_limit: 1,
  starts_at: '',
  expires_at: '',
  is_active: true,
})

const toastMsg = ref('')
function notify(msg) {
  toastMsg.value = msg
  setTimeout(() => {
    toastMsg.value = ''
  }, 3000)
}

async function fetchCoupons() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/coupons', {
      params: { search: search.value || undefined },
    })
    coupons.value = data.data || []
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingCoupon.value = null
  form.value = {
    code: '',
    type: 'percent',
    value: 10,
    min_subtotal: '',
    min_qty: '',
    max_uses: '',
    per_user_limit: 1,
    starts_at: '',
    expires_at: '',
    is_active: true,
  }
  showModal.value = true
}

function openEdit(c) {
  editingCoupon.value = c
  form.value = {
    code: c.code,
    type: c.type,
    value: Number(c.value),
    min_subtotal: c.min_subtotal ?? '',
    min_qty: c.min_qty ?? '',
    max_uses: c.max_uses ?? '',
    per_user_limit: c.per_user_limit ?? '',
    starts_at: c.starts_at ? c.starts_at.slice(0, 10) : '',
    expires_at: c.expires_at ? c.expires_at.slice(0, 10) : '',
    is_active: !!c.is_active,
  }
  showModal.value = true
}

async function saveCoupon() {
  saving.value = true
  const payload = {
    ...form.value,
    min_subtotal: form.value.min_subtotal === '' ? null : Number(form.value.min_subtotal),
    min_qty: form.value.min_qty === '' ? null : Number(form.value.min_qty),
    max_uses: form.value.max_uses === '' ? null : Number(form.value.max_uses),
    per_user_limit: form.value.per_user_limit === '' ? null : Number(form.value.per_user_limit),
    starts_at: form.value.starts_at || null,
    expires_at: form.value.expires_at || null,
  }
  try {
    if (editingCoupon.value) {
      await axios.put(`/api/admin/coupons/${editingCoupon.value.id}`, payload)
      notify('Coupon updated')
    } else {
      await axios.post('/api/admin/coupons', payload)
      notify('Coupon created')
    }
    showModal.value = false
    await fetchCoupons()
  } catch (err) {
    notify(err.response?.data?.message || 'Failed to save coupon')
  } finally {
    saving.value = false
  }
}

async function removeCoupon(c) {
  if (!confirm(`Delete coupon "${c.code}"?`)) return
  try {
    await axios.delete(`/api/admin/coupons/${c.id}`)
    notify('Coupon deleted')
    await fetchCoupons()
  } catch (err) {
    notify('Could not delete coupon')
  }
}

onMounted(fetchCoupons)
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
        <h1 class="text-2xl font-semibold text-[#111111] tracking-tight">Coupons & Promo Rules</h1>
        <p class="text-sm text-[#6B7280] mt-1">
          Create percentage, fixed amount, free shipping, and "Buy X items get Y% off" discount rules.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#111111] text-white text-xs font-medium hover:opacity-90"
        @click="openCreate"
      >
        <Plus class="w-4 h-4" />
        Create Coupon
      </button>
    </div>

    <div class="bg-white border border-[#EAE8E3] rounded-xl p-4 flex items-center justify-between gap-4">
      <div class="relative flex-1 max-w-sm">
        <Search class="w-4 h-4 text-[#6B7280] absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          v-model="search"
          type="text"
          placeholder="Search promo code..."
          class="w-full pl-9 pr-4 py-2 text-xs border border-[#EAE8E3] rounded-lg focus:outline-none focus:border-[#111111]"
          @input="fetchCoupons"
        />
      </div>
      <span class="text-xs text-[#6B7280]">{{ coupons.length }} coupon(s)</span>
    </div>

    <div class="bg-white border border-[#EAE8E3] rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-[#EAE8E3] bg-[#FAF9F6] text-[11px] font-semibold uppercase tracking-wider text-[#6B7280]">
              <th class="py-3 px-4">Code</th>
              <th class="py-3 px-4">Discount</th>
              <th class="py-3 px-4">Conditions</th>
              <th class="py-3 px-4">Usage</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAE8E3] text-xs">
            <tr v-if="!coupons.length && !loading">
              <td colspan="6" class="py-10 text-center text-[#6B7280]">No coupons created yet.</td>
            </tr>
            <tr v-for="c in coupons" :key="c.id" class="hover:bg-[#FAF9F6]">
              <td class="py-3 px-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#F8F7F4] border border-[#EAE8E3] font-mono font-semibold text-[#111111]">
                  <TicketPercent class="w-3.5 h-3.5 text-[var(--brand)]" />
                  {{ c.code }}
                </span>
              </td>
              <td class="py-3 px-4 font-medium text-[#111111]">
                <template v-if="c.type === 'percent'">{{ Number(c.value) }}% OFF</template>
                <template v-else-if="c.type === 'free_shipping'">Free Shipping</template>
                <template v-else>${{ Number(c.value).toFixed(2) }} OFF</template>
              </td>
              <td class="py-3 px-4 text-[#6B7280]">
                <div v-if="c.min_subtotal">Min subtotal: ${{ Number(c.min_subtotal).toFixed(2) }}</div>
                <div v-if="c.min_qty">Buy at least {{ c.min_qty }} items</div>
                <div v-if="!c.min_subtotal && !c.min_qty">No minimum</div>
              </td>
              <td class="py-3 px-4 tabular-nums text-[#6B7280]">
                {{ c.used_count }} / {{ c.max_uses || '∞' }}
              </td>
              <td class="py-3 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[11px] font-medium"
                  :class="c.is_active ? 'bg-emerald-50 text-[#15803D]' : 'bg-gray-100 text-[#6B7280]'"
                >
                  {{ c.is_active ? 'Active' : 'Disabled' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right space-x-2">
                <button type="button" class="p-1.5 text-[#6B7280] hover:text-[#111111]" @click="openEdit(c)">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button type="button" class="p-1.5 text-[#6B7280] hover:text-[#DC2626]" @click="removeCoupon(c)">
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-[#EAE8E3] w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-[#EAE8E3] flex items-center justify-between">
          <h3 class="text-sm font-semibold text-[#111111]">{{ editingCoupon ? 'Edit Coupon' : 'Create Coupon' }}</h3>
          <button type="button" @click="showModal = false"><X class="w-4 h-4 text-[#6B7280]" /></button>
        </div>
        <form class="p-6 space-y-4" @submit.prevent="saveCoupon">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Coupon Code *</label>
              <input v-model="form.code" type="text" required placeholder="WELCOME10" class="w-full px-3 py-2 text-xs font-mono uppercase border border-[#EAE8E3] rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Discount Type</label>
              <select v-model="form.type" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg bg-white">
                <option value="percent">Percentage (%)</option>
                <option value="fixed">Fixed Amount ($)</option>
                <option value="free_shipping">Free Shipping</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Discount Value *</label>
              <input v-model="form.value" type="number" step="0.01" min="0" required class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Min Subtotal ($)</label>
              <input v-model="form.min_subtotal" type="number" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Min Qty (Buy X)</label>
              <input v-model="form.min_qty" type="number" min="1" placeholder="e.g. 2" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Max Total Uses</label>
              <input v-model="form.max_uses" type="number" min="1" placeholder="Unlimited" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Uses Per Customer</label>
              <input v-model="form.per_user_limit" type="number" min="1" placeholder="1" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Start Date</label>
              <input v-model="form.starts_at" type="date" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
            </div>
            <div>
              <label class="block text-xs font-medium text-[#111111] mb-1">Expiry Date</label>
              <input v-model="form.expires_at" type="date" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg" />
            </div>
          </div>

          <label class="flex items-center gap-2 text-xs text-[#111111]">
            <input v-model="form.is_active" type="checkbox" class="rounded border-[#EAE8E3]" />
            Coupon is active
          </label>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2 rounded-full border border-[#EAE8E3] text-xs" @click="showModal = false">Cancel</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 rounded-full bg-[#111111] text-white text-xs font-medium">
              {{ saving ? 'Saving...' : 'Save Coupon' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
