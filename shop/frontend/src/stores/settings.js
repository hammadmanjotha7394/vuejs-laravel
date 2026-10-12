import { defineStore } from 'pinia'
import axios from 'axios'

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    publicSettings: null,
    adminSettings: {},
    loading: false,
    adminLoading: false,
  }),

  getters: {
    brandColor: (state) => state.publicSettings?.general?.brand_color || '#2F5D50',
    siteName: (state) => state.publicSettings?.general?.site_name || 'Boutique',
    currencySymbol: (state) => state.publicSettings?.general?.currency_symbol || '$',
    currencyCode: (state) => state.publicSettings?.general?.currency_code || 'USD',
    announcement: (state) => state.publicSettings?.announcement || null,
  },

  actions: {
    async fetchPublicSettings() {
      this.loading = true
      try {
        const response = await axios.get('/api/public-settings')
        this.publicSettings = response.data.data

        // Dynamically apply brand color CSS variable
        if (this.publicSettings?.general?.brand_color) {
          document.documentElement.style.setProperty(
            '--brand',
            this.publicSettings.general.brand_color
          )
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
        document.documentElement.style.setProperty('--brand', payload.brand_color)
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
