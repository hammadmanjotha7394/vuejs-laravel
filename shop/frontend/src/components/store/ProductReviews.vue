<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import {
  Star,
  CheckCircle2,
  ThumbsUp,
  MessageSquarePlus,
  X,
  Loader2,
  ChevronDown,
  Upload,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useSettingsStore } from '@/stores/settings'

const props = defineProps({
  productId: {
    type: [Number, String],
    required: true,
  },
  ratingSummary: {
    type: Object,
    default: () => ({
      average: 5.0,
      count: 0,
      breakdown: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 },
    }),
  },
  initialReviews: {
    type: Array,
    default: () => [],
  },
})

const authStore = useAuthStore()
const toastStore = useToastStore()
const settingsStore = useSettingsStore()

const reviewsList = ref([...props.initialReviews])
const sortOption = ref('newest')
const isModalOpen = ref(false)
const isSubmitting = ref(false)
const uploadingPhoto = ref(false)
const votedHelpful = ref(new Set())

// Review form state
const form = ref({
  rating: 5,
  title: '',
  body: '',
  author_name: authStore.user?.name || '',
  photos: [],
})

// Star rating hover effect in form
const hoveredRating = ref(0)

// Sort options
const sortOptions = [
  { value: 'newest', label: 'Most Recent' },
  { value: 'highest', label: 'Highest Rating' },
  { value: 'lowest', label: 'Lowest Rating' },
  { value: 'helpful', label: 'Most Helpful' },
]

// Sorted reviews
const sortedReviews = computed(() => {
  const list = [...reviewsList.value]
  switch (sortOption.value) {
    case 'highest':
      return list.sort((a, b) => b.rating - a.rating)
    case 'lowest':
      return list.sort((a, b) => a.rating - b.rating)
    case 'helpful':
      return list.sort((a, b) => (b.helpful_count || 0) - (a.helpful_count || 0))
    case 'newest':
    default:
      return list.sort((a, b) => new Date(b.review_date || 0) - new Date(a.review_date || 0))
  }
})

