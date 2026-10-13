<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useHead } from '@unhead/vue'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'
import { User, Lock, AlertTriangle, Trash2, X } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const toast = useToastStore()

useHead({
  title: () => `Profile & Security | ${settingsStore.siteName}`,
})

// Profile Form State
const profileForm = ref({
  name: '',
  email: '',
  phone: '',
})
const savingProfile = ref(false)
const profileError = ref('')

// Password Form State
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const savingPassword = ref(false)
const passwordError = ref('')

// Delete Account Modal State
const deleteModalOpen = ref(false)
const deletePassword = ref('')
const deletingAccount = ref(false)
const deleteError = ref('')

onMounted(() => {
  if (authStore.user) {
    profileForm.value = {
      name: authStore.user.name || '',
      email: authStore.user.email || '',
      phone: authStore.user.phone || '',
    }
  }
})

const handleUpdateProfile = async () => {
  savingProfile.value = true
  profileError.value = ''
  try {
    await authStore.updateProfile(profileForm.value)
    toast.success('Profile updated successfully.')
  } catch (err) {
    profileError.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.errors?.name?.[0] ||
      err.response?.data?.message ||
      'Unable to update profile.'
  } finally {
    savingProfile.value = false
  }
}

const handleChangePassword = async () => {
  savingPassword.value = true
  passwordError.value = ''
  try {
    await authStore.changePassword(passwordForm.value)
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
    toast.success('Password updated successfully.')
  } catch (err) {
    passwordError.value =
      err.response?.data?.errors?.current_password?.[0] ||
      err.response?.data?.errors?.password?.[0] ||
      err.response?.data?.message ||
      'Unable to update password.'
  } finally {
    savingPassword.value = false
  }
}

const handleDeleteAccount = async () => {
  deleteError.value = ''
  if (!deletePassword.value) {
    deleteError.value = 'Please enter your current password to confirm.'
    return
  }

  deletingAccount.value = true
  try {
    await authStore.deleteAccount(deletePassword.value)
    deleteModalOpen.value = false
    toast.info('Your account has been permanently deleted.')
    router.push('/')
  } catch (err) {
    deleteError.value =
      err.response?.data?.errors?.password?.[0] ||
      err.response?.data?.message ||
      'Incorrect password or unable to delete account.'
  } finally {
    deletingAccount.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Personal Information Card -->
    <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
      <div class="flex items-center gap-2.5 pb-4 border-b border-line mb-5">
        <div class="w-8 h-8 rounded-xl bg-brand/10 text-brand flex items-center justify-center">
          <User class="w-4 h-4" />
        </div>
        <div>
          <h2 class="text-base font-bold text-ink">Personal Information</h2>
          <p class="text-xs text-ink-muted">Update your name, email address, and phone number</p>
        </div>
      </div>

      <form @submit.prevent="handleUpdateProfile" class="space-y-4 text-xs max-w-xl">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-ink mb-1.5">Full Name *</label>
            <input
              v-model="profileForm.name"
              type="text"
              required
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <div>
            <label class="block font-semibold text-ink mb-1.5">Phone Number</label>
            <input
              v-model="profileForm.phone"
              type="text"
              placeholder="+1 (555) 000-0000"
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-ink mb-1.5">Email Address *</label>
          <input
            v-model="profileForm.email"
            type="email"
            required
            class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
          />
        </div>

        <p v-if="profileError" class="text-xs text-error font-medium">
          {{ profileError }}
        </p>

        <div class="pt-1">
          <button
            type="submit"
            :disabled="savingProfile"
            class="px-5 py-2.5 rounded-pill bg-brand text-white font-semibold hover:bg-brand-dark disabled:opacity-50 transition"
          >
            {{ savingProfile ? 'Saving Changes...' : 'Save Profile' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Change Password Card -->
    <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
      <div class="flex items-center gap-2.5 pb-4 border-b border-line mb-5">
        <div class="w-8 h-8 rounded-xl bg-brand/10 text-brand flex items-center justify-center">
          <Lock class="w-4 h-4" />
        </div>
        <div>
          <h2 class="text-base font-bold text-ink">Change Password</h2>
          <p class="text-xs text-ink-muted">Use a strong password of at least 8 characters</p>
        </div>
      </div>

      <form @submit.prevent="handleChangePassword" class="space-y-4 text-xs max-w-xl">
        <div>
          <label class="block font-semibold text-ink mb-1.5">Current Password *</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-ink mb-1.5">New Password *</label>
            <input
              v-model="passwordForm.password"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <div>
            <label class="block font-semibold text-ink mb-1.5">Confirm New Password *</label>
            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-brand"
            />
          </div>
        </div>

        <p v-if="passwordError" class="text-xs text-error font-medium">
          {{ passwordError }}
        </p>

        <div class="pt-1">
          <button
            type="submit"
            :disabled="savingPassword"
            class="px-5 py-2.5 rounded-pill bg-ink text-white font-semibold hover:bg-ink/90 disabled:opacity-50 transition"
          >
            {{ savingPassword ? 'Updating...' : 'Update Password' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Danger Zone: Delete Account -->
    <div class="bg-white rounded-card border border-red-200 p-5 sm:p-6 shadow-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-xl bg-red-50 text-error flex items-center justify-center shrink-0 mt-0.5">
            <AlertTriangle class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-ink">Delete My Account</h2>
            <p class="text-xs text-ink-muted mt-0.5 max-w-lg">
              Permanently remove your personal profile, saved addresses, and active sessions. This action cannot be undone.
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="deleteModalOpen = true"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill border border-red-200 bg-red-50 text-error text-xs font-semibold hover:bg-red-100 transition self-start sm:self-auto shrink-0"
        >
          <Trash2 class="w-3.5 h-3.5" />
          <span>Delete My Account</span>
        </button>
      </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div
      v-if="deleteModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4"
    >
      <div class="bg-white rounded-card border border-line max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
          <div class="flex items-center gap-2 text-error">
            <AlertTriangle class="w-4 h-4" />
            <h3 class="text-base font-bold text-ink">Delete Account Permanently</h3>
          </div>
          <button
            type="button"
            @click="deleteModalOpen = false"
            class="p-1 rounded-full text-ink-muted hover:text-ink"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <p class="text-xs text-ink-muted mb-4">
          Are you sure you want to delete your account? Enter your current password below to confirm permanent account deletion.
        </p>

        <form @submit.prevent="handleDeleteAccount" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-ink mb-1.5">Current Password *</label>
            <input
              v-model="deletePassword"
              type="password"
              required
              placeholder="Enter your password"
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-ink focus:outline-none focus:border-error"
            />
          </div>

          <p v-if="deleteError" class="text-xs text-error font-medium">
            {{ deleteError }}
          </p>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="deleteModalOpen = false"
              class="px-4 py-2 rounded-pill border border-line font-semibold text-ink-muted hover:text-ink"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="deletingAccount"
              class="px-5 py-2 rounded-pill bg-error text-white font-semibold hover:bg-red-700 disabled:opacity-50 transition"
            >
              {{ deletingAccount ? 'Deleting...' : 'Confirm Delete' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
