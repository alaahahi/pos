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
  'inline-flex items-center justify-center font-medium rounded-xl transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none';

const variants = {
  primary:
    'shop-brand-bg text-white shadow-sm hover:shadow-shop focus-visible:ring-2 focus-visible:ring-offset-2',
  secondary:
    'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 active:bg-slate-100 focus-visible:ring-slate-400',
  ghost: 'text-slate-600 hover:bg-slate-100 active:bg-slate-200 focus-visible:ring-slate-400',
  danger: 'text-red-600 hover:bg-red-50 active:bg-red-100 focus-visible:ring-red-400',
  success:
    'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 focus-visible:ring-emerald-500',
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
