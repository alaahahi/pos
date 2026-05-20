<template>
  <div class="space-y-2 text-sm" aria-live="polite">
    <div v-if="loading" class="space-y-2 py-2">
      <div v-for="i in 3" :key="i" class="h-4 animate-pulse rounded-lg bg-slate-100" />
    </div>
    <template v-else-if="pricing">
      <div class="flex justify-between text-slate-600">
        <span>المجموع الفرعي</span>
        <span>{{ pricing.subtotal }} {{ currency }}</span>
      </div>
      <div v-if="pricing.bundle_discount > 0" class="flex justify-between text-emerald-600">
        <span>خصم الحزم</span>
        <span>-{{ pricing.bundle_discount }}</span>
      </div>
      <div v-if="pricing.promotion_discount > 0" class="flex justify-between text-emerald-600">
        <span>خصم تلقائي</span>
        <span>-{{ pricing.promotion_discount }}</span>
      </div>
      <div v-if="pricing.coupon_discount > 0" class="flex justify-between text-emerald-600">
        <span>كوبون</span>
        <span>-{{ pricing.coupon_discount }}</span>
      </div>
      <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-slate-900">
        <span>الإجمالي</span>
        <span class="text-shop-600">{{ pricing.total }} {{ currency }}</span>
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  pricing: Object,
  currency: { type: String, default: 'USD' },
  loading: Boolean,
});
</script>
