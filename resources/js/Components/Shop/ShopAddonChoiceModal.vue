<template>
  <Teleport to="body">
    <Transition name="shop-modal">
      <div
        v-if="product"
        class="shop-addon-overlay fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        @click.self="$emit('close')"
      >
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" aria-hidden="true" />

        <div
          class="shop-addon-modal relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl animate-shop-slide-up"
          dir="rtl"
          lang="ar"
        >
          <header class="shop-addon-modal__header border-b border-slate-100 px-5 py-4">
            <h2 :id="titleId" class="shop-addon-modal__title text-lg font-bold text-slate-900">إضافة إلى السلة</h2>
            <p class="shop-addon-modal__subtitle mt-1 text-sm text-slate-600">{{ product.name }}</p>
          </header>

          <div class="space-y-3 p-5">
            <button
              type="button"
              class="shop-addon-option flex w-full items-center justify-between gap-3 rounded-xl border-2 border-slate-200 bg-white p-4 text-right transition hover:border-shop-400 hover:bg-shop-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
              @click="$emit('confirm', false)"
            >
              <div>
                <p class="shop-addon-option__title font-semibold text-slate-900">بدون خدمة إضافية</p>
                <p class="shop-addon-option__hint mt-0.5 text-sm text-slate-500">سعر المنتج فقط</p>
              </div>
              <span class="shop-addon-option__price shrink-0 text-lg font-bold text-shop-600">
                {{ formatPrice(product.price) }}
                <span class="shop-addon-option__currency text-xs font-normal text-slate-500">{{ currencyLabel }}</span>
              </span>
            </button>

            <button
              type="button"
              class="shop-addon-option is-selected flex w-full items-center justify-between gap-3 rounded-xl border-2 border-shop-500 bg-shop-50 p-4 text-right transition hover:bg-shop-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
              @click="$emit('confirm', true)"
            >
              <div class="min-w-0">
                <p class="shop-addon-option__title font-semibold text-slate-900">مع {{ product.addon_name }}</p>
                <p class="shop-addon-option__hint mt-0.5 text-sm text-slate-500">
                  +{{ formatPrice(product.addon_price) }} {{ currencyLabel }}
                </p>
              </div>
              <span class="shop-addon-option__price shrink-0 text-lg font-bold text-shop-600">
                {{ formatPrice(priceWithAddon) }}
                <span class="shop-addon-option__currency text-xs font-normal text-slate-500">{{ currencyLabel }}</span>
              </span>
            </button>
          </div>

          <footer class="shop-addon-modal__footer border-t border-slate-100 px-5 py-3">
            <button
              type="button"
              class="shop-addon-modal__cancel w-full rounded-xl py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
              @click="$emit('close')"
            >
              إلغاء
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  product: { type: Object, default: null },
  currency: { type: String, default: 'USD' },
});

defineEmits(['confirm', 'close']);

const titleId = 'shop-addon-modal-title';

const currencyLabel = computed(() => props.product?.currency || props.currency || 'USD');

const priceWithAddon = computed(() => {
  if (!props.product) return 0;
  return (parseFloat(props.product.price) || 0) + (parseFloat(props.product.addon_price) || 0);
});

const formatPrice = (n) =>
  parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
</script>

<style scoped>
.shop-modal-enter-active,
.shop-modal-leave-active {
  transition: opacity 0.2s ease;
}
.shop-modal-enter-from,
.shop-modal-leave-to {
  opacity: 0;
}
</style>
