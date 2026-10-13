<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import {
  MessageCircle,
  X,
  Send,
  Volume2,
  VolumeX,
  Image as ImageIcon,
  Loader2,
  Sparkles,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'

const route = useRoute()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const isOpen = ref(false)
const soundEnabled = ref(localStorage.getItem('boutique_chat_sound') !== '0')
const visitorToken = ref('')
const visitorName = ref('')
const visitorEmail = ref('')
const hasStartedConversation = ref(false)

const messages = ref([])
const unreadCount = ref(0)
const isOnline = ref(true)
const greetingText = ref('We usually reply within a few minutes.')
const offlineText = ref(
  'Our studio team is currently away. Leave your name and email and we will reply by email shortly.'
)
const conversationStatus = ref('open')

const messageText = ref('')
const attachmentUrl = ref('')
const isSending = ref(false)
const isUploading = ref(false)
const messagesEndRef = ref(null)

let pollTimeout = null
let errorBackoffMs = 0

function initVisitorToken() {
  let token = localStorage.getItem('boutique_visitor_token')
  if (!token) {
    token = 'vis_' + Math.random().toString(36).slice(2) + '_' + Date.now().toString(36)
    localStorage.setItem('boutique_visitor_token', token)
  }
  document.cookie = `boutique_visitor_token=${token}; path=/; max-age=31536000; SameSite=Lax`
  visitorToken.value = token

  visitorName.value = localStorage.getItem('boutique_visitor_name') || authStore.user?.name || ''
  visitorEmail.value = localStorage.getItem('boutique_visitor_email') || authStore.user?.email || ''
}

const lastMessageId = computed(() => {
  if (!messages.value.length) return 0
  return Math.max(...messages.value.map((m) => Number(m.id) || 0))
})

const needsIdentityPrompt = computed(() => {
  if (authStore.isAuthenticated) return false
  if (hasStartedConversation.value || messages.value.length > 0) return false
  return true
})

function toggleSound() {
  soundEnabled.value = !soundEnabled.value
  localStorage.setItem('boutique_chat_sound', soundEnabled.value ? '1' : '0')
}

function playNotificationChime() {
  if (!soundEnabled.value) return
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext
    if (!AudioCtx) return
    const ctx = new AudioCtx()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.type = 'sine'
    osc.frequency.setValueAtTime(587.33, ctx.currentTime) // D5
    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08) // A5
    gain.gain.setValueAtTime(0.08, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28)
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.start()
    osc.stop(ctx.currentTime + 0.3)
  } catch (e) {
    // Audio context blocked until user interaction
  }
}

async function scrollToBottom() {
  await nextTick()
  if (messagesEndRef.value) {
    messagesEndRef.value.scrollTop = messagesEndRef.value.scrollHeight
  }
}

async function pollMessages() {
  if (document.hidden) return

  try {
    const res = await axios.get('/api/chat/messages', {
      params: {
        visitor_token: visitorToken.value,
        since_id: lastMessageId.value,
        panel_open: isOpen.value ? 1 : 0,
        current_page: route.fullPath,
      },
      headers: {
        'X-Visitor-Token': visitorToken.value,
      },
    })

    errorBackoffMs = 0

    const data = res.data || {}
    isOnline.value = data.is_online !== false
    if (data.greeting) greetingText.value = data.greeting
    if (data.offline_message) offlineText.value = data.offline_message

    if (data.conversation) {
      hasStartedConversation.value = true
      conversationStatus.value = data.conversation.status || 'open'
      if (data.conversation.visitor_name && !visitorName.value) {
        visitorName.value = data.conversation.visitor_name
      }
      if (data.conversation.visitor_email && !visitorEmail.value) {
        visitorEmail.value = data.conversation.visitor_email
      }
    }

    const incoming = Array.isArray(data.messages) ? data.messages : []
    if (incoming.length > 0) {
      const existingIds = new Set(messages.value.map((m) => m.id))
      let hasNewAdminMsg = false
      for (const msg of incoming) {
        if (!existingIds.has(msg.id)) {
          messages.value.push(msg)
          if (msg.sender_type === 'admin') {
            hasNewAdminMsg = true
          }
        }
      }
      if (hasNewAdminMsg) {
        playNotificationChime()
      }
      if (isOpen.value) {
        scrollToBottom()
      }
    }

    unreadCount.value = isOpen.value ? 0 : Number(data.unread_count) || 0
  } catch (err) {
    // Exponential backoff on network or rate-limit errors (up to 60s)
    errorBackoffMs = errorBackoffMs ? Math.min(errorBackoffMs * 2, 60000) : 8000
  } finally {
    scheduleNextPoll()
  }
}

