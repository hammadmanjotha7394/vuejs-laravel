<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import {
  Settings,
  Megaphone,
  CreditCard,
  Mail,
  Truck,
  Globe,
  Sliders,
  Share2,
  LayoutTemplate,
  Upload,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Send,
  Sparkles,
} from 'lucide-vue-next'

const settingsStore = useSettingsStore()
const authStore = useAuthStore()

const activeTab = ref('general')
const saving = ref(false)
const testingStripe = ref(false)
const testingMail = ref(false)
const testMailEmail = ref('')
const stripeTestResult = ref(null)
const mailTestResult = ref(null)
const successMessage = ref('')
const errorMessage = ref('')
const uploadingAsset = ref(false)

// Form state cloned from admin settings
const form = ref({
  // General
  site_name: '',
  site_tagline: '',
  logo_url: '',
  favicon_url: '',
  brand_color: '#2F5D50',
  support_email: '',
  support_phone: '',
  store_address: '',
  currency_code: 'USD',
  currency_symbol: '$',
  timezone: 'UTC',

  // Announcement
  announcement_enabled: '1',
  announcement_text: '',
  announcement_link: '',
  announcement_bg: '#2F5D50',
  announcement_text_color: '#FFFFFF',
  announcement_dismissible: '1',
  announcement_start_date: '',
  announcement_end_date: '',

  // Stripe
  stripe_enabled: '0',
  stripe_mode: 'test',
  stripe_currency: 'usd',
  stripe_publishable_key: '',
  stripe_secret_key: '',
  stripe_webhook_secret: '',

  // Mail
  mail_host: 'smtp.hostinger.com',
  mail_port: 465,
  mail_encryption: 'ssl',
  mail_username: '',
  mail_password: '',
  mail_from_name: '',
  mail_from_address: '',
  mail_reply_to: '',

  // Shipping & Tax
  default_shipping_fee: 8.0,
  free_shipping_threshold: 75.0,
  tax_rate_percent: 7.5,
  prices_include_tax: '0',

  // SEO
  seo_title: '',
  seo_description: '',
  seo_social_image: '',
  google_analytics_id: '',
  meta_pixel_id: '',
  custom_head_scripts: '',
  custom_body_scripts: '',

  // Store
  maintenance_mode: '0',
  maintenance_message: '',
  guest_checkout: '1',
  review_moderation: '1',
  require_email_verification: '0',
  low_stock_threshold: 5,
  low_stock_email: '',

  // Social
  social_instagram: '',
  social_facebook: '',
  social_tiktok: '',
  social_youtube: '',
  social_x: '',
  social_whatsapp: '',

  // Footer
  footer_tagline: '',
  footer_copyright: '',
  payment_icons_enabled: '1',
})

const tabs = computed(() => [
  { id: 'general', label: 'General & Brand', icon: Settings },
  { id: 'announcement', label: 'Announcement Bar', icon: Megaphone },
  { id: 'stripe', label: 'Stripe Payments', icon: CreditCard, perm: 'stripe.manage' },
  { id: 'mail', label: 'Email (SMTP)', icon: Mail, perm: 'mail.manage' },
  { id: 'shipping', label: 'Shipping & Taxes', icon: Truck },
  { id: 'seo', label: 'SEO & Meta', icon: Globe },
  { id: 'store', label: 'Store & Operations', icon: Sliders },
  { id: 'social', label: 'Social Media', icon: Share2 },
  { id: 'footer', label: 'Footer & Legal', icon: LayoutTemplate },
])

const canEditGeneral = computed(() => authStore.hasPermission('settings.update'))
const canEditStripe = computed(() => authStore.hasPermission('stripe.manage'))
const canEditMail = computed(() => authStore.hasPermission('mail.manage'))
const isSuperAdmin = computed(() => authStore.hasRole('super_admin'))

