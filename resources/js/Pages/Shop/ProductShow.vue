<template>
  <div class="min-h-screen bg-slate-50 font-sans" dir="rtl" lang="ar">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-5xl items-center px-4 py-3 sm:px-6">
        <button
          type="button"
          class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
          @click="router.visit(route('shop.index'))"
        >
          <i class="bi bi-arrow-right" aria-hidden="true" />
          العودة للمتجر
        </button>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
      <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
        <!-- Media -->
        <div class="space-y-3">
          <div class="shop-card overflow-hidden">
            <img
              v-if="currentImage"
              :src="currentImage"
              :alt="product.name"
              class="aspect-square w-full object-cover"
              @error="onMainImageError"
            />
          </div>
          <div
            v-if="galleryUrls.length > 1"
            class="flex flex-wrap gap-2"
            role="list"
            aria-label="صور المنتج"
          >
            <button
              v-for="(img, i) in galleryUrls"
              :key="i"
              type="button"
              role="listitem"
              class="overflow-hidden rounded-xl border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
              :class="currentImage === img ? 'border-shop-600 ring-2 ring-shop-500/30' : 'border-transparent opacity-70 hover:opacity-100'"
              @click="currentImage = img"
            >
              <img
                :src="img"
                :alt="`صورة ${i + 1}`"
                class="h-16 w-16 object-cover sm:h-20 sm:w-20"
                @error="(e) => onThumbImageError(e, i)"
              />
            </button>
          </div>
          <div v-if="product.video_url" class="shop-card overflow-hidden p-2">
            <video :src="product.video_url" controls class="w-full rounded-lg" />
          </div>
          <template v-for="(link, i) in product.youtube_links || []" :key="'yt' + i">
            <div class="shop-card overflow-hidden p-2">
              <iframe
                :src="youtubeEmbed(link)"
                class="aspect-video w-full rounded-lg"
                frameborder="0"
                allowfullscreen
                :title="`فيديو ${product.name}`"
              />
            </div>
          </template>
        </div>

        <!-- Info -->
        <div class="animate-shop-slide-up">
          <p v-if="product.category" class="mb-2 text-sm font-medium text-shop-600">
            {{ product.category.name }}
          </p>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            {{ product.name }}
          </h1>
          <p class="mt-4 text-3xl font-bold text-shop-600">
            {{ formatPrice(displayUnitPrice) }}
            <span class="text-base font-normal text-slate-500">{{ product.currency || shop.currency }}</span>
          </p>
          <p v-if="product.has_addon && !withAddon" class="mt-1 text-sm text-slate-500">
            سعر المنتج فقط — يمكن إضافة {{ product.addon_name }} (+{{ formatPrice(product.addon_price) }})
          </p>

          <div
            v-if="product.has_addon"
            class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
          >
            <p class="text-sm font-semibold text-slate-800">خدمة إضافية (اختياري)</p>
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
              :class="withAddon ? 'border-shop-500 bg-shop-50' : 'border-slate-200 hover:border-slate-300'">
              <input
                v-model="withAddon"
                type="checkbox"
                class="mt-1 h-4 w-4 rounded border-slate-300 text-shop-600 focus:ring-shop-500"
              />
              <div class="min-w-0 flex-1">
                <span class="font-medium text-slate-900">{{ product.addon_name }}</span>
                <span class="mr-2 text-shop-600">+{{ formatPrice(product.addon_price) }} {{ product.currency || shop.currency }}</span>
                <p class="mt-1 text-xs text-slate-500">أزل التحديد للشراء بدون الخدمة</p>
              </div>
            </label>
            <p class="text-sm text-slate-600">
              <span class="text-slate-500">المنتج:</span> {{ formatPrice(product.price) }}
              <template v-if="withAddon">
                <span class="mx-1">+</span>
                <span class="text-slate-500">{{ product.addon_name }}:</span> {{ formatPrice(product.addon_price) }}
                <span class="mx-2">=</span>
                <strong class="text-shop-700">{{ formatPrice(displayUnitPrice) }} {{ product.currency || shop.currency }}</strong>
              </template>
            </p>
          </div>

          <div
            v-if="product.description"
            class="prose prose-slate mt-6 max-w-none text-sm leading-relaxed text-slate-600"
            v-html="descriptionHtml"
          />
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <ShopButton variant="primary" size="lg" class="flex-1" @click="addAndOpenCart">
              <i class="bi bi-cart-plus" aria-hidden="true" />
              {{ withAddon && product.has_addon ? 'أضف مع الخدمة' : 'أضف للسلة' }}
            </ShopButton>
            <ShopButton variant="secondary" size="lg" @click="router.visit(route('shop.index'))">
              متابعة التسوّق
            </ShopButton>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ShopButton from '@/Components/Shop/ShopButton.vue';
import { useShopCart } from '@/composables/useShopCart';
import { useShopStorageUrl } from '@/composables/useShopStorageUrl';

const PLACEHOLDER = '/dashboard-assets/img/placeholder.jpg';

const props = defineProps({ product: Object, shop: Object });
const toast = useToast();
const { addItem } = useShopCart();
const withAddon = ref(false);

const { src: productImageSrc, onError: handleImageError } = useShopStorageUrl(
  props.shop?.storageBases || []
);

const galleryUrls = computed(() => {
  const urls = props.product.images_urls?.length
    ? [...props.product.images_urls]
    : [productImageSrc(props.product)].filter(Boolean);
  return urls.length ? urls : [PLACEHOLDER];
});

const resolveProductImage = () => galleryUrls.value[0] || PLACEHOLDER;

const currentImage = ref(resolveProductImage());

const displayUnitPrice = computed(() => {
  const base = parseFloat(props.product.price) || 0;
  if (withAddon.value && props.product.has_addon) {
    return base + (parseFloat(props.product.addon_price) || 0);
  }
  return base;
});

const onMainImageError = (e) => {
  handleImageError(e, props.product, PLACEHOLDER);
  currentImage.value = e.target?.src || PLACEHOLDER;
};

const onThumbImageError = (e, index) => {
  handleImageError(e, props.product, PLACEHOLDER);
  if (galleryUrls.value[index]) {
    e.target.src = galleryUrls.value[index] || PLACEHOLDER;
  }
};

const descriptionHtml = computed(() => (props.product.description || '').replace(/\n/g, '<br>'));

const formatPrice = (n) =>
  parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const youtubeEmbed = (url) => {
  if (!url) return '';
  const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/);
  return m ? `https://www.youtube.com/embed/${m[1]}` : url;
};

const addAndOpenCart = () => {
  addItem(props.product, 1, { withAddon: withAddon.value });
  toast.success(withAddon.value && props.product.has_addon ? 'أُضيف مع الخدمة' : 'أُضيف إلى السلة');
  router.visit(route('shop.index'));
};
</script>
