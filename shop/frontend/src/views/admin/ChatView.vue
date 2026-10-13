<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'
import {
  MessageSquare,
  Search,
  Send,
  Image as ImageIcon,
  Volume2,
  VolumeX,
  CheckCircle2,
  RotateCcw,
  ShoppingBag,
  Globe,
  User,
  StickyNote,
  Zap,
  Plus,
  Trash2,
  Loader2,
  X,
  Bell,
} from 'lucide-vue-next'

const conversations = ref([])
const staffList = ref([])
const cannedResponses = ref([])

const activeFilter = ref('open')
const searchQuery = ref('')
const selectedId = ref(null)
const activeConversation = ref(null)
const messages = ref([])
const visitorContext = ref({ cart_items: [], cart_subtotal: 0, past_orders: [] })

const replyText = ref('')
const attachmentUrl = ref('')
const internalNotesDraft = ref('')

const loadingList = ref(true)
const loadingThread = ref(false)
const sending = ref(false)
const uploading = ref(false)
const savingNotes = ref(false)
const savingCanned = ref(false)

const soundEnabled = ref(localStorage.getItem('admin_chat_sound') !== '0')
const isCannedModalOpen = ref(false)
const showQuickReplies = ref(false)
const messagesEndRef = ref(null)

let pollInterval = null

const lastMessageId = computed(() => {
  if (!messages.value.length) return 0
  return Math.max(...messages.value.map((m) => Number(m.id) || 0))
})

function playChime() {
  if (!soundEnabled.value) return
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext
    if (!AudioCtx) return
    const ctx = new AudioCtx()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.type = 'sine'
    osc.frequency.setValueAtTime(659.25, ctx.currentTime) // E5
    osc.frequency.setValueAtTime(987.77, ctx.currentTime + 0.08) // B5
    gain.gain.setValueAtTime(0.08, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28)
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.start()
    osc.stop(ctx.currentTime + 0.3)
  } catch (e) {
    // Non-fatal
  }
}

function toggleSound() {
  soundEnabled.value = !soundEnabled.value
  localStorage.setItem('admin_chat_sound', soundEnabled.value ? '1' : '0')
}

function requestBrowserNotifications() {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }
}

function notifyBrowser(title, body) {
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification(title, { body })
  }
}

async function scrollToBottom() {
  await nextTick()
  if (messagesEndRef.value) {
    messagesEndRef.value.scrollTop = messagesEndRef.value.scrollHeight
  }
}

async function fetchConversations(silent = false) {
  if (document.hidden && silent) return
  if (!silent) loadingList.value = true

  try {
    const res = await axios.get('/api/admin/chat/conversations', {
      params: {
        filter: activeFilter.value,
        search: searchQuery.value.trim() || undefined,
      },
    })
    const newList = res.data.data || []

    // Check if any conversation has higher unread count than before
    if (silent && conversations.value.length > 0) {
      const prevMap = new Map(conversations.value.map((c) => [c.id, c.unread_admin_count]))
      for (const c of newList) {
        const oldUnread = prevMap.get(c.id) || 0
        if (c.unread_admin_count > oldUnread) {
          playChime()
          notifyBrowser(`New message from ${c.visitor_name}`, c.last_message?.body || 'Sent a message')
          break
        }
      }
    }

    conversations.value = newList
    if (res.data.staff) staffList.value = res.data.staff

    if (!selectedId.value && newList.length > 0) {
      await selectConversation(newList[0].id)
    }
  } catch (e) {
    // Non-fatal
  } finally {
    loadingList.value = false
  }
}

async function selectConversation(id) {
  selectedId.value = id
  loadingThread.value = true
  try {
    const res = await axios.get(`/api/admin/chat/conversations/${id}`)
    const data = res.data.data
    activeConversation.value = data.conversation
    messages.value = data.messages || []
    visitorContext.value = data.visitor_context || {
      cart_items: [],
      cart_subtotal: 0,
      past_orders: [],
    }
    internalNotesDraft.value = data.conversation.internal_notes || ''

    // Clear unread badge in local list
    const item = conversations.value.find((c) => c.id === id)
    if (item) item.unread_admin_count = 0

    scrollToBottom()
  } catch (e) {
    // Non-fatal
  } finally {
    loadingThread.value = false
  }
}

