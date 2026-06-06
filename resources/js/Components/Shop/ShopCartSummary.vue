<template>
  <div class="space-y-3 text-sm" aria-live="polite">
    <div v-if="loading" class="space-y-2 py-2">
      <div v-for="i in 3" :key="i" class="h-4 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800" />
    </div>
    <template v-else-if="pricing">
      <div
        v-for="bucket in buckets"
        :key="bucket.currency"
        class="space-y-1.5"
        :class="{ 'border-t shop-divider pt-3': bucket !== buckets[0] }"
      >
        <div v-if="buckets.length > 1" class="text-xs font-semibold shop-text-muted">
          {{ bucket.currency }}
        </div>
        <div class="flex justify-between shop-text-secondary">
          <span>المجموع الفرعي</span>
          <span>{{ formatAmount(bucket.subtotal) }} {{ bucket.currency }}</span>
        </div>
        <div v-if="bucket.bundle_discount > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400">
          <span>خصم الحزم</span>
          <span>-{{ formatAmount(bucket.bundle_discount) }} {{ bucket.currency }}</span>
        </div>
        <div v-if="bucket.promotion_discount > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400">
          <span>خصم تلقائي</span>
          <span>-{{ formatAmount(bucket.promotion_discount) }} {{ bucket.currency }}</span>
        </div>
        <div v-if="bucket.coupon_discount > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400">
          <span>كوبون</span>
          <span>-{{ formatAmount(bucket.coupon_discount) }} {{ bucket.currency }}</span>
        </div>
        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold shop-text-primary dark:border-slate-700">
          <span>الإجمالي</span>
          <span class="text-shop-600">{{ formatAmount(bucket.total) }} {{ bucket.currency }}</span>
        </div>
      </div>

      <p v-if="buckets.length > 1" class="text-[11px] leading-relaxed text-slate-500">
        <template v-if="pricing?.exchange_rate">
          الخصم التلقائي يُحسب بدمج الدينار والدولار بسعر الصرف ({{ formatAmount(pricing.exchange_rate) }} IQD/USD).
        </template>
        <template v-else>
          الفاتورة مقسّمة حسب العملة. كل عملة لها إجمالي مستقل.
        </template>
      </p>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  pricing: Object,
  currency: { type: String, default: 'USD' },
  loading: Boolean,
});

const buckets = computed(() => {
  if (!props.pricing) return [];

  const totals = props.pricing.totals_by_currency;
  if (totals && typeof totals === 'object') {
    return Object.entries(totals)
      .map(([cur, t]) => ({
        currency: cur,
        subtotal: Number(t.subtotal || 0),
        bundle_discount: Number(t.bundle_discount || 0),
        promotion_discount: Number(t.promotion_discount || 0),
        coupon_discount: Number(t.coupon_discount || 0),
        total: Number(t.total || 0),
      }))
      .filter((b) => b.subtotal > 0 || b.total > 0);
  }

  return [
    {
      currency: props.pricing.currency || props.currency,
      subtotal: Number(props.pricing.subtotal || 0),
      bundle_discount: Number(props.pricing.bundle_discount || 0),
      promotion_discount: Number(props.pricing.promotion_discount || 0),
      coupon_discount: Number(props.pricing.coupon_discount || 0),
      total: Number(props.pricing.total || 0),
    },
  ];
});

const formatAmount = (n) => {
  const num = Number(n);
  if (Number.isNaN(num)) return n;
  return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
};
</script>
