<script setup>
import { ref, onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useSettingsStore } from '@/stores/settings'
import { Star, Package, ArrowRight, ThumbsUp } from 'lucide-vue-next'

const settingsStore = useSettingsStore()

useHead({
  title: () => `My Reviews | ${settingsStore.siteName}`,
})

const loading = ref(true)
const reviews = ref([])

const fetchReviews = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/account/reviews')
    reviews.value = res.data?.data || []
  } catch (e) {
    reviews.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchReviews()
})
</script>

<template>
  <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-5 border-b border-line mb-6">
      <div>
        <h2 class="text-base sm:text-lg font-bold text-ink">My Product Reviews</h2>
        <p class="text-xs text-ink-muted">
          Reviews and ratings you have shared with our community
        </p>
      </div>
      <span v-if="!loading" class="text-xs font-semibold text-ink-muted">
        {{ reviews.length }} {{ reviews.length === 1 ? 'review' : 'reviews' }}
      </span>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 2" :key="i" class="h-28 rounded-xl bg-surface animate-pulse" />
    </div>

    <div v-else-if="reviews.length === 0" class="text-center py-12">
      <div class="w-12 h-12 rounded-full bg-surface text-ink-muted flex items-center justify-center mx-auto mb-3">
        <Star class="w-5 h-5" />
      </div>
      <p class="text-sm font-semibold text-ink">No reviews submitted yet</p>
      <p class="text-xs text-ink-muted mt-1 mb-5">
        Visit any product page to leave a rating and review for items you've tried.
      </p>
      <RouterLink
        to="/shop"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
      >
        <span>Browse Products</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </RouterLink>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="review in reviews"
        :key="review.id"
        class="rounded-card border border-line p-4 sm:p-5 space-y-3"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-line">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-surface border border-line overflow-hidden shrink-0 flex items-center justify-center">
              <img
                v-if="review.product?.image"
                :src="review.product.image"
                :alt="review.product.name"
                class="w-full h-full object-cover"
              />
              <Package v-else class="w-4 h-4 text-ink-muted" />
            </div>
            <div>
              <RouterLink
                v-if="review.product?.slug"
                :to="`/products/${review.product.slug}`"
                class="text-sm font-bold text-ink hover:text-brand transition"
              >
                {{ review.product.name }}
              </RouterLink>
              <p v-else class="text-sm font-bold text-ink">Product</p>
              <p class="text-[11px] text-ink-muted">Reviewed on {{ review.review_date }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border"
              :class="
                review.is_approved
                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                  : 'bg-amber-50 text-amber-800 border-amber-200'
              "
            >
              {{ review.is_approved ? 'Published' : 'Pending Approval' }}
            </span>
          </div>
        </div>

        <div class="space-y-1.5">
          <div class="flex items-center gap-1">
            <Star
              v-for="n in 5"
              :key="n"
              class="w-3.5 h-3.5"
              :class="n <= review.rating ? 'text-amber-500 fill-amber-500' : 'text-gray-200'"
            />
            <span v-if="review.title" class="ml-2 text-xs font-bold text-ink">
              {{ review.title }}
            </span>
          </div>
          <p class="text-xs text-ink-muted leading-relaxed">
            {{ review.body }}
          </p>
        </div>

        <div v-if="review.helpful_count > 0" class="pt-1 flex items-center gap-1.5 text-[11px] text-ink-muted">
          <ThumbsUp class="w-3 h-3 text-brand" />
          <span>{{ review.helpful_count }} {{ review.helpful_count === 1 ? 'person' : 'people' }} found this helpful</span>
        </div>
      </div>
    </div>
  </div>
</template>
