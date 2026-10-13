import { defineStore } from 'pinia'
import axios from 'axios'

export const useMenuStore = defineStore('menu', {
  state: () => ({
    mainMenu: null,
    footerMenu: null,
    categories: [],
    loading: false,
    loaded: false,
  }),

  actions: {
    async fetchMenus() {
      if (this.loaded && this.mainMenu && this.footerMenu) return

      this.loading = true
      try {
        const [mainRes, footerRes, catsRes] = await Promise.allSettled([
          axios.get('/api/menu/main'),
          axios.get('/api/menu/footer'),
          axios.get('/api/categories'),
        ])

        if (mainRes.status === 'fulfilled') {
          this.mainMenu = mainRes.value.data.data
        }
        if (footerRes.status === 'fulfilled') {
          this.footerMenu = footerRes.value.data.data
        }
        if (catsRes.status === 'fulfilled') {
          this.categories = catsRes.value.data.data
        }

        this.loaded = true
      } catch (err) {
        console.error('Failed to load navigation menus:', err)
      } finally {
        this.loading = false
      }
    },

    async reload() {
      this.loaded = false
      return this.fetchMenus()
    },

    async fetchMenu(slug, force = false) {
      if (force) {
        this.loaded = false
      }
      return this.fetchMenus()
    },
  },
})
