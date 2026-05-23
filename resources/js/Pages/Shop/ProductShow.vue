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

        <!-- Info -->
        <div class="animate-shop-slide-up">
          <p v-if="product.category" class="mb-2 text-sm font-medium text-shop-600">
            {{ product.category.name }}
          </p>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            {{ product.name }}
          </h1>
          <p class="mt-4 text-3xl font-bold text-shop-600">
            {{ formatPrice(product.price) }}
            <span class="text-base font-normal text-slate-500">{{ product.currency || shop.currency }}</span>
          </p>
          <p v-if="product.has_addon" class="mt-2 text-sm text-slate-600">
            <span class="text-slate-500">خدمة اختيارية:</span>
            {{ product.addon_name }}
            <span class="text-shop-600">(+{{ formatPrice(product.addon_price) }} {{ product.currency || shop.currency }})</span>
          </p>
          <p
            v-if="product.rental_duration"
            class="mt-2 inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-sm text-slate-700"
          >
            <i class="bi bi-clock text-shop-600" aria-hidden="true" />
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

    <ShopAddonChoiceModal
      :product="addonModalProduct"
      :currency="shop.currency"
      @confirm="onAddonConfirm"
      @close="closeAddonModal"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ShopButton from '@/Components/Shop/ShopButton.vue';
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

const { src: productImageSrc } = useShopStorageUrl(props.shop?.storageBases || []);

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
</script>
