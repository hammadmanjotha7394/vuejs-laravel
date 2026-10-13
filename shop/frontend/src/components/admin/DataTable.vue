<script setup>
import { computed } from 'vue'
import {
  Search,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  ChevronLeft,
  ChevronRight,
  Inbox,
} from 'lucide-vue-next'

const props = defineProps({
  columns: {
    type: Array,
    required: true,
    // [{ key: 'name', label: 'Product', sortable: true, align: 'left' }]
  },
  rows: {
    type: Array,
    default: () => [],
  },
  rowKey: {
    type: String,
    default: 'id',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  searchable: {
    type: Boolean,
    default: true,
  },
  search: {
    type: String,
    default: '',
  },
  searchPlaceholder: {
    type: String,
    default: 'Search records...',
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  selectedIds: {
    type: Array,
    default: () => [],
  },
  sortBy: {
    type: String,
    default: '',
  },
  sortDir: {
    type: String,
    default: 'asc',
  },
  page: {
    type: Number,
    default: 1,
  },
  lastPage: {
    type: Number,
    default: 1,
  },
  total: {
    type: Number,
    default: 0,
  },
  emptyTitle: {
    type: String,
    default: 'No records found',
  },
  emptyDescription: {
    type: String,
    default: 'Try adjusting your search or filter criteria.',
  },
})

const emit = defineEmits([
  'update:search',
  'update:selectedIds',
  'sort',
  'page-change',
  'row-click',
])

const allRowIds = computed(() => props.rows.map((r) => r[props.rowKey]))

const isAllSelected = computed(() => {
  return (
    props.rows.length > 0 &&
    allRowIds.value.every((id) => props.selectedIds.includes(id))
  )
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    const remaining = props.selectedIds.filter((id) => !allRowIds.value.includes(id))
    emit('update:selectedIds', remaining)
  } else {
    const merged = Array.from(new Set([...props.selectedIds, ...allRowIds.value]))
    emit('update:selectedIds', merged)
  }
}

const toggleSelectRow = (id) => {
  if (props.selectedIds.includes(id)) {
    emit(
      'update:selectedIds',
      props.selectedIds.filter((item) => item !== id)
    )
  } else {
    emit('update:selectedIds', [...props.selectedIds, id])
  }
}

const handleSort = (col) => {
  if (!col.sortable) return
  const nextDir =
    props.sortBy === col.key && props.sortDir === 'asc' ? 'desc' : 'asc'
  emit('sort', { key: col.key, dir: nextDir })
}
</script>

<template>
  <div class="bg-white rounded-card border border-line shadow-card overflow-hidden">
    <!-- Top Toolbar: Search, Filters & Actions -->
    <div
      v-if="searchable || $slots.filters || $slots.actions"
      class="p-4 border-b border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3"
    >
      <div class="flex flex-wrap items-center gap-2.5 flex-1">
        <!-- Search Input -->
        <div v-if="searchable" class="relative w-full sm:w-64">
          <Search class="w-3.5 h-3.5 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            type="search"
            :value="search"
            :placeholder="searchPlaceholder"
            class="w-full pl-8 pr-3 py-2 rounded-xl border border-line bg-surface/50 text-xs text-ink placeholder:text-ink-muted focus:bg-white focus:outline-none focus:border-brand transition"
            @input="emit('update:search', $event.target.value)"
          />
        </div>

        <!-- Custom Filter Controls Slot -->
        <slot name="filters" />
      </div>

      <!-- Right Action Buttons Slot -->
      <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
        <slot name="actions" />
      </div>
    </div>

    <!-- Bulk Selection Bar -->
    <div
      v-if="selectable && selectedIds.length > 0"
      class="px-4 py-2.5 bg-brand/5 border-b border-brand/20 flex flex-wrap items-center justify-between gap-2 text-xs"
    >
      <div class="flex items-center gap-2 font-semibold text-brand">
        <span>{{ selectedIds.length }} selected</span>
        <button
          type="button"
          @click="emit('update:selectedIds', [])"
          class="text-ink-muted hover:text-ink underline font-medium ml-1"
        >
          Clear selection
        </button>
      </div>
      <div class="flex items-center gap-2">
        <slot name="bulk-actions" :selected-ids="selectedIds" />
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="p-4 space-y-3">
      <div v-for="i in 5" :key="i" class="h-12 rounded-xl bg-surface animate-pulse" />
    </div>

    <!-- Empty State -->
    <div v-else-if="rows.length === 0" class="py-12 px-4 text-center">
      <div class="w-11 h-11 rounded-2xl bg-surface border border-line text-ink-muted flex items-center justify-center mx-auto mb-3">
        <Inbox class="w-5 h-5" />
      </div>
      <p class="text-sm font-bold text-ink">{{ emptyTitle }}</p>
      <p class="text-xs text-ink-muted mt-1 max-w-sm mx-auto">{{ emptyDescription }}</p>
      <div v-if="$slots.empty" class="mt-4">
        <slot name="empty" />
      </div>
    </div>

    <!-- Data Content: Desktop Sticky Table + Mobile Card View -->
    <template v-else>
      <!-- Desktop Table View (md and up) -->
      <div class="hidden md:block overflow-x-auto max-h-[640px]">
        <table class="w-full text-left border-collapse">
          <thead class="sticky top-0 z-10 bg-surface/95 backdrop-blur-xs border-b border-line text-[11px] font-bold uppercase tracking-wider text-ink-muted">
            <tr>
              <th v-if="selectable" class="py-3 pl-4 pr-2 w-10">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-line text-brand focus:ring-brand cursor-pointer"
                />
              </th>
              <th
                v-for="col in columns"
                :key="col.key"
                class="py-3 px-4 whitespace-nowrap"
                :class="[
                  col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                  col.sortable ? 'cursor-pointer select-none hover:text-ink' : '',
                  col.class || '',
                ]"
                @click="handleSort(col)"
              >
                <div
                  class="inline-flex items-center gap-1"
                  :class="col.align === 'right' ? 'justify-end' : ''"
                >
                  <span>{{ col.label }}</span>
                  <template v-if="col.sortable">
                    <ArrowUp
                      v-if="sortBy === col.key && sortDir === 'asc'"
                      class="w-3 h-3 text-brand"
                    />
                    <ArrowDown
                      v-else-if="sortBy === col.key && sortDir === 'desc'"
                      class="w-3 h-3 text-brand"
                    />
                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                  </template>
                </div>
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-line text-xs text-ink">
            <tr
              v-for="row in rows"
              :key="row[rowKey]"
              class="hover:bg-surface/60 transition-colors"
              @click="emit('row-click', row)"
            >
              <td v-if="selectable" class="py-3.5 pl-4 pr-2 w-10" @click.stop>
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(row[rowKey])"
                  @change="toggleSelectRow(row[rowKey])"
                  class="rounded border-line text-brand focus:ring-brand cursor-pointer"
                />
              </td>
              <td
                v-for="col in columns"
                :key="col.key"
                class="py-3.5 px-4 align-middle"
                :class="[
                  col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                  col.cellClass || '',
                ]"
              >
                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                  {{ row[col.key] ?? '—' }}
                </slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Responsive Mobile Card View (< md) -->
      <div class="md:hidden divide-y divide-line">
        <div
          v-for="row in rows"
          :key="'m-' + row[rowKey]"
          class="p-4 space-y-2.5 hover:bg-surface/40 transition"
          @click="emit('row-click', row)"
        >
          <slot name="mobile-card" :row="row">
            <div class="flex items-center justify-between gap-2" v-if="selectable">
              <label class="inline-flex items-center gap-2 text-xs font-semibold text-ink-muted" @click.stop>
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(row[rowKey])"
                  @change="toggleSelectRow(row[rowKey])"
                  class="rounded border-line text-brand focus:ring-brand"
                />
                <span>Select</span>
              </label>
            </div>
            <div
              v-for="col in columns"
              :key="'mc-' + col.key"
              class="flex items-start justify-between gap-3 text-xs"
            >
              <span class="text-ink-muted font-medium shrink-0">{{ col.label }}</span>
              <div class="text-right font-medium text-ink min-w-0">
                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                  {{ row[col.key] ?? '—' }}
                </slot>
              </div>
            </div>
          </slot>
        </div>
      </div>
    </template>

    <!-- Pagination Footer -->
    <div
      v-if="lastPage > 1 || total > 0"
      class="px-4 py-3 border-t border-line bg-surface/30 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
    >
      <span class="text-ink-muted">
        Showing page <strong class="text-ink">{{ page }}</strong> of
        <strong class="text-ink">{{ lastPage }}</strong>
        <span v-if="total"> ({{ total }} total records)</span>
      </span>

      <div v-if="lastPage > 1" class="flex items-center gap-1.5">
        <button
          type="button"
          :disabled="page <= 1"
          @click="emit('page-change', page - 1)"
          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill border border-line bg-white font-semibold text-ink disabled:opacity-40 hover:bg-surface transition"
        >
          <ChevronLeft class="w-3.5 h-3.5" />
          <span>Previous</span>
        </button>

        <button
          type="button"
          :disabled="page >= lastPage"
          @click="emit('page-change', page + 1)"
          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill border border-line bg-white font-semibold text-ink disabled:opacity-40 hover:bg-surface transition"
        >
          <span>Next</span>
          <ChevronRight class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  </div>
</template>
