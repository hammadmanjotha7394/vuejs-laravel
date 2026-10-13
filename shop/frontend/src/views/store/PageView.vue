<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import axios from 'axios'
import {
  ChevronRight,
  ChevronDown,
  Loader2,
  FileText,
  ArrowLeft,
  Truck,
  RotateCcw,
  ShieldCheck,
  Sparkles,
  MessageSquare,
  PackageSearch,
} from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'

const route = useRoute()
const settingsStore = useSettingsStore()

const page = ref(null)
const loading = ref(true)
const notFound = ref(false)
const openFaqIndex = ref(0)

const currentSlug = computed(() => {
  if (route.params.slug) return String(route.params.slug)
  if (route.meta?.pageSlug) return String(route.meta.pageSlug)
  return route.path.replace(/^\/(pages?\/)?/, '').replace(/\/$/, '')
})

const defaultPages = {
  about: {
    title: 'About Our Studio',
    seo_title: 'About Our Studio — Mindful Craft & Heritage Workshops',
    seo_description:
      'Discover our philosophy of quiet proportion, durable utility, and direct partnerships with independent European and Japanese workshops.',
    content_html: `
      <h2>Mindful Design for Quiet Living</h2>
      <p>Founded on principles of calm proportion, honest materials, and enduring utility, we collaborate directly with family-run mills and ceramic studios across Portugal, Spain, Italy, and Japan. Rather than chasing fleeting seasonal trends, we release small, deliberate batches designed to age with character.</p>
      <h3>Our Three Pillars</h3>
      <ul>
        <li><strong>Traceable Raw Materials:</strong> Certified European flax linen, extra-fine merino wool, vegetable-tanned full-grain leather, and hand-dug Kyoto stoneware clay.</li>
        <li><strong>Small-Batch Production:</strong> Every garment and vessel is produced in limited runs to eliminate overproduction and honor the pace of traditional craftsmanship.</li>
        <li><strong>Plastic-Free Packaging:</strong> Every order leaves our studio in 100% recycled, biodegradable kraft mailers and FSC-certified boxes.</li>
      </ul>
    `,
  },
  faq: {
    title: 'Frequently Asked Questions',
    seo_title: 'Frequently Asked Questions — Customer Care',
    seo_description: 'Answers to common questions about sizing, garment care, shipping timelines, and 30-day returns.',
    content_html: `
      <p>Find quick answers to our most common inquiries below. If you need personalized sizing or product advice, our studio team is always available via Live Chat or our Contact page.</p>
    `,
  },
  shipping: {
    title: 'Shipping & Delivery',
    seo_title: 'Shipping & Delivery Information',
    seo_description: 'Domestic and international shipping timelines, complimentary shipping thresholds, and carbon-neutral dispatch.',
    content_html: `
      <h2>Fast, Plastic-Free Dispatch</h2>
      <p>Orders placed before <strong>2:00 PM EST</strong> Monday through Friday are packed and dispatched from our studio the same business day. Once your parcel ships, you will receive an email with a live tracking link.</p>
      <h3>Shipping Options & Rates</h3>
      <ul>
        <li><strong>Complimentary Standard Shipping:</strong> Automatically applied at checkout on all orders above our free shipping threshold.</li>
        <li><strong>Standard Ground (2–4 Business Days):</strong> $8.00 flat rate for orders below the complimentary threshold.</li>
        <li><strong>Express Priority Courier (1–2 Business Days):</strong> $18.00 flat rate with signature confirmation.</li>
        <li><strong>International DHL Express (4–7 Business Days):</strong> Calculated at checkout with duties and taxes clearly itemized.</li>
      </ul>
    `,
  },
  returns: {
    title: 'Returns & Exchanges',
    seo_title: 'Returns & 30-Day Guarantee',
    seo_description: 'Simple 30-day return and exchange policy for unworn garments and unused homeware.',
    content_html: `
      <h2>Hassle-Free 30-Day Returns</h2>
      <p>We stand behind the quality of every piece we make. If an item is not quite right for your wardrobe or space, you may request a return or size exchange within <strong>30 days of delivery</strong>.</p>
      <h3>Return Conditions</h3>
      <ul>
        <li>Items must be unworn, unwashed, and in original condition with all woven tags and protective packaging intact.</li>
        <li>Ceramic and glass objects must be returned in their original double-walled protective boxes.</li>
        <li>Once received and inspected at our warehouse, refunds are issued back to your original Stripe payment method within 3–5 business days.</li>
      </ul>
    `,
  },
  privacy: {
    title: 'Privacy Policy',
    seo_title: 'Privacy Policy & Data Protection',
    seo_description: 'How we protect your personal data, payment security via Stripe, and cookie preferences.',
    content_html: `
      <h2>Your Digital Privacy Matters</h2>
      <p>We respect your personal information and never sell, rent, or trade your data with third-party data brokers. We collect only the essential details required to fulfill your orders, provide customer support, and send optional studio dispatches you explicitly subscribe to.</p>
      <h3>Payment & Session Security</h3>
      <ul>
        <li><strong>Encrypted Checkout:</strong> All credit card and digital wallet transactions are processed directly by <strong>Stripe</strong> using PCI-DSS Level 1 encryption. Your card numbers never touch our servers.</li>
        <li><strong>Essential Cookies:</strong> Used strictly to maintain your shopping bag, wishlist, and signed-in account session.</li>
        <li><strong>Right to Erasure:</strong> Registered customers can permanently delete their account and associated personal records at any time from their Account Profile settings.</li>
      </ul>
    `,
  },
  terms: {
    title: 'Terms of Service',
    seo_title: 'Terms of Service & Conditions of Sale',
    seo_description: 'Terms governing use of our online storefront, pricing, intellectual property, and consumer rights.',
    content_html: `
      <h2>Conditions of Use & Sale</h2>
      <p>By browsing or placing an order on our storefront, you agree to these Terms of Service. All product prices are displayed in the store’s active currency and verified on the server prior to checkout completion.</p>
      <h3>Product Availability & Handcrafted Variations</h3>
      <p>Because many of our textiles and ceramic pieces are finished by hand, subtle variations in glaze tone, linen slub, and natural leather grain are inherent hallmarks of authenticity rather than defects.</p>
      <h3>Order Acceptance & Cancellation</h3>
      <p>You may request an order cancellation directly from your Account Orders page at any time prior to shipment dispatch. Once a parcel has been handed to the courier, our standard 30-day return policy applies.</p>
    `,
  },
}

