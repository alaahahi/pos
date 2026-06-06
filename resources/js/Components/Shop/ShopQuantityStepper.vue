<template>
  <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800" role="group" :aria-label="ariaLabel">
    <button
      type="button"
      class="flex h-9 w-9 items-center justify-center rounded-r-xl shop-text-secondary transition hover:bg-white hover:text-shop-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 disabled:opacity-40 dark:hover:bg-slate-700 dark:hover:text-shop-400"
      :disabled="quantity <= 1"
      :aria-label="`تقليل الكمية`"
      @click="$emit('update:quantity', quantity - 1)"
    >
      <i class="bi bi-dash-lg text-sm" aria-hidden="true" />
    </button>
    <span class="min-w-[2rem] px-1 text-center text-sm font-semibold shop-text-primary" aria-live="polite">{{ quantity }}</span>
    <button
      type="button"
      class="flex h-9 w-9 items-center justify-center rounded-l-xl shop-text-secondary transition hover:bg-white hover:text-shop-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 dark:hover:bg-slate-700 dark:hover:text-shop-400"
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
