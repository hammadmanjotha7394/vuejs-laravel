<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import {
  Sparkles,
  Image as ImageIcon,
  MessageSquare,
  ShieldCheck,
  Plus,
  Trash2,
  Edit2,
  ArrowUp,
  ArrowDown,
  Upload,
  Eye,
  CheckCircle2,
  AlertCircle,
  Monitor,
  Smartphone,
  ArrowRight,
  X,
  MousePointerClick,
  Users,
  BarChart3,
  Truck,
  RotateCcw,
  Headphones,
  Heart,
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import ToggleSwitch from '@/components/admin/ToggleSwitch.vue'
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue'

const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const canManageHero = computed(() => authStore.hasPermission('hero.manage'))
const canManagePopups = computed(() => authStore.hasPermission('popups.manage'))
const canManageBanners = computed(
  () => authStore.hasPermission('hero.manage') || authStore.hasPermission('settings.update')
)

const activeTab = ref(canManageHero.value ? 'hero' : canManagePopups.value ? 'popups' : 'banners')
const toastMessage = ref('')
const errorMessage = ref('')

const showToast = (msg) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

// ==========================================
// 1. HERO SLIDES STATE & LOGIC
// ==========================================
const heroSlides = ref([])
const isLoadingHero = ref(false)
const isSavingHero = ref(false)
const isUploadingHeroDesktop = ref(false)
const isUploadingHeroMobile = ref(false)
const showHeroEditor = ref(false)
const editingSlideId = ref(null)
const heroPreviewMode = ref('desktop') // 'desktop' | 'mobile'
const slideToDelete = ref(null)

const heroForm = reactive({
  title: '',
  subtitle: '',
  button_text: 'Shop Collection',
  button_url: '/shop',
  image_desktop: '',
  image_mobile: '',
  text_position: 'left',
  overlay_opacity: 0.55,
  starts_at: '',
  ends_at: '',
  is_active: true,
})

const fetchHeroSlides = async () => {
  if (!canManageHero.value) return
  isLoadingHero.value = true
  try {
    const res = await apiClient.get('/admin/hero-slides')
    heroSlides.value = (res.data.data || []).map((s) => ({
      ...s,
      overlay_opacity: Number(s.overlay_opacity ?? 0.55),
    }))
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load hero slides.'
  } finally {
    isLoadingHero.value = false
  }
}

const openCreateHero = () => {
  editingSlideId.value = null
  heroForm.title = 'Quiet Luxury in Every Thread'
  heroForm.subtitle = 'Discover breathable European flax linen and tactile homeware crafted for calm living.'
  heroForm.button_text = 'Explore Collection'
  heroForm.button_url = '/shop'
  heroForm.image_desktop = 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1600&q=80'
  heroForm.image_mobile = ''
  heroForm.text_position = 'left'
  heroForm.overlay_opacity = 0.55
  heroForm.starts_at = ''
  heroForm.ends_at = ''
  heroForm.is_active = true
  showHeroEditor.value = true
}

const openEditHero = (slide) => {
  editingSlideId.value = slide.id
  heroForm.title = slide.title || ''
  heroForm.subtitle = slide.subtitle || ''
  heroForm.button_text = slide.button_text || ''
  heroForm.button_url = slide.button_url || ''
  heroForm.image_desktop = slide.image_desktop || ''
  heroForm.image_mobile = slide.image_mobile || ''
  heroForm.text_position = slide.text_position || 'left'
  heroForm.overlay_opacity = Number(slide.overlay_opacity ?? 0.55)
  heroForm.starts_at = slide.starts_at ? slide.starts_at.slice(0, 16) : ''
  heroForm.ends_at = slide.ends_at ? slide.ends_at.slice(0, 16) : ''
  heroForm.is_active = Boolean(slide.is_active)
  showHeroEditor.value = true
}

const uploadMarketingImage = async (file) => {
  const formData = new FormData()
  formData.append('image', file)
  const endpoint = canManageHero.value ? '/admin/hero-slides/upload-image' : '/admin/popups/upload-image'
  const res = await apiClient.post(endpoint, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return res.data.url
}

const handleHeroImageUpload = async (e, target) => {
  const file = e.target.files?.[0]
  if (!file) return
  if (target === 'desktop') isUploadingHeroDesktop.value = true
  else isUploadingHeroMobile.value = true

  try {
    const url = await uploadMarketingImage(file)
    if (target === 'desktop') heroForm.image_desktop = url
    else heroForm.image_mobile = url
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Image upload failed.'
  } finally {
    isUploadingHeroDesktop.value = false
    isUploadingHeroMobile.value = false
    e.target.value = ''
  }
}

const saveHeroSlide = async () => {
  isSavingHero.value = true
  errorMessage.value = ''
  try {
    const payload = {
      title: heroForm.title,
      subtitle: heroForm.subtitle || null,
      button_text: heroForm.button_text || null,
      button_url: heroForm.button_url || null,
      image_desktop: heroForm.image_desktop,
      image_mobile: heroForm.image_mobile || null,
      text_position: heroForm.text_position,
      overlay_opacity: Number(heroForm.overlay_opacity),
      starts_at: heroForm.starts_at || null,
      ends_at: heroForm.ends_at || null,
      is_active: heroForm.is_active,
    }

    if (editingSlideId.value) {
      await apiClient.put(`/admin/hero-slides/${editingSlideId.value}`, payload)
      showToast('Hero slide updated.')
    } else {
      await apiClient.post('/admin/hero-slides', payload)
      showToast('Hero slide created.')
    }

    showHeroEditor.value = false
    await fetchHeroSlides()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save hero slide.'
  } finally {
    isSavingHero.value = false
  }
}

const updateSlideOpacity = async (slide, newOpacity) => {
  const val = Number(Number(newOpacity).toFixed(2))
  slide.overlay_opacity = val
  try {
    await apiClient.put(`/admin/hero-slides/${slide.id}`, {
      overlay_opacity: val,
    })
    showToast(`Black layer opacity for "${slide.title}" set to ${Math.round(val * 100)}%.`)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Could not update overlay opacity.'
  }
}

const toggleHeroSlideActive = async (slide, val) => {
  slide.is_active = val
  try {
    await apiClient.put(`/admin/hero-slides/${slide.id}`, { is_active: val })
    showToast(`Slide "${slide.title}" ${val ? 'activated' : 'deactivated'}.`)
  } catch {
    slide.is_active = !val
  }
}

const moveHeroSlide = async (idx, dir) => {
  const targetIdx = idx + dir
  if (targetIdx < 0 || targetIdx >= heroSlides.value.length) return
  const list = [...heroSlides.value]
  const [moved] = list.splice(idx, 1)
  list.splice(targetIdx, 0, moved)
  heroSlides.value = list

  try {
    await apiClient.post('/admin/hero-slides/reorder', {
      ids: heroSlides.value.map((s) => s.id),
    })
    showToast('Hero slides order saved.')
  } catch {
    fetchHeroSlides()
  }
}

const confirmDeleteHero = async () => {
  if (!slideToDelete.value) return
  try {
    await apiClient.delete(`/admin/hero-slides/${slideToDelete.value.id}`)
    slideToDelete.value = null
    showToast('Hero slide deleted.')
    fetchHeroSlides()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete hero slide.'
  }
}

// ==========================================
// 2. POPUPS STATE & LOGIC
// ==========================================
const popups = ref([])
const isLoadingPopups = ref(false)
const isSavingPopup = ref(false)
const isUploadingPopupImage = ref(false)
const showPopupEditor = ref(false)
const editingPopupId = ref(null)
const popupToDelete = ref(null)
const livePreviewPopupData = ref(null)

const popupForm = reactive({
  name: '',
  type: 'newsletter',
  title: '',
  body: '',
  image: '',
  button_text: 'Claim 10% Voucher',
  button_url: '/shop',
  trigger: 'delay',
  trigger_value: 5,
  pages_mode: 'all', // 'all' | 'home' | 'custom'
  custom_pages: '/shop, /category/women',
  frequency: 'once_per_session',
  starts_at: '',
  ends_at: '',
  is_active: true,
})

const fetchPopups = async () => {
  if (!canManagePopups.value) return
  isLoadingPopups.value = true
  try {
    const res = await apiClient.get('/admin/popups')
    popups.value = res.data.data || []
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load popups.'
  } finally {
    isLoadingPopups.value = false
  }
}

const openCreatePopup = () => {
  editingPopupId.value = null
  popupForm.name = 'Autumn Welcome Voucher'
  popupForm.type = 'newsletter'
  popupForm.title = 'Join the Studio Journal'
  popupForm.body = 'Receive 10% off your first order and early access to small-batch seasonal releases.'
  popupForm.image = 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=800&q=80'
  popupForm.button_text = 'Claim Your 10% Voucher'
  popupForm.button_url = '/shop'
  popupForm.trigger = 'delay'
  popupForm.trigger_value = 5
  popupForm.pages_mode = 'all'
  popupForm.custom_pages = '/shop'
  popupForm.frequency = 'once_per_session'
  popupForm.starts_at = ''
  popupForm.ends_at = ''
  popupForm.is_active = true
  showPopupEditor.value = true
}

const openEditPopup = (popup) => {
  editingPopupId.value = popup.id
  popupForm.name = popup.name || ''
  popupForm.type = popup.type || 'newsletter'
  popupForm.title = popup.title || ''
  popupForm.body = popup.body || ''
  popupForm.image = popup.image || ''
  popupForm.button_text = popup.button_text || ''
  popupForm.button_url = popup.button_url || ''
  popupForm.trigger = popup.trigger || 'delay'
  popupForm.trigger_value = Number(popup.trigger_value ?? 5)

  const pages = Array.isArray(popup.show_on_pages_json) ? popup.show_on_pages_json : ['all']
  if (pages.includes('all')) {
    popupForm.pages_mode = 'all'
    popupForm.custom_pages = ''
  } else if (pages.length === 1 && (pages[0] === 'home' || pages[0] === '/')) {
    popupForm.pages_mode = 'home'
    popupForm.custom_pages = ''
  } else {
    popupForm.pages_mode = 'custom'
    popupForm.custom_pages = pages.join(', ')
  }

  popupForm.frequency = popup.frequency || 'once_per_session'
  popupForm.starts_at = popup.starts_at ? popup.starts_at.slice(0, 16) : ''
  popupForm.ends_at = popup.ends_at ? popup.ends_at.slice(0, 16) : ''
  popupForm.is_active = Boolean(popup.is_active)
  showPopupEditor.value = true
}

const handlePopupImageUpload = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  isUploadingPopupImage.value = true
  try {
    popupForm.image = await uploadMarketingImage(file)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Image upload failed.'
  } finally {
    isUploadingPopupImage.value = false
    e.target.value = ''
  }
}

const savePopup = async () => {
  isSavingPopup.value = true
  errorMessage.value = ''
  try {
    let showPages = ['all']
    if (popupForm.pages_mode === 'home') {
      showPages = ['/']
    } else if (popupForm.pages_mode === 'custom') {
      showPages = popupForm.custom_pages
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
      if (!showPages.length) showPages = ['all']
    }

    const payload = {
      name: popupForm.name,
      type: popupForm.type,
      title: popupForm.title || null,
      body: popupForm.body || null,
      image: popupForm.image || null,
      button_text: popupForm.button_text || null,
      button_url: popupForm.button_url || null,
      trigger: popupForm.trigger,
      trigger_value: Number(popupForm.trigger_value || 0),
      show_on_pages_json: showPages,
      frequency: popupForm.frequency,
      starts_at: popupForm.starts_at || null,
      ends_at: popupForm.ends_at || null,
      is_active: popupForm.is_active,
    }

    if (editingPopupId.value) {
      await apiClient.put(`/admin/popups/${editingPopupId.value}`, payload)
      showToast('Popup updated.')
    } else {
      await apiClient.post('/admin/popups', payload)
      showToast('Popup created.')
    }

    showPopupEditor.value = false
    await fetchPopups()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save popup.'
  } finally {
    isSavingPopup.value = false
  }
}

const togglePopupActive = async (popup, val) => {
  popup.is_active = val
  try {
    await apiClient.put(`/admin/popups/${popup.id}`, { is_active: val })
    showToast(`Popup "${popup.name}" ${val ? 'enabled' : 'disabled'}.`)
  } catch {
    popup.is_active = !val
  }
}

const confirmDeletePopup = async () => {
  if (!popupToDelete.value) return
  try {
    await apiClient.delete(`/admin/popups/${popupToDelete.value.id}`)
    popupToDelete.value = null
    showToast('Popup deleted.')
    fetchPopups()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete popup.'
  }
}

const openPopupLivePreview = (popupObj) => {
  livePreviewPopupData.value = { ...popupObj }
}

// ==========================================
// 3. BANNERS & TRUST POINTS STATE & LOGIC
// ==========================================
const promoBanners = ref([])
const trustPoints = ref([])
const isLoadingBlocks = ref(false)
const isSavingBlocks = ref(false)

const fetchHomeBlocks = async () => {
  if (!canManageBanners.value) return
  isLoadingBlocks.value = true
  try {
    const res = await apiClient.get('/admin/marketing/home-blocks')
    promoBanners.value = res.data.data?.promo_banners || []
    trustPoints.value = res.data.data?.trust_points || []
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load home banners & trust points.'
  } finally {
    isLoadingBlocks.value = false
  }
}

const addPromoBanner = () => {
  promoBanners.value.push({
    tag: 'New Release',
    title: 'Seasonal Capsule',
    description: 'Hand-finished garments woven from certified organic fibers.',
    button_text: 'Shop Now',
    button_url: '/shop',
    image: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1000&q=80',
  })
}

const removePromoBanner = (idx) => {
  promoBanners.value.splice(idx, 1)
}

const handleBannerImageUpload = async (e, idx) => {
  const file = e.target.files?.[0]
  if (!file) return
  try {
    promoBanners.value[idx].image = await uploadMarketingImage(file)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Banner image upload failed.'
  } finally {
    e.target.value = ''
  }
}

const saveHomeBlocks = async () => {
  isSavingBlocks.value = true
  errorMessage.value = ''
  try {
    await apiClient.put('/admin/marketing/home-blocks', {
      promo_banners: promoBanners.value,
      trust_points: trustPoints.value,
    })
    await settingsStore.fetchPublicSettings(true)
    showToast('Home page banners and trust points saved and live on storefront.')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save home blocks.'
  } finally {
    isSavingBlocks.value = false
  }
}

const getTrustIconComponent = (iconName) => {
  const map = {
    truck: Truck,
    'rotate-ccw': RotateCcw,
    'shield-check': ShieldCheck,
    headphones: Headphones,
    sparkles: Sparkles,
    heart: Heart,
  }
  return map[iconName] || ShieldCheck
}

onMounted(() => {
  fetchHeroSlides()
  fetchPopups()
  fetchHomeBlocks()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Header & Section Switcher -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Marketing & Storefront Merchandising</h1>
        <p class="text-xs text-ink-muted mt-1">
          Manage homepage hero slides with live preview, targeted promotional popups with conversion tracking, and home promo banners & trust points.
        </p>
      </div>

      <div class="inline-flex flex-wrap items-center bg-surface border border-line rounded-pill p-1 self-start">
        <button
          v-if="canManageHero"
          type="button"
          @click="activeTab = 'hero'"
          class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-pill text-xs font-semibold transition"
          :class="activeTab === 'hero' ? 'bg-page text-ink shadow-sm' : 'text-ink-muted hover:text-ink'"
        >
          <ImageIcon class="w-3.5 h-3.5" />
          <span>Hero Slides ({{ heroSlides.length }})</span>
        </button>

        <button
          v-if="canManagePopups"
          type="button"
          @click="activeTab = 'popups'"
          class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-pill text-xs font-semibold transition"
          :class="activeTab === 'popups' ? 'bg-page text-ink shadow-sm' : 'text-ink-muted hover:text-ink'"
        >
          <MessageSquare class="w-3.5 h-3.5" />
          <span>Popups ({{ popups.length }})</span>
        </button>

        <button
          v-if="canManageBanners"
          type="button"
          @click="activeTab = 'banners'"
          class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-pill text-xs font-semibold transition"
          :class="activeTab === 'banners' ? 'bg-page text-ink shadow-sm' : 'text-ink-muted hover:text-ink'"
        >
          <Sparkles class="w-3.5 h-3.5" />
          <span>Home Banners & Trust Points</span>
        </button>
      </div>
    </div>

    <!-- Toasts / Errors -->
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
      <div class="flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <button @click="errorMessage = ''" class="text-[11px] underline">Dismiss</button>
    </div>

    <!-- ==========================================
         TAB 1: HERO SLIDES & LIVE PREVIEW
         ========================================== -->
    <div v-if="activeTab === 'hero' && canManageHero" class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-ink">Homepage Hero Slider</h2>
          <p class="text-xs text-ink-muted">Reorder slides or click Edit to customize text alignment, overlay opacity, and schedule.</p>
        </div>
        <button
          type="button"
          @click="openCreateHero"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
        >
          <Plus class="w-4 h-4" />
          <span>Add Hero Slide</span>
        </button>
      </div>

      <!-- Hero Slide Editor + Live Storefront Preview -->
      <div v-if="showHeroEditor" class="bg-page border border-line rounded-card p-6 shadow-soft space-y-6">
        <div class="flex items-center justify-between border-b border-line pb-4">
          <div>
            <h3 class="text-sm font-bold text-ink">
              {{ editingSlideId ? 'Edit Hero Slide' : 'Create New Hero Slide' }}
            </h3>
            <p class="text-xs text-ink-muted">Changes are reflected immediately in the live preview on the right.</p>
          </div>
          <button
            type="button"
            @click="showHeroEditor = false"
            class="text-xs text-ink-muted hover:text-ink"
          >
            Cancel
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Form Controls (Left 6 Cols) -->
          <div class="lg:col-span-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-ink mb-1">Headline Title *</label>
              <input
                v-model="heroForm.title"
                type="text"
                placeholder="e.g. Autumn Linen Essentials"
                class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-ink mb-1">Subtitle</label>
              <textarea
                v-model="heroForm.subtitle"
                rows="2"
                placeholder="Supporting description shown below the headline..."
                class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-ink mb-1">Button Text</label>
                <input
                  v-model="heroForm.button_text"
                  type="text"
                  placeholder="Shop Collection"
                  class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-ink mb-1">Button Link</label>
                <input
                  v-model="heroForm.button_url"
                  type="text"
                  placeholder="/category/women"
                  class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
                />
              </div>
            </div>

            <!-- Desktop Image -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-semibold text-ink">Desktop Image (16:9 / 1920×900) *</label>
                <label class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand cursor-pointer hover:underline">
                  <Upload class="w-3 h-3" />
                  <span>{{ isUploadingHeroDesktop ? 'Uploading...' : 'Upload File' }}</span>
                  <input type="file" accept="image/*" class="hidden" @change="(e) => handleHeroImageUpload(e, 'desktop')" />
                </label>
              </div>
              <input
                v-model="heroForm.image_desktop"
                type="text"
                placeholder="https://... or upload image"
                class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <!-- Mobile Image -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-semibold text-ink">Mobile Portrait Image (Optional)</label>
                <label class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand cursor-pointer hover:underline">
                  <Upload class="w-3 h-3" />
                  <span>{{ isUploadingHeroMobile ? 'Uploading...' : 'Upload File' }}</span>
                  <input type="file" accept="image/*" class="hidden" @change="(e) => handleHeroImageUpload(e, 'mobile')" />
                </label>
              </div>
              <input
                v-model="heroForm.image_mobile"
                type="text"
                placeholder="Defaults to Desktop Image if left blank"
                class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <!-- Text Position & Overlay Opacity -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-ink mb-1.5">Text Position</label>
                <div class="grid grid-cols-3 gap-1.5">
                  <button
                    v-for="pos in ['left', 'center', 'right']"
                    :key="pos"
                    type="button"
                    @click="heroForm.text_position = pos"
                    class="py-1.5 px-2.5 rounded-lg text-xs font-semibold capitalize border transition"
                    :class="
                      heroForm.text_position === pos
                        ? 'bg-brand text-white border-brand'
                        : 'bg-surface text-ink border-line'
                    "
                  >
                    {{ pos }}
                  </button>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-xs font-semibold text-ink mb-1.5">
                  <span>Black Layer Opacity</span>
                  <span class="font-mono text-brand">{{ Math.round(heroForm.overlay_opacity * 100) }}%</span>
                </div>
                <input
                  v-model.number="heroForm.overlay_opacity"
                  type="range"
                  min="0"
                  max="0.95"
                  step="0.05"
                  class="w-full accent-brand"
                />
                <div class="flex items-center gap-1 mt-1.5">
                  <button
                    v-for="preset in [
                      { label: '25% Light', val: 0.25 },
                      { label: '55% Medium', val: 0.55 },
                      { label: '70% Dark', val: 0.7 },
                      { label: '85% Max', val: 0.85 },
                    ]"
                    :key="preset.val"
                    type="button"
                    @click="heroForm.overlay_opacity = preset.val"
                    class="flex-1 py-1 px-1.5 rounded text-[10px] font-semibold border transition"
                    :class="
                      Math.abs(heroForm.overlay_opacity - preset.val) < 0.03
                        ? 'bg-brand text-white border-brand'
                        : 'bg-surface text-ink-muted border-line hover:text-ink'
                    "
                  >
                    {{ preset.label }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Schedule & Active -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-ink mb-1">Start Date (Optional)</label>
                <input
                  v-model="heroForm.starts_at"
                  type="datetime-local"
                  class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-ink mb-1">End Date (Optional)</label>
                <input
                  v-model="heroForm.ends_at"
                  type="datetime-local"
                  class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
                />
              </div>
            </div>

            <div class="flex items-center justify-between pt-2">
              <div class="flex items-center gap-2">
                <ToggleSwitch v-model="heroForm.is_active" />
                <span class="text-xs font-semibold text-ink">Active on Storefront</span>
              </div>

              <button
                type="button"
                @click="saveHeroSlide"
                :disabled="isSavingHero"
                class="px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition disabled:opacity-50"
              >
                {{ isSavingHero ? 'Saving Slide...' : 'Save Hero Slide' }}
              </button>
            </div>
          </div>

          <!-- Live Hero Preview (Right 6 Cols) -->
          <div class="lg:col-span-6 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-ink uppercase tracking-wider">Live Hero Preview</span>
              <div class="inline-flex items-center bg-surface border border-line rounded-pill p-0.5">
                <button
                  type="button"
                  @click="heroPreviewMode = 'desktop'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-pill text-[11px] font-semibold"
                  :class="heroPreviewMode === 'desktop' ? 'bg-page text-ink shadow-sm' : 'text-ink-muted'"
                >
                  <Monitor class="w-3 h-3" />
                  <span>Desktop</span>
                </button>
                <button
                  type="button"
                  @click="heroPreviewMode = 'mobile'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-pill text-[11px] font-semibold"
                  :class="heroPreviewMode === 'mobile' ? 'bg-page text-ink shadow-sm' : 'text-ink-muted'"
                >
                  <Smartphone class="w-3 h-3" />
                  <span>Mobile</span>
                </button>
              </div>
            </div>

            <div
              class="relative rounded-card overflow-hidden border border-line shadow-soft transition-all mx-auto flex items-center bg-gray-950"
              :class="heroPreviewMode === 'mobile' ? 'max-w-[320px] h-[440px]' : 'w-full h-[360px]'"
            >
              <img
                :src="(heroPreviewMode === 'mobile' && heroForm.image_mobile) ? heroForm.image_mobile : heroForm.image_desktop"
                :alt="heroForm.title"
                class="absolute inset-0 w-full h-full object-cover"
              />
              <div
                class="absolute inset-0 bg-black transition-opacity"
                :style="{ opacity: heroForm.overlay_opacity }"
              ></div>

              <div
                class="relative z-10 w-full p-6 sm:p-8 flex flex-col text-white"
                :class="{
                  'items-start text-left': heroForm.text_position === 'left',
                  'items-center text-center': heroForm.text_position === 'center',
                  'items-end text-right': heroForm.text_position === 'right',
                }"
              >
                <div class="max-w-md space-y-3">
                  <h4
                    class="text-xl sm:text-2xl font-bold tracking-tight leading-tight text-white"
                    style="text-shadow: 0 2px 14px rgba(0, 0, 0, 0.65)"
                  >
                    {{ heroForm.title || 'Slide Headline' }}
                  </h4>
                  <p
                    v-if="heroForm.subtitle"
                    class="text-xs sm:text-sm text-white/95 leading-relaxed"
                    style="text-shadow: 0 1px 8px rgba(0, 0, 0, 0.75)"
                  >
                    {{ heroForm.subtitle }}
                  </p>
                  <div v-if="heroForm.button_text" class="pt-1">
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-white text-ink text-xs font-semibold shadow-sm">
                      <span>{{ heroForm.button_text }}</span>
                      <ArrowRight class="w-3.5 h-3.5" />
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hero Slides Reorderable List -->
      <div class="bg-page border border-line rounded-card divide-y divide-line shadow-soft">
        <div v-if="isLoadingHero" class="p-8 text-center text-xs text-ink-muted">Loading hero slides...</div>
        <div v-else-if="!heroSlides.length" class="p-8 text-center text-xs text-ink-muted">
          No hero slides created yet. Click "Add Hero Slide" to create your first slide.
        </div>

        <div
          v-for="(slide, idx) in heroSlides"
          :key="slide.id"
          class="p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-surface/50 transition"
        >
          <div class="flex items-center gap-4 min-w-0">
            <!-- Reorder Controls -->
            <div class="flex flex-col gap-1 shrink-0">
              <button
                type="button"
                @click="moveHeroSlide(idx, -1)"
                :disabled="idx === 0"
                class="p-1 rounded border border-line bg-page text-ink-muted hover:text-ink disabled:opacity-30"
              >
                <ArrowUp class="w-3 h-3" />
              </button>
              <button
                type="button"
                @click="moveHeroSlide(idx, 1)"
                :disabled="idx === heroSlides.length - 1"
                class="p-1 rounded border border-line bg-page text-ink-muted hover:text-ink disabled:opacity-30"
              >
                <ArrowDown class="w-3 h-3" />
              </button>
            </div>

            <!-- Slide Thumbnail with Live Black Overlay Preview -->
            <div class="relative w-28 h-16 rounded-lg overflow-hidden border border-line bg-gray-900 shrink-0">
              <img
                :src="slide.image_desktop"
                :alt="slide.title"
                class="w-full h-full object-cover"
              />
              <div
                class="absolute inset-0 bg-black transition-opacity"
                :style="{ opacity: slide.overlay_opacity ?? 0.55 }"
              ></div>
              <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white px-1 text-center truncate">
                {{ Math.round((slide.overlay_opacity ?? 0.55) * 100) }}% Dark
              </span>
            </div>

            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-ink truncate">{{ slide.title }}</h3>
                <span class="px-2 py-0.5 rounded-pill text-[10px] font-semibold bg-surface border border-line capitalize text-ink-muted">
                  Align: {{ slide.text_position }}
                </span>
              </div>
              <p class="text-xs text-ink-muted truncate mt-0.5">{{ slide.subtitle || 'No subtitle' }}</p>
              <p v-if="slide.button_text" class="text-[11px] text-brand font-medium mt-0.5">
                CTA: {{ slide.button_text }} → {{ slide.button_url }}
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3 self-end lg:self-center shrink-0">
            <!-- Quick Inline Black Layer Opacity Slider -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface border border-line">
              <span class="text-[11px] font-semibold text-ink-muted whitespace-nowrap">
                Black Layer:
                <strong class="text-ink font-mono">{{ Math.round((slide.overlay_opacity ?? 0.55) * 100) }}%</strong>
              </span>
              <input
                :value="slide.overlay_opacity ?? 0.55"
                type="range"
                min="0"
                max="0.95"
                step="0.05"
                class="w-24 accent-brand cursor-pointer"
                @input="(e) => (slide.overlay_opacity = Number(e.target.value))"
                @change="(e) => updateSlideOpacity(slide, e.target.value)"
              />
              <div class="hidden sm:flex items-center gap-1">
                <button
                  v-for="p in [0.35, 0.55, 0.75]"
                  :key="p"
                  type="button"
                  @click="updateSlideOpacity(slide, p)"
                  class="px-1.5 py-0.5 rounded text-[10px] font-semibold border transition"
                  :class="
                    Math.abs((slide.overlay_opacity ?? 0.55) - p) < 0.03
                      ? 'bg-brand text-white border-brand'
                      : 'bg-page text-ink-muted border-line hover:text-ink'
                  "
                >
                  {{ Math.round(p * 100) }}%
                </button>
              </div>
            </div>

            <ToggleSwitch
              :model-value="Boolean(slide.is_active)"
              @update:model-value="(val) => toggleHeroSlideActive(slide, val)"
            />
            <button
              type="button"
              @click="openEditHero(slide)"
              class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:border-brand hover:text-brand"
            >
              <Edit2 class="w-3.5 h-3.5" />
              <span>Edit</span>
            </button>
            <button
              type="button"
              @click="slideToDelete = slide"
              class="p-1.5 rounded-pill border border-line bg-page text-ink-muted hover:text-error hover:border-red-200"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==========================================
         TAB 2: PROMOTIONAL POPUPS & ANALYTICS
         ========================================== -->
    <div v-if="activeTab === 'popups' && canManagePopups" class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-ink">Promotional & Newsletter Popups</h2>
          <p class="text-xs text-ink-muted">
            Configure trigger timing, page targeting, frequency rules, and track views, clicks, and newsletter signups.
          </p>
        </div>
        <button
          type="button"
          @click="openCreatePopup"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
        >
          <Plus class="w-4 h-4" />
          <span>Create Popup</span>
        </button>
      </div>

      <!-- Popup Create / Edit Form -->
      <div v-if="showPopupEditor" class="bg-page border border-line rounded-card p-6 shadow-soft space-y-5">
        <div class="flex items-center justify-between border-b border-line pb-4">
          <div>
            <h3 class="text-sm font-bold text-ink">
              {{ editingPopupId ? 'Edit Promotional Popup' : 'Create Promotional Popup' }}
            </h3>
            <p class="text-xs text-ink-muted">Click "Preview Popup Live" at any time to test the modal experience.</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="openPopupLivePreview(popupForm)"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-pill border border-brand text-brand text-xs font-semibold hover:bg-brand-soft"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Preview Popup Live</span>
            </button>
            <button
              type="button"
              @click="showPopupEditor = false"
              class="text-xs text-ink-muted hover:text-ink px-2"
            >
              Cancel
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-ink mb-1">Internal Campaign Name *</label>
            <input
              v-model="popupForm.name"
              type="text"
              placeholder="e.g. Spring 10% Welcome Voucher"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-ink mb-1">Popup Type *</label>
            <select
              v-model="popupForm.type"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            >
              <option value="newsletter">Newsletter Signup</option>
              <option value="content">Content + CTA Button</option>
              <option value="announcement">Announcement Notice</option>
              <option value="image">Image Only</option>
            </select>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="text-xs font-semibold text-ink">Side / Banner Image</label>
              <label class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand cursor-pointer hover:underline">
                <Upload class="w-3 h-3" />
                <span>{{ isUploadingPopupImage ? 'Uploading...' : 'Upload' }}</span>
                <input type="file" accept="image/*" class="hidden" @change="handlePopupImageUpload" />
              </label>
            </div>
            <input
              v-model="popupForm.image"
              type="text"
              placeholder="https://... or upload image"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-ink mb-1">Headline Title</label>
            <input
              v-model="popupForm.title"
              type="text"
              placeholder="e.g. Enjoy 10% Off Your First Order"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-ink mb-1">Button Text</label>
            <input
              v-model="popupForm.button_text"
              type="text"
              placeholder="Claim Voucher"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-ink mb-1">Body Copy</label>
            <textarea
              v-model="popupForm.body"
              rows="2"
              placeholder="Short invitation message..."
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            ></textarea>
          </div>

          <div v-if="popupForm.type !== 'newsletter'">
            <label class="block text-xs font-semibold text-ink mb-1">Button Target URL</label>
            <input
              v-model="popupForm.button_url"
              type="text"
              placeholder="/shop"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <!-- Trigger & Value -->
          <div>
            <label class="block text-xs font-semibold text-ink mb-1">Trigger Condition</label>
            <select
              v-model="popupForm.trigger"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            >
              <option value="on_load">Immediately on Load</option>
              <option value="delay">After X Seconds Delay</option>
              <option value="scroll">After Scrolling X% of Page</option>
              <option value="exit_intent">Exit Intent (Mouse Leaves Top)</option>
            </select>
          </div>

          <div v-if="popupForm.trigger === 'delay' || popupForm.trigger === 'scroll'">
            <label class="block text-xs font-semibold text-ink mb-1">
              {{ popupForm.trigger === 'delay' ? 'Delay (Seconds)' : 'Scroll Depth (%)' }}
            </label>
            <input
              v-model.number="popupForm.trigger_value"
              type="number"
              min="1"
              max="100"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <!-- Frequency -->
          <div>
            <label class="block text-xs font-semibold text-ink mb-1">Display Frequency</label>
            <select
              v-model="popupForm.frequency"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            >
              <option value="once_per_session">Once per Session</option>
              <option value="once_per_day">Once per Day</option>
              <option value="once_ever">Once Ever</option>
              <option value="always">Always Show</option>
            </select>
          </div>

          <!-- Pages to show on -->
          <div>
            <label class="block text-xs font-semibold text-ink mb-1">Pages to Show On</label>
            <select
              v-model="popupForm.pages_mode"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            >
              <option value="all">All Storefront Pages</option>
              <option value="home">Homepage Only (/)</option>
              <option value="custom">Specific Page Paths</option>
            </select>
          </div>

          <div v-if="popupForm.pages_mode === 'custom'">
            <label class="block text-xs font-semibold text-ink mb-1">Page Paths (comma-separated)</label>
            <input
              v-model="popupForm.custom_pages"
              type="text"
              placeholder="/shop, /category/women"
              class="w-full px-3.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-ink mb-1">Start Date (Optional)</label>
            <input
              v-model="popupForm.starts_at"
              type="datetime-local"
              class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-ink mb-1">End Date (Optional)</label>
            <input
              v-model="popupForm.ends_at"
              type="datetime-local"
              class="w-full px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink"
            />
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-line">
          <div class="flex items-center gap-2">
            <ToggleSwitch v-model="popupForm.is_active" />
            <span class="text-xs font-semibold text-ink">Active Campaign</span>
          </div>

          <button
            type="button"
            @click="savePopup"
            :disabled="isSavingPopup"
            class="px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition disabled:opacity-50"
          >
            {{ isSavingPopup ? 'Saving Popup...' : 'Save Popup Campaign' }}
          </button>
        </div>
      </div>

      <!-- Popups Table with Views / Clicks / Signups -->
      <div class="bg-page border border-line rounded-card divide-y divide-line shadow-soft">
        <div v-if="isLoadingPopups" class="p-8 text-center text-xs text-ink-muted">Loading popups...</div>
        <div v-else-if="!popups.length" class="p-8 text-center text-xs text-ink-muted">
          No popups configured yet.
        </div>

        <div
          v-for="popup in popups"
          :key="popup.id"
          class="p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-surface/50 transition"
        >
          <div class="flex items-center gap-4 min-w-0">
            <img
              v-if="popup.image"
              :src="popup.image"
              :alt="popup.name"
              class="w-16 h-16 rounded-xl object-cover border border-line bg-surface shrink-0"
            />
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-bold text-ink">{{ popup.name }}</h3>
                <span class="px-2 py-0.5 rounded-pill text-[10px] font-semibold uppercase bg-brand-soft text-brand">
                  {{ popup.type }}
                </span>
                <span class="px-2 py-0.5 rounded-pill text-[10px] font-medium bg-surface border border-line text-ink-muted">
                  Trigger: {{ popup.trigger }} ({{ popup.trigger_value }})
                </span>
                <span class="px-2 py-0.5 rounded-pill text-[10px] font-medium bg-surface border border-line text-ink-muted">
                  {{ (popup.frequency || '').replaceAll('_', ' ') }}
                </span>
              </div>
              <p class="text-xs text-ink-muted truncate mt-1">{{ popup.title }} — {{ popup.body }}</p>
            </div>
          </div>

          <!-- Analytics Counters + Actions -->
          <div class="flex flex-wrap items-center gap-4 self-end lg:self-center shrink-0">
            <div class="flex items-center gap-3 bg-surface border border-line rounded-xl px-3 py-1.5 text-xs">
              <div class="flex items-center gap-1 text-ink-muted" title="Popup Views">
                <BarChart3 class="w-3.5 h-3.5 text-brand" />
                <span class="font-bold text-ink">{{ popup.views_count || 0 }}</span>
                <span class="text-[10px]">views</span>
              </div>
              <div class="flex items-center gap-1 text-ink-muted border-l border-line pl-3" title="CTA Clicks">
                <MousePointerClick class="w-3.5 h-3.5 text-indigo-600" />
                <span class="font-bold text-ink">{{ popup.clicks_count || 0 }}</span>
                <span class="text-[10px]">clicks</span>
              </div>
              <div class="flex items-center gap-1 text-ink-muted border-l border-line pl-3" title="Newsletter Signups">
                <Users class="w-3.5 h-3.5 text-success" />
                <span class="font-bold text-ink">{{ popup.signups_count || 0 }}</span>
                <span class="text-[10px]">signups</span>
              </div>
            </div>

            <ToggleSwitch
              :model-value="Boolean(popup.is_active)"
              @update:model-value="(val) => togglePopupActive(popup, val)"
            />

            <button
              type="button"
              @click="openPopupLivePreview(popup)"
              class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:border-brand hover:text-brand"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Preview</span>
            </button>

            <button
              type="button"
              @click="openEditPopup(popup)"
              class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:border-brand hover:text-brand"
            >
              <Edit2 class="w-3.5 h-3.5" />
              <span>Edit</span>
            </button>

            <button
              type="button"
              @click="popupToDelete = popup"
              class="p-1.5 rounded-pill border border-line bg-page text-ink-muted hover:text-error hover:border-red-200"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==========================================
         TAB 3: HOME BANNERS & TRUST POINTS
         ========================================== -->
    <div v-if="activeTab === 'banners' && canManageBanners" class="space-y-6">
      <!-- Promo Banners Card -->
      <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-5">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-ink">Home Promotional Banners</h2>
            <p class="text-xs text-ink-muted">Two-column editorial promo cards displayed on the storefront homepage.</p>
          </div>
          <button
            type="button"
            @click="addPromoBanner"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-pill border border-line bg-surface text-xs font-semibold text-ink hover:border-brand"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Banner</span>
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
          <div
            v-for="(banner, idx) in promoBanners"
            :key="idx"
            class="bg-surface border border-line rounded-card p-4 space-y-3"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-ink">Banner #{{ idx + 1 }}</span>
              <button
                type="button"
                @click="removePromoBanner(idx)"
                class="text-xs text-error hover:underline inline-flex items-center gap-1"
              >
                <Trash2 class="w-3 h-3" />
                <span>Remove</span>
              </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-semibold text-ink-muted mb-1">Eyebrow Tag</label>
                <input
                  v-model="banner.tag"
                  type="text"
                  class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
                />
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-ink-muted mb-1">Headline Title *</label>
                <input
                  v-model="banner.title"
                  type="text"
                  class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
                />
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-ink-muted mb-1">Description</label>
              <input
                v-model="banner.description"
                type="text"
                class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-semibold text-ink-muted mb-1">Button Text</label>
                <input
                  v-model="banner.button_text"
                  type="text"
                  class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
                />
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-ink-muted mb-1">Button Link</label>
                <input
                  v-model="banner.button_url"
                  type="text"
                  class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
                />
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-[11px] font-semibold text-ink-muted">Banner Image URL *</label>
                <label class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand cursor-pointer hover:underline">
                  <Upload class="w-3 h-3" />
                  <span>Upload Image</span>
                  <input type="file" accept="image/*" class="hidden" @change="(e) => handleBannerImageUpload(e, idx)" />
                </label>
              </div>
              <input
                v-model="banner.image"
                type="text"
                class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 4 Trust Points Card -->
      <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-5">
        <div>
          <h2 class="text-base font-bold text-ink">Home Page Trust Points ("Why Shop With Us")</h2>
          <p class="text-xs text-ink-muted">Four value propositions displayed above the storefront footer.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="(point, idx) in trustPoints"
            :key="idx"
            class="bg-surface border border-line rounded-card p-4 space-y-3"
          >
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-page border border-line flex items-center justify-center text-brand">
                <component :is="getTrustIconComponent(point.icon)" class="w-4 h-4" />
              </div>
              <div class="flex-1">
                <label class="block text-[10px] font-semibold uppercase text-ink-muted">Icon Style</label>
                <select
                  v-model="point.icon"
                  class="w-full px-2.5 py-1 text-xs bg-page border border-line rounded-lg text-ink mt-0.5"
                >
                  <option value="truck">Truck (Delivery)</option>
                  <option value="rotate-ccw">Returns Arrow</option>
                  <option value="shield-check">Shield Check (Provenance)</option>
                  <option value="headphones">Headphones (Support)</option>
                  <option value="sparkles">Sparkles (Quality)</option>
                  <option value="heart">Heart (Sustainability)</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-ink-muted mb-1">Heading *</label>
              <input
                v-model="point.title"
                type="text"
                class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
              />
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-ink-muted mb-1">Description *</label>
              <input
                v-model="point.desc"
                type="text"
                class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
              />
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-ink-muted mb-1">Optional Link URL</label>
              <input
                v-model="point.link"
                type="text"
                placeholder="/page/shipping-returns"
                class="w-full px-3 py-1.5 text-xs bg-page border border-line rounded-lg text-ink"
              />
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button
            type="button"
            @click="saveHomeBlocks"
            :disabled="isSavingBlocks"
            class="px-6 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition disabled:opacity-50"
          >
            {{ isSavingBlocks ? 'Saving Changes...' : 'Save Banners & Trust Points' }}
          </button>
        </div>
      </div>
    </div>

    <!-- LIVE POPUP PREVIEW MODAL -->
    <div
      v-if="livePreviewPopupData"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="livePreviewPopupData = null"
    >
      <div class="relative w-full max-w-lg bg-white rounded-card shadow-2xl border border-line overflow-hidden flex flex-col md:flex-row">
        <button
          type="button"
          @click="livePreviewPopupData = null"
          class="absolute top-3 right-3 z-20 p-1.5 rounded-full bg-white/80 hover:bg-white text-ink-muted hover:text-ink shadow-sm"
        >
          <X class="w-4 h-4" />
        </button>

        <!-- Image Only Popup -->
        <div v-if="livePreviewPopupData.type === 'image'" class="w-full">
          <img
            :src="livePreviewPopupData.image"
            :alt="livePreviewPopupData.title || livePreviewPopupData.name"
            class="w-full max-h-[420px] object-cover"
          />
        </div>

        <template v-else>
          <div
            v-if="livePreviewPopupData.image"
            class="w-full md:w-5/12 aspect-[4/3] md:aspect-auto relative bg-surface overflow-hidden shrink-0"
          >
            <img
              :src="livePreviewPopupData.image"
              :alt="livePreviewPopupData.title"
              class="w-full h-full object-cover"
            />
          </div>

          <div class="p-6 md:p-8 flex-1 flex flex-col justify-between">
            <div>
              <span class="inline-block text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-pill bg-brand/10 text-brand mb-2">
                {{ livePreviewPopupData.type === 'newsletter' ? 'Exclusive Invitation' : 'Live Preview' }}
              </span>
              <h3 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
                {{ livePreviewPopupData.title }}
              </h3>
              <p class="text-xs text-ink-muted leading-relaxed mt-2.5">
                {{ livePreviewPopupData.body }}
              </p>
            </div>

            <div v-if="livePreviewPopupData.type === 'newsletter'" class="mt-6 space-y-2">
              <input
                type="email"
                placeholder="Enter your email address"
                disabled
                class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-xl text-xs"
              />
              <button
                type="button"
                @click="livePreviewPopupData = null"
                class="w-full py-2.5 px-4 rounded-pill bg-brand text-white text-xs font-semibold flex items-center justify-center gap-2"
              >
                <span>{{ livePreviewPopupData.button_text || 'Claim Your Voucher' }}</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>

            <div v-else-if="livePreviewPopupData.button_text" class="mt-6">
              <button
                type="button"
                @click="livePreviewPopupData = null"
                class="w-full py-2.5 px-4 rounded-pill bg-brand text-white text-xs font-semibold flex items-center justify-center gap-2"
              >
                <span>{{ livePreviewPopupData.button_text }}</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Confirm Delete Dialogs -->
    <ConfirmDialog
      :open="Boolean(slideToDelete)"
      title="Delete Hero Slide"
      :message="`Are you sure you want to delete '${slideToDelete?.title}'?`"
      confirm-label="Delete Slide"
      @cancel="slideToDelete = null"
      @confirm="confirmDeleteHero"
    />

    <ConfirmDialog
      :open="Boolean(popupToDelete)"
      title="Delete Popup Campaign"
      :message="`Are you sure you want to delete '${popupToDelete?.name}'?`"
      confirm-label="Delete Popup"
      @cancel="popupToDelete = null"
      @confirm="confirmDeletePopup"
    />
  </div>
</template>
