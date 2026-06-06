<template>
  <div
    class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4"
    role="status"
    aria-label="جاري تحميل المنتجات"
  >
    <div v-for="i in count" :key="i" class="shop-card overflow-hidden">
      <div class="aspect-[4/3] animate-pulse bg-slate-200 dark:bg-slate-700" />
      <div class="space-y-3 p-4">
        <div class="h-4 w-3/4 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-700" />
        <div class="h-5 w-1/3 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-700" />
        <div class="h-9 w-full animate-pulse rounded-xl bg-slate-200 dark:bg-slate-700" />
      </div>
    </div>
    <span class="sr-only">جاري التحميل...</span>
  </div>
</template>

<script setup>
defineProps({ count: { type: Number, default: 8 } });
</script>
