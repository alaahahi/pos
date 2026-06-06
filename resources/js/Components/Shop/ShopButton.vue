<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="classes"
    v-bind="$attrs"
  >
    <span v-if="loading" class="inline-flex items-center gap-2">
      <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true" />
      <span>{{ loadingText }}</span>
    </span>
    <span v-else class="inline-flex items-center justify-center gap-2">
      <slot />
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  type: { type: String, default: 'button' },
  disabled: Boolean,
  loading: Boolean,
  loadingText: { type: String, default: 'جاري التحميل...' },
  block: Boolean,
});

const base =
  'shop-btn inline-flex items-center justify-center font-medium rounded-xl transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none dark:focus-visible:ring-offset-slate-950';

const variants = {
  primary: 'shop-btn-primary focus-visible:ring-shop-500',
  secondary: 'shop-btn-secondary focus-visible:ring-slate-400',
  ghost: 'shop-btn-ghost focus-visible:ring-slate-400',
  danger: 'shop-btn-danger focus-visible:ring-red-400',
  success: 'shop-btn-success focus-visible:ring-emerald-500',
};

const sizes = {
  sm: 'text-xs px-3 py-1.5',
  md: 'text-sm px-4 py-2.5',
  lg: 'text-base px-5 py-3',
};

const classes = computed(() => [
  base,
  variants[props.variant] || variants.primary,
  sizes[props.size] || sizes.md,
  props.block ? 'w-full' : '',
]);
</script>
