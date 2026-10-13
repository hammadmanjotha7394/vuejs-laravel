<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  Printer,
  FileText,
  Mail,
  RotateCcw,
  Truck,
  CheckCircle2,
  MapPin,
  CreditCard,
  User as UserIcon,
  Clock,
  MessageSquarePlus,
  ExternalLink,
  Package,
  AlertCircle,
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const order = ref(null)
const customerStats = ref({ orders_count: 1, total_spent: 0 })
const isLoading = ref(true)
const errorMessage = ref('')
const toastMessage = ref('')

// Print mode ('invoice' | 'packing')
const printMode = ref('invoice')

// Status update form
const statusForm = reactive({
  status: 'paid',
  note: '',
  notify_customer: true,
})
const isSavingStatus = ref(false)

// Tracking form
const trackingForm = reactive({
  tracking_number: '',
  tracking_url: '',
  mark_shipped: false,
  notify_customer: true,
})
const isSavingTracking = ref(false)

// Internal note form
const noteForm = reactive({
  note: '',
  admin_notes: '',
})
const isSavingNote = ref(false)
const isResendingEmail = ref(false)

// Refund Modal
const showRefundModal = ref(false)
const refundForm = reactive({
  type: 'full', // 'full' | 'partial'
  amount: '',
  reason: '',
})
const isRefunding = ref(false)

const canUpdateOrder = computed(() => authStore.hasPermission('orders.update'))
const canRefundOrder = computed(() => authStore.hasPermission('orders.refund'))

const maxRefundable = computed(() => {
  if (!order.value) return 0
  const grand = Number(order.value.grand_total || 0)
  const refunded = Number(order.value.refunded_amount || 0)
  return Math.max(0, Number((grand - refunded).toFixed(2)))
})

const showToast = (msg) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = ''
  }, 4000)
}

const fetchOrder = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.get(`/admin/orders/${route.params.id}`)
    order.value = res.data.data
    if (res.data.customer_stats) {
      customerStats.value = res.data.customer_stats
    }

    statusForm.status = order.value.status || 'pending'
    statusForm.note = ''
    statusForm.notify_customer = ['shipped', 'delivered', 'cancelled'].includes(order.value.status)

    trackingForm.tracking_number = order.value.tracking_number || ''
    trackingForm.tracking_url = order.value.tracking_url || ''
    trackingForm.mark_shipped = false
    trackingForm.notify_customer = true

    noteForm.admin_notes = order.value.admin_notes || ''
    noteForm.note = ''
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load order details.'
  } finally {
    isLoading.value = false
  }
}

const handleStatusSelectChange = () => {
  if (['shipped', 'delivered', 'cancelled'].includes(statusForm.status)) {
    statusForm.notify_customer = true
  }
}

const updateOrderStatus = async () => {
  isSavingStatus.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.patch(`/admin/orders/${order.value.id}/status`, {
      status: statusForm.status,
      note: statusForm.note || null,
      notify_customer: statusForm.notify_customer,
      tracking_number: trackingForm.tracking_number || null,
      tracking_url: trackingForm.tracking_url || null,
    })
    order.value = res.data.data
    statusForm.note = ''
    showToast(
      statusForm.notify_customer
        ? `Order status updated to "${order.value.status}" and customer notified by email.`
        : `Order status updated to "${order.value.status}".`
    )
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update order status.'
  } finally {
    isSavingStatus.value = false
  }
}

const saveTracking = async () => {
  isSavingTracking.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.patch(`/admin/orders/${order.value.id}/tracking`, {
      tracking_number: trackingForm.tracking_number || null,
      tracking_url: trackingForm.tracking_url || null,
      mark_shipped: trackingForm.mark_shipped,
      notify_customer: trackingForm.notify_customer,
    })
    order.value = res.data.data
    statusForm.status = order.value.status
    showToast('Tracking details saved.')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update tracking details.'
  } finally {
    isSavingTracking.value = false
  }
}