onMounted(async () => {
  try {
    const data = await settingsStore.fetchAdminSettings()
    if (data) {
      Object.keys(form.value).forEach((key) => {
        if (data[key] !== undefined && data[key] !== null) {
          form.value[key] = data[key]
        }
      })
      if (authStore.user?.email) {
        testMailEmail.value = authStore.user.email
      }
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load settings.'
  }
})

function clearAlerts() {
  successMessage.value = ''
  errorMessage.value = ''
}

async function saveGeneral() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateSettings({
      site_name: form.value.site_name,
      site_tagline: form.value.site_tagline,
      logo_url: form.value.logo_url,
      favicon_url: form.value.favicon_url,
      brand_color: form.value.brand_color,
      support_email: form.value.support_email,
      support_phone: form.value.support_phone,
      store_address: form.value.store_address,
      currency_code: form.value.currency_code,
      currency_symbol: form.value.currency_symbol,
      timezone: form.value.timezone,
    })
    successMessage.value = 'General settings saved successfully.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save general settings.'
  } finally {
    saving.value = false
  }
}

async function saveAnnouncement() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateSettings({
      announcement_enabled: form.value.announcement_enabled,
      announcement_text: form.value.announcement_text,
      announcement_link: form.value.announcement_link,
      announcement_bg: form.value.announcement_bg,
      announcement_text_color: form.value.announcement_text_color,
      announcement_dismissible: form.value.announcement_dismissible,
      announcement_start_date: form.value.announcement_start_date || null,
      announcement_end_date: form.value.announcement_end_date || null,
    })
    successMessage.value = 'Announcement bar settings updated.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update announcement.'
  } finally {
    saving.value = false
  }
}

async function saveStripe() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateStripe({
      stripe_enabled: form.value.stripe_enabled,
      stripe_mode: form.value.stripe_mode,
      stripe_currency: form.value.stripe_currency,
      stripe_publishable_key: form.value.stripe_publishable_key,
      stripe_secret_key: form.value.stripe_secret_key,
      stripe_webhook_secret: form.value.stripe_webhook_secret,
    })
    successMessage.value = 'Stripe configuration saved securely.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save Stripe settings.'
  } finally {
    saving.value = false
  }
}

async function handleTestStripe() {
  testingStripe.value = true
  stripeTestResult.value = null
  try {
    const res = await settingsStore.testStripe()
    stripeTestResult.value = res
  } catch (err) {
    stripeTestResult.value = {
      success: false,
      message: err.response?.data?.message || 'Network error communicating with Stripe.',
    }
  } finally {
    testingStripe.value = false
  }
}

async function saveMail() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateMail({
      mail_host: form.value.mail_host,
      mail_port: Number(form.value.mail_port),
      mail_encryption: form.value.mail_encryption,
      mail_username: form.value.mail_username,
      mail_password: form.value.mail_password,
      mail_from_name: form.value.mail_from_name,
      mail_from_address: form.value.mail_from_address,
      mail_reply_to: form.value.mail_reply_to,
    })
    successMessage.value = 'Email & SMTP settings saved.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save email settings.'
  } finally {
    saving.value = false
  }
}

async function handleTestMail() {
  testingMail.value = true
  mailTestResult.value = null
  try {
    const res = await settingsStore.testMail(testMailEmail.value)
    mailTestResult.value = res
  } catch (err) {
    mailTestResult.value = {
      success: false,
      message: err.response?.data?.message || 'Error triggering test email.',
    }
  } finally {
    testingMail.value = false
  }
}

async function saveShippingAndTax() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateSettings({
      default_shipping_fee: form.value.default_shipping_fee,
      free_shipping_threshold: form.value.free_shipping_threshold,
      tax_rate_percent: form.value.tax_rate_percent,
      prices_include_tax: form.value.prices_include_tax,
    })
    successMessage.value = 'Shipping & tax rules saved.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update shipping & tax.'
  } finally {
    saving.value = false
  }
}

async function saveSeo() {
  clearAlerts()
  saving.value = true
  try {
    const payload = {
      seo_title: form.value.seo_title,
      seo_description: form.value.seo_description,
      seo_social_image: form.value.seo_social_image,
      google_analytics_id: form.value.google_analytics_id,
      meta_pixel_id: form.value.meta_pixel_id,
    }
    if (isSuperAdmin.value) {
      payload.custom_head_scripts = form.value.custom_head_scripts
      payload.custom_body_scripts = form.value.custom_body_scripts
    }
    await settingsStore.updateSettings(payload)
    successMessage.value = 'SEO and analytics settings updated.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update SEO settings.'
  } finally {
    saving.value = false
  }
}

