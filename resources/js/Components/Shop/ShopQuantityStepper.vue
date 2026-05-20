<template>
  <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50" role="group" :aria-label="ariaLabel">
    <button
      type="button"
      class="flex h-9 w-9 items-center justify-center rounded-r-xl text-slate-600 transition hover:bg-white hover:text-shop-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 disabled:opacity-40"
      :disabled="quantity <= 1"
      :aria-label="`تقليل الكمية`"
      @click="$emit('update:quantity', quantity - 1)"
    >
      <i class="bi bi-dash-lg text-sm" aria-hidden="true" />
    </button>
    <span class="min-w-[2rem] px-1 text-center text-sm font-semibold text-slate-900" aria-live="polite">{{ quantity }}</span>
    <button
      type="button"
      class="flex h-9 w-9 items-center justify-center rounded-l-xl text-slate-600 transition hover:bg-white hover:text-shop-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
      :aria-label="`زيادة الكمية`"
      @click="$emit('update:quantity', quantity + 1)"
    >
      <i class="bi bi-plus-lg text-sm" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup>
defineProps({
  quantity: { type: Number, required: true },
  ariaLabel: { type: String, default: 'تعديل الكمية' },
});
defineEmits(['update:quantity']);
</script>