const faqItems = [
  {
    q: 'How quickly will my order ship?',
    a: 'Orders placed before 2:00 PM EST on business days are packed and handed to our courier the same day. Standard domestic delivery takes 2–4 business days, and you can check real-time progress anytime on our Track Order page.',
  },
  {
    q: 'Do you offer complimentary shipping?',
    a: 'Yes! Orders that meet or exceed our free shipping threshold (shown in the announcement bar and cart drawer progress meter) automatically qualify for free standard shipping.',
  },
  {
    q: 'How do I initiate a return or size exchange?',
    a: 'Sign in to your account and visit My Orders to request a cancellation or return, or reach out via our Contact page with your order number and email address. We provide a prepaid return label for domestic exchanges.',
  },
  {
    q: 'How should I care for organic linen and merino wool pieces?',
    a: 'Wash organic linen in cold water on a gentle cycle and line dry in the shade; avoid tumble drying on high heat. Merino wool garments should be hand-washed cold with a mild wool detergent and dried flat.',
  },
  {
    q: 'What happens if an item I want is out of stock?',
    a: 'Because we produce in small batches, popular sizes and glazes occasionally sell out. Select the out-of-stock variant on the product page and enter your email in the "Notify Me When Available" box to receive an instant alert the moment it returns.',
  },
]

const quickHelpLinks = [
  { label: 'About Us', to: '/about', slug: 'about' },
  { label: 'FAQ', to: '/faq', slug: 'faq' },
  { label: 'Shipping & Delivery', to: '/shipping', slug: 'shipping' },
  { label: 'Returns & Exchanges', to: '/returns', slug: 'returns' },
  { label: 'Privacy Policy', to: '/privacy', slug: 'privacy' },
  { label: 'Terms of Service', to: '/terms', slug: 'terms' },
]

const resolvedHtml = computed(() => {
  if (!page.value) return ''
  const raw = page.value.content_html || page.value.content || ''
  const fallback = defaultPages[currentSlug.value]?.content_html || ''
  // If seeded DB page only has a 1-paragraph stub, enrich it with the full editorial content
  if (raw.length < 320 && fallback.length > raw.length) {
    return fallback
  }
  return raw
})

