<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import {
  Mail,
  Send,
  Eye,
  Edit3,
  RotateCcw,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Search,
  Copy,
  Terminal,
  Sparkles,
  X,
  Settings,
} from 'lucide-vue-next'

const toastMsg = ref('')
const toastType = ref('success')
let toastTimer = null

function showToast(msg, type = 'success') {
  toastMsg.value = msg
  toastType.value = type
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    toastMsg.value = ''
  }, 3500)
}

const toast = {
  success: (m) => showToast(m, 'success'),
  error: (m) => showToast(m, 'error'),
  info: (m) => showToast(m, 'info'),
}

const api = {
  get: (url, cfg) => axios.get(`/api${url}`, cfg),
  post: (url, data, cfg) => axios.post(`/api${url}`, data, cfg),
  put: (url, data, cfg) => axios.put(`/api${url}`, data, cfg),
}

const activeTab = ref('templates') // 'templates' | 'logs' | 'cron'
const audienceFilter = ref('all') // 'all' | 'customer' | 'admin'
const loading = ref(false)

const templates = ref([])
const smtpSummary = ref({})
const cronCommands = ref({
  schedule: '* * * * * cd /home/USER/shop-app && php artisan schedule:run >> /dev/null 2>&1',
  queue: '* * * * * cd /home/USER/shop-app && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1',
})

// Editor & Live Preview Modal
const showEditorModal = ref(false)
const editingTemplate = ref(null)
const form = ref({
  subject: '',
  body_html: '',
  is_active: true,
})
const previewHtml = ref('')
const previewSubject = ref('')
const previewLoading = ref(false)
const savingTemplate = ref(false)
const testEmailRecipient = ref('')
const sendingTestId = ref(null)

// Email Logs State
const logs = ref([])
const logsSummary = ref({ total: 0, sent: 0, failed: 0 })
const logsLoading = ref(false)
const logStatusFilter = ref('')
const logSearch = ref('')
const logPage = ref(1)
const logLastPage = ref(1)
const resendingLogId = ref(null)
const viewingLog = ref(null)

const filteredTemplates = computed(() => {
  if (audienceFilter.value === 'all') return templates.value
  return templates.value.filter((t) => t.audience === audienceFilter.value)
})

async function fetchTemplates() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/email-templates')
    templates.value = data.data || []
    smtpSummary.value = data.smtp_summary || {}
    if (data.cron_commands) {
      cronCommands.value = data.cron_commands
    }
  } catch (err) {
    toast.error('Failed to load email templates')
  } finally {
    loading.value = false
  }
}

async function fetchLogs(page = 1) {
  logsLoading.value = true
  logPage.value = page
  try {
    const { data } = await api.get('/admin/email-logs', {
      params: {
        page,
        status: logStatusFilter.value || undefined,
        search: logSearch.value || undefined,
      },
    })
    logs.value = data.data || []
    logLastPage.value = data.last_page || 1
    logsSummary.value = data.summary || { total: 0, sent: 0, failed: 0 }
  } catch (err) {
    toast.error('Failed to load email delivery logs')
  } finally {
    logsLoading.value = false
  }
}

async function toggleActive(tpl) {
  const newStatus = !tpl.is_active
  try {
    await api.put(`/admin/email-templates/${tpl.id}`, {
      is_active: newStatus,
    })
    tpl.is_active = newStatus
    toast.success(`Template '${tpl.name}' ${newStatus ? 'enabled' : 'disabled'}`)
  } catch (err) {
    toast.error('Could not update template status')
  }
}

async function openEditor(tpl) {
  editingTemplate.value = tpl
  form.value = {
    subject: tpl.subject,
    body_html: tpl.body_html,
    is_active: tpl.is_active,
  }
  showEditorModal.value = true
  await refreshPreview()
}

async function refreshPreview() {
  if (!editingTemplate.value) return
  previewLoading.value = true
  try {
    const { data } = await api.post(`/admin/email-templates/${editingTemplate.value.id}/preview`, {
      subject: form.value.subject,
      body_html: form.value.body_html,
    })
    previewSubject.value = data.data?.subject || form.value.subject
    previewHtml.value = data.data?.html || ''
  } catch (err) {
    // Fallback
  } finally {
    previewLoading.value = false
  }
}

