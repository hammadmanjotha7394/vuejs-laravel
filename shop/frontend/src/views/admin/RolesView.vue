<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Shield, Plus, Check, AlertCircle, X, CheckSquare, Square } from 'lucide-vue-next'
import apiClient from '@/api/client'

const roles = ref([])
const permissionGroups = ref({})
const allPermissions = ref([])
const isLoading = ref(true)
const feedbackMessage = ref('')
const errorMessage = ref('')
const showCreateModal = ref(false)

const newRole = reactive({
  name: '',
  permissions: [],
})

const fetchRolesAndPermissions = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const [rolesRes, permsRes] = await Promise.all([
      apiClient.get('/admin/roles'),
      apiClient.get('/admin/permissions'),
    ])
    roles.value = rolesRes.data.data
    permissionGroups.value = permsRes.data.groups
    allPermissions.value = permsRes.data.all
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to load roles and permissions.'
  } finally {
    isLoading.value = false
  }
}

const togglePermission = (perm) => {
  const index = newRole.permissions.indexOf(perm)
  if (index > -1) {
    newRole.permissions.splice(index, 1)
  } else {
    newRole.permissions.push(perm)
  }
}

const toggleGroup = (groupName) => {
  const perms = permissionGroups.value[groupName] || []
  const allSelected = perms.every((p) => newRole.permissions.includes(p))

  if (allSelected) {
    newRole.permissions = newRole.permissions.filter((p) => !perms.includes(p))
  } else {
    perms.forEach((p) => {
      if (!newRole.permissions.includes(p)) {
        newRole.permissions.push(p)
      }
    })
  }
}

const handleCreateRole = async () => {
  if (!newRole.name) {
    errorMessage.value = 'Please provide a role name.'
    return
  }
  if (newRole.permissions.length === 0) {
    errorMessage.value = 'Please select at least one permission.'
    return
  }

  errorMessage.value = ''
  try {
    await apiClient.post('/admin/roles', {
      name: newRole.name,
      permissions: newRole.permissions,
    })
    feedbackMessage.value = `Custom role '${newRole.name}' created successfully.`
    showCreateModal.value = false
    newRole.name = ''
    newRole.permissions = []
    await fetchRolesAndPermissions()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to create role.'
  }
}

const handleDeleteRole = async (role) => {
  if (role.is_core) return
  if (!confirm(`Are you sure you want to delete role '${role.name}'?`)) return

  errorMessage.value = ''
  try {
    await apiClient.delete(`/admin/roles/${role.id}`)
    feedbackMessage.value = `Role '${role.name}' deleted.`
    await fetchRolesAndPermissions()
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Cannot delete role.'
  }
}

onMounted(() => {
  fetchRolesAndPermissions()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-ink">Role & Permission Matrix</h1>
        <p class="text-xs text-ink-muted mt-1">Super Admin Role Builder & Granular Access Control</p>
      </div>

      <button
        @click="showCreateModal = true"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-medium hover:opacity-90 transition shadow-sm"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>Create Custom Role</span>
      </button>
    </div>

    <!-- Feedback Banners -->
    <div v-if="feedbackMessage" class="p-3 bg-emerald-50 border border-emerald-200 rounded-card text-success text-xs flex items-center justify-between">
      <span>{{ feedbackMessage }}</span>
      <button @click="feedbackMessage = ''"><X class="w-4 h-4" /></button>
    </div>
    <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-card text-error text-xs flex items-center justify-between">
      <span>{{ errorMessage }}</span>
      <button @click="errorMessage = ''"><X class="w-4 h-4" /></button>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="role in roles"
        :key="role.id"
        class="bg-page border border-line rounded-card p-5 shadow-soft flex flex-col justify-between"
      >
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-sm capitalize text-ink">{{ role.name.replace('_', ' ') }}</span>
            <span
              v-if="role.is_core"
              class="px-2 py-0.5 rounded-pill text-[10px] font-semibold bg-surface border border-line text-ink-muted uppercase"
            >
              Core System
            </span>
            <button
              v-else
              @click="handleDeleteRole(role)"
              class="text-xs text-error hover:underline"
            >
              Delete
            </button>
          </div>

          <p class="text-xs text-ink-muted mb-4">
            {{ role.users_count }} active staff assigned &middot; {{ role.permissions.length }} permissions granted
          </p>

          <div class="flex flex-wrap gap-1 max-h-36 overflow-y-auto pr-1">
            <span
              v-for="p in role.permissions"
              :key="p"
              class="px-2 py-0.5 rounded-pill bg-surface text-ink-muted text-[10px] font-mono border border-line"
            >
              {{ p }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Custom Role Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-page border border-line rounded-card p-6 shadow-soft max-w-2xl w-full max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-line mb-4">
          <div>
            <h2 class="text-sm font-semibold text-ink">Create New Custom Role</h2>
            <p class="text-[11px] text-ink-muted">Assign granular permissions via the categorized matrix below</p>
          </div>
          <button @click="showCreateModal = false"><X class="w-4 h-4 text-ink-muted" /></button>
        </div>

        <form @submit.prevent="handleCreateRole" class="flex-1 overflow-y-auto space-y-5 pr-2 text-xs">
          <div>
            <label class="block text-ink-muted font-medium mb-1">Role Title</label>
            <input
              v-model="newRole.name"
              required
              placeholder="e.g. Content Editor, Logistics Specialist"
              class="w-full px-3 py-2 bg-surface border border-line rounded-input focus:outline-none focus:border-brand"
            />
          </div>

          <!-- Permission Categories Matrix -->
          <div class="space-y-4">
            <div
              v-for="(perms, groupName) in permissionGroups"
              :key="groupName"
              class="bg-surface border border-line rounded-card p-4"
            >
              <div class="flex items-center justify-between mb-2 pb-1 border-b border-line">
                <span class="font-semibold text-ink text-xs uppercase tracking-wider">{{ groupName }}</span>
                <button
                  type="button"
                  @click="toggleGroup(groupName)"
                  class="text-[11px] text-brand hover:underline font-medium"
                >
                  Toggle All
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                <label
                  v-for="perm in perms"
                  :key="perm"
                  class="flex items-center gap-2 p-1.5 rounded-input hover:bg-page cursor-pointer transition select-none"
                >
                  <input
                    type="checkbox"
                    :checked="newRole.permissions.includes(perm)"
                    @change="togglePermission(perm)"
                    class="h-3.5 w-3.5 text-brand rounded border-line focus:ring-brand"
                  />
                  <span class="font-mono text-[11px] text-ink">{{ perm }}</span>
                </label>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-line flex justify-end gap-2">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 border border-line rounded-pill hover:bg-surface font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-5 py-2 bg-brand text-white rounded-pill hover:opacity-90 font-medium"
            >
              Create Role
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
