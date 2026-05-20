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
              :src="currentImage"
              :alt="product.name"
              class="aspect-square w-full object-cover"
            />
          </div>
          <div
            v-if="product.images_urls?.length > 1"
            class="flex flex-wrap gap-2"
            role="list"
            aria-label="صور المنتج"
          >
            <button
              v-for="(img, i) in product.images_urls"
              :key="i"
              type="button"
              role="listitem"
              class="overflow-hidden rounded-xl border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
              :class="currentImage === img ? 'border-shop-600 ring-2 ring-shop-500/30' : 'border-transparent opacity-70 hover:opacity-100'"
              @click="currentImage = img"
            >
              <img :src="img" :alt="`صورة ${i + 1}`" class="h-16 w-16 object-cover sm:h-20 sm:w-20" />
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
            {{ formatPrice(product.price) }}
            <span class="text-base font-normal text-slate-500">{{ product.currency || shop.currency }}</span>
          </p>
          <div
            v-if="product.description"
            class="prose prose-slate mt-6 max-w-none text-sm leading-relaxed text-slate-600"
            v-html="descriptionHtml"
          />
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <ShopButton variant="primary" size="lg" class="flex-1" @click="addAndOpenCart">
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ShopButton from '@/Components/Shop/ShopButton.vue';
import { useShopCart } from '@/composables/useShopCart';

const props = defineProps({ product: Object, shop: Object });
const toast = useToast();
const { addItem } = useShopCart();

const currentImage = ref(
  props.product.image_url ||
    props.product.images_urls?.[0] ||
    '/dashboard-assets/img/placeholder.jpg'
);

const descriptionHtml = computed(() => (props.product.description || '').replace(/\n/g, '<br>'));

const formatPrice = (n) =>
  parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const youtubeEmbed = (url) => {
  if (!url) return '';
  const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/);
  return m ? `https://www.youtube.com/embed/${m[1]}` : url;
};

const addAndOpenCart = () => {
  addItem(props.product);
  toast.success('أُضيف إلى السلة');
  router.visit(route('shop.index'));
};
</script>