function insertPlaceholder(token) {
  form.value.body_html += ` ${token}`
  refreshPreview()
  toast.info(`Inserted ${token}`)
}

async function saveTemplate() {
  if (!editingTemplate.value) return
  savingTemplate.value = true
  try {
    const { data } = await api.put(`/admin/email-templates/${editingTemplate.value.id}`, form.value)
    const idx = templates.value.findIndex((t) => t.id === editingTemplate.value.id)
    if (idx !== -1) {
      templates.value[idx] = {
        ...templates.value[idx],
        subject: data.data.subject,
        body_html: data.data.body_html,
        is_active: data.data.is_active,
      }
    }
    toast.success('Email template saved')
    showEditorModal.value = false
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to save template')
  } finally {
    savingTemplate.value = false
  }
}

async function resetTemplate() {
  if (!editingTemplate.value) return
  try {
    const { data } = await api.post(`/admin/email-templates/${editingTemplate.value.id}/reset`)
    form.value.subject = data.data.subject
    form.value.body_html = data.data.body_html
    form.value.is_active = data.data.is_active
    const idx = templates.value.findIndex((t) => t.id === editingTemplate.value.id)
    if (idx !== -1) {
      templates.value[idx].subject = data.data.subject
      templates.value[idx].body_html = data.data.body_html
    }
    await refreshPreview()
    toast.success('Restored default template content')
  } catch (err) {
    toast.error('Failed to reset template')
  }
}