const saveInternalNote = async () => {
  isSavingNote.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.post(`/admin/orders/${order.value.id}/notes`, {
      note: noteForm.note || null,
      admin_notes: noteForm.admin_notes || null,
    })
    order.value = res.data.data
    noteForm.note = ''
    showToast('Internal staff note saved.')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save internal note.'
  } finally {
    isSavingNote.value = false
  }
}

const resendConfirmation = async () => {
  isResendingEmail.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.post(`/admin/orders/${order.value.id}/resend-confirmation`)
    if (res.data.data?.status_history) {
      order.value.status_history = res.data.data.status_history
    }
    showToast(res.data.message || 'Confirmation email resent.')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to resend confirmation email.'
  } finally {
    isResendingEmail.value = false
  }
}

const openRefundDialog = () => {
  refundForm.type = 'full'
  refundForm.amount = maxRefundable.value.toFixed(2)
  refundForm.reason = ''
  showRefundModal.value = true
}

const submitRefund = async () => {
  isRefunding.value = true
  errorMessage.value = ''
  try {
    const amountToSend = refundForm.type === 'full' ? maxRefundable.value : Number(refundForm.amount)
    const res = await apiClient.post(`/admin/orders/${order.value.id}/refund`, {
      amount: amountToSend,
      reason: refundForm.reason || null,
    })
    showRefundModal.value = false
    await fetchOrder()
    showToast(res.data.message || 'Refund processed successfully.')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to process refund.'
  } finally {
    isRefunding.value = false
  }
}

const printDocument = async (mode) => {
  printMode.value = mode
  await nextTick()
  window.print()
}

const statusBadgeClass = (status) => {
  const map = {
    pending: 'bg-amber-50 text-amber-800 border-amber-200',
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    processing: 'bg-blue-50 text-blue-700 border-blue-200',
    shipped: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    delivered: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
    refunded: 'bg-red-50 text-red-700 border-red-200',
  }
  return map[status] || 'bg-surface text-ink-muted border-line'
}

