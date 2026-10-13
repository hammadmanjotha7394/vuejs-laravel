import { defineStore } from 'pinia'
import apiClient, { getCsrfCookie, setAuthToken } from '@/api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    roles: [],
    permissions: [],
    isAuthenticated: false,
    isLoading: false,
    authChecked: false,
  }),

  getters: {
    isSuperAdmin: (state) => state.roles.includes('super_admin'),
    isStaff: (state) => state.roles.some((r) => r !== 'customer'),
    hasPermission: (state) => (perm) => {
      if (state.roles.includes('super_admin')) return true
      return state.permissions.includes(perm)
    },
    hasRole: (state) => (role) => state.roles.includes(role),
  },

  actions: {
    async fetchMe() {
      this.isLoading = true
      try {
        const response = await apiClient.get('/me')
        this.setUser(response.data.user)
      } catch (error) {
        this.clearUser()
      } finally {
        this.isLoading = false
        this.authChecked = true
      }
    },

    async login(credentials) {
      this.isLoading = true
      try {
        await getCsrfCookie()
        const response = await apiClient.post('/login', credentials)
        if (response.data.token) {
          setAuthToken(response.data.token)
        }
        this.setUser(response.data.user)

        // Merge guest cart & sync guest wishlist
        const { useCartStore } = await import('./cart')
        const { useWishlistStore } = await import('./wishlist')
        useCartStore().mergeGuestCart().catch(() => {})
        useWishlistStore().syncFromGuestStorage().catch(() => {})

        return response.data
      } finally {
        this.isLoading = false
        this.authChecked = true
      }
    },

    async register(userData) {
      this.isLoading = true
      try {
        await getCsrfCookie()
        const response = await apiClient.post('/register', userData)
        if (response.data.token) {
          setAuthToken(response.data.token)
        }
        this.setUser(response.data.user)

        // Merge guest cart & sync guest wishlist
        const { useCartStore } = await import('./cart')
        const { useWishlistStore } = await import('./wishlist')
        useCartStore().mergeGuestCart().catch(() => {})
        useWishlistStore().syncFromGuestStorage().catch(() => {})

        return response.data
      } finally {
        this.isLoading = false
        this.authChecked = true
      }
    },

    async logout() {
      this.isLoading = true
      try {
        await apiClient.post('/logout')
      } catch (e) {
        // Continue clearing local state even if server returns error
      } finally {
        this.clearUser()
        this.isLoading = false
      }
    },

    async updateProfile(profileData) {
      const response = await apiClient.put('/profile', profileData)
      this.setUser(response.data.user)
      return response.data
    },

    async changePassword(passwordData) {
      const response = await apiClient.put('/password', passwordData)
      return response.data
    },

    async deleteAccount(password) {
      const response = await apiClient.delete('/account', {
        data: { password },
      })
      this.clearUser()
      return response.data
    },

    setUser(userData) {
      if (!userData) {
        this.clearUser()
        return
      }
      this.user = userData
      this.roles = userData.roles || []
      this.permissions = userData.permissions || []
      this.isAuthenticated = true
    },

    clearUser() {
      setAuthToken(null)
      this.user = null
      this.roles = []
      this.permissions = []
      this.isAuthenticated = false
    },
  },
})
