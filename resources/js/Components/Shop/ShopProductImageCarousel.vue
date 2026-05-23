<template>
  <div class="space-y-3">
    <div class="shop-card relative overflow-hidden">
      <div class="relative aspect-square w-full bg-slate-100">
        <img
          v-if="activeSrc"
          :key="`${currentIndex}-${activeSrc}`"
          :src="activeSrc"
          :alt="alt"
          class="h-full w-full object-cover"
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
              v-for="(_, i) in slideCount"
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
            {{ currentIndex + 1 }} / {{ slideCount }}
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
        v-for="i in slideCount"
        :key="i"
        type="button"
        role="listitem"
        class="shrink-0 snap-start overflow-hidden rounded-xl border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
        :class="currentIndex === i - 1 ? 'border-shop-600 ring-2 ring-shop-500/30' : 'border-transparent opacity-70 hover:opacity-100'"
        @click="goTo(i - 1)"
      >
        <img
          :src="displayUrls[i - 1]"
          :alt="`معاينة ${i}`"
          class="h-16 w-16 object-cover sm:h-20 sm:w-20"
          @error="(e) => onThumbError(e, i - 1)"
        />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  urls: { type: Array, default: () => [] },
  paths: { type: Array, default: () => [] },
  storageBases: { type: Array, default: () => [] },
  alt: { type: String, default: '' },
  placeholder: { type: String, default: '/dashboard-assets/img/placeholder.jpg' },
});

const bases = computed(() => (props.storageBases || []).filter(Boolean));

const slideCount = computed(() => Math.max(props.paths?.length || 0, props.urls?.length || 0, 0));

const displayUrls = ref([]);
/** @type {import('vue').Ref<Record<number, number>>} */
const baseAttemptBySlide = ref({});

const buildUrlForPath = (path, baseIndex = 0) => {
  if (!path) return null;
  if (String(path).startsWith('http://') || String(path).startsWith('https://')) {
    return path;
  }
  const clean = String(path).replace(/^\//, '');
  const base = bases.value[baseIndex];
  if (!base) return null;
  return `${base.replace(/\/$/, '')}/${clean}`;
};

const syncDisplayUrls = () => {
  const count = slideCount.value;
  if (!count) {
    displayUrls.value = [props.placeholder];
    return;
  }

  baseAttemptBySlide.value = {};
  displayUrls.value = Array.from({ length: count }, (_, i) => {
    const serverUrl = props.urls?.[i];
    if (serverUrl) return serverUrl;
    const path = props.paths?.[i];
    return buildUrlForPath(path, 0) || props.placeholder;
  });
};

watch(
  () => [props.urls, props.paths, props.storageBases],
  syncDisplayUrls,
  { immediate: true, deep: true }
);

const hasMultiple = computed(() => slideCount.value > 1);
const currentIndex = ref(0);

watch(slideCount, () => {
  if (currentIndex.value >= slideCount.value) {
    currentIndex.value = 0;
  }
});

const activeSrc = computed(() => displayUrls.value[currentIndex.value] || props.placeholder);

const goTo = (index) => {
  if (index >= 0 && index < slideCount.value) {
    currentIndex.value = index;
  }
};

const goPrev = () => {
  const n = slideCount.value;
  if (n > 0) currentIndex.value = (currentIndex.value - 1 + n) % n;
};

const goNext = () => {
  const n = slideCount.value;
  if (n > 0) currentIndex.value = (currentIndex.value + 1) % n;
};

const tryNextBaseForSlide = (index, imgEl) => {
  const path = props.paths?.[index];
  if (!path || !bases.value.length) return false;

  const start = (baseAttemptBySlide.value[index] ?? -1) + 1;
  for (let b = start; b < bases.value.length; b += 1) {
    const candidate = buildUrlForPath(path, b);
    if (candidate && candidate !== imgEl.src) {
      baseAttemptBySlide.value[index] = b;
      displayUrls.value[index] = candidate;
      imgEl.src = candidate;
      return true;
    }
  }
  return false;
};

const onSlideError = (e) => {
  const img = e?.target;
  if (!img) return;
  if (tryNextBaseForSlide(currentIndex.value, img)) return;
  displayUrls.value[currentIndex.value] = props.placeholder;
  img.src = props.placeholder;
};

const onThumbError = (e, index) => {
  const img = e?.target;
  if (!img) return;
  if (tryNextBaseForSlide(index, img)) return;
  img.src = props.placeholder;
};
</script>
