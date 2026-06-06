<template>
  <div
    class="shop-root flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    dir="rtl"
    lang="ar"
    :style="brandVars"
  >
    <header class="shop-header">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <slot name="header-leading">
            <img
              v-if="logoUrl"
              :src="logoUrl"
              :alt="shop.company_name"
              class="h-10 w-10 shrink-0 rounded-xl object-contain sm:h-12 sm:w-12"
              @error="onLogoError"
            />
            <div class="min-w-0">
              <h1 class="truncate text-lg font-bold tracking-tight shop-text-primary sm:text-xl">
                {{ shop.company_name }}
              </h1>
              <p v-if="shop.tagline" class="hidden truncate text-xs shop-text-muted sm:block">
                {{ shop.tagline }}
              </p>
              <p v-else class="hidden text-xs shop-text-muted sm:block">تسوّق وأكمل طلبك عبر واتساب</p>
            </div>
          </slot>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <ShopThemeToggle />
          <slot name="header-trailing">
            <button
              type="button"
              class="shop-brand-btn relative flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 lg:hidden"
              aria-label="فتح السلة"
              @click="$emit('open-mobile-cart')"
            >
              <i class="bi bi-cart3 text-lg" aria-hidden="true" />
              <span>السلة</span>
              <span
                v-if="cartCount"
                class="absolute -left-1.5 -top-1.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1 text-xs font-bold shadow dark:bg-slate-100"
                :style="{ color: shop.primary_color || '#4f46e5' }"
              >
                {{ cartCount }}
              </span>
            </button>
          </slot>
        </div>
      </div>
    </header>

    <div class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:py-8">
      <slot />
    </div>

    <ShopFooter :shop="shop" />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import ShopFooter from '@/Components/Shop/ShopFooter.vue';
import ShopThemeToggle from '@/Components/Shop/ShopThemeToggle.vue';
import { useShopStorageUrl } from '@/composables/useShopStorageUrl';
import { useShopTheme } from '@/composables/useShopTheme';

const props = defineProps({
  shop: { type: Object, required: true },
  cartCount: { type: Number, default: 0 },
});
defineEmits(['open-mobile-cart']);

const { registerShopPage, unregisterShopPage, initShopTheme } = useShopTheme();

onMounted(() => {
  initShopTheme();
  registerShopPage();
});

onUnmounted(() => {
  unregisterShopPage();
});

const logoFailed = ref(false);
const { src: storageSrc } = useShopStorageUrl(props.shop?.storageBases || []);

const logoUrl = computed(() => {
  if (logoFailed.value) {
    return props.shop?.logo_fallback || null;
  }
  if (props.shop?.logo) {
    return storageSrc({ image: props.shop.logo });
  }
  return props.shop?.logo_fallback || null;
});

const onLogoError = () => {
  if (!logoFailed.value && props.shop?.logo_fallback) {
    logoFailed.value = true;
  }
};

const brandVars = computed(() => {
  const primary = props.shop?.primary_color || '#4f46e5';

  return {
    '--shop-primary': primary,
    '--shop-primary-dark': shadeColor(primary, -12),
    '--shop-primary-light': shadeColor(primary, 88),
  };
});

function shadeColor(hex, percent) {
  const normalized = String(hex || '#4f46e5').replace('#', '');
  if (normalized.length !== 6) return hex || '#4f46e5';

  const num = parseInt(normalized, 16);
  const r = Math.min(255, Math.max(0, (num >> 16) + percent));
  const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00ff) + percent));
  const b = Math.min(255, Math.max(0, (num & 0x0000ff) + percent));

  return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
}
</script>

<style scoped>
.shop-brand-btn {
  background-color: var(--shop-primary);
}
.shop-brand-btn:hover {
  background-color: var(--shop-primary-dark);
}
.shop-brand-btn:focus-visible {
  --tw-ring-color: var(--shop-primary);
}
</style>
