import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),

  actions: {
    /**
     * Add a toast notification.
     * @param {Object} options - { title, message, type: 'success'|'error'|'info'|'warning', duration: 4000 }
     */
    add({ title, message, type = 'success', duration = 4000 }) {
      const id = Date.now() + Math.random().toString(36).substring(2, 9)
      const toast = { id, title, message, type }

      this.toasts.push(toast)

      if (duration > 0) {
        setTimeout(() => {
          this.remove(id)
        }, duration)
      }

      return id
    },

    success(message, title = 'Success') {
      return this.add({ title, message, type: 'success' })
    },

    error(message, title = 'Error') {
      return this.add({ title, message, type: 'error', duration: 5000 })
    },

    info(message, title = 'Notice') {
      return this.add({ title, message, type: 'info' })
    },

    warning(message, title = 'Warning') {
      return this.add({ title, message, type: 'warning' })
    },

    remove(id) {
      const index = this.toasts.findIndex((t) => t.id === id)
      if (index !== -1) {
        this.toasts.splice(index, 1)
      }
    },

    clear() {
      this.toasts = []
    },
  },
})
