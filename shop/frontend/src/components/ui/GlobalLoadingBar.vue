<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const isLoading = ref(false)
const progress = ref(0)
let timer = null

const startLoading = () => {
  isLoading.value = true
  progress.value = 15
  clearInterval(timer)
  timer = setInterval(() => {
    if (progress.value < 85) {
      progress.value += Math.random() * 15
    }
  }, 150)
}

const finishLoading = () => {
  progress.value = 100
  clearInterval(timer)
  setTimeout(() => {
    isLoading.value = false
    progress.value = 0
  }, 250)
}

let removeBeforeHook
let removeAfterHook

onMounted(() => {
  removeBeforeHook = router.beforeEach((to, from, next) => {
    startLoading()
    next()
  })

  removeAfterHook = router.afterEach(() => {
    finishLoading()
  })
})

onUnmounted(() => {
  if (removeBeforeHook) removeBeforeHook()
  if (removeAfterHook) removeAfterHook()
  clearInterval(timer)
})
</script>

<template>
  <div
    v-if="isLoading"
    class="fixed top-0 left-0 right-0 z-50 h-0.5 bg-transparent overflow-hidden pointer-events-none"
    role="progressbar"
    :aria-valuenow="progress"
    aria-valuemin="0"
    aria-valuemax="100"
  >
    <div
      class="h-full bg-brand transition-all duration-200 ease-out"
      :style="{ width: `${progress}%` }"
    ></div>
  </div>
</template>
