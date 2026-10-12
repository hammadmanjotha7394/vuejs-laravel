<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Wrench, ShieldCheck } from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'

const settingsStore = useSettingsStore()
const authStore = useAuthStore()

const isMaintenanceActive = computed(() => settingsStore.maintenanceMode)
const isStaffBypassed = computed(() => authStore.isAuthenticated && authStore.isStaff)
</script>

<template>
  <div>
    <!-- Staff banner when maintenance mode is active -->
    <aside
      v-if="isMaintenanceActive && isStaffBypassed"
      class="bg-amber-500 text-white text-xs font-semibold py-1.5 px-4 text-center sticky top-0 z-50 flex items-center justify-center gap-2"
      role="status"
    >
      <Wrench class="w-3.5 h-3.5" />
      <span>Store is currently in Maintenance Mode (visible to staff only)</span>
      <RouterLink
        to="/admin/settings"
        class="underline underline-offset-2 ml-2 hover:opacity-85"
      >
        Manage
      </RouterLink>
    </aside>

    <!-- Full-screen takeover for normal visitors -->
    <div
      v-else-if="isMaintenanceActive && !isStaffBypassed"
      class="fixed inset-0 z-50 bg-white flex items-center justify-center p-6 text-center"
      role="alert"
    >
      <div class="max-w-md w-full space-y-6">
        <div class="flex justify-center">
          <img
            v-if="settingsStore.logoUrl"
            :src="settingsStore.logoUrl"
            :alt="settingsStore.siteName"
            class="h-10 object-contain"
          />
          <h1 v-else class="text-2xl font-bold tracking-tight text-ink">
            {{ settingsStore.siteName }}
          </h1>
        </div>

        <div class="w-14 h-14 rounded-full bg-brand/10 text-brand flex items-center justify-center mx-auto">
          <Wrench class="w-7 h-7" />
        </div>

        <div>
          <h2 class="text-xl font-bold text-ink">
            Under Scheduled Maintenance
          </h2>
          <p class="text-xs sm:text-sm text-ink-muted leading-relaxed mt-2.5">
            {{ settingsStore.maintenanceMessage }}
          </p>
        </div>

        <div class="pt-4 border-t border-line text-xs text-ink-muted flex items-center justify-center gap-2">
          <span>Are you an administrator?</span>
          <RouterLink
            to="/login"
            class="font-semibold text-brand hover:underline inline-flex items-center gap-1"
          >
            <ShieldCheck class="w-3.5 h-3.5" />
            <span>Staff Login</span>
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