async function saveStore() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateSettings({
      maintenance_mode: form.value.maintenance_mode,
      maintenance_message: form.value.maintenance_message,
      guest_checkout: form.value.guest_checkout,
      review_moderation: form.value.review_moderation,
      require_email_verification: form.value.require_email_verification,
      low_stock_threshold: form.value.low_stock_threshold,
      low_stock_email: form.value.low_stock_email,
    })
    successMessage.value = 'Store operational settings updated.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save store settings.'
  } finally {
    saving.value = false
  }
}

async function saveSocial() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateSettings({
      social_instagram: form.value.social_instagram,
      social_facebook: form.value.social_facebook,
      social_tiktok: form.value.social_tiktok,
      social_youtube: form.value.social_youtube,
      social_x: form.value.social_x,
      social_whatsapp: form.value.social_whatsapp,
    })
    successMessage.value = 'Social media links updated.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save social links.'
  } finally {
    saving.value = false
  }
}

async function saveFooter() {
  clearAlerts()
  saving.value = true
  try {
    await settingsStore.updateSettings({
      footer_tagline: form.value.footer_tagline,
      footer_copyright: form.value.footer_copyright,
      payment_icons_enabled: form.value.payment_icons_enabled,
    })
    successMessage.value = 'Footer configuration updated.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update footer.'
  } finally {
    saving.value = false
  }
}

