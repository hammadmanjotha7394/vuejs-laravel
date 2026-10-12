import { defineStore } from 'pinia'

const STORAGE_KEY = 'boutique_cart_v1'

export const useCartStore = defineStore('cart', {
  state: () => {
    let savedItems = []
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (raw) savedItems = JSON.parse(raw)
    } catch (e) {
      console.warn('Failed to parse cart from storage:', e)
    }

    return {
      items: Array.isArray(savedItems) ? savedItems : [],
      isOpen: false,
    }
  },

  getters: {
    itemCount: (state) => state.items.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0),
    subtotal: (state) =>
      state.items.reduce(
        (sum, item) => sum + (Number(item.price) || 0) * (Number(item.quantity) || 1),
        0
      ),
  },

  actions: {
    persist() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items))
      } catch (e) {
        console.warn('Failed to save cart to localStorage:', e)
      }
    },

    openDrawer() {
      this.isOpen = true
    },

    closeDrawer() {
      this.isOpen = false
    },

    toggleDrawer() {
      this.isOpen = !this.isOpen
    },

    addItem(product, variant = null, quantity = 1) {
      const key = variant ? `${product.id}-${variant.id}` : `${product.id}`
      const existing = this.items.find((i) => i.key === key)

      if (existing) {
        existing.quantity += quantity
      } else {
        this.items.push({
          key,
          productId: product.id,
          variantId: variant?.id || null,
          title: product.name,
          variantTitle: variant?.title || null,
          slug: product.slug,
          price: Number(variant?.price ?? product.price ?? 0),
          image: variant?.image_url || product.primary_image_url || product.image_url || null,
          quantity: quantity,
        })
      }

      this.persist()
    },

    updateQuantity(key, quantity) {
      const item = this.items.find((i) => i.key === key)
      if (!item) return

      if (quantity <= 0) {
        this.removeItem(key)
      } else {
        item.quantity = quantity
        this.persist()
      }
    },

    removeItem(key) {
      const index = this.items.findIndex((i) => i.key === key)
      if (index !== -1) {
        this.items.splice(index, 1)
        this.persist()
      }
    },

    clearCart() {
      this.items = []
      this.persist()
    },
  },
})
