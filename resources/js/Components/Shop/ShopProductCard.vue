<template>
  <article
    class="shop-card-hover group flex flex-col overflow-hidden animate-shop-fade-in"
    :aria-label="product.name"
  >
    <button
      type="button"
      class="relative aspect-[4/3] w-full overflow-hidden bg-slate-100 dark:bg-slate-800 text-right focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-shop-500"
      @click="$emit('view', product.id)"
    >
      <img
        v-if="productImageSrc(product)"
        :src="productImageSrc(product)"
        :alt="product.name"
        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
        loading="lazy"
        @error="(e) => onProductImageError(e, product)"
      />
      <div
        v-else
        class="flex h-full w-full items-center justify-center bg-slate-200 text-slate-400 dark:bg-slate-700 dark:text-slate-500"
      >
        <i class="bi bi-image text-4xl" aria-hidden="true" />
      </div>
    </button>

    <div class="flex flex-1 flex-col p-4">
      <button
        type="button"
        class="mb-1 text-right text-sm font-semibold shop-text-primary line-clamp-2 hover:text-shop-600 focus:outline-none focus-visible:text-shop-600 dark:hover:text-shop-400"
        @click="$emit('view', product.id)"
      >
        {{ product.name }}
      </button>
      <p class="text-lg font-bold text-shop-600" :class="product.rental_duration ? 'mb-1' : 'mb-3'">
        {{ formatPrice(product.price) }}
        <span class="text-xs font-normal shop-text-muted">{{ product.currency || currency }}</span>
      </p>
      <p v-if="product.rental_duration" class="mb-3 text-xs shop-text-muted">
        مدة الإيجار: {{ product.rental_duration }}
      </p>
      <ShopButton
        variant="primary"
        size="sm"
        block
        class="mt-auto"
        :aria-label="`أضف ${product.name} إلى السلة`"
        @click="$emit('add', product)"
      >
        <i class="bi bi-cart-plus" aria-hidden="true" />
        أضف للسلة
      </ShopButton>
    </div>
  </article>
</template>

<script setup>
import ShopButton from './ShopButton.vue';
import { useShopStorageUrl } from '@/composables/useShopStorageUrl';

const PLACEHOLDER = '/dashboard-assets/img/placeholder.jpg';

const props = defineProps({
  product: { type: Object, required: true },
  currency: { type: String, default: 'USD' },
  storageBases: { type: Array, default: () => [] },
});

defineEmits(['view', 'add']);

const { src: productImageSrc, onError: handleImageError } = useShopStorageUrl(props.storageBases);

const onProductImageError = (e, product) => handleImageError(e, product, PLACEHOLDER);

const formatPrice = (n) => {
  const num = parseFloat(n);
  return Number.isNaN(num) ? n : num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};
</script>