async function handleFileUpload(event, targetField, type) {
  const file = event.target.files[0]
  if (!file) return

  clearAlerts()
  uploadingAsset.value = true
  try {
    const res = await settingsStore.uploadAsset(file, type)
    form.value[targetField] = res.url
    successMessage.value = `${type.toUpperCase()} uploaded successfully.`
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to upload asset. Ensure it is a valid, clean image.'
  } finally {
    uploadingAsset.value = false
    event.target.value = ''
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">System Settings</h1>
        <p class="text-sm text-gray-500 mt-1">
          Configure site branding, payment gateways, Hostinger SMTP mail, and store behaviors.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <span
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
        >
          <Sparkles class="w-3.5 h-3.5 mr-1" />
          File-cached & Auto-purging
        </span>
      </div>
    </div>

    <!-- Alert notifications -->
    <div
      v-if="successMessage"
      class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 class="w-5 h-5 text-emerald-600 flex-shrink-0" />
        <span class="text-sm font-medium">{{ successMessage }}</span>
      </div>
      <button class="text-emerald-700 hover:text-emerald-900 text-sm font-medium" @click="successMessage = ''">
        Dismiss
      </button>
    </div>

    <div
      v-if="errorMessage"
      class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <AlertCircle class="w-5 h-5 text-rose-600 flex-shrink-0" />
        <span class="text-sm font-medium">{{ errorMessage }}</span>
      </div>
      <button class="text-rose-700 hover:text-rose-900 text-sm font-medium" @click="errorMessage = ''">
        Dismiss
      </button>
    </div>

    <!-- Layout: Tabs + Card Content -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
      <!-- Navigation Tabs -->
      <nav class="bg-white rounded-2xl border border-gray-100 shadow-sm p-2 space-y-1 lg:col-span-1">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors text-left"
          :class="[
            activeTab === tab.id
              ? 'bg-[#2F5D50] text-white shadow-sm'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
          ]"
          @click="activeTab = tab.id; clearAlerts()"
        >
          <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
          <span>{{ tab.label }}</span>
        </button>
      </nav>

      <!-- Main Form Area -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 lg:col-span-3">
        <!-- 1. GENERAL & BRANDING -->
        <div v-if="activeTab === 'general'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">General & Branding</h2>
            <p class="text-xs text-gray-500 mt-1">Manage brand aesthetics, identity, and contact information.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Site Name</label>
              <input
                v-model="form.site_name"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Site Tagline</label>
              <input
                v-model="form.site_tagline"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <!-- Brand Color with Preview -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Brand Color</label>
              <div class="flex items-center gap-3">
                <input
                  v-model="form.brand_color"
                  type="color"
                  class="w-10 h-10 p-1 rounded-xl border border-gray-200 cursor-pointer"
                />
                <input
                  v-model="form.brand_color"
                  type="text"
                  placeholder="#2F5D50"
                  class="w-32 px-3.5 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm uppercase font-mono"
                />
                <div
                  class="px-3 py-1.5 rounded-lg text-xs font-medium text-white shadow-sm"
                  :style="{ backgroundColor: form.brand_color }"
                >
                  Live Preview
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Store Timezone</label>
              <input
                v-model="form.timezone"
                type="text"
                placeholder="UTC"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <!-- Currency Settings -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Currency Code</label>
              <input
                v-model="form.currency_code"
                type="text"
                placeholder="USD"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm uppercase"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Currency Symbol</label>
              <input
                v-model="form.currency_symbol"
                type="text"
                placeholder="$"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <!-- Contact info -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Support Email</label>
              <input
                v-model="form.support_email"
                type="email"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Support Phone</label>
              <input
                v-model="form.support_phone"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Physical Store Address</label>
              <input
                v-model="form.store_address"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <!-- Logo Upload -->
            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-3">
              <span class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Store Logo</span>
              <div v-if="form.logo_url" class="p-2 bg-white rounded-lg border border-gray-200 inline-block">
                <img :src="form.logo_url" alt="Logo preview" class="h-10 object-contain max-w-[200px]" />
              </div>
              <div class="flex items-center gap-3">
                <label
                  class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-gray-200 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50"
                >
                  <Upload class="w-3.5 h-3.5" />
                  <span>Upload Logo (PNG, WebP, SVG)</span>
                  <input
                    type="file"
                    class="hidden"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    @change="handleFileUpload($event, 'logo_url', 'logo')"
                  />
                </label>
                <button
                  v-if="form.logo_url"
                  type="button"
                  class="text-xs text-rose-600 hover:underline"
                  @click="form.logo_url = ''"
                >
                  Remove
                </button>
              </div>
            </div>

            <!-- Favicon Upload -->
            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-3">
              <span class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Browser Favicon</span>
              <div v-if="form.favicon_url" class="p-2 bg-white rounded-lg border border-gray-200 inline-block">
                <img :src="form.favicon_url" alt="Favicon preview" class="w-6 h-6 object-contain" />
              </div>
              <div class="flex items-center gap-3">
                <label
                  class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-gray-200 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50"
                >
                  <Upload class="w-3.5 h-3.5" />
                  <span>Upload Favicon (ICO, PNG, SVG)</span>
                  <input
                    type="file"
                    class="hidden"
                    accept="image/png,image/x-icon,image/svg+xml"
                    @change="handleFileUpload($event, 'favicon_url', 'favicon')"
                  />
                </label>
                <button
                  v-if="form.favicon_url"
                  type="button"
                  class="text-xs text-rose-600 hover:underline"
                  @click="form.favicon_url = ''"
                >
                  Remove
                </button>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditGeneral"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveGeneral"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save General Settings</span>
            </button>
          </div>
        </div>

        <!-- 2. ANNOUNCEMENT BAR -->
        <div v-if="activeTab === 'announcement'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">Announcement Bar</h2>
            <p class="text-xs text-gray-500 mt-1">Configure the header banner for promotions, shipping alerts, and flash sales.</p>
          </div>

          <!-- Live Preview -->
          <div class="space-y-2">
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Live Preview</span>
            <div
              v-if="form.announcement_enabled === '1'"
              class="w-full py-2.5 px-4 rounded-xl text-center text-xs font-medium flex items-center justify-between"
              :style="{ backgroundColor: form.announcement_bg, color: form.announcement_text_color }"
            >
              <span class="flex-1">{{ form.announcement_text || 'Enter announcement text...' }}</span>
              <span v-if="form.announcement_dismissible === '1'" class="opacity-70 text-xs">✕</span>
            </div>
            <div v-else class="py-2.5 px-4 rounded-xl bg-gray-100 text-gray-400 text-center text-xs">
              Announcement bar is currently disabled.
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Status</label>
              <select
                v-model="form.announcement_enabled"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="1">Enabled (Visible)</option>
                <option value="0">Disabled (Hidden)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Dismissible by Visitor</label>
              <select
                v-model="form.announcement_dismissible"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="1">Yes (Show close button)</option>
                <option value="0">No (Persistent)</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Announcement Text</label>
              <input
                v-model="form.announcement_text"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Target Link (Optional)</label>
              <input
                v-model="form.announcement_link"
                type="text"
                placeholder="/shop or https://..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Colors</label>
              <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                  <input v-model="form.announcement_bg" type="color" class="w-8 h-8 rounded-lg cursor-pointer" />
                  <span class="text-xs text-gray-600 font-mono">BG</span>
                </div>
                <div class="flex items-center gap-2">
                  <input v-model="form.announcement_text_color" type="color" class="w-8 h-8 rounded-lg cursor-pointer" />
                  <span class="text-xs text-gray-600 font-mono">Text</span>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditGeneral"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveAnnouncement"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save Announcement Bar</span>
            </button>
          </div>
        </div>

        <!-- 3. STRIPE PAYMENTS -->
        <div v-if="activeTab === 'stripe'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">Stripe Payment Gateway</h2>
            <p class="text-xs text-gray-500 mt-1">
              Configure checkout credentials. Secrets are stored with AES-256 encryption and masked when viewed.
            </p>
          </div>

          <!-- Stripe Test Result Banner -->
          <div
            v-if="stripeTestResult"
            class="p-4 rounded-xl text-sm"
            :class="[
              stripeTestResult.success
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border border-rose-200 text-rose-800',
            ]"
          >
            <div class="flex items-center gap-2 font-medium">
              <CheckCircle2 v-if="stripeTestResult.success" class="w-5 h-5 text-emerald-600" />
              <AlertCircle v-else class="w-5 h-5 text-rose-600" />
              <span>{{ stripeTestResult.message }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Enable Stripe Checkout</label>
              <select
                v-model="form.stripe_enabled"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="1">Enabled (Accept Credit Cards)</option>
                <option value="0">Disabled</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Processing Mode</label>
              <select
                v-model="form.stripe_mode"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="test">Test Mode (Sandbox)</option>
                <option value="live">Live Mode (Production)</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Publishable Key</label>
              <input
                v-model="form.stripe_publishable_key"
                type="text"
                placeholder="pk_test_..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
            </div>

            <div class="md:col-span-2">
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Secret Key</label>
                <span class="text-xs text-gray-400">Encrypted in database · Masked for security</span>
              </div>
              <input
                v-model="form.stripe_secret_key"
                type="text"
                placeholder="sk_test_..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
              <p class="text-xs text-gray-400 mt-1">Leave masked value untouched to retain current secret.</p>
            </div>

            <div class="md:col-span-2">
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Webhook Signing Secret</label>
                <span class="text-xs text-gray-400">Encrypted</span>
              </div>
              <input
                v-model="form.stripe_webhook_secret"
                type="text"
                placeholder="whsec_..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <button
              :disabled="testingStripe || !canEditStripe"
              class="w-full sm:w-auto px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 text-xs font-medium disabled:opacity-50 transition-colors inline-flex items-center justify-center gap-2"
              @click="handleTestStripe"
            >
              <Loader2 v-if="testingStripe" class="w-3.5 h-3.5 animate-spin" />
              <CreditCard v-else class="w-3.5 h-3.5" />
              <span>Test Stripe Connection</span>
            </button>

            <button
              :disabled="saving || !canEditStripe"
              class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center justify-center gap-2"
              @click="saveStripe"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save Stripe Settings</span>
            </button>
          </div>
        </div>

        <!-- 4. EMAIL (HOSTINGER SMTP) -->
        <div v-if="activeTab === 'mail'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">Hostinger SMTP & Mail Server</h2>
            <p class="text-xs text-gray-500 mt-1">
              Transactional email setup. Config is dynamically applied at runtime with fallback to environment defaults.
            </p>
          </div>

          <!-- Mail Test Result Banner -->
          <div
            v-if="mailTestResult"
            class="p-4 rounded-xl text-sm"
            :class="[
              mailTestResult.success
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border border-rose-200 text-rose-800',
            ]"
          >
            <div class="flex items-center gap-2 font-medium">
              <CheckCircle2 v-if="mailTestResult.success" class="w-5 h-5 text-emerald-600" />
              <AlertCircle v-else class="w-5 h-5 text-rose-600" />
              <span>{{ mailTestResult.message }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">SMTP Host</label>
              <input
                v-model="form.mail_host"
                type="text"
                placeholder="smtp.hostinger.com"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Port</label>
                <input
                  v-model="form.mail_port"
                  type="number"
                  placeholder="465"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Encryption</label>
                <select
                  v-model="form.mail_encryption"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
                >
                  <option value="ssl">SSL (Port 465)</option>
                  <option value="tls">TLS (Port 587)</option>
                  <option value="none">None</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">SMTP Username</label>
              <input
                v-model="form.mail_username"
                type="text"
                placeholder="support@yourdomain.com"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">SMTP Password</label>
                <span class="text-xs text-gray-400">Encrypted</span>
              </div>
              <input
                v-model="form.mail_password"
                type="password"
                placeholder="••••••••••••"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Sender From Name</label>
              <input
                v-model="form.mail_from_name"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Sender From Address</label>
              <input
                v-model="form.mail_from_address"
                type="email"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Reply-To Address</label>
              <input
                v-model="form.mail_reply_to"
                type="email"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
          </div>

          <!-- Send Test Email Widget -->
          <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/70 space-y-3">
            <span class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Test SMTP Dispatch</span>
            <div class="flex flex-col sm:flex-row items-center gap-3">
              <input
                v-model="testMailEmail"
                type="email"
                placeholder="Recipient email address..."
                class="w-full sm:flex-1 px-3.5 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm bg-white"
              />
              <button
                :disabled="testingMail || !canEditMail"
                class="w-full sm:w-auto px-4 py-2 rounded-full border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 text-xs font-medium disabled:opacity-50 transition-colors inline-flex items-center justify-center gap-2"
                @click="handleTestMail"
              >
                <Loader2 v-if="testingMail" class="w-3.5 h-3.5 animate-spin" />
                <Send v-else class="w-3.5 h-3.5" />
                <span>Send Test Email</span>
              </button>
            </div>
            <p class="text-xs text-gray-400">All dispatches and connection attempts are logged into the email_logs table.</p>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditMail"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveMail"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save Email Settings</span>
            </button>
          </div>
        </div>

        <!-- 5. SHIPPING & TAXES -->
        <div v-if="activeTab === 'shipping'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">Shipping & Tax Defaults</h2>
            <p class="text-xs text-gray-500 mt-1">Configure baseline shipping thresholds and sales tax calculation.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Standard Flat Rate Shipping</label>
              <input
                v-model="form.default_shipping_fee"
                type="number"
                step="0.01"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Free Shipping Threshold</label>
              <input
                v-model="form.free_shipping_threshold"
                type="number"
                step="0.01"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Tax Rate Percentage (%)</label>
              <input
                v-model="form.tax_rate_percent"
                type="number"
                step="0.01"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Display Prices Include Tax</label>
              <select
                v-model="form.prices_include_tax"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="1">Yes (Prices shown are tax-inclusive)</option>
                <option value="0">No (Tax calculated at checkout)</option>
              </select>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditGeneral"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveShippingAndTax"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save Shipping & Taxes</span>
            </button>
          </div>
        </div>

        <!-- 6. SEO & ANALYTICS -->
        <div v-if="activeTab === 'seo'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">SEO & Tracking Scripts</h2>
            <p class="text-xs text-gray-500 mt-1">Search engine metadata, social share cards, Google Analytics, and pixels.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Default SEO Title</label>
              <input
                v-model="form.seo_title"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Meta Description</label>
              <textarea
                v-model="form.seo_description"
                rows="3"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Google Analytics ID</label>
              <input
                v-model="form.google_analytics_id"
                type="text"
                placeholder="G-XXXXXXXXXX"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Meta Pixel ID</label>
              <input
                v-model="form.meta_pixel_id"
                type="text"
                placeholder="1234567890"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm font-mono"
              />
            </div>

            <!-- Custom Scripts (Super Admin Only) -->
            <div class="md:col-span-2 pt-2 border-t border-gray-100">
              <div class="flex items-center gap-2 mb-2">
                <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Custom &lt;head&gt; Scripts</label>
                <span
                  class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                  :class="isSuperAdmin ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500'"
                >
                  {{ isSuperAdmin ? 'Super Admin Only' : 'Locked' }}
                </span>
              </div>
              <textarea
                v-model="form.custom_head_scripts"
                :disabled="!isSuperAdmin"
                rows="3"
                placeholder="<!-- Custom header script tags -->"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-xs font-mono disabled:bg-gray-50"
              ></textarea>
            </div>

            <div class="md:col-span-2">
              <div class="flex items-center gap-2 mb-2">
                <label class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Custom &lt;body&gt; Scripts</label>
                <span
                  class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                  :class="isSuperAdmin ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500'"
                >
                  {{ isSuperAdmin ? 'Super Admin Only' : 'Locked' }}
                </span>
              </div>
              <textarea
                v-model="form.custom_body_scripts"
                :disabled="!isSuperAdmin"
                rows="3"
                placeholder="<!-- Custom body tracking script tags -->"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-xs font-mono disabled:bg-gray-50"
              ></textarea>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditGeneral"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveSeo"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save SEO & Scripts</span>
            </button>
          </div>
        </div>

        <!-- 7. STORE & OPERATIONS -->
        <div v-if="activeTab === 'store'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">Store Status & Behavior</h2>
            <p class="text-xs text-gray-500 mt-1">Maintenance mode, guest checkout rules, and inventory threshold alerts.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Maintenance Mode</label>
              <select
                v-model="form.maintenance_mode"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="0">Off (Live Storefront)</option>
                <option value="1">On (Maintenance Screen)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Guest Checkout</label>
              <select
                v-model="form.guest_checkout"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="1">Allowed (No account required)</option>
                <option value="0">Account Required to Checkout</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Maintenance Notice Message</label>
              <input
                v-model="form.maintenance_message"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Customer Reviews Moderation</label>
              <select
                v-model="form.review_moderation"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="1">Require Staff Approval Before Publishing</option>
                <option value="0">Publish Immediately</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Require Email Verification</label>
              <select
                v-model="form.require_email_verification"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="0">No (Instant signup access)</option>
                <option value="1">Yes (Require verified link)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Low Stock Alert Threshold</label>
              <input
                v-model="form.low_stock_threshold"
                type="number"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Low Stock Notification Email</label>
              <input
                v-model="form.low_stock_email"
                type="email"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditGeneral"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveStore"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save Store Settings</span>
            </button>
          </div>
        </div>

        <!-- 8. SOCIAL MEDIA -->
        <div v-if="activeTab === 'social'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">Social Media Links</h2>
            <p class="text-xs text-gray-500 mt-1">Official handles surfaced across the footer, contact page, and mega menu.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Instagram URL</label>
              <input
                v-model="form.social_instagram"
                type="text"
                placeholder="https://instagram.com/..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Facebook URL</label>
              <input
                v-model="form.social_facebook"
                type="text"
                placeholder="https://facebook.com/..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">TikTok URL</label>
              <input
                v-model="form.social_tiktok"
                type="text"
                placeholder="https://tiktok.com/@..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">YouTube URL</label>
              <input
                v-model="form.social_youtube"
                type="text"
                placeholder="https://youtube.com/@..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">X / Twitter URL</label>
              <input
                v-model="form.social_x"
                type="text"
                placeholder="https://x.com/..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">WhatsApp Number</label>
              <input
                v-model="form.social_whatsapp"
                type="text"
                placeholder="+15550123456"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditGeneral"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveSocial"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save Social Links</span>
            </button>
          </div>
        </div>

        <!-- 9. FOOTER & LEGAL -->
        <div v-if="activeTab === 'footer'" class="space-y-6">
          <div class="border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-gray-900">Footer & Legal Notice</h2>
            <p class="text-xs text-gray-500 mt-1">Storefront bottom section text, copyright notice, and trust badges.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Footer Tagline</label>
              <input
                v-model="form.footer_tagline"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Copyright Statement</label>
              <input
                v-model="form.footer_copyright"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Payment Icons Badge</label>
              <select
                v-model="form.payment_icons_enabled"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#2F5D50] text-sm"
              >
                <option value="1">Show Payment Card Icons</option>
                <option value="0">Hide</option>
              </select>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              :disabled="saving || !canEditGeneral"
              class="px-5 py-2.5 rounded-full bg-[#2F5D50] text-white font-medium text-sm hover:bg-[#264a40] disabled:opacity-50 transition-colors inline-flex items-center gap-2"
              @click="saveFooter"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>Save Footer Settings</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
