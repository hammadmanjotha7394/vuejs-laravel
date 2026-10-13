<script setup>
import { ref, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useMenuStore } from '@/stores/menu'
import { useCartStore } from '@/stores/cart'
import { useWishlistStore } from '@/stores/wishlist'
import GlobalLoadingBar from '@/components/ui/GlobalLoadingBar.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import ErrorBoundary from '@/components/ui/ErrorBoundary.vue'
import StoreHeader from '@/components/store/StoreHeader.vue'
import StoreFooter from '@/components/store/StoreFooter.vue'
import MobileDrawer from '@/components/store/MobileDrawer.vue'
import SearchOverlay from '@/components/store/SearchOverlay.vue'
import PopupManager from '@/components/store/PopupManager.vue'
import MaintenanceOverlay from '@/components/store/MaintenanceOverlay.vue'
import CartDrawer from '@/components/store/CartDrawer.vue'
import LiveChatWidget from '@/components/store/LiveChatWidget.vue'
import CookieConsentBanner from '@/components/store/CookieConsentBanner.vue'

const settingsStore = useSettingsStore()
const menuStore = useMenuStore()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()

const mobileDrawerOpen = ref(false)
const searchOverlayOpen = ref(false)

const openDrawer = () => {
  mobileDrawerOpen.value = true
}

const closeDrawer = () => {
  mobileDrawerOpen.value = false
}

const openSearch = () => {
  searchOverlayOpen.value = true
}

const closeSearch = () => {
  searchOverlayOpen.value = false
}

const handleCartClick = () => {
  cartStore.toggleDrawer()
}

onMounted(() => {
  settingsStore.fetchPublicSettings()
  menuStore.fetchMenus()
  cartStore.fetchCart()
  wishlistStore.fetchWishlist()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-page text-ink selection:bg-brand/20 selection:text-brand">
    <!-- Global top progress bar -->
    <GlobalLoadingBar />

    <!-- Maintenance overlay (if active) -->
    <MaintenanceOverlay />

    <!-- Sticky Main Header & Desktop Mega Menu -->
    <StoreHeader
      @open-drawer="openDrawer"
      @open-search="openSearch"
      @open-cart="handleCartClick"
    />

    <!-- Main View Outlet with Error Boundary -->
    <main class="flex-1 w-full" id="main-content">
      <ErrorBoundary>
        <router-view />
      </ErrorBoundary>
    </main>

    <!-- Clean Light Storefront Footer -->
    <StoreFooter />

    <!-- Mobile Slide-In Navigation Drawer -->
    <MobileDrawer
      :is-open="mobileDrawerOpen"
      @close="closeDrawer"
      @open-search="openSearch"
    />

    <!-- Catalog Live Search Overlay -->
    <SearchOverlay
      :is-open="searchOverlayOpen"
      @close="closeSearch"
    />

    <!-- Active Marketing Popups Manager -->
    <PopupManager />

    <!-- Slide-in Mini Cart Drawer -->
    <CartDrawer />

    <!-- Visitor Live Chat Floating Widget -->
    <LiveChatWidget />

    <!-- Cookie Consent Notice Banner -->
    <CookieConsentBanner />

    <!-- Global Floating Toast Notification Stack -->
    <ToastContainer />
  </div>
</template>
