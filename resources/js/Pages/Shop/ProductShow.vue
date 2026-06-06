<template>
  <Head>
    <title>{{ pageTitle }}</title>
    <meta head-key="description" name="description" :content="pageDescription" />
    <meta head-key="og:title" property="og:title" :content="pageTitle" />
    <meta head-key="og:description" property="og:description" :content="pageDescription" />
    <meta v-if="ogImage" head-key="og:image" property="og:image" :content="ogImage" />
    <meta head-key="og:type" property="og:type" content="product" />
    <meta head-key="og:site_name" property="og:site_name" :content="shop.company_name" />
  </Head>

  <div
    class="flex min-h-screen flex-col bg-slate-50 font-sans"
    dir="rtl"
    lang="ar"
    :style="brandVars"
  >
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
        <button
          type="button"
          class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2"
          :style="{ '--tw-ring-color': shop.primary_color || '#4f46e5' }"
          @click="router.visit(route('shop.index'))"
        >
          <i class="bi bi-arrow-right" aria-hidden="true" />
          العودة للمتجر
        </button>
        <div v-if="logoUrl" class="flex items-center gap-2">
          <img
            :src="logoUrl"
            :alt="shop.company_name"
            class="h-8 w-8 rounded-lg object-contain"
            @error="onLogoError"
          />
          <span class="hidden text-sm font-semibold text-slate-800 sm:inline">{{ shop.company_name }}</span>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-8">
      <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
        <div class="space-y-3">
          <ShopProductImageCarousel
            :urls="galleryUrls"
            :paths="galleryPaths"
            :storage-bases="shop.storageBases || []"
            :alt="product.name"
          />
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

        <div class="animate-shop-slide-up">
          <p v-if="product.category" class="mb-2 text-sm font-medium shop-brand-text">
            {{ product.category.name }}
          </p>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            {{ product.name }}
          </h1>
          <p class="mt-4 text-3xl font-bold shop-brand-text">
            {{ formatPrice(product.price) }}
            <span class="text-base font-normal text-slate-500">{{ product.currency || shop.currency }}</span>
          </p>
          <p v-if="product.has_addon" class="mt-2 text-sm text-slate-600">
            <span class="text-slate-500">خدمة اختيارية:</span>
            {{ product.addon_name }}
            <span class="shop-brand-text">(+{{ formatPrice(product.addon_price) }} {{ product.currency || shop.currency }})</span>
          </p>
          <p
            v-if="product.rental_duration"
            class="mt-2 inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-sm text-slate-700"
          >
            <i class="bi bi-clock shop-brand-text" aria-hidden="true" />
            <span><span class="text-slate-500">مدة الإيجار:</span> {{ product.rental_duration }}</span>
          </p>

          <div
            v-if="product.description"
            class="prose prose-slate mt-6 max-w-none text-sm leading-relaxed text-slate-600"
            v-html="descriptionHtml"
          />
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <ShopButton variant="primary" size="lg" class="flex-1" @click="requestAddToCart(product)">
              <i class="bi bi-cart-plus" aria-hidden="true" />
              أضف للسلة
            </ShopButton>
            <ShopButton variant="secondary" size="lg" @click="router.visit(route('shop.index'))">
              متابعة التسوّق
            </ShopButton>
          </div>
        </div>
      </div>
    </main>

    <ShopFooter :shop="shop" />

    <ShopAddonChoiceModal
      :product="addonModalProduct"
      :currency="shop.currency"
      @confirm="onAddonConfirm"
      @close="closeAddonModal"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ShopButton from '@/Components/Shop/ShopButton.vue';
import ShopFooter from '@/Components/Shop/ShopFooter.vue';
import ShopProductImageCarousel from '@/Components/Shop/ShopProductImageCarousel.vue';
import ShopAddonChoiceModal from '@/Components/Shop/ShopAddonChoiceModal.vue';
import { useShopCart } from '@/composables/useShopCart';
import { useShopAddToCart } from '@/composables/useShopAddToCart';
import { useShopStorageUrl } from '@/composables/useShopStorageUrl';

const props = defineProps({ product: Object, shop: Object });
const toast = useToast();
const { addItem } = useShopCart();

const { addonModalProduct, requestAddToCart, confirmAddonChoice, closeAddonModal } = useShopAddToCart(
  addItem,
  toast
);

const onAddonConfirm = (withAddon) => {
  confirmAddonChoice(withAddon);
  router.visit(route('shop.index'));
};

const { src: productImageSrc, src: storageSrc } = useShopStorageUrl(props.shop?.storageBases || []);

const logoFailed = ref(false);
const logoUrl = computed(() => {
  if (logoFailed.value) return props.shop?.logo_fallback || null;
  if (props.shop?.logo) return storageSrc({ image: props.shop.logo });
  return props.shop?.logo_fallback || null;
});
const onLogoError = () => {
  if (!logoFailed.value && props.shop?.logo_fallback) logoFailed.value = true;
};

const brandVars = computed(() => ({
  '--shop-primary': props.shop?.primary_color || '#4f46e5',
  '--shop-primary-dark': shadeColor(props.shop?.primary_color || '#4f46e5', -12),
}));

const pageTitle = computed(() => {
  const name = props.product?.name || 'منتج';
  const company = props.shop?.company_name || 'المتجر';
  return `${name} | ${company}`;
});

const pageDescription = computed(() => {
  const raw = props.product?.description || props.shop?.seo_description || props.shop?.tagline || '';
  const plain = String(raw).replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
  return plain.slice(0, 160) || pageTitle.value;
});

const ogImage = computed(() => galleryUrls.value[0] || props.shop?.logo_fallback || null);

const galleryPaths = computed(() => {
  const paths = [];
  const add = (p) => {
    if (!p) return;
    const s = String(p).replace(/^\//, '');
    if (!paths.includes(s)) paths.push(s);
  };
  add(props.product.image);
  (props.product.images || []).forEach(add);
  return paths;
});

const galleryUrls = computed(() => {
  if (props.product.images_urls?.length) {
    return [...props.product.images_urls];
  }
  return galleryPaths.value
    .map((path) => productImageSrc({ image: path }))
    .filter(Boolean);
});

const descriptionHtml = computed(() => (props.product.description || '').replace(/\n/g, '<br>'));

const formatPrice = (n) =>
  parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const youtubeEmbed = (url) => {
  if (!url) return '';
  const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/);
  return m ? `https://www.youtube.com/embed/${m[1]}` : url;
};

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
