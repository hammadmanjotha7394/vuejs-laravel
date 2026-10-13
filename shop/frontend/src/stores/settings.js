import { defineStore } from 'pinia'
import axios from 'axios'

function applyBrandTheme(hex) {
  if (!hex || typeof hex !== 'string') return
  const color = hex.trim()
  if (!color.startsWith('#')) {
    document.documentElement.style.setProperty('--brand', color)
    return
  }

  try {
    let clean = color.replace('#', '')
    if (clean.length === 3) {
      clean = clean.split('').map((c) => c + c).join('')
    }
    if (clean.length === 6) {
      const num = parseInt(clean, 16)
      const r = (num >> 16) & 255
      const g = (num >> 8) & 255
      const b = num & 255

      // Calculate hover (darken by ~12%)
      const darken = (c) => Math.max(0, Math.floor(c * 0.88))
      const hoverHex = `#${((1 << 24) + (darken(r) << 16) + (darken(g) << 8) + darken(b)).toString(16).slice(1)}`

      // Light tint with 8% opacity
      const lightTint = `rgba(${r}, ${g}, ${b}, 0.08)`

      document.documentElement.style.setProperty('--brand', color)
      document.documentElement.style.setProperty('--brand-hover', hoverHex)
      document.documentElement.style.setProperty('--brand-light', lightTint)
      return
    }
  } catch (e) {
    // fallback
  }
  document.documentElement.style.setProperty('--brand', color)
}

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    publicSettings: null,
    adminSettings: {},
    loading: false,
    adminLoading: false,
  }),

  getters: {
    general: (state) => state.publicSettings?.general || {},
    brandColor: (state) => state.publicSettings?.general?.brand_color || '#2F5D50',
    siteName: (state) => state.publicSettings?.general?.site_name || 'Boutique',
    siteTagline: (state) => state.publicSettings?.general?.site_tagline || 'Thoughtfully crafted essentials',
    logoUrl: (state) => state.publicSettings?.general?.logo_url || null,
    faviconUrl: (state) => state.publicSettings?.general?.favicon_url || null,
    currencySymbol: (state) => state.publicSettings?.general?.currency_symbol || '$',
    currencyCode: (state) => state.publicSettings?.general?.currency_code || 'USD',
    supportEmail: (state) => state.publicSettings?.general?.support_email || '',
    supportPhone: (state) => state.publicSettings?.general?.support_phone || '',
    storeAddress: (state) => state.publicSettings?.general?.store_address || '',
    announcement: (state) => state.publicSettings?.announcement || null,
    socialLinks: (state) => state.publicSettings?.social || state.publicSettings?.social_links || {},
    headerFooter: (state) => {
      const f = state.publicSettings?.footer || {}
      const hf = state.publicSettings?.header_footer || {}
      return {
        copyright_text: f.copyright || hf.copyright_text || '',
        footer_text: f.tagline || hf.footer_text || state.publicSettings?.general?.site_tagline || '',
        payment_icons: f.payment_icons_enabled !== undefined ? f.payment_icons_enabled : (hf.payment_icons !== false),
        columns: f.columns || hf.columns || [],
      }
    },
    freeShippingThreshold: (state) => Number(state.publicSettings?.shipping?.free_shipping_threshold ?? 75.0),
    defaultShippingFee: (state) => Number(state.publicSettings?.shipping?.default_shipping_fee ?? 8.0),
    maintenanceMode: (state) => Boolean(state.publicSettings?.store?.maintenance_mode),
    maintenanceMessage: (state) =>
      state.publicSettings?.store?.maintenance_message ||
      'We are currently performing scheduled maintenance. Please check back shortly.',
    formatPrice: (state) => (val) => {
      const num = Number(val) || 0
      const sym = state.publicSettings?.general?.currency_symbol || '$'
      return `${sym}${num.toFixed(2)}`
    },
  },

  actions: {
    async fetchPublicSettings() {
      this.loading = true
      try {
        const response = await axios.get('/api/public-settings')
        this.publicSettings = response.data.data

        // Dynamically apply brand color and shades
        if (this.publicSettings?.general?.brand_color) {
          applyBrandTheme(this.publicSettings.general.brand_color)
        }

        // Dynamically update favicon if available
        if (this.publicSettings?.general?.favicon_url) {
          let link = document.querySelector("link[rel*='icon']")
          if (!link) {
            link = document.createElement('link')
            link.rel = 'shortcut icon'
            document.getElementsByTagName('head')[0].appendChild(link)
          }
          link.href = this.publicSettings.general.favicon_url
        }

        return this.publicSettings
      } catch (error) {
        console.error('Failed to load public settings:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchAdminSettings() {
      this.adminLoading = true
      try {
        const response = await axios.get('/api/admin/settings')
        this.adminSettings = response.data.data
        return this.adminSettings
      } finally {
        this.adminLoading = false
      }
    },

    async updateSettings(payload) {
      const response = await axios.put('/api/admin/settings', payload)
      this.adminSettings = response.data.data

      if (payload.brand_color) {
        applyBrandTheme(payload.brand_color)
      }

      await this.fetchPublicSettings()
      return response.data
    },

    async updateStripe(payload) {
      const response = await axios.put('/api/admin/settings/stripe', payload)
      this.adminSettings = response.data.data
      await this.fetchPublicSettings()
      return response.data
    },

    async testStripe() {
      const response = await axios.post('/api/admin/settings/stripe/test')
      return response.data
    },

    async updateMail(payload) {
      const response = await axios.put('/api/admin/settings/mail', payload)
      this.adminSettings = response.data.data
      return response.data
    },

    async testMail(email = null) {
      const response = await axios.post('/api/admin/settings/mail/test', { email })
      return response.data
    },

    async uploadAsset(file, type = 'asset') {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('type', type)

      const response = await axios.post('/api/admin/settings/upload-asset', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      return response.data
    },
  },
})