async function pollActiveConversation() {
  if (document.hidden || !selectedId.value) return
  try {
    const res = await axios.get(`/api/admin/chat/conversations/${selectedId.value}`, {
      params: { since_id: lastMessageId.value },
    })
    const data = res.data.data
    if (data.conversation) {
      activeConversation.value = data.conversation
    }
    if (data.visitor_context) {
      visitorContext.value = data.visitor_context
    }
    const incoming = data.messages || []
    if (incoming.length > 0) {
      const existingIds = new Set(messages.value.map((m) => m.id))
      let hasVisitorMsg = false
      for (const msg of incoming) {
        if (!existingIds.has(msg.id)) {
          messages.value.push(msg)
          if (msg.sender_type === 'visitor') hasVisitorMsg = true
        }
      }
      if (hasVisitorMsg) playChime()
      scrollToBottom()
    }
  } catch (e) {
    // Non-fatal
  }
}

async function fetchCannedResponses() {
  try {
    const res = await axios.get('/api/admin/chat/canned-responses')
    cannedResponses.value = res.data.data || []
  } catch (e) {
    // Non-fatal
  }
}

async function handleSendReply() {
  if ((!replyText.value.trim() && !attachmentUrl.value) || !selectedId.value || sending.value) return

  sending.value = true
  try {
    const res = await axios.post(`/api/admin/chat/conversations/${selectedId.value}/messages`, {
      body: replyText.value.trim(),
      attachment_path: attachmentUrl.value || null,
    })
    if (res.data.data) {
      const msg = res.data.data
      if (!messages.value.some((m) => m.id === msg.id)) {
        messages.value.push(msg)
      }
    }
    replyText.value = ''
    attachmentUrl.value = ''
    scrollToBottom()
    fetchConversations(true)
  } catch (e) {
    // Non-fatal
  } finally {
    sending.value = false
  }
}

async function handleUploadAttachment(e) {
  const file = e.target.files?.[0]
  if (!file) return

  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('image', file)
    const res = await axios.post('/api/admin/chat/upload-attachment', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data.url) {
      attachmentUrl.value = res.data.url
    }
  } catch (err) {
    // Non-fatal
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

async function handleUpdateConversation(payload) {
  if (!selectedId.value) return
  try {
    const res = await axios.patch(`/api/admin/chat/conversations/${selectedId.value}`, payload)
    if (res.data.data) {
      activeConversation.value = {
        ...activeConversation.value,
        ...res.data.data,
      }
    }
    fetchConversations(true)
  } catch (e) {
    // Non-fatal
  }
}

async function handleSaveInternalNotes() {
  if (!selectedId.value) return
  savingNotes.value = true
  try {
    await handleUpdateConversation({ internal_notes: internalNotesDraft.value })
  } finally {
    savingNotes.value = false
  }
}

function insertCannedResponse(item) {
  replyText.value = item.body
  showQuickReplies.value = false
}

async function handleSaveCannedResponses() {
  savingCanned.value = true
  try {
    const clean = cannedResponses.value.filter((r) => r.title.trim() && r.body.trim())
    const res = await axios.put('/api/admin/chat/canned-responses', { responses: clean })
    cannedResponses.value = res.data.data || clean
    isCannedModalOpen.value = false
  } catch (e) {
    // Non-fatal
  } finally {
    savingCanned.value = false
  }
}

function formatTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  fetchConversations(false)
  fetchCannedResponses()

  // Poll every 3 seconds on the admin chat page (pauses automatically when tab is hidden)
  pollInterval = setInterval(() => {
    if (!document.hidden) {
      fetchConversations(true)
      pollActiveConversation()
    }
  }, 3000)
})

onUnmounted(() => {
  clearInterval(pollInterval)
})
</script>

