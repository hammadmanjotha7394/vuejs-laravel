import { defineStore } from 'pinia'
import { useToastStore } from './toast'

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

    toggle(product) {
      const toast = useToastStore()
      const index = this.items.findIndex((item) => item.id === product.id)

      if (index !== -1) {
        this.items.splice(index, 1)
        this.persist()
        toast.info(`Removed "${product.name}" from your wishlist`)
      } else {
        this.items.push({
          id: product.id,
          name: product.name,
          slug: product.slug,
          price: product.price,
          compare_price: product.compare_price,
          image: product.primary_image_url || product.image_url || null,
        })
        this.persist()
        toast.success(`Saved "${product.name}" to your wishlist`)
      }
    },

    remove(productId) {
      const index = this.items.findIndex((item) => item.id === productId)
      if (index !== -1) {
        this.items.splice(index, 1)
        this.persist()
      }
    },

    clear() {
      this.items = []
      this.persist()
    },
  },
})
