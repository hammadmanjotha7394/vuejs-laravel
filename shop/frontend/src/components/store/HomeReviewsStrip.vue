<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { Star, CheckCircle2 } from 'lucide-vue-next'

const reviews = ref([])
const loading = ref(true)

const fetchFeaturedReviews = async () => {
  try {
    const res = await axios.get('/api/reviews/featured')
    reviews.value = res.data.data || []
  } catch (err) {
    console.warn('Could not load featured reviews:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchFeaturedReviews()
})
</script>

<template>
  <section
    v-if="reviews.length > 0"
    class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16"
    aria-label="Customer reflections"
  >
    <div class="text-center max-w-xl mx-auto mb-10 sm:mb-12">
      <div class="flex items-center justify-center gap-1 mb-2">
        <Star v-for="i in 5" :key="'h-star-' + i" class="w-4 h-4 fill-amber-400 text-amber-400" />
      </div>
      <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-ink">
        Reflections From Our Community
      </h2>
      <p class="text-xs sm:text-sm text-ink-muted mt-2">
        Unfiltered customer thoughts on our materials, craftsmanship, and daily utility.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="review in reviews"
        :key="'rev-' + review.id"
        class="bg-white border border-line rounded-card p-6 shadow-sm flex flex-col justify-between hover:shadow-soft transition"
      >
        <div class="space-y-3">
          <!-- Star Rating -->
          <div class="flex items-center gap-1">
            <Star
              v-for="i in review.rating"
              :key="'star-' + i"
              class="w-3.5 h-3.5 fill-amber-400 text-amber-400"
            />
          </div>

          <!-- Title -->
          <h4 class="text-xs sm:text-sm font-semibold text-ink line-clamp-1">
            "{{ review.title }}"
          </h4>

          <!-- Body excerpt -->
          <p class="text-xs text-ink-muted leading-relaxed line-clamp-3 italic">
            {{ review.body }}
          </p>
        </div>

        <div class="pt-4 mt-4 border-t border-line/60 flex items-center justify-between text-[11px]">
          <div>
            <p class="font-semibold text-ink">{{ review.author_name }}</p>
            <p v-if="review.product" class="text-ink-muted truncate max-w-[140px]">
              {{ review.product.name }}
            </p>
          </div>

          <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-success bg-emerald-50 px-2 py-0.5 rounded-pill border border-emerald-200">
            <CheckCircle2 class="w-3 h-3" />
            <span>Verified</span>
          </span>
        </div>
      </div>
    </div>
  </section>
</template>
