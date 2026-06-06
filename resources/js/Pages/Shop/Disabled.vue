<template>
  <div
    class="shop-root flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 text-center font-sans dark:bg-slate-950"
    dir="rtl"
    lang="ar"
  >
    <div class="absolute top-4 left-4 sm:top-6 sm:left-6">
      <ShopThemeToggle />
    </div>

    <div class="shop-card max-w-md p-10 animate-shop-fade-in">
      <div
        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"
        aria-hidden="true"
      >
        <i class="bi bi-shop-window text-3xl" />
      </div>
      <h1 class="text-xl font-bold shop-text-primary">{{ companyName }}</h1>
      <p class="mt-2 shop-text-muted">المتجر غير متاح حالياً. يُرجى المحاولة لاحقاً.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import ShopThemeToggle from '@/Components/Shop/ShopThemeToggle.vue';
import { useShopTheme } from '@/composables/useShopTheme';

defineProps({ companyName: String });

const { registerShopPage, unregisterShopPage, initShopTheme } = useShopTheme();

onMounted(() => {
  initShopTheme();
  registerShopPage();
});

onUnmounted(() => {
  unregisterShopPage();
});
</script>
