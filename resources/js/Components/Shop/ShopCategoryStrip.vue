<template>
  <div class="space-y-3">
    <h2 class="text-sm font-semibold shop-text-secondary">التصنيفات</h2>
    <div
      class="-mx-1 flex gap-3 overflow-x-auto pb-2 scroll-smooth snap-x snap-mandatory"
      role="tablist"
      aria-label="تصفية حسب التصنيف"
    >
      <button
        v-for="cat in categories"
        :key="cat.id"
        type="button"
        role="tab"
        :aria-selected="selectedId === cat.id"
        class="group flex w-[7.5rem] shrink-0 snap-start flex-col overflow-hidden rounded-2xl border-2 text-right transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950"
        :class="selectedId === cat.id
          ? 'border-shop-600 bg-shop-50 shadow-shop-md ring-2 ring-shop-500/20 dark:border-shop-500 dark:bg-shop-900/40 dark:ring-shop-500/30'
          : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-shop dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800'"
        @click="$emit('select', cat.id)"
      >
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800">
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
            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700"
          >
            <i class="bi bi-tag text-3xl text-slate-400 dark:text-slate-500" aria-hidden="true" />
          </div>
          <div v-if="selectedId === cat.id" class="absolute inset-0 bg-shop-600/10 dark:bg-shop-400/10" aria-hidden="true" />
        </div>
        <div class="p-2.5">
          <p
            class="truncate text-sm font-semibold"
            :class="selectedId === cat.id ? 'text-shop-700 dark:text-shop-300' : 'shop-text-primary'"
          >
            {{ cat.name }}
          </p>
          <p class="text-xs shop-text-muted">{{ cat.active_products_count ?? 0 }} منتج</p>
        </div>
      </button>
    </div>

    <div
      v-if="activeCategory && (activeCategory.description || bundleOfferLabel)"
      class="flex flex-col gap-2"
      role="region"
      :aria-label="`معلومات ${activeCategory.name}`"
    >
      <p
        v-if="activeCategory.description"
        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-right text-sm leading-relaxed shop-text-secondary dark:border-slate-700 dark:bg-slate-800/60"
      >
        {{ activeCategory.description }}
      </p>
      <p
        v-if="bundleOfferLabel"
        class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-l from-amber-500 to-orange-600 px-4 py-2.5 text-center text-sm font-bold leading-snug text-white shadow-md"
        role="status"
      >
        <i class="bi bi-tags-fill shrink-0 text-base opacity-90" aria-hidden="true" />
        <span>{{ bundleOfferLabel }}</span>
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useShopStorageUrl } from '@/composables/useShopStorageUrl';

const props = defineProps({
  categories: { type: Array, default: () => [] },
  selectedId: { type: [String, null], default: null },
  storageBases: { type: Array, default: () => [] },
  currency: { type: String, default: 'USD' },
});

defineEmits(['select']);

const { src: categoryImageSrc, onError: onCategoryImageError } = useShopStorageUrl(props.storageBases);

const activeCategory = computed(() =>
  props.categories.find((c) => c.id === props.selectedId) ?? null
);

const formatMoney = (n) => {
  const num = parseFloat(n);
  return Number.isNaN(num) ? n : num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};

const bundleOfferLabel = computed(() => {
  const cat = activeCategory.value;
  if (!cat?.bundle_quantity || cat.bundle_price == null || cat.bundle_price === '') {
    return null;
  }
  const cur = cat.bundle_currency || props.currency || 'USD';
  return `استأجر ${cat.bundle_quantity} بسعر ${formatMoney(cat.bundle_price)} ${cur}`;
});
</script>
