<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  Users,
  UserPlus,
  Search,
  CheckCircle2,
  XCircle,
  KeyRound,
  Trash2,
  AlertCircle,
  X,
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const users = ref([])
const rolesList = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedRole = ref('')
const feedbackMessage = ref('')
const errorMessage = ref('')

const showCreateModal = ref(false)
const showResetModal = ref(false)
const targetUser = ref(null)

const createForm = reactive({
  name: '',
  email: '',
  phone: '',
  role: 'customer',
  password: '',
  is_active: true,
})

const resetPasswordForm = reactive({
  new_password: '',
})

const fetchUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.get('/admin/users', {
      params: {
        search: searchQuery.value,
        role: selectedRole.value,
      },
    })
    users.value = res.data.data
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load users'
  } finally {
    isLoading.value = false
  }
}

const fetchRoles = async () => {
  try {
    const res = await apiClient.get('/admin/roles')
    rolesList.value = res.data.data
  } catch (e) {
    rolesList.value = [
      { name: 'super_admin' },
      { name: 'product_manager' },
      { name: 'order_manager' },
      { name: 'support_agent' },
      { name: 'finance_manager' },
      { name: 'customer' },
    ]
  }
}

const handleCreateUser = async () => {
  errorMessage.value = ''
  try {
    await apiClient.post('/admin/users', createForm)
    feedbackMessage.value = `Staff member ${createForm.name} created successfully.`
    showCreateModal.value = false
    createForm.name = ''
    createForm.email = ''
    createForm.phone = ''
    createForm.password = ''
    createForm.role = 'customer'
    await fetchUsers()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Error creating user.'
  }
}

const toggleUserStatus = async (user) => {
  errorMessage.value = ''
  try {
    const updatedStatus = !user.is_active
    await apiClient.put(`/admin/users/${user.id}`, {
      name: user.name,
      email: user.email,
      is_active: updatedStatus,
    })
    user.is_active = updatedStatus
    feedbackMessage.value = `User status updated.`
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update user status.'
  }
}

const openResetModal = (user) => {
  targetUser.value = user
  resetPasswordForm.new_password = ''
  showResetModal.value = true
}

const handleResetPassword = async () => {
  if (!targetUser.value || !resetPasswordForm.new_password) return
  errorMessage.value = ''
  try {
    await apiClient.post(`/admin/users/${targetUser.value.id}/reset-password`, {
      password: resetPasswordForm.new_password,
    })
    feedbackMessage.value = `Password reset for ${targetUser.value.name}.`
    showResetModal.value = false
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to reset password.'
  }
}

const handleDeleteUser = async (user) => {
  if (!confirm(`Are you sure you want to remove ${user.name}?`)) return
  errorMessage.value = ''
  try {
    await apiClient.delete(`/admin/users/${user.id}`)
    feedbackMessage.value = `User removed successfully.`
    await fetchUsers()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete user.'
  }
}

