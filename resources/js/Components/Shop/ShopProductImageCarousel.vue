<template>
  <div class="space-y-3">
    <div class="shop-card relative overflow-hidden">
      <div class="relative aspect-square w-full bg-slate-100">
        <img
          :src="slides[currentIndex]"
          :alt="alt"
          class="h-full w-full object-cover transition-opacity duration-300"
          @error="onSlideError"
        />

        <template v-if="hasMultiple">
          <button
            type="button"
            class="absolute right-2 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-md transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
            aria-label="الصورة السابقة"
            @click="goPrev"
          >
            <i class="bi bi-chevron-right text-lg" aria-hidden="true" />
          </button>
          <button
            type="button"
            class="absolute left-2 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-md transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
            aria-label="الصورة التالية"
            @click="goNext"
          >
            <i class="bi bi-chevron-left text-lg" aria-hidden="true" />
          </button>

          <div
            class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 gap-1.5 rounded-full bg-black/40 px-2 py-1.5 backdrop-blur-sm"
            role="tablist"
            aria-label="اختيار الصورة"
          >
            <button
              v-for="(_, i) in slides"
              :key="i"
              type="button"
              role="tab"
              :aria-selected="currentIndex === i"
              :aria-label="`صورة ${i + 1}`"
              class="h-2 rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
              :class="currentIndex === i ? 'w-5 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
              @click="goTo(i)"
            />
          </div>

          <span
            class="absolute top-3 left-3 z-10 rounded-lg bg-black/50 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm"
          >
            {{ currentIndex + 1 }} / {{ slides.length }}
          </span>
        </template>
      </div>
    </div>

    <div
      v-if="hasMultiple"
      class="flex gap-2 overflow-x-auto pb-1 scroll-smooth snap-x snap-mandatory"
      role="list"
      aria-label="معاينة الصور"
    >
      <button
        v-for="(img, i) in slides"
        :key="i"
        type="button"
        role="listitem"
        class="shrink-0 snap-start overflow-hidden rounded-xl border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
        :class="currentIndex === i ? 'border-shop-600 ring-2 ring-shop-500/30' : 'border-transparent opacity-70 hover:opacity-100'"
        @click="goTo(i)"
      >
        <img
          :src="img"
          :alt="`معاينة ${i + 1}`"
          class="h-16 w-16 object-cover sm:h-20 sm:w-20"
          @error="(e) => onThumbError(e, i)"
        />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  urls: { type: Array, default: () => [] },
  alt: { type: String, default: '' },
  placeholder: { type: String, default: '/dashboard-assets/img/placeholder.jpg' },
});

const slides = computed(() => {
  const list = (props.urls || []).filter(Boolean);
  return list.length ? list : [props.placeholder];
});

const hasMultiple = computed(() => slides.value.length > 1);
const currentIndex = ref(0);

watch(
  () => props.urls,
  () => {
    currentIndex.value = 0;
  },
  { deep: true }
);

const goTo = (index) => {
  currentIndex.value = index;
};

const goPrev = () => {
  const n = slides.value.length;
  currentIndex.value = (currentIndex.value - 1 + n) % n;
};

const goNext = () => {
  const n = slides.value.length;
  currentIndex.value = (currentIndex.value + 1) % n;
};

const onSlideError = (e) => {
  e.target.src = props.placeholder;
};

const onThumbError = (e, index) => {
  if (slides.value[index]) {
    e.target.src = props.placeholder;
  }
};
</script>
