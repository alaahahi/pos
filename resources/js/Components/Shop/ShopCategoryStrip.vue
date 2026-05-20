<template>
  <div class="space-y-3">
    <h2 class="text-sm font-semibold text-slate-700">التصنيفات</h2>
    <div
      class="-mx-1 flex gap-3 overflow-x-auto pb-2 scroll-smooth snap-x snap-mandatory"
      role="tablist"
      aria-label="تصفية حسب التصنيف"
    >
      <button
        type="button"
        role="tab"
        :aria-selected="!selectedId"
        class="group flex w-[7.5rem] shrink-0 snap-start flex-col overflow-hidden rounded-2xl border-2 text-right transition focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 focus-visible:ring-offset-2"
        :class="!selectedId
          ? 'border-shop-600 bg-shop-50 shadow-shop-md ring-2 ring-shop-500/20'
          : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-shop'"
        @click="$emit('select', null)"
      >
        <div class="flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-shop-100 to-shop-200">
          <i
            class="bi bi-grid-3x3-gap text-3xl transition"
            :class="!selectedId ? 'text-shop-600' : 'text-shop-400 group-hover:text-shop-500'"
            aria-hidden="true"
          />
        </div>
        <div class="p-2.5">
          <p class="truncate text-sm font-semibold" :class="!selectedId ? 'text-shop-700' : 'text-slate-800'">الكل</p>
          <p class="text-xs text-slate-500">كل المنتجات</p>
        </div>
      </button>

      <button
        v-for="cat in categories"
        :key="cat.id"
        type="button"
        role="tab"
        :aria-selected="selectedId === cat.id"
        class="group flex w-[7.5rem] shrink-0 snap-start flex-col overflow-hidden rounded-2xl border-2 text-right transition focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 focus-visible:ring-offset-2"
        :class="selectedId === cat.id
          ? 'border-shop-600 bg-shop-50 shadow-shop-md ring-2 ring-shop-500/20'
          : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-shop'"
        @click="$emit('select', cat.id)"
      >
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
          <img
            v-if="categoryImageSrc(cat)"
            :src="categoryImageSrc(cat)"
            :alt="cat.name"
            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            loading="lazy"
            @error="(e) => onCategoryImageError(e, cat)"
          />
          <div
            v-else
            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200"
          >
            <i class="bi bi-tag text-3xl text-slate-400" aria-hidden="true" />
          </div>
          <div v-if="selectedId === cat.id" class="absolute inset-0 bg-shop-600/10" aria-hidden="true" />
        </div>
        <div class="p-2.5">
          <p class="truncate text-sm font-semibold" :class="selectedId === cat.id ? 'text-shop-700' : 'text-slate-800'">
            {{ cat.name }}
          </p>
          <p class="text-xs text-slate-500">{{ cat.active_products_count ?? 0 }} منتج</p>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useShopStorageUrl } from '@/composables/useShopStorageUrl';

const props = defineProps({
  categories: { type: Array, default: () => [] },
  selectedId: { type: [String, null], default: null },
  storageBases: { type: Array, default: () => [] },
});

defineEmits(['select']);

const { src: categoryImageSrc, onError: onCategoryImageError } = useShopStorageUrl(props.storageBases);
</script>