onMounted(async () => {
  await fetchUsers()
  await fetchRoles()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-ink">Staff & User Management</h1>
        <p class="text-xs text-ink-muted mt-1">Manage staff roles, customer profiles, and account authorizations</p>
      </div>

      <button
        v-if="authStore.hasPermission('users.create')"
        @click="showCreateModal = true"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-medium hover:opacity-90 transition shadow-sm"
      >
        <UserPlus class="w-3.5 h-3.5" />
        <span>Add Staff Member</span>
      </button>
    </div>

    <!-- Feedback Toasts -->
    <div v-if="feedbackMessage" class="p-3 bg-emerald-50 border border-emerald-200 rounded-card text-success text-xs flex items-center justify-between">
      <span>{{ feedbackMessage }}</span>
      <button @click="feedbackMessage = ''"><X class="w-4 h-4" /></button>
    </div>
    <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-card text-error text-xs flex items-center justify-between">
      <span>{{ errorMessage }}</span>
      <button @click="errorMessage = ''"><X class="w-4 h-4" /></button>
    </div>

    <!-- Filters -->
    <div class="bg-page border border-line rounded-card p-4 shadow-soft flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <Search class="w-4 h-4 absolute left-3 top-2.5 text-ink-muted" />
        <input
          v-model="searchQuery"
          @keyup.enter="fetchUsers"
          type="text"
          placeholder="Search name, email, or phone..."
          class="w-full pl-9 pr-3 py-1.5 text-xs bg-surface border border-line rounded-input focus:outline-none focus:border-brand"
        />
      </div>

      <select
        v-model="selectedRole"
        @change="fetchUsers"
        class="text-xs bg-surface border border-line rounded-input px-3 py-1.5 focus:outline-none focus:border-brand"
      >
        <option value="">All Roles</option>
        <option v-for="r in rolesList" :key="r.name" :value="r.name">{{ r.name }}</option>
      </select>

      <button
        @click="fetchUsers"
        class="px-4 py-1.5 rounded-pill bg-surface border border-line text-xs font-medium hover:bg-page transition"
      >
        Apply Filter
      </button>
    </div>

    <!-- Users Table -->
    <div class="bg-page border border-line rounded-card shadow-soft overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="border-b border-line text-ink-muted uppercase tracking-wider bg-surface/50">
            <tr>
              <th class="py-3 px-4">User</th>
              <th class="py-3 px-4">Role</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Last Login</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <tr v-for="u in users" :key="u.id" class="hover:bg-surface/50 transition">
              <td class="py-3 px-4">
                <div class="font-semibold text-ink">{{ u.name }}</div>
                <div class="text-[11px] text-ink-muted">{{ u.email }}</div>
              </td>
              <td class="py-3 px-4">
                <span
                  v-for="r in u.roles"
                  :key="r.name"
                  class="inline-block px-2.5 py-0.5 rounded-pill text-[11px] font-medium bg-brand/10 text-brand capitalize"
                >
                  {{ r.name.replace('_', ' ') }}
                </span>
              </td>
              <td class="py-3 px-4">
                <button
                  @click="toggleUserStatus(u)"
                  :disabled="!authStore.hasPermission('users.update')"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-pill text-[11px] font-semibold transition"
                  :class="u.is_active ? 'bg-emerald-50 text-success border border-emerald-200' : 'bg-red-50 text-error border border-red-200'"
                >
                  <CheckCircle2 v-if="u.is_active" class="w-3 h-3" />
                  <XCircle v-else class="w-3 h-3" />
                  <span>{{ u.is_active ? 'Active' : 'Disabled' }}</span>
                </button>
              </td>
              <td class="py-3 px-4 text-ink-muted">
                {{ u.last_login_at ? new Date(u.last_login_at).toLocaleDateString() : 'Never' }}
              </td>
              <td class="py-3 px-4 text-right space-x-2">
                <button
                  v-if="authStore.hasPermission('users.update')"
                  @click="openResetModal(u)"
                  class="p-1 text-ink-muted hover:text-ink rounded transition"
                  title="Reset password"
                >
                  <KeyRound class="w-3.5 h-3.5" />
                </button>
                <button
                  v-if="authStore.hasPermission('users.update')"
                  @click="handleDeleteUser(u)"
                  class="p-1 text-ink-muted hover:text-error rounded transition"
                  title="Remove user"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create User Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-page border border-line rounded-card p-6 shadow-soft max-w-md w-full">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-ink">Add New Staff Member</h2>
          <button @click="showCreateModal = false"><X class="w-4 h-4 text-ink-muted" /></button>
        </div>

        <form @submit.prevent="handleCreateUser" class="space-y-3 text-xs">
          <div>
            <label class="block text-ink-muted font-medium mb-1">Full Name</label>
            <input v-model="createForm.name" required class="w-full px-3 py-2 bg-surface border border-line rounded-input focus:outline-none focus:border-brand" />
          </div>
          <div>
            <label class="block text-ink-muted font-medium mb-1">Email</label>
            <input v-model="createForm.email" type="email" required class="w-full px-3 py-2 bg-surface border border-line rounded-input focus:outline-none focus:border-brand" />
          </div>
          <div>
            <label class="block text-ink-muted font-medium mb-1">Role Assignment</label>
            <select v-model="createForm.role" required class="w-full px-3 py-2 bg-surface border border-line rounded-input focus:outline-none focus:border-brand">
              <option v-for="r in rolesList" :key="r.name" :value="r.name">{{ r.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-ink-muted font-medium mb-1">Initial Password (min 8 chars)</label>
            <input v-model="createForm.password" type="password" required class="w-full px-3 py-2 bg-surface border border-line rounded-input focus:outline-none focus:border-brand" />
          </div>

          <div class="pt-4 flex justify-end gap-2">
            <button type="button" @click="showCreateModal = false" class="px-4 py-2 border border-line rounded-pill hover:bg-surface">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-brand text-white rounded-pill hover:opacity-90">Create Staff</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reset Password Modal -->
    <div v-if="showResetModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-page border border-line rounded-card p-6 shadow-soft max-w-sm w-full">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-semibold text-ink">Reset Password: {{ targetUser?.name }}</h2>
          <button @click="showResetModal = false"><X class="w-4 h-4 text-ink-muted" /></button>
        </div>

        <form @submit.prevent="handleResetPassword" class="space-y-3 text-xs">
          <div>
            <label class="block text-ink-muted font-medium mb-1">New Password (min 8 chars)</label>
            <input v-model="resetPasswordForm.new_password" type="password" required class="w-full px-3 py-2 bg-surface border border-line rounded-input focus:outline-none focus:border-brand" />
          </div>

          <div class="pt-4 flex justify-end gap-2">
            <button type="button" @click="showResetModal = false" class="px-4 py-2 border border-line rounded-pill hover:bg-surface">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-brand text-white rounded-pill hover:opacity-90">Set Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