const pageTitle = computed(() => {
  if (!page.value) return settingsStore.siteName || 'ATELIER'
  return page.value.seo_title || `${page.value.title} — ${settingsStore.siteName || 'ATELIER'}`
})

const pageDescription = computed(() => {
  if (!page.value) return ''
  return page.value.seo_description || page.value.title || ''
})

useHead({
  title: pageTitle,
  meta: [
    { name: 'description', content: pageDescription },
    { property: 'og:title', content: pageTitle },
    { property: 'og:description', content: pageDescription },
  ],
})

async function fetchPage(slug) {
  if (!slug) return
  loading.value = true
  notFound.value = false
  try {
    const res = await axios.get(`/api/pages/${encodeURIComponent(slug)}`)
    page.value = res.data.data
  } catch (err) {
    if (defaultPages[slug]) {
      page.value = { slug, ...defaultPages[slug] }
      notFound.value = false
    } else {
      page.value = null
      notFound.value = true
    }
  } finally {
    loading.value = false
  }
}

watch(currentSlug, (newSlug) => {
  if (newSlug) fetchPage(newSlug)
})

onMounted(() => {
  fetchPage(currentSlug.value)
})
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-ink-muted mb-6" aria-label="Breadcrumb">
      <RouterLink to="/" class="hover:text-ink transition">Home</RouterLink>
      <ChevronRight class="w-3.5 h-3.5" />
      <span class="text-ink font-medium truncate">{{ page?.title || currentSlug }}</span>
    </nav>

    <!-- Quick Navigation Bar for Care & Policy Pages -->
    <div class="flex items-center gap-2 overflow-x-auto sm:flex-wrap no-scrollbar [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden pb-3 mb-8 border-b border-line">
      <RouterLink
        v-for="item in quickHelpLinks"
        :key="item.slug"
        :to="item.to"
        class="px-3.5 py-1.5 rounded-pill text-xs font-semibold whitespace-nowrap transition border"
        :class="
          currentSlug === item.slug
            ? 'bg-brand text-white border-brand shadow-sm'
            : 'bg-white text-ink-muted border-line hover:text-ink hover:border-gray-300'
        "
      >
        {{ item.label }}
      </RouterLink>
      <RouterLink
        to="/track-order"
        class="px-3.5 py-1.5 rounded-pill text-xs font-semibold whitespace-nowrap transition border bg-white text-ink-muted border-line hover:text-brand hover:border-brand inline-flex items-center gap-1.5"
      >
        <PackageSearch class="w-3.5 h-3.5" />
        <span>Track Order</span>
      </RouterLink>
      <RouterLink
        to="/contact"
        class="px-3.5 py-1.5 rounded-pill text-xs font-semibold whitespace-nowrap transition border bg-white text-ink-muted border-line hover:text-brand hover:border-brand inline-flex items-center gap-1.5"
      >
        <MessageSquare class="w-3.5 h-3.5" />
        <span>Contact Us</span>
      </RouterLink>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="bg-white rounded-card border border-line p-8 sm:p-12 text-center space-y-3">
      <Loader2 class="w-6 h-6 animate-spin mx-auto text-brand" />
      <p class="text-xs text-ink-muted">Loading page content...</p>
    </div>

    <!-- 404 Not Found State -->
    <div
      v-else-if="notFound || !page"
      class="bg-white rounded-card border border-line p-10 sm:p-16 text-center space-y-4 shadow-sm"
    >
      <div class="w-12 h-12 rounded-full bg-surface border border-line flex items-center justify-center mx-auto text-ink-muted">
        <FileText class="w-6 h-6 stroke-[1.5]" />
      </div>
      <h1 class="text-xl sm:text-2xl font-bold text-ink">Page Not Found</h1>
      <p class="text-xs sm:text-sm text-ink-muted max-w-md mx-auto">
        The informational page you are looking for is not published yet or has moved to a new address.
      </p>
      <div class="pt-2">
        <RouterLink
          to="/"
          class="inline-flex items-center gap-2 px-6 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-95 transition shadow-sm"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Return to Storefront</span>
        </RouterLink>
      </div>
    </div>

    <!-- Published CMS Page Content -->
    <article
      v-else
      class="bg-white rounded-card border border-line p-6 sm:p-12 shadow-sm space-y-8"
    >
      <header class="border-b border-line pb-6">
        <span class="text-[11px] font-bold uppercase tracking-widest text-brand">
          {{ settingsStore.siteName }} Information
        </span>
        <h1 class="text-2xl sm:text-4xl font-bold tracking-tight text-ink mt-1">
          {{ page.title }}
        </h1>
        <p v-if="page.seo_description" class="text-xs sm:text-sm text-ink-muted mt-2.5 leading-relaxed max-w-2xl">
          {{ page.seo_description }}
        </p>
      </header>

      <!-- Value Highlights on About / Shipping / Returns -->
      <div
        v-if="['about', 'shipping', 'returns'].includes(currentSlug)"
        class="grid grid-cols-1 sm:grid-cols-3 gap-4"
      >
        <div class="p-4 rounded-xl bg-surface/70 border border-line flex items-start gap-3">
          <Sparkles class="w-5 h-5 text-brand flex-shrink-0 mt-0.5" />
          <div>
            <h3 class="text-xs font-bold text-ink">Artisanal Small Batches</h3>
            <p class="text-[11px] text-ink-muted mt-0.5 leading-relaxed">
              Crafted with heritage European & Japanese workshops.
            </p>
          </div>
        </div>
        <div class="p-4 rounded-xl bg-surface/70 border border-line flex items-start gap-3">
          <Truck class="w-5 h-5 text-brand flex-shrink-0 mt-0.5" />
          <div>
            <h3 class="text-xs font-bold text-ink">Same-Day Studio Dispatch</h3>
            <p class="text-[11px] text-ink-muted mt-0.5 leading-relaxed">
              Plastic-free carbon-neutral transit with live tracking.
            </p>
          </div>
        </div>
        <div class="p-4 rounded-xl bg-surface/70 border border-line flex items-start gap-3">
          <RotateCcw class="w-5 h-5 text-brand flex-shrink-0 mt-0.5" />
          <div>
            <h3 class="text-xs font-bold text-ink">30-Day Guarantee</h3>
            <p class="text-[11px] text-ink-muted mt-0.5 leading-relaxed">
              Effortless returns and complimentary size exchanges.
            </p>
          </div>
        </div>
      </div>

      <!-- Sanitized HTML Content -->
      <div
        class="prose prose-sm sm:prose-base max-w-none text-ink leading-relaxed prose-headings:font-bold prose-headings:text-ink prose-a:text-brand space-y-4"
        v-html="resolvedHtml"
      />

      <!-- Interactive FAQ Accordion when viewing /faq -->
      <div v-if="currentSlug === 'faq'" class="space-y-3 pt-2">
        <div
          v-for="(item, idx) in faqItems"
          :key="idx"
          class="border border-line rounded-xl overflow-hidden bg-surface/30"
        >
          <button
            type="button"
            @click="openFaqIndex = openFaqIndex === idx ? -1 : idx"
            class="w-full flex items-center justify-between px-5 py-4 text-left text-sm font-semibold text-ink hover:bg-surface/80 transition"
          >
            <span>{{ item.q }}</span>
            <ChevronDown
              class="w-4 h-4 text-ink-muted transition-transform duration-200 flex-shrink-0 ml-4"
              :class="{ 'rotate-180 text-brand': openFaqIndex === idx }"
            />
          </button>
          <div
            v-show="openFaqIndex === idx"
            class="px-5 pb-4 text-xs sm:text-sm text-ink-muted leading-relaxed border-t border-line/60 pt-3 bg-white"
          >
            {{ item.a }}
          </div>
        </div>
      </div>

      <!-- Bottom Customer Care Callout -->
      <footer class="pt-6 border-t border-line flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface/50 -mx-6 sm:-mx-12 -mb-6 sm:-mb-12 p-6 sm:px-12 sm:py-6 rounded-b-card">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-brand/10 text-brand flex items-center justify-center flex-shrink-0">
            <ShieldCheck class="w-4 h-4" />
          </div>
          <div>
            <p class="text-xs font-bold text-ink">Still have questions?</p>
            <p class="text-[11px] text-ink-muted">Our customer care specialists respond within 1 business day.</p>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <RouterLink
            to="/track-order"
            class="px-4 py-2 rounded-pill border border-line bg-white text-xs font-semibold text-ink hover:border-brand transition"
          >
            Track an Order
          </RouterLink>
          <RouterLink
            to="/contact"
            class="px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:opacity-90 transition shadow-sm"
          >
            Contact Studio
          </RouterLink>
        </div>
      </footer>
    </article>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