function scheduleNextPoll() {
  clearTimeout(pollTimeout)
  if (document.hidden) return

  // 4s when chat panel is open, 30s when closed (plus any exponential error backoff)
  const baseInterval = isOpen.value ? 4000 : 30000
  const delay = Math.max(baseInterval, errorBackoffMs)
  pollTimeout = setTimeout(pollMessages, delay)
}

function handleVisibilityChange() {
  if (document.hidden) {
    clearTimeout(pollTimeout)
  } else {
    pollMessages()
  }
}

function togglePanel() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    unreadCount.value = 0
    pollMessages()
    scrollToBottom()
  } else {
    scheduleNextPoll()
  }
}

async function handleAttachmentUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return

  isUploading.value = true
  try {
    const fd = new FormData()
    fd.append('image', file)
    const res = await axios.post('/api/chat/upload-attachment', fd, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-Visitor-Token': visitorToken.value,
      },
    })
    if (res.data.url) {
      attachmentUrl.value = res.data.url
    }
  } catch (err) {
    // Non-fatal
  } finally {
    isUploading.value = false
    e.target.value = ''
  }
}

async function handleSendMessage() {
  if (!messageText.value.trim() && !attachmentUrl.value) return
  if (isSending.value) return

  if (visitorName.value.trim()) {
    localStorage.setItem('boutique_visitor_name', visitorName.value.trim())
  }
  if (visitorEmail.value.trim()) {
    localStorage.setItem('boutique_visitor_email', visitorEmail.value.trim())
  }

  isSending.value = true
  try {
    const res = await axios.post(
      '/api/chat/messages',
      {
        visitor_token: visitorToken.value,
        visitor_name: visitorName.value.trim() || authStore.user?.name || 'Guest Visitor',
        visitor_email: visitorEmail.value.trim() || authStore.user?.email || null,
        current_page: route.fullPath,
        body: messageText.value.trim(),
        attachment_path: attachmentUrl.value || null,
      },
      {
        headers: {
          'X-Visitor-Token': visitorToken.value,
        },
      }
    )

    if (res.data.data) {
      const msg = res.data.data
      if (!messages.value.some((m) => m.id === msg.id)) {
        messages.value.push(msg)
      }
      hasStartedConversation.value = true
    }

    messageText.value = ''
    attachmentUrl.value = ''
    scrollToBottom()
    scheduleNextPoll()
  } catch (err) {
    // Non-fatal
  } finally {
    isSending.value = false
  }
}

// Merge conversation with user account on login
watch(
  () => authStore.isAuthenticated,
  async (loggedIn) => {
    if (loggedIn && visitorToken.value) {
      try {
        await axios.post(
          '/api/chat/merge',
          { visitor_token: visitorToken.value },
          { headers: { 'X-Visitor-Token': visitorToken.value } }
        )
      } catch (e) {
        // Non-fatal
      }
    }
  }
)

function formatTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  initVisitorToken()
  document.addEventListener('visibilitychange', handleVisibilityChange)
  pollMessages()
})

onUnmounted(() => {
  clearTimeout(pollTimeout)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})
</script>

