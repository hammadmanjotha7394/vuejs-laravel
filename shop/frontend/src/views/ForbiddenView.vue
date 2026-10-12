<script setup>
import { RouterLink } from 'vue-router'
import { ShieldAlert, ArrowLeft } from 'lucide-vue-next'
</script>

<template>
  <div class="min-h-screen bg-page flex flex-col justify-center items-center px-4 py-16 text-center text-ink">
    <div class="w-16 h-16 rounded-full bg-red-100 text-error flex items-center justify-center mb-6">
      <ShieldAlert class="w-8 h-8" />
    </div>

    <h1 class="text-3xl font-bold tracking-tight text-ink sm:text-4xl">Access Restricted</h1>
    <p class="mt-3 text-base text-ink-muted max-w-md mx-auto">
      You do not have the required role or permissions to access this administrative resource.
    </p>

    <div class="mt-8 flex items-center justify-center gap-4">
      <RouterLink
        to="/admin"
        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-pill bg-brand text-white text-sm font-medium hover:opacity-90 transition"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Return to Dashboard</span>
      </RouterLink>

      <RouterLink
        to="/"
        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-pill border border-line bg-page text-sm font-medium hover:bg-surface transition"
      >
        Storefront Home
      </RouterLink>
    </div>
  </div>
</template>
