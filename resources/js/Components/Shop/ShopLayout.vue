<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900" dir="rtl" lang="ar">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="min-w-0">
          <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
            {{ shop.company_name }}
          </h1>
          <p class="hidden text-xs text-slate-500 sm:block">تسوّق وأكمل طلبك عبر واتساب</p>
        </div>
        <button
          type="button"
          class="relative flex items-center gap-2 rounded-xl bg-shop-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-shop-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 focus-visible:ring-offset-2 lg:hidden"
          aria-label="فتح السلة"
          @click="$emit('open-mobile-cart')"
        >
          <i class="bi bi-cart3 text-lg" aria-hidden="true" />
          <span>السلة</span>
          <span
            v-if="cartCount"
            class="absolute -left-1.5 -top-1.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1 text-xs font-bold text-shop-600 shadow"
          >
            {{ cartCount }}
          </span>
        </button>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  shop: { type: Object, required: true },
  cartCount: { type: Number, default: 0 },
});
defineEmits(['open-mobile-cart']);
</script>