const formatDate = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  fetchOrder()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Header & Action Bar (Hidden in Print) -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 print:hidden">
      <div class="flex items-start gap-3">
        <button
          type="button"
          @click="router.push('/admin/orders')"
          class="p-2 rounded-pill border border-line bg-page text-ink-muted hover:text-ink transition mt-0.5"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
        <div v-if="order">
          <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-bold tracking-tight text-ink font-mono">{{ order.order_number }}</h1>
            <span
              class="px-2.5 py-0.5 rounded-pill text-xs font-semibold capitalize border"
              :class="statusBadgeClass(order.status)"
            >
              {{ order.status }}
            </span>
            <span
              class="px-2.5 py-0.5 rounded-pill text-xs font-semibold capitalize border bg-surface text-ink-muted border-line"
            >
              Payment: {{ (order.payment_status || 'pending').replace('_', ' ') }}
            </span>
          </div>
          <p class="text-xs text-ink-muted mt-1">
            Placed on {{ formatDate(order.placed_at || order.created_at) }}
            <span v-if="order.guest_email || order.user?.email">
              · {{ order.guest_email || order.user?.email }}
            </span>
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div v-if="order" class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          @click="printDocument('packing')"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:bg-surface transition"
        >
          <Package class="w-3.5 h-3.5 text-ink-muted" />
          <span>Print Packing Slip</span>
        </button>

        <button
          type="button"
          @click="printDocument('invoice')"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:bg-surface transition"
        >
          <Printer class="w-3.5 h-3.5 text-ink-muted" />
          <span>Print Invoice</span>
        </button>

        <button
          v-if="canUpdateOrder"
          type="button"
          @click="resendConfirmation"
          :disabled="isResendingEmail"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:bg-surface transition disabled:opacity-50"
        >
          <Mail class="w-3.5 h-3.5 text-brand" />
          <span>{{ isResendingEmail ? 'Sending...' : 'Resend Confirmation' }}</span>
        </button>

        <button
          v-if="canRefundOrder && ['paid', 'partially_refunded'].includes(order.payment_status) && maxRefundable > 0"
          type="button"
          @click="openRefundDialog"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-red-50 border border-red-200 text-xs font-semibold text-error hover:bg-red-100 transition"
        >
          <RotateCcw class="w-3.5 h-3.5" />
          <span>Issue Refund</span>
        </button>
      </div>
    </div>

    <!-- Toasts / Errors -->
    <div
      v-if="toastMessage"
      class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-success px-4 py-3 rounded-card text-xs font-medium print:hidden"
    >
      <CheckCircle2 class="w-4 h-4 shrink-0" />
      <span>{{ toastMessage }}</span>
    </div>

    <div
      v-if="errorMessage"
      class="flex items-center justify-between bg-red-50 border border-red-200 text-error px-4 py-3 rounded-card text-xs font-medium print:hidden"
    >
      <div class="flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <button @click="errorMessage = ''" class="text-[11px] underline">Dismiss</button>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="isLoading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 print:hidden">
      <div class="lg:col-span-2 bg-page border border-line rounded-card p-6 h-80 animate-pulse"></div>
      <div class="bg-page border border-line rounded-card p-6 h-80 animate-pulse"></div>
    </div>

    <!-- MAIN CONTENT (Screen View) -->
    <div v-else-if="order" class="grid grid-cols-1 lg:grid-cols-3 gap-6 print:hidden">
      <!-- Left 2 Columns -->
      <div class="lg:col-span-2 space-y-6">
        <!-- 1. Order Line Items & Totals -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-ink">Order Items ({{ order.items?.length || 0 }})</h2>
            <span class="text-xs text-ink-muted">{{ order.currency || 'USD' }}</span>
          </div>

          <div class="divide-y divide-line">
            <div
              v-for="item in order.items"
              :key="item.id"
              class="py-3.5 flex items-center justify-between gap-4"
            >
              <div class="flex items-center gap-3.5 min-w-0">
                <img
                  v-if="item.image_snapshot"
                  :src="item.image_snapshot"
                  :alt="item.name_snapshot"
                  class="w-12 h-14 rounded-lg object-cover border border-line bg-surface shrink-0"
                />
                <div
                  v-else
                  class="w-12 h-14 rounded-lg border border-line bg-surface flex items-center justify-center text-ink-muted shrink-0"
                >
                  <Package class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                  <router-link
                    v-if="item.product_id"
                    :to="`/admin/products/${item.product_id}/edit`"
                    class="text-xs font-bold text-ink hover:text-brand truncate block"
                  >
                    {{ item.name_snapshot }}
                  </router-link>
                  <p v-else class="text-xs font-bold text-ink truncate">{{ item.name_snapshot }}</p>
                  <p class="text-[11px] font-mono text-ink-muted mt-0.5">SKU: {{ item.sku_snapshot || '—' }}</p>
                </div>
              </div>

              <div class="text-right shrink-0">
                <p class="text-xs font-semibold text-ink">
                  ${{ Number(item.unit_price).toFixed(2) }} × {{ item.qty }}
                </p>
                <p class="text-xs font-bold text-ink mt-0.5">${{ Number(item.line_total).toFixed(2) }}</p>
              </div>
            </div>
          </div>

          <!-- Totals Breakdown -->
          <div class="mt-5 pt-4 border-t border-line space-y-2 text-xs">
            <div class="flex justify-between text-ink-muted">
              <span>Subtotal</span>
              <span class="font-medium text-ink">${{ Number(order.subtotal).toFixed(2) }}</span>
            </div>
            <div v-if="Number(order.discount_total) > 0" class="flex justify-between text-emerald-700">
              <span>
                Discount
                <span v-if="order.coupon_code" class="ml-1 px-1.5 py-0.5 rounded bg-emerald-50 border border-emerald-200 font-mono text-[10px]">
                  {{ order.coupon_code }}
                </span>
              </span>
              <span>-${{ Number(order.discount_total).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-ink-muted">
              <span>Shipping</span>
              <span class="font-medium text-ink">
                {{ Number(order.shipping_total) > 0 ? `$${Number(order.shipping_total).toFixed(2)}` : 'Free' }}
              </span>
            </div>
            <div class="flex justify-between text-ink-muted">
              <span>Tax</span>
              <span class="font-medium text-ink">${{ Number(order.tax_total).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-sm font-bold text-ink pt-2 border-t border-line">
              <span>Grand Total</span>
              <span>${{ Number(order.grand_total).toFixed(2) }}</span>
            </div>
            <div
              v-if="Number(order.refunded_amount) > 0"
              class="flex justify-between text-xs font-semibold text-error pt-1"
            >
              <span>Refunded Amount</span>
              <span>-${{ Number(order.refunded_amount).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- 2. Fulfillment Status & Tracking Management -->
        <div v-if="canUpdateOrder" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Update Status Card -->
          <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-4">
            <h2 class="text-sm font-bold text-ink flex items-center gap-2">
              <CheckCircle2 class="w-4 h-4 text-brand" />
              <span>Update Order Status</span>
            </h2>

            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Order Status</label>
              <select
                v-model="statusForm.status"
                @change="handleStatusSelectChange"
                class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              >
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
                <option value="refunded">Refunded</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Status Note (Optional)</label>
              <textarea
                v-model="statusForm.note"
                rows="2"
                placeholder="e.g. Handed over to DHL Express courier..."
                class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              ></textarea>
            </div>

            <label class="flex items-center gap-2 text-xs text-ink cursor-pointer select-none">
              <input
                v-model="statusForm.notify_customer"
                type="checkbox"
                class="rounded border-line text-brand focus:ring-brand"
              />
              <span>Notify customer by email</span>
            </label>

            <button
              type="button"
              @click="updateOrderStatus"
              :disabled="isSavingStatus"
              class="w-full py-2.5 px-4 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition disabled:opacity-50"
            >
              {{ isSavingStatus ? 'Updating Status...' : 'Save Order Status' }}
            </button>
          </div>

          <!-- Tracking Details Card -->
          <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-4">
            <h2 class="text-sm font-bold text-ink flex items-center gap-2">
              <Truck class="w-4 h-4 text-indigo-600" />
              <span>Shipment Tracking</span>
            </h2>

            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Tracking Number</label>
              <input
                v-model="trackingForm.tracking_number"
                type="text"
                placeholder="e.g. 1Z999AA10123456784"
                class="w-full px-3 py-2 text-xs font-mono bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Carrier Tracking URL</label>
              <input
                v-model="trackingForm.tracking_url"
                type="url"
                placeholder="https://www.ups.com/track?tracknum=..."
                class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <div class="space-y-1.5">
              <label class="flex items-center gap-2 text-xs text-ink cursor-pointer select-none">
                <input
                  v-model="trackingForm.mark_shipped"
                  type="checkbox"
                  class="rounded border-line text-brand focus:ring-brand"
                />
                <span>Also mark order as <strong>Shipped</strong></span>
              </label>

              <label
                v-if="trackingForm.mark_shipped"
                class="flex items-center gap-2 text-xs text-ink-muted cursor-pointer select-none pl-5"
              >
                <input
                  v-model="trackingForm.notify_customer"
                  type="checkbox"
                  class="rounded border-line text-brand focus:ring-brand"
                />
                <span>Send shipped confirmation email with tracking link</span>
              </label>
            </div>

            <button
              type="button"
              @click="saveTracking"
              :disabled="isSavingTracking"
              class="w-full py-2.5 px-4 rounded-pill border border-line bg-surface text-ink text-xs font-semibold hover:border-brand hover:text-brand transition disabled:opacity-50"
            >
              {{ isSavingTracking ? 'Saving Tracking...' : 'Save Tracking Details' }}
            </button>
          </div>
        </div>

        <!-- 3. Internal Notes & Order Timeline -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-6">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-ink flex items-center gap-2">
              <Clock class="w-4 h-4 text-ink-muted" />
              <span>Order Timeline & Internal Notes</span>
            </h2>
          </div>

          <!-- Internal Note Editor -->
          <div v-if="canUpdateOrder" class="bg-surface border border-line rounded-xl p-4 space-y-3">
            <div>
              <label class="block text-xs font-semibold text-ink mb-1">Pinned Internal Order Summary Note</label>
              <textarea
                v-model="noteForm.admin_notes"
                rows="2"
                placeholder="Persistent internal staff note for this order (never shown to customer)..."
                class="w-full px-3 py-2 text-xs bg-page border border-line rounded-lg text-ink focus:outline-none focus:border-brand"
              ></textarea>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
              <input
                v-model="noteForm.note"
                type="text"
                placeholder="Add a timestamped entry to the order timeline..."
                class="flex-1 px-3 py-2 text-xs bg-page border border-line rounded-lg text-ink focus:outline-none focus:border-brand"
              />
              <button
                type="button"
                @click="saveInternalNote"
                :disabled="isSavingNote"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-pill bg-ink text-white text-xs font-semibold hover:opacity-90 transition disabled:opacity-50"
              >
                <MessageSquarePlus class="w-3.5 h-3.5" />
                <span>{{ isSavingNote ? 'Saving...' : 'Save Note' }}</span>
              </button>
            </div>
          </div>

          <!-- Timeline List -->
          <div class="space-y-4">
            <div
              v-for="entry in order.status_history"
              :key="entry.id"
              class="flex items-start gap-3.5 text-xs border-l-2 border-line pl-4 py-1 relative"
            >
              <div class="w-2.5 h-2.5 rounded-full bg-brand absolute -left-[6px] top-2"></div>
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="px-2 py-0.5 rounded-pill text-[10px] font-semibold capitalize border"
                    :class="statusBadgeClass(entry.status)"
                  >
                    {{ entry.status }}
                  </span>
                  <span class="text-[11px] text-ink-muted">{{ formatDate(entry.created_at) }}</span>
                  <span v-if="entry.changer" class="text-[11px] font-medium text-ink">
                    by {{ entry.changer.name }}
                  </span>
                </div>
                <p v-if="entry.note" class="text-xs text-ink mt-1">{{ entry.note }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Customer, Addresses & Payment -->
      <div class="space-y-6">
        <!-- Customer Profile Card -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-ink flex items-center gap-2">
              <UserIcon class="w-4 h-4 text-brand" />
              <span>Customer</span>
            </h2>
            <router-link
              v-if="order.user_id"
              :to="`/admin/customers?customer_id=${order.user_id}`"
              class="text-xs font-semibold text-brand hover:underline inline-flex items-center gap-1"
            >
              <span>View Profile</span>
              <ExternalLink class="w-3 h-3" />
            </router-link>
          </div>

          <div class="text-xs space-y-1.5">
            <p class="font-bold text-ink text-sm">
              {{ order.user?.name || `${order.shipping_address_json?.first_name || ''} ${order.shipping_address_json?.last_name || ''}`.trim() || 'Guest Customer' }}
            </p>
            <p class="text-ink-muted">{{ order.guest_email || order.user?.email || '—' }}</p>
            <p v-if="order.user?.phone || order.shipping_address_json?.phone" class="text-ink-muted">
              {{ order.user?.phone || order.shipping_address_json?.phone }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3 pt-3 border-t border-line text-xs">
            <div class="bg-surface rounded-xl p-3">
              <span class="text-[11px] text-ink-muted block">Total Orders</span>
              <span class="text-sm font-bold text-ink">{{ customerStats.orders_count }}</span>
            </div>
            <div class="bg-surface rounded-xl p-3">
              <span class="text-[11px] text-ink-muted block">Lifetime Spend</span>
              <span class="text-sm font-bold text-ink">${{ Number(customerStats.total_spent).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Shipping Address Card -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-3">
          <h2 class="text-sm font-bold text-ink flex items-center gap-2">
            <MapPin class="w-4 h-4 text-brand" />
            <span>Shipping Address</span>
          </h2>

          <div v-if="order.shipping_address_json" class="text-xs text-ink space-y-1 leading-relaxed">
            <p class="font-semibold">
              {{ order.shipping_address_json.first_name }} {{ order.shipping_address_json.last_name }}
            </p>
            <p>{{ order.shipping_address_json.line1 }}</p>
            <p v-if="order.shipping_address_json.line2">{{ order.shipping_address_json.line2 }}</p>
            <p>
              {{ order.shipping_address_json.city }}<span v-if="order.shipping_address_json.state">, {{ order.shipping_address_json.state }}</span>
              {{ order.shipping_address_json.postal_code }}
            </p>
            <p class="font-medium">{{ order.shipping_address_json.country }}</p>
            <p v-if="order.shipping_address_json.phone" class="text-ink-muted pt-1">
              Phone: {{ order.shipping_address_json.phone }}
            </p>
          </div>

          <div v-if="order.notes" class="pt-3 border-t border-line">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-ink-muted block mb-1">
              Customer Delivery Note
            </span>
            <p class="text-xs text-ink bg-surface p-3 rounded-xl border border-line">{{ order.notes }}</p>
          </div>
        </div>

        <!-- Payment & Stripe Metadata Card -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-3">
          <h2 class="text-sm font-bold text-ink flex items-center gap-2">
            <CreditCard class="w-4 h-4 text-brand" />
            <span>Payment & Stripe Info</span>
          </h2>

          <div class="text-xs space-y-2">
            <div class="flex justify-between">
              <span class="text-ink-muted">Payment Status</span>
              <span class="font-semibold capitalize text-ink">{{ (order.payment_status || 'pending').replace('_', ' ') }}</span>
            </div>
            <div v-if="order.stripe_payment_intent_id" class="space-y-0.5">
              <span class="text-ink-muted block">Stripe PaymentIntent</span>
              <span class="font-mono text-[11px] text-ink break-all bg-surface px-2 py-1 rounded block border border-line">
                {{ order.stripe_payment_intent_id }}
              </span>
            </div>
            <div v-if="order.stripe_session_id" class="space-y-0.5">
              <span class="text-ink-muted block">Stripe Session ID</span>
              <span class="font-mono text-[11px] text-ink-muted break-all bg-surface px-2 py-1 rounded block border border-line">
                {{ order.stripe_session_id }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PRINT-ONLY TEMPLATE (Invoice OR Packing Slip) -->
    <div v-if="order" class="hidden print:block bg-white text-black p-8 space-y-6">
      <div class="flex items-start justify-between border-b border-gray-300 pb-6">
        <div>
          <h1 class="text-2xl font-bold uppercase tracking-wider">
            {{ printMode === 'packing' ? 'Packing Slip' : 'Tax Invoice' }}
          </h1>
          <p class="text-sm font-mono mt-1">Order: {{ order.order_number }}</p>
          <p class="text-xs text-gray-600 mt-0.5">
            Date: {{ formatDate(order.placed_at || order.created_at) }}
          </p>
        </div>
        <div class="text-right text-xs">
          <p class="font-bold text-base">Maison Boutique</p>
          <p>Order Fulfillment Department</p>
          <p v-if="order.tracking_number" class="font-mono mt-1">Tracking: {{ order.tracking_number }}</p>
        </div>
      </div>

      <!-- Ship To -->
      <div class="grid grid-cols-2 gap-6 text-xs">
        <div>
          <h3 class="font-bold uppercase text-gray-500 mb-1">Ship To</h3>
          <div v-if="order.shipping_address_json">
            <p class="font-bold">
              {{ order.shipping_address_json.first_name }} {{ order.shipping_address_json.last_name }}
            </p>
            <p>{{ order.shipping_address_json.line1 }}</p>
            <p v-if="order.shipping_address_json.line2">{{ order.shipping_address_json.line2 }}</p>
            <p>
              {{ order.shipping_address_json.city }}, {{ order.shipping_address_json.state }}
              {{ order.shipping_address_json.postal_code }}
            </p>
            <p>{{ order.shipping_address_json.country }}</p>
          </div>
        </div>
        <div>
          <h3 class="font-bold uppercase text-gray-500 mb-1">Customer</h3>
          <p>{{ order.guest_email || order.user?.email }}</p>
          <p v-if="order.notes" class="mt-2 italic">Note: {{ order.notes }}</p>
        </div>
      </div>

      <!-- Items Table -->
      <table class="w-full text-xs border-collapse">
        <thead>
          <tr class="border-b-2 border-gray-300 text-left">
            <th class="py-2">Item</th>
            <th class="py-2">SKU</th>
            <th class="py-2 text-right">Qty</th>
            <th v-if="printMode === 'invoice'" class="py-2 text-right">Unit Price</th>
            <th v-if="printMode === 'invoice'" class="py-2 text-right">Line Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in order.items" :key="item.id">
            <td class="py-2.5 font-medium">{{ item.name_snapshot }}</td>
            <td class="py-2.5 font-mono">{{ item.sku_snapshot || '—' }}</td>
            <td class="py-2.5 text-right font-bold">{{ item.qty }}</td>
            <td v-if="printMode === 'invoice'" class="py-2.5 text-right">${{ Number(item.unit_price).toFixed(2) }}</td>
            <td v-if="printMode === 'invoice'" class="py-2.5 text-right font-bold">${{ Number(item.line_total).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Invoice Totals (Hidden on Packing Slip) -->
      <div v-if="printMode === 'invoice'" class="w-64 ml-auto space-y-1.5 text-xs pt-4 border-t border-gray-300">
        <div class="flex justify-between">
          <span>Subtotal:</span>
          <span>${{ Number(order.subtotal).toFixed(2) }}</span>
        </div>
        <div v-if="Number(order.discount_total) > 0" class="flex justify-between">
          <span>Discount:</span>
          <span>-${{ Number(order.discount_total).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Shipping:</span>
          <span>${{ Number(order.shipping_total).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Tax:</span>
          <span>${{ Number(order.tax_total).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between font-bold text-sm pt-2 border-t border-gray-300">
          <span>Total:</span>
          <span>${{ Number(order.grand_total).toFixed(2) }}</span>
        </div>
      </div>
    </div>

    <!-- REFUND MODAL -->
    <div
      v-if="showRefundModal && order"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 print:hidden"
    >
      <div class="bg-page border border-line rounded-card max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-ink">Issue Stripe Refund</h3>
          <button @click="showRefundModal = false" class="text-xs text-ink-muted hover:text-ink">Close</button>
        </div>

        <p class="text-xs text-ink-muted">
          Refund order <span class="font-mono font-bold text-ink">{{ order.order_number }}</span> via Stripe. Maximum refundable balance:
          <strong class="text-ink">${{ maxRefundable.toFixed(2) }}</strong>.
        </p>

        <div class="grid grid-cols-2 gap-2.5">
          <button
            type="button"
            @click="refundForm.type = 'full'; refundForm.amount = maxRefundable.toFixed(2)"
            class="py-2 px-3 rounded-xl text-xs font-semibold border transition"
            :class="refundForm.type === 'full' ? 'bg-brand text-white border-brand' : 'bg-surface text-ink border-line'"
          >
            Full Refund (${{ maxRefundable.toFixed(2) }})
          </button>
          <button
            type="button"
            @click="refundForm.type = 'partial'"
            class="py-2 px-3 rounded-xl text-xs font-semibold border transition"
            :class="refundForm.type === 'partial' ? 'bg-brand text-white border-brand' : 'bg-surface text-ink border-line'"
          >
            Partial Refund
          </button>
        </div>

        <div v-if="refundForm.type === 'partial'">
          <label class="block text-xs font-semibold text-ink mb-1">Refund Amount ($)</label>
          <input
            v-model="refundForm.amount"
            type="number"
            step="0.01"
            min="0.01"
            :max="maxRefundable"
            class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-ink mb-1">Reason for Refund</label>
          <input
            v-model="refundForm.reason"
            type="text"
            placeholder="e.g. Customer returned item / Sizing exchange"
            class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
          />
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button
            type="button"
            @click="showRefundModal = false"
            class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink hover:bg-surface"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="submitRefund"
            :disabled="isRefunding"
            class="px-4 py-2 rounded-pill bg-error text-white text-xs font-semibold hover:opacity-90 disabled:opacity-50"
          >
            {{ isRefunding ? 'Processing Refund...' : 'Confirm Refund' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
