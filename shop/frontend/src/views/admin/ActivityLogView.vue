<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  Activity,
  Mail,
  MessageSquare,
  ArrowRightLeft,
  Search,
  Download,
  Trash2,
  CheckCircle2,
  Plus,
} from 'lucide-vue-next'

const activeTab = ref('activity') // 'activity' | 'newsletter' | 'contact' | 'redirects'

const logs = ref([])
const logsTotal = ref(0)
const logSearch = ref('')
const loadingLogs = ref(false)

const subscribers = ref([])
const subscribersActive = ref(0)
const subSearch = ref('')

const contactMessages = ref([])
const unreadContactCount = ref(0)

const redirects = ref([])
const redirectForm = ref({ source_path: '', target_path: '', status_code: 301 })

const toastMsg = ref('')
function notify(msg) {
  toastMsg.value = msg
  setTimeout(() => {
    toastMsg.value = ''
  }, 3000)
}

async function fetchActivityLogs() {
  loadingLogs.value = true
  try {
    const { data } = await axios.get('/api/admin/activity-logs', {
      params: { search: logSearch.value || undefined },
    })
    logs.value = data.data || []
    logsTotal.value = data.total || 0
  } finally {
    loadingLogs.value = false
  }
}

async function fetchSubscribers() {
  const { data } = await axios.get('/api/admin/newsletter', {
    params: { search: subSearch.value || undefined },
  })
  subscribers.value = data.data || []
  subscribersActive.value = data.active_count || 0
}

function exportSubscribersCsv() {
  window.open('/api/admin/newsletter/export-csv', '_blank')
}

async function deleteSubscriber(sub) {
  await axios.delete(`/api/admin/newsletter/${sub.id}`)
  notify('Subscriber removed')
  await fetchSubscribers()
}

async function fetchContactMessages() {
  const { data } = await axios.get('/api/admin/contact-messages')
  contactMessages.value = data.data || []
  unreadContactCount.value = data.unread_count || 0
}

async function updateContactStatus(msg, status) {
  await axios.patch(`/api/admin/contact-messages/${msg.id}`, { status })
  msg.status = status
  notify(`Marked as ${status}`)
  await fetchContactMessages()
}

async function deleteContactMessage(msg) {
  await axios.delete(`/api/admin/contact-messages/${msg.id}`)
  notify('Message deleted')
  await fetchContactMessages()
}

async function fetchRedirects() {
  const { data } = await axios.get('/api/admin/redirects')
  redirects.value = data.data || []
}

async function createRedirect() {
  if (!redirectForm.value.source_path || !redirectForm.value.target_path) return
  await axios.post('/api/admin/redirects', redirectForm.value)
  redirectForm.value = { source_path: '', target_path: '', status_code: 301 }
  notify('301 redirect rule saved')
  await fetchRedirects()
}

async function deleteRedirect(r) {
  await axios.delete(`/api/admin/redirects/${r.id}`)
  notify('Redirect deleted')
  await fetchRedirects()
}

onMounted(() => {
  fetchActivityLogs()
  fetchSubscribers()
  fetchContactMessages()
  fetchRedirects()
})
</script>