async function sendTestTemplate(tpl) {
  sendingTestId.value = tpl.id
  try {
    const { data } = await api.post(`/admin/email-templates/${tpl.id}/test`, {
      email: testEmailRecipient.value || undefined,
    })
    toast.success(data.message || 'Test email sent!')
    if (activeTab.value === 'logs') {
      fetchLogs(logPage.value)
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Test email failed')
  } finally {
    sendingTestId.value = null
  }
}

async function resendLoggedEmail(log) {
  resendingLogId.value = log.id
  try {
    const { data } = await api.post(`/admin/email-logs/${log.id}/resend`)
    toast.success(data.message || 'Email resent!')
    await fetchLogs(logPage.value)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Resend failed')
    await fetchLogs(logPage.value)
  } finally {
    resendingLogId.value = null
  }
}

function copyText(text) {
  navigator.clipboard?.writeText(text)
  toast.success('Copied to clipboard')
}

onMounted(() => {
  fetchTemplates()
  fetchLogs()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Toast Feedback Banner -->
    <div
      v-if="toastMsg"
      class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-medium flex items-center gap-2"
      :class="toastType === 'error' ? 'bg-red-50 border-red-200 text-[#DC2626]' : 'bg-[#111111] border-[#111111] text-white'"
    >
      <span>{{ toastMsg }}</span>
    </div>

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-[#111111] tracking-tight">Email Notifications & SMTP Logs</h1>
        <p class="text-sm text-[#6B7280] mt-1">
          Customize table-based HTML email templates with live placeholders and monitor Hostinger SMTP delivery.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <div class="hidden md:flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-[#EAE8E3] text-xs text-[#6B7280]">
          <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
          <span>SMTP: <strong class="text-[#111111]">{{ smtpSummary.host || 'smtp.hostinger.com' }}:{{ smtpSummary.port || 465 }}</strong></span>
        </div>
        <router-link
          to="/admin/settings"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-[#EAE8E3] bg-white text-xs font-medium text-[#111111] hover:bg-[#F8F7F4] transition-colors"
        >
          <Settings class="w-3.5 h-3.5" />
          SMTP Settings
        </router-link>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#EAE8E3]">
      <div class="flex items-center gap-6">
        <button
          type="button"
          class="pb-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
          :class="activeTab === 'templates' ? 'border-[#111111] text-[#111111]' : 'border-transparent text-[#6B7280] hover:text-[#111111]'"
          @click="activeTab = 'templates'"
        >
          <Mail class="w-4 h-4" />
          Email Templates ({{ templates.length }})
        </button>
        <button
          type="button"
          class="pb-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
          :class="activeTab === 'logs' ? 'border-[#111111] text-[#111111]' : 'border-transparent text-[#6B7280] hover:text-[#111111]'"
          @click="activeTab = 'logs'; fetchLogs(1)"
        >
          <Send class="w-4 h-4" />
          Email Delivery Logs
          <span class="px-2 py-0.5 text-[11px] rounded-full bg-[#F8F7F4] text-[#111111] border border-[#EAE8E3]">
            {{ logsSummary.total }}
          </span>
        </button>
        <button
          type="button"
          class="pb-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
          :class="activeTab === 'cron' ? 'border-[#111111] text-[#111111]' : 'border-transparent text-[#6B7280] hover:text-[#111111]'"
          @click="activeTab = 'cron'"
        >
          <Terminal class="w-4 h-4" />
          Hostinger Queue & Cron
        </button>
      </div>

      <!-- Audience Filter for Templates -->
      <div v-if="activeTab === 'templates'" class="flex items-center gap-1.5 pb-2">
        <button
          v-for="opt in [
            { key: 'all', label: 'All' },
            { key: 'customer', label: 'Customer Emails' },
            { key: 'admin', label: 'Admin Alerts' },
          ]"
          :key="opt.key"
          type="button"
          class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
          :class="audienceFilter === opt.key ? 'bg-[#111111] text-white' : 'bg-white border border-[#EAE8E3] text-[#6B7280] hover:text-[#111111]'"
          @click="audienceFilter = opt.key"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <!-- TAB 1: EMAIL TEMPLATES -->
    <div v-if="activeTab === 'templates'" class="space-y-4">
      <!-- Quick Test Recipient Bar -->
      <div class="bg-white border border-[#EAE8E3] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 text-xs text-[#6B7280]">
          <Sparkles class="w-4 h-4 text-[var(--brand)] shrink-0" />
          <span>
            All templates are rendered inside a <strong>600px Gmail/Outlook-safe table layout</strong> with your store logo, brand color, and footer address.
          </span>
        </div>
        <div class="flex items-center gap-2">
          <input
            v-model="testEmailRecipient"
            type="email"
            placeholder="Test recipient email (optional)"
            class="px-3 py-1.5 text-xs border border-[#EAE8E3] rounded-lg focus:outline-none focus:border-[#111111] w-56"
          />
        </div>
      </div>

      <!-- Templates Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="tpl in filteredTemplates"
          :key="tpl.id"
          class="bg-white border border-[#EAE8E3] rounded-xl p-5 flex flex-col justify-between gap-4 hover:border-[#D5D1C8] transition-colors"
        >
          <div class="space-y-2">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-sm font-semibold text-[#111111]">{{ tpl.name }}</h3>
                  <span
                    class="px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider rounded-full"
                    :class="tpl.audience === 'admin' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'"
                  >
                    {{ tpl.audience === 'admin' ? 'Staff Alert' : 'Customer' }}
                  </span>
                </div>
                <p class="text-xs text-[#6B7280] mt-0.5">{{ tpl.description }}</p>
              </div>

              <!-- Enable / Disable Toggle -->
              <button
                type="button"
                role="switch"
                :aria-checked="tpl.is_active"
                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                :class="tpl.is_active ? 'bg-[#15803D]' : 'bg-gray-200'"
                @click="toggleActive(tpl)"
              >
                <span
                  class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200"
                  :class="tpl.is_active ? 'translate-x-4' : 'translate-x-0'"
                />
              </button>
            </div>

            <div class="pt-2 border-t border-[#F2F0EC] text-xs space-y-1">
              <div class="flex items-center gap-2 text-[#6B7280]">
                <span class="font-medium text-[#111111]">Subject:</span>
                <span class="truncate">{{ tpl.subject }}</span>
              </div>
              <div class="flex flex-wrap gap-1 pt-1">
                <span
                  v-for="ph in tpl.placeholders"
                  :key="ph"
                  class="px-2 py-0.5 rounded bg-[#F8F7F4] text-[11px] font-mono text-[#6B7280]"
                >
                  {{ ph }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between pt-3 border-t border-[#EAE8E3]">
            <code class="text-[11px] text-[#6B7280] font-mono">{{ tpl.key }}</code>
            <div class="flex items-center gap-2">
              <button
                type="button"
                :disabled="sendingTestId === tpl.id"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-[#EAE8E3] text-xs font-medium text-[#111111] hover:bg-[#F8F7F4] transition-colors disabled:opacity-50"
                @click="sendTestTemplate(tpl)"
              >
                <Send class="w-3.5 h-3.5" />
                {{ sendingTestId === tpl.id ? 'Sending...' : 'Send Test' }}
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-[#111111] text-white text-xs font-medium hover:opacity-90 transition-opacity"
                @click="openEditor(tpl)"
              >
                <Edit3 class="w-3.5 h-3.5" />
                Edit & Preview
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: EMAIL DELIVERY LOGS -->
    <div v-else-if="activeTab === 'logs'" class="space-y-4">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-[#EAE8E3] rounded-xl p-4">
          <div class="text-xs text-[#6B7280]">Total Logged Emails</div>
          <div class="text-2xl font-semibold text-[#111111] mt-1 tabular-nums">{{ logsSummary.total }}</div>
        </div>
        <div class="bg-white border border-[#EAE8E3] rounded-xl p-4">
          <div class="text-xs text-[#15803D] font-medium">Delivered / Sent</div>
          <div class="text-2xl font-semibold text-[#15803D] mt-1 tabular-nums">{{ logsSummary.sent }}</div>
        </div>
        <div class="bg-white border border-[#EAE8E3] rounded-xl p-4">
          <div class="text-xs text-[#DC2626] font-medium">Failed Deliveries</div>
          <div class="text-2xl font-semibold text-[#DC2626] mt-1 tabular-nums">{{ logsSummary.failed }}</div>
        </div>
      </div>

      <!-- Search & Filter Bar -->
      <div class="bg-white border border-[#EAE8E3] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="relative flex-1 max-w-md">
          <Search class="w-4 h-4 text-[#6B7280] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="logSearch"
            type="text"
            placeholder="Search recipient email, subject, or template key..."
            class="w-full pl-9 pr-4 py-2 text-xs border border-[#EAE8E3] rounded-lg focus:outline-none focus:border-[#111111]"
            @keyup.enter="fetchLogs(1)"
          />
        </div>
        <div class="flex items-center gap-2">
          <select
            v-model="logStatusFilter"
            class="px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg bg-white focus:outline-none focus:border-[#111111]"
            @change="fetchLogs(1)"
          >
            <option value="">All Statuses</option>
            <option value="sent">Sent</option>
            <option value="failed">Failed</option>
          </select>
          <button
            type="button"
            class="px-3.5 py-2 rounded-lg border border-[#EAE8E3] text-xs font-medium hover:bg-[#F8F7F4]"
            @click="fetchLogs(1)"
          >
            Refresh
          </button>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="bg-white border border-[#EAE8E3] rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-[#EAE8E3] bg-[#FAF9F6] text-[11px] font-semibold uppercase tracking-wider text-[#6B7280]">
                <th class="py-3 px-4">Recipient</th>
                <th class="py-3 px-4">Subject & Template</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Sent At</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EAE8E3] text-xs">
              <tr v-if="!logs.length && !logsLoading">
                <td colspan="5" class="py-10 text-center text-[#6B7280]">No email logs found.</td>
              </tr>
              <tr v-for="log in logs" :key="log.id" class="hover:bg-[#FAF9F6]">
                <td class="py-3 px-4 font-medium text-[#111111]">{{ log.to }}</td>
                <td class="py-3 px-4">
                  <div class="font-medium text-[#111111]">{{ log.subject }}</div>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="font-mono text-[11px] text-[#6B7280]">{{ log.template_key || 'custom' }}</span>
                    <span v-if="log.error" class="text-[11px] text-[#DC2626] truncate max-w-xs" :title="log.error">
                      — {{ log.error }}
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium"
                    :class="log.status === 'sent' ? 'bg-emerald-50 text-[#15803D]' : 'bg-red-50 text-[#DC2626]'"
                  >
                    <CheckCircle2 v-if="log.status === 'sent'" class="w-3 h-3" />
                    <AlertCircle v-else class="w-3 h-3" />
                    {{ log.status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-[#6B7280] tabular-nums">
                  {{ log.sent_at ? new Date(log.sent_at).toLocaleString() : '—' }}
                </td>
                <td class="py-3 px-4 text-right">
                  <div class="inline-flex items-center gap-2">
                    <button
                      v-if="log.body_html"
                      type="button"
                      class="px-2.5 py-1 rounded border border-[#EAE8E3] text-[11px] font-medium hover:bg-[#F8F7F4]"
                      @click="viewingLog = log"
                    >
                      View HTML
                    </button>
                    <button
                      type="button"
                      :disabled="resendingLogId === log.id"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-[#EAE8E3] text-[11px] font-medium text-[#111111] hover:bg-[#F8F7F4] disabled:opacity-50"
                      @click="resendLoggedEmail(log)"
                    >
                      <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': resendingLogId === log.id }" />
                      Resend
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 3: HOSTINGER QUEUE & CRON -->
    <div v-else-if="activeTab === 'cron'" class="bg-white border border-[#EAE8E3] rounded-xl p-6 space-y-5">
      <div>
        <h2 class="text-base font-semibold text-[#111111]">Hostinger Business Shared Hosting — Cron & Queue Setup</h2>
        <p class="text-xs text-[#6B7280] mt-1">
          In Hostinger hPanel, open <strong>Advanced → Cron Jobs</strong> (select Type: Custom, every minute) and add these two commands so queued emails and scheduled tasks run automatically without a permanent daemon:
        </p>
      </div>

      <div class="space-y-3">
        <div class="p-4 rounded-xl bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-between gap-4">
          <div>
            <div class="text-xs font-semibold text-[#111111] mb-1">1. Database Queue Worker (processes queued SMTP emails every minute)</div>
            <code class="text-xs font-mono text-[#111111] break-all">{{ cronCommands.queue }}</code>
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#EAE8E3] text-xs font-medium hover:bg-gray-50"
            @click="copyText(cronCommands.queue)"
          >
            <Copy class="w-3.5 h-3.5" />
            Copy
          </button>
        </div>

        <div class="p-4 rounded-xl bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-between gap-4">
          <div>
            <div class="text-xs font-semibold text-[#111111] mb-1">2. Task Scheduler (abandoned cart reminders, daily cleanup & backups)</div>
            <code class="text-xs font-mono text-[#111111] break-all">{{ cronCommands.schedule }}</code>
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#EAE8E3] text-xs font-medium hover:bg-gray-50"
            @click="copyText(cronCommands.schedule)"
          >
            <Copy class="w-3.5 h-3.5" />
            Copy
          </button>
        </div>
      </div>
    </div>

    <!-- TEMPLATE EDITOR + LIVE 600PX PREVIEW MODAL -->
    <div
      v-if="showEditorModal && editingTemplate"
      class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl border border-[#EAE8E3] w-full max-w-6xl max-h-[92vh] flex flex-col overflow-hidden shadow-xl">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-[#EAE8E3] flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-[#111111]">
              Edit Template: {{ editingTemplate.name }}
              <span class="ml-2 text-xs font-mono text-[#6B7280]">({{ editingTemplate.key }})</span>
            </h3>
            <p class="text-xs text-[#6B7280]">Click any placeholder pill to insert it into your template.</p>
          </div>
          <button type="button" class="p-1.5 rounded-lg text-[#6B7280] hover:text-[#111111]" @click="showEditorModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Split Editor & Live Preview -->
        <div class="flex-1 overflow-y-auto grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-[#EAE8E3]">
          <!-- Left: Form & Placeholders -->
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-[#111111] mb-1.5">Email Subject Line</label>
              <input
                v-model="form.subject"
                type="text"
                class="w-full px-3.5 py-2 text-sm border border-[#EAE8E3] rounded-lg focus:outline-none focus:border-[#111111]"
                @input="refreshPreview"
              />
            </div>

            <!-- Placeholders Helper -->
            <div>
              <label class="block text-xs font-semibold text-[#111111] mb-1.5">Available Placeholders (Click to insert)</label>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="ph in editingTemplate.placeholders"
                  :key="ph"
                  type="button"
                  class="px-2.5 py-1 rounded-md bg-[#F8F7F4] hover:bg-[#EAE8E3] text-xs font-mono text-[#111111] transition-colors"
                  @click="insertPlaceholder(ph)"
                >
                  {{ ph }}
                </button>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-semibold text-[#111111]">Inner HTML Content (Table-wrapped automatically)</label>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 text-xs text-[#6B7280] hover:text-[#111111]"
                  @click="resetTemplate"
                >
                  <RotateCcw class="w-3.5 h-3.5" />
                  Restore Default
                </button>
              </div>
              <textarea
                v-model="form.body_html"
                rows="12"
                class="w-full px-3.5 py-2.5 text-xs font-mono border border-[#EAE8E3] rounded-lg focus:outline-none focus:border-[#111111]"
                @input="refreshPreview"
              ></textarea>
            </div>
          </div>

          <!-- Right: Live 600px Email Preview -->
          <div class="p-6 bg-[#F8F7F4] flex flex-col">
            <div class="flex items-center justify-between mb-3">
              <div>
                <span class="text-xs font-semibold text-[#111111] flex items-center gap-1.5">
                  <Eye class="w-4 h-4" />
                  Live 600px Email Preview
                </span>
                <p class="text-xs text-[#6B7280] mt-0.5">Subject: <strong class="text-[#111111]">{{ previewSubject }}</strong></p>
              </div>
              <button
                type="button"
                class="px-3 py-1 rounded-full bg-white border border-[#EAE8E3] text-xs font-medium"
                @click="refreshPreview"
              >
                Refresh Preview
              </button>
            </div>

            <div class="flex-1 bg-white rounded-xl border border-[#EAE8E3] overflow-hidden min-h-[380px]">
              <iframe
                :srcdoc="previewHtml"
                title="Email Template Preview"
                class="w-full h-full min-h-[380px] border-0"
              />
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-[#EAE8E3] bg-[#FAF9F6] flex items-center justify-between gap-4">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border border-[#EAE8E3] bg-white text-xs font-medium text-[#111111] hover:bg-[#F8F7F4]"
            @click="sendTestTemplate(editingTemplate)"
          >
            <Send class="w-3.5 h-3.5" />
            Send Test to Myself
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded-full border border-[#EAE8E3] bg-white text-xs font-medium text-[#6B7280] hover:text-[#111111]"
              @click="showEditorModal = false"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="savingTemplate"
              class="px-5 py-2 rounded-full bg-[#111111] text-white text-xs font-medium hover:opacity-90 disabled:opacity-50"
              @click="saveTemplate"
            >
              {{ savingTemplate ? 'Saving...' : 'Save Template' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- VIEW LOGGED EMAIL HTML MODAL -->
    <div
      v-if="viewingLog"
      class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl border border-[#EAE8E3] w-full max-w-3xl max-h-[88vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-[#EAE8E3] flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-[#111111]">{{ viewingLog.subject }}</h3>
            <p class="text-xs text-[#6B7280]">To: {{ viewingLog.to }} · {{ viewingLog.template_key }}</p>
          </div>
          <button type="button" class="p-1.5 text-[#6B7280] hover:text-[#111111]" @click="viewingLog = null">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="flex-1 bg-[#F8F7F4] p-4 overflow-y-auto">
          <iframe :srcdoc="viewingLog.body_html" class="w-full h-[460px] bg-white rounded-xl border border-[#EAE8E3]" />
        </div>
      </div>
    </div>
  </div>
</template>