<template>
  <div class="fixed bottom-5 right-5 z-40">
    <!-- Chat Panel -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-4 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-4 scale-95"
    >
      <div
        v-if="isOpen"
        class="mb-3 w-[340px] sm:w-[380px] bg-white rounded-card shadow-2xl border border-line overflow-hidden flex flex-col max-h-[540px]"
        role="dialog"
        aria-label="Live Customer Support Chat"
      >
        <!-- Header -->
        <div class="px-5 py-4 bg-brand text-white flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="relative">
              <div class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center">
                <Sparkles class="w-4 h-4 text-white" />
              </div>
              <span
                class="w-2.5 h-2.5 rounded-full border-2 border-brand absolute bottom-0 right-0"
                :class="isOnline ? 'bg-emerald-400' : 'bg-amber-300'"
              />
            </div>
            <div>
              <h3 class="text-sm font-bold leading-tight">
                {{ settingsStore.siteName || 'Studio Concierge' }}
              </h3>
              <p class="text-[11px] text-white/85 leading-tight mt-0.5">
                {{ isOnline ? greetingText : 'Offline — Replies sent via email' }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1">
            <button
              type="button"
              @click="toggleSound"
              class="p-1.5 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition"
              :title="soundEnabled ? 'Mute chat sound' : 'Enable chat sound'"
            >
              <Volume2 v-if="soundEnabled" class="w-4 h-4" />
              <VolumeX v-else class="w-4 h-4" />
            </button>
            <button
              type="button"
              @click="togglePanel"
              class="p-1.5 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition"
              aria-label="Close chat"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Offline Notice Banner -->
        <div
          v-if="!isOnline"
          class="px-4 py-2.5 bg-amber-50 border-b border-amber-200 text-[11px] text-amber-900 leading-relaxed"
        >
          {{ offlineText }}
        </div>

        <!-- Messages Area -->
        <div
          ref="messagesEndRef"
          class="flex-1 p-4 space-y-3 overflow-y-auto bg-page min-h-[240px] max-h-[320px]"
        >
          <!-- Welcome bubble -->
          <div class="flex items-start gap-2">
            <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white border border-line px-3.5 py-2.5 text-xs text-ink shadow-xs">
              Hello! Welcome to {{ settingsStore.siteName || 'our studio' }}. Ask us anything about sizing, materials, or shipping.
            </div>
          </div>

          <!-- Message bubbles -->
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="flex flex-col"
            :class="msg.sender_type === 'visitor' ? 'items-end' : 'items-start'"
          >
            <div
              class="max-w-[85%] rounded-2xl px-3.5 py-2.5 text-xs leading-relaxed shadow-xs space-y-1.5"
              :class="
                msg.sender_type === 'visitor'
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
                <img
                  :src="msg.attachment_path"
                  alt="Attachment"
                  class="max-h-36 rounded-lg object-cover border border-white/20"
                />
              </a>
            </div>
            <span class="text-[10px] text-ink-muted mt-1 px-1">
              {{ formatTime(msg.created_at) }}
            </span>
          </div>
        </div>

        <!-- Composer Form -->
        <form class="p-3 bg-white border-t border-line space-y-2" @submit.prevent="handleSendMessage">
          <!-- First-message Name & Email fields for guests -->
          <div v-if="needsIdentityPrompt" class="grid grid-cols-2 gap-2">
            <input
              v-model="visitorName"
              type="text"
              required
              placeholder="Your name *"
              class="px-3 py-1.5 rounded-xl border border-line bg-surface text-xs text-ink focus:outline-none focus:border-brand"
            />
            <input
              v-model="visitorEmail"
              type="email"
              :required="!isOnline"
              placeholder="Your email *"
              class="px-3 py-1.5 rounded-xl border border-line bg-surface text-xs text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <!-- Attachment Preview -->
          <div v-if="attachmentUrl" class="flex items-center gap-2 bg-surface px-2.5 py-1.5 rounded-xl">
            <img :src="attachmentUrl" class="w-8 h-8 rounded object-cover border border-line" />
            <span class="text-[11px] text-ink-muted flex-1 truncate">Image attached</span>
            <button type="button" @click="attachmentUrl = ''" class="text-ink-muted hover:text-ink">
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <div class="flex items-center gap-1.5">
            <label
              class="p-2 rounded-full text-ink-muted hover:text-brand hover:bg-surface cursor-pointer transition"
              title="Attach image"
            >
              <Loader2 v-if="isUploading" class="w-4 h-4 animate-spin text-brand" />
              <ImageIcon v-else class="w-4 h-4" />
              <input type="file" class="hidden" accept="image/*" @change="handleAttachmentUpload" />
            </label>

            <input
              v-model="messageText"
              type="text"
              maxlength="1500"
              placeholder="Write a message..."
              class="flex-1 px-3.5 py-2 rounded-pill border border-line bg-surface text-xs text-ink focus:outline-none focus:border-brand focus:bg-white transition"
            />

            <button
              type="submit"
              :disabled="isSending || (!messageText.trim() && !attachmentUrl)"
              class="w-9 h-9 rounded-full bg-brand text-white flex items-center justify-center hover:opacity-95 disabled:opacity-40 transition shadow-sm flex-shrink-0"
              aria-label="Send message"
            >
              <Loader2 v-if="isSending" class="w-3.5 h-3.5 animate-spin" />
              <Send v-else class="w-3.5 h-3.5" />
            </button>
          </div>
        </form>
      </div>
    </Transition>

    <!-- Floating Round Button Bottom-Right -->
    <button
      type="button"
      @click="togglePanel"
      class="relative w-14 h-14 rounded-full bg-brand text-white shadow-xl hover:opacity-95 transition flex items-center justify-center focus:outline-none"
      aria-label="Open live chat"
    >
      <X v-if="isOpen" class="w-6 h-6" />
      <MessageCircle v-else class="w-6 h-6" />

      <!-- Unread Badge -->
      <span
        v-if="!isOpen && unreadCount > 0"
        class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 rounded-full bg-rose-600 text-white text-[11px] font-bold flex items-center justify-center shadow"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>
  </div>
</template>
