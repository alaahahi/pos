<template>
  <button
    type="button"
    class="shop-theme-toggle group relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl shadow-sm transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950"
    :aria-label="isDark ? 'تفعيل الوضع النهاري' : 'تفعيل الوضع الليلي'"
    :aria-pressed="isDark"
    @click="toggleTheme"
  >
    <span class="sr-only">{{ isDark ? 'الوضع الليلي مفعّل' : 'الوضع النهاري مفعّل' }}</span>
    <Transition name="shop-theme-icon" mode="out-in">
      <i
        :key="isDark ? 'moon' : 'sun'"
        class="text-lg"
        :class="isDark ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill'"
        aria-hidden="true"
      />
    </Transition>
    <span
      class="pointer-events-none absolute inset-0 rounded-xl opacity-0 transition-opacity duration-300 group-hover:opacity-100"
      :class="isDark
        ? 'bg-gradient-to-br from-indigo-500/10 to-violet-500/10'
        : 'bg-gradient-to-br from-amber-400/10 to-orange-400/10'"
      aria-hidden="true"
    />
  </button>
</template>

<script setup>
import { useShopTheme } from '@/composables/useShopTheme';

const { isDark, toggleTheme } = useShopTheme();
</script>

<style scoped>
.shop-theme-icon-enter-active,
.shop-theme-icon-leave-active {
  transition: opacity 0.2s ease, transform 0.25s ease;
}
.shop-theme-icon-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.5);
}
.shop-theme-icon-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.5);
}
</style>