<template>
  <div class="space-y-4">
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Live Chat Inbox</h1>
        <p class="text-xs sm:text-sm text-ink-muted mt-0.5">
          Real-time customer conversations with visitor cart context, order history, and canned quick replies.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="requestBrowserNotifications"
          class="px-3.5 py-2 rounded-pill border border-line bg-white hover:bg-surface text-xs font-semibold text-ink inline-flex items-center gap-1.5"
        >
          <Bell class="w-3.5 h-3.5 text-brand" />
          <span>Alerts</span>
        </button>

        <button
          type="button"
          @click="toggleSound"
          class="px-3.5 py-2 rounded-pill border border-line bg-white hover:bg-surface text-xs font-semibold text-ink inline-flex items-center gap-1.5"
        >
          <Volume2 v-if="soundEnabled" class="w-3.5 h-3.5 text-brand" />
          <VolumeX v-else class="w-3.5 h-3.5 text-ink-muted" />
          <span>{{ soundEnabled ? 'Sound On' : 'Muted' }}</span>
        </button>

        <button
          type="button"
          @click="isCannedModalOpen = true"
          class="px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 inline-flex items-center gap-1.5 shadow-sm"
        >
          <Zap class="w-3.5 h-3.5" />
          <span>Manage Quick Replies</span>
        </button>
      </div>
    </div>

    <!-- 3-Pane Chat Inbox Grid -->
    <div class="bg-white rounded-card border border-line shadow-sm overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[620px]">
      <!-- LEFT PANE: Conversations List (3 cols) -->
      <div class="lg:col-span-3 border-r border-line flex flex-col bg-surface/30">
        <!-- Filter Tabs + Search -->
        <div class="p-3 border-b border-line space-y-2.5 bg-white">
          <div class="grid grid-cols-4 gap-1 p-1 rounded-pill bg-surface border border-line text-[11px] font-semibold">
            <button
              v-for="f in [
                { key: 'open', label: 'Open' },
                { key: 'mine', label: 'Mine' },
                { key: 'closed', label: 'Closed' },
                { key: 'all', label: 'All' },
              ]"
              :key="f.key"
              type="button"
              @click="
                activeFilter = f.key;
                fetchConversations(false)
              "
              class="py-1 rounded-pill transition text-center"
              :class="activeFilter === f.key ? 'bg-white text-brand shadow-xs' : 'text-ink-muted hover:text-ink'"
            >
              {{ f.label }}
            </button>
          </div>

          <div class="relative">
            <Search class="w-3.5 h-3.5 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search name, email, message..."
              class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-line text-xs text-ink focus:outline-none focus:border-brand"
              @input="fetchConversations(false)"
            />
          </div>
        </div>

        <!-- Conversation Rows -->
        <div class="flex-1 overflow-y-auto divide-y divide-line">
          <div v-if="loadingList" class="p-8 text-center text-xs text-ink-muted">
            <Loader2 class="w-5 h-5 animate-spin mx-auto mb-1.5 text-brand" />
            <span>Loading inbox...</span>
          </div>

          <div v-else-if="conversations.length === 0" class="p-8 text-center text-xs text-ink-muted">
            No conversations in this view.
          </div>

          <button
            v-for="conv in conversations"
            :key="conv.id"
            type="button"
            @click="selectConversation(conv.id)"
            class="w-full text-left p-3.5 transition flex items-start justify-between gap-2 hover:bg-white"
            :class="selectedId === conv.id ? 'bg-white border-l-4 border-l-brand' : ''"
          >
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1.5">
                <span
                  class="w-2 h-2 rounded-full flex-shrink-0"
                  :class="conv.is_visitor_online ? 'bg-emerald-500' : 'bg-gray-300'"
                  :title="conv.is_visitor_online ? 'Online now' : 'Offline'"
                />
                <span class="text-xs font-bold text-ink truncate">{{ conv.visitor_name }}</span>
              </div>
              <p v-if="conv.visitor_email" class="text-[10px] text-ink-muted truncate mt-0.5">
                {{ conv.visitor_email }}
              </p>
              <p class="text-xs text-ink-muted truncate mt-1">
                {{ conv.last_message?.body || 'Attachment' }}
              </p>
            </div>

            <div class="flex flex-col items-end gap-1 flex-shrink-0">
              <span class="text-[10px] text-ink-muted">
                {{ formatTime(conv.last_message_at) }}
              </span>
              <span
                v-if="conv.unread_admin_count > 0"
                class="px-1.5 py-0.5 rounded-full bg-brand text-white text-[10px] font-bold"
              >
                {{ conv.unread_admin_count }}
              </span>
            </div>
          </button>
        </div>
      </div>

      <!-- CENTER PANE: Active Conversation Thread (6 cols) -->
      <div class="lg:col-span-6 border-r border-line flex flex-col">
        <div v-if="!activeConversation" class="flex-1 flex flex-col items-center justify-center p-12 text-center text-ink-muted">
          <MessageSquare class="w-8 h-8 mb-2 stroke-[1.5]" />
          <p class="text-sm font-semibold text-ink">Select a conversation</p>
          <p class="text-xs text-ink-muted mt-0.5">Choose a visitor thread on the left to read and reply.</p>
        </div>

        <template v-else>
          <!-- Conversation Header -->
          <div class="px-5 py-3.5 border-b border-line bg-white flex flex-wrap items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-ink">{{ activeConversation.visitor_name }}</span>
                <span
                  class="px-2 py-0.5 rounded-pill text-[10px] font-semibold border"
                  :class="
                    activeConversation.status === 'open'
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                      : 'bg-gray-100 text-gray-600 border-gray-200'
                  "
                >
                  {{ activeConversation.status === 'open' ? 'Open' : 'Closed' }}
                </span>
              </div>
              <p class="text-[11px] text-ink-muted">
                {{ activeConversation.visitor_email || 'No email provided' }} · Viewing
                <span class="font-mono text-ink">{{ activeConversation.current_page }}</span>
              </p>
            </div>

            <div class="flex items-center gap-2">
              <!-- Assign to Staff -->
              <select
                :value="activeConversation.assigned_admin_id || ''"
                class="px-2.5 py-1.5 rounded-xl border border-line text-xs bg-surface text-ink focus:outline-none focus:border-brand"
                @change="handleUpdateConversation({ assigned_admin_id: $event.target.value ? Number($event.target.value) : null })"
              >
                <option value="">Unassigned</option>
                <option v-for="st in staffList" :key="st.id" :value="st.id">
                  Assigned: {{ st.name }}
                </option>
              </select>

              <!-- Close / Reopen -->
              <button
                type="button"
                @click="
                  handleUpdateConversation({
                    status: activeConversation.status === 'open' ? 'closed' : 'open',
                  })
                "
                class="px-3 py-1.5 rounded-pill border border-line text-xs font-semibold hover:bg-surface inline-flex items-center gap-1"
              >
                <CheckCircle2 v-if="activeConversation.status === 'open'" class="w-3.5 h-3.5 text-emerald-600" />
                <RotateCcw v-else class="w-3.5 h-3.5 text-brand" />
                <span>{{ activeConversation.status === 'open' ? 'Close Chat' : 'Reopen' }}</span>
              </button>
            </div>
          </div>

          <!-- Messages Scroll Area -->
          <div
            ref="messagesEndRef"
            class="flex-1 p-5 space-y-3 overflow-y-auto bg-page max-h-[440px]"
          >
            <div
              v-for="msg in messages"
              :key="msg.id"
              class="flex flex-col"
              :class="msg.sender_type === 'admin' ? 'items-end' : 'items-start'"
            >
              <div
                class="max-w-[80%] rounded-2xl px-4 py-2.5 text-xs leading-relaxed shadow-xs space-y-1.5"
                :class="
                  msg.sender_type === 'admin'
                    ? 'bg-brand text-white rounded-br-sm'
                    : 'bg-white border border-line text-ink rounded-bl-sm'
                "
              >
                <p v-if="msg.body" class="whitespace-pre-line">{{ msg.body }}</p>
                <a
                  v-if="msg.attachment_path"
                  :href="msg.attachment_path"
                  target="_blank"
                  rel="noopener"
                  class="block"
                >
                  <img :src="msg.attachment_path" class="max-h-40 rounded-lg object-cover" />
                </a>
              </div>
              <span class="text-[10px] text-ink-muted mt-1 px-1">
                {{ msg.sender_type === 'admin' ? msg.sender?.name || 'Staff' : activeConversation.visitor_name }} ·
                {{ formatTime(msg.created_at) }}
              </span>
            </div>
          </div>

          <!-- Composer -->
          <form class="p-3.5 bg-white border-t border-line space-y-2 relative" @submit.prevent="handleSendReply">
            <!-- Quick Replies Popover -->
            <div
              v-if="showQuickReplies"
              class="absolute bottom-full left-3 mb-2 w-80 bg-white rounded-xl border border-line shadow-xl p-2 z-20 space-y-1 max-h-56 overflow-y-auto"
            >
              <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-ink-muted flex justify-between">
                <span>Canned Quick Replies</span>
                <button type="button" @click="showQuickReplies = false">✕</button>
              </div>
              <button
                v-for="(cr, idx) in cannedResponses"
                :key="idx"
                type="button"
                @click="insertCannedResponse(cr)"
                class="w-full text-left p-2 rounded-lg hover:bg-surface text-xs"
              >
                <div class="font-bold text-ink">{{ cr.title }}</div>
                <div class="text-ink-muted truncate">{{ cr.body }}</div>
              </button>
            </div>

            <!-- Attachment Preview -->
            <div v-if="attachmentUrl" class="flex items-center gap-2 bg-surface px-3 py-1.5 rounded-xl">
              <img :src="attachmentUrl" class="w-8 h-8 rounded object-cover border border-line" />
              <span class="text-xs text-ink-muted flex-1 truncate">Image attached</span>
              <button type="button" @click="attachmentUrl = ''" class="text-ink-muted hover:text-ink">
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="showQuickReplies = !showQuickReplies"
                class="p-2 rounded-full border border-line text-brand hover:bg-surface transition"
                title="Insert Canned Quick Reply"
              >
                <Zap class="w-4 h-4" />
              </button>

              <label
                class="p-2 rounded-full border border-line text-ink-muted hover:text-ink hover:bg-surface cursor-pointer transition"
                title="Upload image"
              >
                <Loader2 v-if="uploading" class="w-4 h-4 animate-spin text-brand" />
                <ImageIcon v-else class="w-4 h-4" />
                <input type="file" class="hidden" accept="image/*" @change="handleUploadAttachment" />
              </label>

              <input
                v-model="replyText"
                type="text"
                placeholder="Type a reply (auto-emails visitor if offline)..."
                class="flex-1 px-4 py-2 rounded-pill border border-line bg-surface text-xs text-ink focus:outline-none focus:border-brand focus:bg-white"
              />

              <button
                type="submit"
                :disabled="sending || (!replyText.trim() && !attachmentUrl)"
                class="px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 disabled:opacity-40 inline-flex items-center gap-1.5 shadow-sm"
              >
                <Loader2 v-if="sending" class="w-3.5 h-3.5 animate-spin" />
                <Send v-else class="w-3.5 h-3.5" />
                <span>Send</span>
              </button>
            </div>
          </form>
        </template>
      </div>

      <!-- RIGHT PANE: Visitor Info, Cart Contents, Past Orders & Internal Notes (3 cols) -->
      <div class="lg:col-span-3 p-4 space-y-5 bg-surface/20 overflow-y-auto max-h-[620px]">
        <template v-if="activeConversation">
          <!-- Visitor Info -->
          <div class="bg-white rounded-xl border border-line p-3.5 space-y-2">
            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-ink">
              <User class="w-3.5 h-3.5 text-brand" />
              <span>Visitor Profile</span>
            </div>
            <div class="text-xs space-y-1">
              <p><span class="text-ink-muted">Name:</span> <strong class="text-ink">{{ activeConversation.visitor_name }}</strong></p>
              <p><span class="text-ink-muted">Email:</span> <span class="text-ink">{{ activeConversation.visitor_email || '—' }}</span></p>
              <p class="flex items-center gap-1">
                <Globe class="w-3 h-3 text-ink-muted" />
                <span class="font-mono text-[11px] text-ink truncate">{{ activeConversation.current_page }}</span>
              </p>
            </div>
          </div>

          <!-- Active Cart Contents -->
          <div class="bg-white rounded-xl border border-line p-3.5 space-y-2.5">
            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-ink">
              <span class="flex items-center gap-1.5">
                <ShoppingBag class="w-3.5 h-3.5 text-brand" />
                <span>Visitor Cart</span>
              </span>
              <span class="text-brand">${{ Number(visitorContext.cart_subtotal || 0).toFixed(2) }}</span>
            </div>

            <div v-if="!visitorContext.cart_items?.length" class="text-xs text-ink-muted">
              Cart is currently empty.
            </div>
            <ul v-else class="space-y-2 divide-y divide-line text-xs">
              <li v-for="ci in visitorContext.cart_items" :key="ci.id" class="pt-1.5 flex justify-between gap-2">
                <span class="text-ink truncate">{{ ci.quantity }}× {{ ci.name }}</span>
                <span class="font-semibold text-ink">${{ Number(ci.line_total).toFixed(2) }}</span>
              </li>
            </ul>
          </div>

          <!-- Past Orders -->
          <div class="bg-white rounded-xl border border-line p-3.5 space-y-2.5">
            <div class="text-xs font-bold uppercase tracking-wider text-ink">
              Past Orders ({{ visitorContext.past_orders?.length || 0 }})
            </div>

            <div v-if="!visitorContext.past_orders?.length" class="text-xs text-ink-muted">
              No previous orders found.
            </div>
            <div v-else class="space-y-2 text-xs">
              <RouterLink
                v-for="ord in visitorContext.past_orders"
                :key="ord.id"
                :to="`/admin/orders/${ord.id}`"
                class="block p-2 rounded-lg border border-line hover:border-brand transition"
              >
                <div class="flex justify-between font-bold text-ink">
                  <span>{{ ord.order_number }}</span>
                  <span>${{ Number(ord.grand_total).toFixed(2) }}</span>
                </div>
                <div class="text-[10px] text-ink-muted uppercase mt-0.5">
                  {{ ord.status }} · {{ ord.payment_status }}
                </div>
              </RouterLink>
            </div>
          </div>

          <!-- Internal Staff Notes -->
          <div class="bg-white rounded-xl border border-line p-3.5 space-y-2">
            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-ink">
              <StickyNote class="w-3.5 h-3.5 text-brand" />
              <span>Internal Staff Notes</span>
            </div>
            <textarea
              v-model="internalNotesDraft"
              rows="3"
              placeholder="Private notes visible only to staff..."
              class="w-full p-2.5 rounded-lg border border-line text-xs text-ink bg-surface/50 focus:outline-none focus:border-brand"
            />
            <button
              type="button"
              :disabled="savingNotes"
              @click="handleSaveInternalNotes"
              class="w-full py-1.5 rounded-pill bg-surface border border-line hover:bg-brand hover:text-white text-xs font-semibold transition"
            >
              {{ savingNotes ? 'Saving...' : 'Save Note' }}
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- Canned Quick Replies Manager Modal -->
    <div
      v-if="isCannedModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-card max-w-xl w-full p-6 shadow-2xl border border-line space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-line pb-3">
          <h3 class="text-base font-bold text-ink">Manage Canned Quick Replies</h3>
          <button type="button" @click="isCannedModalOpen = false" class="text-ink-muted hover:text-ink">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="space-y-3">
          <div
            v-for="(cr, idx) in cannedResponses"
            :key="idx"
            class="p-3 rounded-xl border border-line bg-surface/40 space-y-2"
          >
            <div class="flex items-center justify-between gap-2">
              <input
                v-model="cr.title"
                type="text"
                placeholder="Shortcut Title (e.g. Shipping Policy)"
                class="flex-1 px-3 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink"
              />
              <button
                type="button"
                @click="cannedResponses.splice(idx, 1)"
                class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
            <textarea
              v-model="cr.body"
              rows="2"
              placeholder="Full response text..."
              class="w-full px-3 py-1.5 rounded-lg border border-line bg-white text-xs text-ink"
            />
          </div>

          <button
            type="button"
            @click="cannedResponses.push({ title: '', body: '' })"
            class="w-full py-2 rounded-xl border border-dashed border-line hover:border-brand text-xs font-semibold text-brand inline-flex items-center justify-center gap-1"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Quick Reply</span>
          </button>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-line">
          <button
            type="button"
            @click="isCannedModalOpen = false"
            class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted"
          >
            Cancel
          </button>
          <button
            type="button"
            :disabled="savingCanned"
            @click="handleSaveCannedResponses"
            class="px-5 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95"
          >
            {{ savingCanned ? 'Saving...' : 'Save Quick Replies' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
