import { defineStore } from 'pinia'
import axios from 'axios'
import { useToastStore } from './toast'
import { useAuthStore } from './auth'

const STORAGE_KEY = 'boutique_wishlist_v1'

export const useWishlistStore = defineStore('wishlist', {
  state: () => {
    let saved = []
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (raw) saved = JSON.parse(raw)
    } catch (e) {
      console.warn('Failed to parse wishlist from storage:', e)
    }

    return {
      items: Array.isArray(saved) ? saved : [],
      isLoading: false,
    }
  },

  getters: {
    count: (state) => state.items.length,
    hasItem: (state) => (productId) => state.items.some((item) => item.id === productId),
  },

  actions: {
    persist() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items))
      } catch (e) {
        console.warn('Failed to save wishlist to localStorage:', e)
      }
    },

    // Fetch authenticated user's wishlist from backend
    async fetchWishlist() {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return

      this.isLoading = true
      try {
        const res = await axios.get('/api/wishlist')
        this.items = res.data.data || []
      } catch (err) {
        console.warn('Could not fetch wishlist:', err)
      } finally {
        this.isLoading = false
      }
    },

    // Sync guest wishlist from localStorage into database after user logs in
    async syncFromGuestStorage() {
      const authStore = useAuthStore()
      if (!authStore.isAuthenticated) return

      let guestItems = []
      try {
        const raw = localStorage.getItem(STORAGE_KEY)
        if (raw) guestItems = JSON.parse(raw)
      } catch (e) {}

      if (guestItems.length > 0) {
        try {
          const ids = guestItems.map((item) => item.id)
          const res = await axios.post('/api/wishlist/sync', { product_ids: ids })
          this.items = res.data.data || []
          localStorage.removeItem(STORAGE_KEY)
        } catch (err) {
          console.warn('Failed to sync wishlist on login:', err)
          await this.fetchWishlist()
        }
      } else {
        await this.fetchWishlist()
      }
    },

    // Toggle wishlist item (supports both guest localStorage and authenticated backend)
    async toggle(product) {
      const toast = useToastStore()
      const authStore = useAuthStore()

      if (authStore.isAuthenticated) {
        try {
          const res = await axios.post('/api/wishlist/toggle', {
            product_id: product.id,
          })

          const isSaved = res.data.saved
          if (isSaved) {
            // Add if not already present
            if (!this.hasItem(product.id)) {
              this.items.unshift(product)
            }
            toast.success(`Saved "${product.name}" to your wishlist`)
          } else {
            // Remove
            this.items = this.items.filter((item) => item.id !== product.id)
            toast.info(`Removed "${product.name}" from your wishlist`)
          }
        } catch (err) {
          toast.error('Could not update wishlist.')
        }
      } else {
        // Guest mode via localStorage
        const index = this.items.findIndex((item) => item.id === product.id)

        if (index !== -1) {
          this.items.splice(index, 1)
          this.persist()
          toast.info(`Removed "${product.name}" from your wishlist`)
        } else {
          this.items.unshift({
            id: product.id,
            name: product.name,
            slug: product.slug,
            price: product.price ?? product.base_price,
            compare_at_price: product.compare_at_price,
            primary_image: product.primary_image || (product.images?.[0] ? product.images[0] : null),
            is_on_sale: product.is_on_sale || false,
            sale_percentage: product.sale_percentage || 0,
            rating: product.rating || 5.0,
            reviews_count: product.reviews_count || 0,
          })
          this.persist()
          toast.success(`Saved "${product.name}" to your wishlist`)
        }
      }
    },

    async remove(productId) {
      const authStore = useAuthStore()
      const toast = useToastStore()

      if (authStore.isAuthenticated) {
        try {
          await axios.delete(`/api/wishlist/${productId}`)
          this.items = this.items.filter((item) => item.id !== productId)
          toast.info('Item removed from your wishlist')
        } catch (err) {
          toast.error('Could not remove item.')
        }
      } else {
        this.items = this.items.filter((item) => item.id !== productId)
        this.persist()
        toast.info('Item removed from your wishlist')
      }
    },

    clear() {
      this.items = []
      this.persist()
    },
  },
})
