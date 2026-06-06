<template>
  <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center animate-shop-fade-in dark:border-slate-700 dark:bg-slate-900/50">
    <div
      class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"
      aria-hidden="true"
    >
      <i :class="icon" class="text-3xl" />
    </div>
    <h3 class="text-lg font-semibold shop-text-primary">{{ title }}</h3>
    <p v-if="description" class="mt-1 max-w-sm text-sm shop-text-muted">{{ description }}</p>
    <slot name="action" />
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'لا توجد عناصر' },
  description: String,
  icon: { type: String, default: 'bi bi-inbox' },
});
</script>