<template>
  <div class="space-y-6">
    <div
      v-if="toastMsg"
      class="fixed bottom-6 right-6 z-50 px-4 py-2.5 rounded-xl bg-[#111111] text-white text-xs font-medium shadow-lg"
    >
      {{ toastMsg }}
    </div>

    <div>
      <h1 class="text-2xl font-semibold text-[#111111] tracking-tight">System Audit, Inbox & SEO Redirects</h1>
      <p class="text-sm text-[#6B7280] mt-1">
        Super admin activity log, newsletter subscribers, contact inquiries, and 301 URL redirect rules.
      </p>
    </div>

    <!-- Tabs -->
    <div class="flex flex-wrap items-center gap-6 border-b border-[#EAE8E3]">
      <button
        type="button"
        class="pb-3 text-sm font-medium border-b-2 flex items-center gap-2"
        :class="activeTab === 'activity' ? 'border-[#111111] text-[#111111]' : 'border-transparent text-[#6B7280]'"
        @click="activeTab = 'activity'"
      >
        <Activity class="w-4 h-4" />
        Activity Log ({{ logsTotal }})
      </button>
      <button
        type="button"
        class="pb-3 text-sm font-medium border-b-2 flex items-center gap-2"
        :class="activeTab === 'contact' ? 'border-[#111111] text-[#111111]' : 'border-transparent text-[#6B7280]'"
        @click="activeTab = 'contact'"
      >
        <MessageSquare class="w-4 h-4" />
        Contact Inbox
        <span v-if="unreadContactCount" class="px-2 py-0.5 text-[11px] rounded-full bg-[#111111] text-white">
          {{ unreadContactCount }}
        </span>
      </button>
      <button
        type="button"
        class="pb-3 text-sm font-medium border-b-2 flex items-center gap-2"
        :class="activeTab === 'newsletter' ? 'border-[#111111] text-[#111111]' : 'border-transparent text-[#6B7280]'"
        @click="activeTab = 'newsletter'"
      >
        <Mail class="w-4 h-4" />
        Newsletter ({{ subscribersActive }})
      </button>
      <button
        type="button"
        class="pb-3 text-sm font-medium border-b-2 flex items-center gap-2"
        :class="activeTab === 'redirects' ? 'border-[#111111] text-[#111111]' : 'border-transparent text-[#6B7280]'"
        @click="activeTab = 'redirects'"
      >
        <ArrowRightLeft class="w-4 h-4" />
        301 Redirects ({{ redirects.length }})
      </button>
    </div>

    <!-- TAB 1: ACTIVITY LOG -->
    <div v-if="activeTab === 'activity'" class="space-y-4">
      <div class="bg-white border border-[#EAE8E3] rounded-xl p-4 flex items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
          <Search class="w-4 h-4 text-[#6B7280] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="logSearch"
            type="text"
            placeholder="Search action, staff name, email, or IP address..."
            class="w-full pl-9 pr-4 py-2 text-xs border border-[#EAE8E3] rounded-lg focus:outline-none focus:border-[#111111]"
            @input="fetchActivityLogs"
          />
        </div>
      </div>

      <div class="bg-white border border-[#EAE8E3] rounded-xl overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-[#EAE8E3] bg-[#FAF9F6] text-[11px] font-semibold uppercase tracking-wider text-[#6B7280]">
              <th class="py-3 px-4">Staff Actor</th>
              <th class="py-3 px-4">Action</th>
              <th class="py-3 px-4">Details</th>
              <th class="py-3 px-4">IP Address</th>
              <th class="py-3 px-4">Timestamp</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAE8E3] text-xs">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-[#FAF9F6]">
              <td class="py-3 px-4 font-medium text-[#111111]">
                {{ log.user?.name || 'System' }}
                <div class="text-[11px] text-[#6B7280] font-normal">{{ log.user?.email || '' }}</div>
              </td>
              <td class="py-3 px-4">
                <code class="px-2 py-0.5 rounded bg-[#F8F7F4] border border-[#EAE8E3] font-mono text-[11px] text-[#111111]">
                  {{ log.action }}
                </code>
              </td>
              <td class="py-3 px-4 font-mono text-[11px] text-[#6B7280] max-w-md truncate">
                {{ log.properties_json ? JSON.stringify(log.properties_json) : '—' }}
              </td>
              <td class="py-3 px-4 font-mono text-[#6B7280]">{{ log.ip || '127.0.0.1' }}</td>
              <td class="py-3 px-4 text-[#6B7280] tabular-nums">
                {{ log.created_at ? new Date(log.created_at).toLocaleString() : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: CONTACT INBOX -->
    <div v-else-if="activeTab === 'contact'" class="space-y-3">
      <div v-if="!contactMessages.length" class="bg-white border border-[#EAE8E3] rounded-xl p-10 text-center text-xs text-[#6B7280]">
        No contact form messages yet.
      </div>
      <div
        v-for="msg in contactMessages"
        :key="msg.id"
        class="bg-white border border-[#EAE8E3] rounded-xl p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4"
      >
        <div class="space-y-1.5">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-[#111111]">{{ msg.subject }}</span>
            <span
              class="px-2 py-0.5 text-[10px] font-medium uppercase rounded-full"
              :class="msg.status === 'unread' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800'"
            >
              {{ msg.status }}
            </span>
          </div>
          <div class="text-xs text-[#6B7280]">
            From: <strong class="text-[#111111]">{{ msg.name }}</strong> ({{ msg.email }}) ·
            {{ msg.created_at ? new Date(msg.created_at).toLocaleString() : '' }}
          </div>
          <p class="text-xs text-[#111111] leading-relaxed pt-1 whitespace-pre-line">{{ msg.message }}</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            v-if="msg.status === 'unread'"
            type="button"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border border-[#EAE8E3] text-xs font-medium hover:bg-[#F8F7F4]"
            @click="updateContactStatus(msg, 'read')"
          >
            <CheckCircle2 class="w-3.5 h-3.5" />
            Mark Read
          </button>
          <a
            :href="`mailto:${msg.email}?subject=Re: ${encodeURIComponent(msg.subject)}`"
            class="px-3 py-1.5 rounded-full bg-[#111111] text-white text-xs font-medium"
            @click="updateContactStatus(msg, 'replied')"
          >
            Reply via Email
          </a>
          <button type="button" class="p-1.5 text-[#6B7280] hover:text-[#DC2626]" @click="deleteContactMessage(msg)">
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: NEWSLETTER SUBSCRIBERS -->
    <div v-else-if="activeTab === 'newsletter'" class="space-y-4">
      <div class="bg-white border border-[#EAE8E3] rounded-xl p-4 flex items-center justify-between gap-4">
        <div class="relative flex-1 max-w-sm">
          <Search class="w-4 h-4 text-[#6B7280] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="subSearch"
            type="text"
            placeholder="Search subscriber email..."
            class="w-full pl-9 pr-4 py-2 text-xs border border-[#EAE8E3] rounded-lg"
            @input="fetchSubscribers"
          />
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#111111] text-white text-xs font-medium"
          @click="exportSubscribersCsv"
        >
          <Download class="w-3.5 h-3.5" />
          Export CSV
        </button>
      </div>

      <div class="bg-white border border-[#EAE8E3] rounded-xl overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-[#EAE8E3] bg-[#FAF9F6] text-[11px] font-semibold uppercase tracking-wider text-[#6B7280]">
              <th class="py-3 px-4">Email</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Subscribed At</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAE8E3] text-xs">
            <tr v-for="s in subscribers" :key="s.id">
              <td class="py-3 px-4 font-medium text-[#111111]">{{ s.email }}</td>
              <td class="py-3 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[11px] font-medium"
                  :class="s.is_active ? 'bg-emerald-50 text-[#15803D]' : 'bg-gray-100 text-[#6B7280]'"
                >
                  {{ s.is_active ? 'Subscribed' : 'Unsubscribed' }}
                </span>
              </td>
              <td class="py-3 px-4 text-[#6B7280]">{{ s.subscribed_at ? new Date(s.subscribed_at).toLocaleDateString() : '—' }}</td>
              <td class="py-3 px-4 text-right">
                <button type="button" class="text-[#6B7280] hover:text-[#DC2626]" @click="deleteSubscriber(s)">
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 4: 301 URL REDIRECTS -->
    <div v-else-if="activeTab === 'redirects'" class="space-y-4">
      <form
        class="bg-white border border-[#EAE8E3] rounded-xl p-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end"
        @submit.prevent="createRedirect"
      >
        <div>
          <label class="block text-xs font-medium text-[#111111] mb-1">Old Source Path</label>
          <input
            v-model="redirectForm.source_path"
            type="text"
            required
            placeholder="/products/old-slug"
            class="w-full px-3 py-2 text-xs font-mono border border-[#EAE8E3] rounded-lg"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-[#111111] mb-1">New Target Path</label>
          <input
            v-model="redirectForm.target_path"
            type="text"
            required
            placeholder="/products/new-slug"
            class="w-full px-3 py-2 text-xs font-mono border border-[#EAE8E3] rounded-lg"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-[#111111] mb-1">Redirect Type</label>
          <select v-model="redirectForm.status_code" class="w-full px-3 py-2 text-xs border border-[#EAE8E3] rounded-lg bg-white">
            <option :value="301">301 Permanent</option>
            <option :value="302">302 Temporary</option>
          </select>
        </div>
        <button
          type="submit"
          class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full bg-[#111111] text-white text-xs font-medium"
        >
          <Plus class="w-3.5 h-3.5" />
          Add Redirect
        </button>
      </form>

      <div class="bg-white border border-[#EAE8E3] rounded-xl overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-[#EAE8E3] bg-[#FAF9F6] text-[11px] font-semibold uppercase tracking-wider text-[#6B7280]">
              <th class="py-3 px-4">Source Path</th>
              <th class="py-3 px-4">Target Path</th>
              <th class="py-3 px-4">Code</th>
              <th class="py-3 px-4">Hits</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#EAE8E3] text-xs">
            <tr v-for="r in redirects" :key="r.id">
              <td class="py-3 px-4 font-mono text-[#111111]">{{ r.source_path }}</td>
              <td class="py-3 px-4 font-mono text-[var(--brand)]">{{ r.target_path }}</td>
              <td class="py-3 px-4">{{ r.status_code }}</td>
              <td class="py-3 px-4 tabular-nums text-[#6B7280]">{{ r.hits_count }}</td>
              <td class="py-3 px-4 text-right">
                <button type="button" class="text-[#6B7280] hover:text-[#DC2626]" @click="deleteRedirect(r)">
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
