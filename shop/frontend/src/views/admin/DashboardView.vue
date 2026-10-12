<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Shield, ShieldAlert, CheckCircle2, Users, CircleDollarSign, ArrowRight } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const roleName = computed(() => {
  return authStore.roles[0] || 'staff'
})

const permissions = computed(() => {
  return authStore.permissions || []
})
</script>

<template>
  <div class="space-y-6">
    <!-- Welcome Header Card -->
    <div class="bg-page border border-line rounded-card p-6 shadow-soft">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-ink">Welcome, {{ authStore.user?.name }}</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-pill text-xs font-semibold bg-emerald-50 text-success border border-emerald-200">
              Active
            </span>
          </div>
          <p class="text-xs text-ink-muted mt-1">
            Logged in as <strong class="text-ink font-semibold uppercase">{{ roleName.replace('_', ' ') }}</strong>
            &middot; {{ authStore.user?.email }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <RouterLink
            v-if="authStore.hasPermission('finance.view')"
            to="/admin/finance"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill bg-brand text-white text-xs font-medium hover:opacity-90 transition"
          >
            <CircleDollarSign class="w-3.5 h-3.5" />
            <span>Finance Overview</span>
          </RouterLink>

          <RouterLink
            v-if="authStore.hasPermission('users.view')"
            to="/admin/users"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-page text-xs font-medium text-ink hover:bg-surface transition"
          >
            <Users class="w-3.5 h-3.5" />
            <span>Manage Staff</span>
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Role Security Matrix Status -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Active Permissions -->
      <div class="bg-page border border-line rounded-card p-6 shadow-soft">
        <div class="flex items-center gap-2 mb-4">
          <Shield class="w-4 h-4 text-brand" />
          <h2 class="text-sm font-semibold text-ink">Granted Permissions ({{ permissions.length }})</h2>
        </div>
        <div class="flex flex-wrap gap-1.5 max-h-56 overflow-y-auto pr-1">
          <span
            v-for="perm in permissions"
            :key="perm"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-pill bg-surface text-ink text-[11px] font-mono border border-line"
          >
            <CheckCircle2 class="w-3 h-3 text-success flex-shrink-0" />
            <span>{{ perm }}</span>
          </span>
          <span v-if="permissions.length === 0" class="text-xs text-ink-muted italic">
            No administrative permissions assigned.
          </span>
        </div>
      </div>

      <!-- Access Restrictions Card -->
      <div class="bg-page border border-line rounded-card p-6 shadow-soft">
        <div class="flex items-center gap-2 mb-4">
          <ShieldAlert class="w-4 h-4 text-ink-muted" />
          <h2 class="text-sm font-semibold text-ink">Role Access Boundaries</h2>
        </div>
        <p class="text-xs text-ink-muted leading-relaxed">
          Every action in the administration panel is validated by Spatie permission gates and server-side policies.
        </p>

        <div class="mt-4 space-y-2 text-xs">
          <div class="flex items-center justify-between p-2.5 rounded-input bg-surface border border-line">
            <span class="font-medium text-ink">Finance & Revenue</span>
            <span
              :class="authStore.hasPermission('finance.view') ? 'text-success font-semibold' : 'text-error font-medium'"
            >
              {{ authStore.hasPermission('finance.view') ? 'Allowed' : '403 Forbidden' }}
            </span>
          </div>

          <div class="flex items-center justify-between p-2.5 rounded-input bg-surface border border-line">
            <span class="font-medium text-ink">Role Editor (Create/Modify)</span>
            <span
              :class="authStore.hasPermission('roles.manage') ? 'text-success font-semibold' : 'text-error font-medium'"
            >
              {{ authStore.hasPermission('roles.manage') ? 'Allowed' : '403 Forbidden' }}
            </span>
          </div>

          <div class="flex items-center justify-between p-2.5 rounded-input bg-surface border border-line">
            <span class="font-medium text-ink">User Management</span>
            <span
              :class="authStore.hasPermission('users.view') ? 'text-success font-semibold' : 'text-error font-medium'"
            >
              {{ authStore.hasPermission('users.view') ? 'Allowed' : '403 Forbidden' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
