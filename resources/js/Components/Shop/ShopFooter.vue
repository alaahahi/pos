<template>
  <footer class="shop-footer">
    <div class="mx-auto flex max-w-7xl flex-col items-center gap-4 px-4 py-8 text-center sm:px-6">
      <a
        v-if="whatsappUrl"
        :href="whatsappUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950"
        :style="{ backgroundColor: primaryColor, '--tw-ring-color': primaryColor }"
      >
        <i class="bi bi-whatsapp text-lg" aria-hidden="true" />
        تواصل معنا مباشرة
      </a>
      <p class="text-xs shop-text-muted">
        بواسطة
        <a
          href="https://intellij-app.com"
          target="_blank"
          rel="noopener noreferrer"
          class="font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 transition hover:text-slate-900 dark:text-slate-400 dark:decoration-slate-600 dark:hover:text-slate-200"
        >
          https://intellij-app.com
        </a>
      </p>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  shop: { type: Object, required: true },
});

const primaryColor = computed(() => props.shop?.primary_color || '#4f46e5');

const whatsappUrl = computed(() => {
  const raw = String(props.shop?.whatsapp || '').replace(/\D/g, '');
  if (!raw) return null;

  const cc = String(props.shop?.phone_country_code || '964').replace(/\D/g, '');
  const phone = raw.startsWith(cc) ? raw : `${cc}${raw}`;
  const text = encodeURIComponent('دعم فني');

  return `https://wa.me/${phone}?text=${text}`;
});
</script>