// Star breakdown bar percentages
const breakdownPercentages = computed(() => {
  const total = props.ratingSummary.count || 1
  const b = props.ratingSummary.breakdown || { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
  return {
    5: Math.round(((b[5] || 0) / total) * 100),
    4: Math.round(((b[4] || 0) / total) * 100),
    3: Math.round(((b[3] || 0) / total) * 100),
    2: Math.round(((b[2] || 0) / total) * 100),
    1: Math.round(((b[1] || 0) / total) * 100),
  }
})

const getInitials = (name, avatar) => {
  if (avatar && avatar.length <= 3 && !avatar.startsWith('http') && !avatar.startsWith('/')) {
    return avatar.toUpperCase()
  }
  return String(name || 'C')
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

const openReviewModal = () => {
  form.value = {
    rating: 5,
    title: '',
    body: '',
    author_name: authStore.user?.name || '',
    photos: [],
  }
  isModalOpen.value = true
}

const handleCustomerPhotoUpload = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  if (form.value.photos.length >= 4) {
    toastStore.error('Maximum 4 photos per review.')
    return
  }

  uploadingPhoto.value = true
  try {
    const fd = new FormData()
    fd.append('image', file)
    const res = await axios.post('/api/reviews/upload-photo', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data.url) {
      form.value.photos.push(res.data.url)
    }
  } catch (err) {
    toastStore.error(err.response?.data?.message || 'Failed to upload photo.')
  } finally {
    uploadingPhoto.value = false
    e.target.value = ''
  }
}

const submitReview = async () => {
  if (!form.value.title.trim() || !form.value.body.trim()) return

  isSubmitting.value = true
  try {
    const res = await axios.post(`/api/products/${props.productId}/reviews`, {
      rating: form.value.rating,
      title: form.value.title.trim(),
      body: form.value.body.trim(),
      author_name: form.value.author_name.trim() || undefined,
      photos: form.value.photos,
    })

    toastStore.success(res.data.message || 'Review submitted successfully!')

    if (res.data.data) {
      reviewsList.value.unshift(res.data.data)
    }

    isModalOpen.value = false
  } catch (err) {
    const msg =
      err.response?.data?.errors?.body?.[0] ||
      err.response?.data?.message ||
      'Failed to submit review.'
    toastStore.error(msg)
  } finally {
    isSubmitting.value = false
  }
}

const voteHelpful = async (review) => {
  if (votedHelpful.value.has(review.id)) return

  try {
    const res = await axios.post(`/api/reviews/${review.id}/helpful`)
    review.helpful_count = res.data.helpful_count
    votedHelpful.value.add(review.id)
    toastStore.info('Marked review as helpful.')
  } catch (err) {
    // Non-fatal
  }
}
</script>

<template>
  <section class="py-12 border-t border-line" id="reviews-section" aria-label="Customer Reviews">
    <div class="mb-10">
      <h2 class="text-2xl font-bold tracking-tight text-ink">
        Customer Reviews
      </h2>
      <p class="text-xs sm:text-sm text-ink-muted mt-1">
        Authentic impressions and experiences from verified purchasers.
      </p>
    </div>

    <!-- Rating Summary Header & Breakdown Bars -->
    <div class="bg-surface/60 border border-line rounded-card p-6 sm:p-8 mb-10 grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
      <!-- Left: Overall Rating Score (4 cols) -->
      <div class="md:col-span-4 flex flex-col items-center sm:items-start text-center sm:text-left space-y-2">
        <div class="flex items-baseline gap-2">
          <span class="text-5xl font-extrabold tracking-tight text-ink">
            {{ ratingSummary.average || '5.0' }}
          </span>
          <span class="text-sm font-semibold text-ink-muted">/ 5.0</span>
        </div>

        <div class="flex items-center text-amber-400">
          <Star v-for="i in 5" :key="'sum-star-' + i" class="w-4 h-4 fill-current" />
        </div>

        <p class="text-xs text-ink-muted font-medium">
          Based on <strong>{{ ratingSummary.count }}</strong> customer reviews
        </p>

        <button
          type="button"
          @click="openReviewModal"
          class="mt-3 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition inline-flex items-center gap-1.5 shadow-sm"
        >
          <MessageSquarePlus class="w-3.5 h-3.5" />
          <span>Write a Review</span>
        </button>
      </div>

      <!-- Center: 5-to-1 Star Breakdown Bars (8 cols) -->
      <div class="md:col-span-8 space-y-2">
        <div
          v-for="star in [5, 4, 3, 2, 1]"
          :key="'bar-' + star"
          class="flex items-center gap-3 text-xs"
        >
          <span class="w-12 font-medium text-ink flex items-center gap-1 flex-shrink-0">
            <span>{{ star }}</span>
            <Star class="w-3 h-3 fill-amber-400 text-amber-400" />
          </span>

          <div class="flex-1 h-2 bg-line rounded-pill overflow-hidden">
            <div
              class="h-full bg-brand rounded-pill transition-all duration-500 ease-out"
              :style="{ width: `${breakdownPercentages[star]}%` }"
            ></div>
          </div>

          <span class="w-10 text-right text-ink-muted font-mono text-[11px] flex-shrink-0">
            {{ breakdownPercentages[star] }}%
          </span>
        </div>
      </div>
    </div>

    <!-- Review Controls: Sort Dropdown + Count -->
    <div class="flex items-center justify-between pb-4 border-b border-line mb-6">
      <span class="text-xs font-semibold text-ink">
        {{ sortedReviews.length }} Reviews
      </span>

      <div class="flex items-center gap-2">
        <label for="review-sort" class="text-xs text-ink-muted">Sort:</label>
        <div class="relative">
          <select
            id="review-sort"
            v-model="sortOption"
            class="appearance-none pl-3 pr-7 py-1.5 rounded-pill border border-line bg-white text-xs font-medium text-ink focus:outline-none focus:border-brand cursor-pointer shadow-xs"
          >
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-ink-muted absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      </div>
    </div>

    <!-- Reviews List -->
    <div v-if="sortedReviews.length > 0" class="space-y-6">
      <div
        v-for="rev in sortedReviews"
        :key="'review-' + rev.id"
        class="bg-white border border-line rounded-card p-6 shadow-xs space-y-3"
      >
        <!-- Top row: Stars + Date -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1 text-amber-400">
            <Star
              v-for="i in rev.rating"
              :key="'r-star-' + i"
              class="w-3.5 h-3.5 fill-current"
            />
          </div>
          <span class="text-[11px] text-ink-muted">
            {{ rev.review_date }}
          </span>
        </div>

        <!-- Title & Body -->
        <h4 v-if="rev.title" class="text-sm font-bold text-ink">
          {{ rev.title }}
        </h4>
        <p class="text-xs sm:text-sm text-ink-muted leading-relaxed whitespace-pre-line">
          {{ rev.body }}
        </p>

        <!-- Optional Customer / Reviewer Photos -->
        <div
          v-if="Array.isArray(rev.photos) && rev.photos.length > 0"
          class="flex flex-wrap gap-2.5 pt-1"
        >
          <img
            v-for="(photoUrl, pIdx) in rev.photos"
            :key="pIdx"
            :src="photoUrl"
            alt="Customer review photo"
            class="w-20 h-20 rounded-xl object-cover border border-line"
            loading="lazy"
          />
        </div>

        <!-- Optional Public Store Reply -->
        <div
          v-if="rev.admin_reply"
          class="mt-3 p-4 rounded-xl bg-surface/80 border-l-4 border-brand text-xs space-y-1"
        >
          <div class="flex items-center justify-between">
            <span class="font-bold text-ink">Response from {{ settingsStore.siteName || 'Our Studio' }}</span>
            <span v-if="rev.admin_replied_at" class="text-[10px] text-ink-muted">{{ rev.admin_replied_at }}</span>
          </div>
          <p class="text-ink-muted leading-relaxed">{{ rev.admin_reply }}</p>
        </div>

        <!-- Bottom row: Avatar/Initials + Author + Verified Badge + Helpful Vote -->
        <div class="pt-3 border-t border-line/40 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <img
              v-if="rev.author_avatar && (rev.author_avatar.startsWith('http') || rev.author_avatar.startsWith('/'))"
              :src="rev.author_avatar"
              :alt="rev.author_name"
              class="w-7 h-7 rounded-full object-cover border border-line"
            />
            <div
              v-else
              class="w-7 h-7 rounded-full bg-brand/10 text-brand font-bold text-[10px] flex items-center justify-center border border-brand/20"
            >
              {{ getInitials(rev.author_name, rev.author_avatar) }}
            </div>

            <span class="text-xs font-semibold text-ink">{{ rev.author_name }}</span>
            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-success bg-emerald-50 px-2 py-0.5 rounded-pill border border-emerald-200">
              <CheckCircle2 class="w-3 h-3" />
              <span>Verified Customer</span>
            </span>
          </div>

          <button
            type="button"
            @click="voteHelpful(rev)"
            :disabled="votedHelpful.has(rev.id)"
            class="inline-flex items-center gap-1.5 text-xs text-ink-muted hover:text-ink px-2.5 py-1 rounded-pill border border-line bg-surface hover:bg-white transition disabled:opacity-50"
            :aria-label="`Mark review helpful, currently ${rev.helpful_count || 0}`"
          >
            <ThumbsUp class="w-3 h-3" />
            <span>Helpful ({{ rev.helpful_count || 0 }})</span>
          </button>
        </div>
      </div>
    </div>

    <div v-else class="py-12 text-center text-xs text-ink-muted">
      Be the first to review this handcrafted piece.
    </div>

    <!-- Write A Review Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isModalOpen"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Write a Review"
        @click.self="isModalOpen = false"
      >
        <div class="w-full max-w-lg bg-white rounded-card shadow-2xl border border-line p-6 sm:p-8 space-y-5 max-h-[90vh] overflow-y-auto" @click.stop>
          <div class="flex items-center justify-between border-b border-line pb-3">
            <h3 class="font-bold text-base text-ink">Write a Customer Review</h3>
            <button
              type="button"
              @click="isModalOpen = false"
              class="p-1 rounded-full text-ink-muted hover:text-ink transition"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitReview" class="space-y-4">
            <!-- Rating Star Selector -->
            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Rating Score</label>
              <div class="flex items-center gap-1.5 text-amber-400">
                <button
                  v-for="s in 5"
                  :key="'rate-' + s"
                  type="button"
                  @click="form.rating = s"
                  @mouseenter="hoveredRating = s"
                  @mouseleave="hoveredRating = 0"
                  class="p-1 focus:outline-none transition-transform hover:scale-110"
                >
                  <Star
                    class="w-6 h-6 transition-colors"
                    :class="(hoveredRating ? hoveredRating >= s : form.rating >= s) ? 'fill-amber-400' : 'text-line'"
                  />
                </button>
              </div>
            </div>

            <!-- Author Name -->
            <div>
              <label class="block text-xs font-semibold text-ink mb-1">Your Name</label>
              <input
                v-model="form.author_name"
                type="text"
                required
                placeholder="e.g. Eleanor Vance"
                class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <!-- Review Title -->
            <div>
              <label class="block text-xs font-semibold text-ink mb-1">Review Headline</label>
              <input
                v-model="form.title"
                type="text"
                required
                placeholder="e.g. Exceptional build quality and tactile comfort"
                class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <!-- Review Body -->
            <div>
              <label class="block text-xs font-semibold text-ink mb-1">Detailed Review</label>
              <textarea
                v-model="form.body"
                required
                rows="4"
                placeholder="Share your experience regarding texture, sizing, durability, and craftsmanship..."
                class="w-full px-3.5 py-2.5 bg-surface border border-line rounded-input text-xs text-ink focus:outline-none focus:border-brand"
              ></textarea>
            </div>

            <!-- Optional Photos Upload -->
            <div>
              <label class="block text-xs font-semibold text-ink mb-1.5">Photos (Optional, up to 4)</label>
              <div class="flex flex-wrap items-center gap-2.5">
                <div
                  v-for="(pUrl, pIdx) in form.photos"
                  :key="pIdx"
                  class="relative w-14 h-14 rounded-xl overflow-hidden border border-line"
                >
                  <img :src="pUrl" class="w-full h-full object-cover" />
                  <button
                    type="button"
                    @click="form.photos.splice(pIdx, 1)"
                    class="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full p-0.5"
                  >
                    <X class="w-3 h-3" />
                  </button>
                </div>

                <label
                  v-if="form.photos.length < 4"
                  class="cursor-pointer px-3.5 py-2 rounded-xl border border-line bg-surface hover:bg-white text-xs font-medium text-ink inline-flex items-center gap-1.5"
                >
                  <Upload class="w-3.5 h-3.5 text-brand" />
                  <span>{{ uploadingPhoto ? 'Uploading...' : 'Add Photo' }}</span>
                  <input type="file" class="hidden" accept="image/*" @change="handleCustomerPhotoUpload" />
                </label>
              </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
              <button
                type="button"
                @click="isModalOpen = false"
                class="px-4 py-2 rounded-pill border border-line text-xs font-medium text-ink hover:bg-surface"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="px-6 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition flex items-center gap-1.5 shadow-sm disabled:opacity-60"
              >
                <Loader2 v-if="isSubmitting" class="w-3.5 h-3.5 animate-spin" />
                <span>Submit Review</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </section>
</template>
