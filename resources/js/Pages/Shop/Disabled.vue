<template>
  <div
    class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 text-center font-sans"
    dir="rtl"
    lang="ar"
  >
    <div class="shop-card max-w-md p-10 animate-shop-fade-in">
      <div
        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
        aria-hidden="true"
      >
        <i class="bi bi-shop-window text-3xl" />
      </div>
      <h1 class="text-xl font-bold text-slate-900">{{ companyName }}</h1>
      <p class="mt-2 text-slate-500">المتجر غير متاح حالياً. يُرجى المحاولة لاحقاً.</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ companyName: String });
</script>
