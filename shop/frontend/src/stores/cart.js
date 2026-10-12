import { defineStore } from 'pinia'
import axios from 'axios'
import { useToastStore } from './toast'

const TOKEN_KEY = 'boutique_cart_token_v1'

export const useCartStore = defineStore('cart', {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY) || null,
    items: [],
    itemCount: 0,
    subtotal: 0.0,
    discount: 0.0,
    coupon: null,
    shipping: {
      free_threshold: 75.0,
      is_free: false,
      amount_needed: 75.0,
      progress_percentage: 0,
      rates: [],
    },
    grandTotal: 0.0,
    notices: [],
    isOpen: false,
    isLoading: false,
    isUpdating: false,
  }),

  getters: {
    hasItems: (state) => state.items.length > 0,
  },

  actions: {
    // Set axios header helper
    applyHeaders() {
      if (this.token) {
        axios.defaults.headers.common['X-Cart-Token'] = this.token
      } else {
        delete axios.defaults.headers.common['X-Cart-Token']
      }
    },

    saveToken(token) {
      if (!token) return
      this.token = token
      localStorage.setItem(TOKEN_KEY, token)
      this.applyHeaders()
    },

    // Sync state from server response payload
    syncFromServer(data) {
      if (!data) return
      if (data.token) {
        this.saveToken(data.token)
      }
      this.items = data.items || []
      this.itemCount = Number(data.item_count || 0)
      this.subtotal = Number(data.subtotal || 0)
      this.discount = Number(data.discount || 0)
      this.coupon = data.coupon || null
      this.shipping = data.shipping || {
        free_threshold: 75.0,
        is_free: false,
        amount_needed: 75.0,
        progress_percentage: 0,
        rates: [],
      }
      this.grandTotal = Number(data.grand_total || 0)
      this.notices = data.notices || []

      // If server returned notices (e.g. price change or stock reduction), inform user
      if (this.notices.length > 0) {
        const toast = useToastStore()
        this.notices.forEach((msg) => toast.info(msg))
      }
    },

    // Read cart from server
    async fetchCart() {
      this.applyHeaders()
      this.isLoading = true
      try {
        const res = await axios.get('/api/cart')
        this.syncFromServer(res.data.data)
      } catch (err) {
        console.warn('Could not fetch cart:', err)
      } finally {
        this.isLoading = false
      }
    },

    // Add item to cart (Server is source of truth for prices & stock; client NEVER sends prices)
    async addItem(product, variant = null, quantity = 1) {
      this.applyHeaders()
      this.isUpdating = true

      const productId = typeof product === 'object' ? product.id : product
      const variantId = variant && typeof variant === 'object' ? variant.id : variant

      try {
        const res = await axios.post('/api/cart/items', {
          product_id: productId,
          variant_id: variantId || null,
          quantity: Math.max(1, Number(quantity) || 1),
        })

        this.syncFromServer(res.data.data)
        const toast = useToastStore()
        const title = typeof product === 'object' ? product.name : 'Item'
        toast.success(`Added ${quantity} × "${title}" to your bag`)
        return true
      } catch (err) {
        const toast = useToastStore()
        const msg = err.response?.data?.message || err.response?.data?.errors?.quantity?.[0] || 'Could not add item to bag.'
        toast.error(msg)
        return false
      } finally {
        this.isUpdating = false
      }
    },

    // Update item quantity
    async updateQuantity(itemId, quantity) {
      this.applyHeaders()
      this.isUpdating = true
      try {
        const res = await axios.put(`/api/cart/items/${itemId}`, {
          quantity: Math.max(0, Number(quantity)),
        })
        this.syncFromServer(res.data.data)
      } catch (err) {
        const toast = useToastStore()
        const msg = err.response?.data?.message || 'Could not update quantity.'
        toast.error(msg)
      } finally {
        this.isUpdating = false
      }
    },

    // Remove item from cart
    async removeItem(itemId) {
      this.applyHeaders()
      this.isUpdating = true
      try {
        const res = await axios.delete(`/api/cart/items/${itemId}`)
        this.syncFromServer(res.data.data)
        const toast = useToastStore()
        toast.info('Item removed from your bag.')
      } catch (err) {
        const toast = useToastStore()
        toast.error('Could not remove item.')
      } finally {
        this.isUpdating = false
      }
    },

    // Clear entire cart
    async clearCart() {
      this.applyHeaders()
      this.isUpdating = true
      try {
        const res = await axios.delete('/api/cart')
        this.syncFromServer(res.data.data)
        const toast = useToastStore()
        toast.info('Bag cleared.')
      } catch (err) {
        const toast = useToastStore()
        toast.error('Could not clear bag.')
      } finally {
        this.isUpdating = false
      }
    },

    // Apply promo coupon code
    async applyCoupon(code) {
      if (!code || !code.trim()) return false

      this.applyHeaders()
      this.isUpdating = true
      try {
        const res = await axios.post('/api/cart/coupon', {
          code: code.trim().toUpperCase(),
        })
        this.syncFromServer(res.data.data)
        const toast = useToastStore()
        toast.success(`Coupon "${code.trim().toUpperCase()}" applied successfully!`)
        return true
      } catch (err) {
        const toast = useToastStore()
        const msg = err.response?.data?.errors?.coupon?.[0] || err.response?.data?.message || 'Invalid coupon code.'
        toast.error(msg)
        return false
      } finally {
        this.isUpdating = false
      }
    },

    // Remove active coupon
    async removeCoupon() {
      this.applyHeaders()
      this.isUpdating = true
      try {
        const res = await axios.delete('/api/cart/coupon')
        this.syncFromServer(res.data.data)
        const toast = useToastStore()
        toast.info('Coupon removed.')
      } catch (err) {
        const toast = useToastStore()
        toast.error('Could not remove coupon.')
      } finally {
        this.isUpdating = false
      }
    },

    // Merge guest cart on login
    async mergeGuestCart() {
      const guestToken = localStorage.getItem(TOKEN_KEY)
      if (!guestToken) {
        return this.fetchCart()
      }

      this.isLoading = true
      try {
        const res = await axios.post('/api/cart/merge', {
          guest_token: guestToken,
        })
        this.syncFromServer(res.data.data)
      } catch (err) {
        // If merge fails (e.g. token expired), just fetch user's cart
        await this.fetchCart()
      } finally {
        this.isLoading = false
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
  },
})
