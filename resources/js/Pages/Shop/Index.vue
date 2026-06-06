<template>
  <ShopLayout :shop="shop" :cart-count="cartCount" @open-mobile-cart="mobileCartOpen = true">
    <div class="lg:grid lg:grid-cols-[1fr_380px] lg:items-start lg:gap-8">
      <!-- Catalog -->
      <main class="min-w-0 space-y-5">
        <!-- Search & filters -->
        <div class="shop-card space-y-4 p-4">
          <div class="relative">
            <i class="bi bi-search absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input
              v-model="search"
              type="search"
              class="shop-input pr-10"
              placeholder="ابحث عن منتج..."
              aria-label="بحث في المنتجات"
              @keyup.enter="applyFilters"
            />
          </div>
          <ShopButton variant="secondary" size="sm" :loading="isNavigating" @click="applyFilters">
            <i class="bi bi-funnel" aria-hidden="true" />
            تطبيق البحث
          </ShopButton>
        </div>

        <ShopCategoryStrip
          :categories="categories"
          :selected-id="selectedCategory"
          :storage-bases="shop.storageBases || []"
          :currency="shop.currency"
          @select="filterCategory"
        />

        <!-- Products -->
        <ShopProductGridSkeleton v-if="isNavigating" />
        <ShopEmptyState
          v-else-if="!products.data?.length"
          title="لا توجد منتجات"
          description="جرّب فئة أخرى أو غيّر كلمات البحث"
          icon="bi bi-box-seam"
        />
        <div
          v-else
          class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3"
        >
          <ShopProductCard
            v-for="p in products.data"
            :key="p.id"
            :product="p"
            :currency="shop.currency"
            :storage-bases="shop.storageBases || []"
            @view="goProduct"
            @add="onAddToCart"
          />
        </div>

        <!-- Pagination -->
        <nav
          v-if="products.links?.length > 3"
          class="flex flex-wrap justify-center gap-1 pt-2"
          aria-label="تصفح الصفحات"
        >
          <template v-for="(link, i) in products.links" :key="i">
            <button
              v-if="link.url"
              type="button"
              class="min-w-[2.25rem] rounded-lg px-3 py-1.5 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
              :class="link.active ? 'bg-shop-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
              :disabled="!link.url"
              v-html="link.label"
              @click="router.get(link.url, {}, { preserveState: true })"
            />
          </template>
        </nav>
      </main>

      <!-- Desktop sticky cart -->
      <aside class="hidden lg:block">
        <div class="sticky top-24 animate-shop-slide-up">
          <ShopCartPanel
            :items="cartItems"
            :item-count="cartCount"
            :pricing="pricing"
            :pricing-loading="pricingLoading"
            :currency="shop.currency"
            :storage-bases="shop.storageBases || []"
            :coupon-code="couponCode"
            :phone="phone"
            :notes="notes"
            :phone-error="phoneError"
            :submitting="submitting"
            @update-quantity="setQuantity"
            @remove="removeItem"
            @update:coupon-code="couponCode = $event"
            @update:phone="phone = $event"
            @update:notes="notes = $event"
            @apply-coupon="applyCoupon"
            @submit="handleSubmit"
          />
        </div>
      </aside>
    </div>

    <!-- Mobile cart drawer -->
    <Teleport to="body">
      <Transition name="shop-drawer">
        <div
          v-if="mobileCartOpen"
          class="fixed inset-0 z-50 lg:hidden"
          role="dialog"
          aria-modal="true"
          aria-labelledby="cart-heading"
        >
          <div
            class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            aria-hidden="true"
            @click="mobileCartOpen = false"
          />
          <div class="absolute inset-y-0 right-0 w-full max-w-md p-3 animate-shop-slide-up">
            <ShopCartPanel
              show-close
              :items="cartItems"
              :item-count="cartCount"
              :pricing="pricing"
              :pricing-loading="pricingLoading"
              :currency="shop.currency"
              :storage-bases="shop.storageBases || []"
              :coupon-code="couponCode"
              :phone="phone"
              :notes="notes"
              :phone-error="phoneError"
              :submitting="submitting"
              @close="mobileCartOpen = false"
              @update-quantity="setQuantity"
              @remove="removeItem"
              @update:coupon-code="couponCode = $event"
              @update:phone="phone = $event"
              @update:notes="notes = $event"
              @apply-coupon="applyCoupon"
              @submit="handleSubmit"
            />
          </div>
        </div>
      </Transition>
    </Teleport>

    <ShopAddonChoiceModal
      :product="addonModalProduct"
      :currency="shop.currency"
      @confirm="onAddonConfirm"
      @close="closeAddonModal"
    />
  </ShopLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import ShopLayout from '@/Components/Shop/ShopLayout.vue';
import ShopAddonChoiceModal from '@/Components/Shop/ShopAddonChoiceModal.vue';
import ShopCategoryStrip from '@/Components/Shop/ShopCategoryStrip.vue';
import ShopButton from '@/Components/Shop/ShopButton.vue';
import ShopProductCard from '@/Components/Shop/ShopProductCard.vue';
import ShopProductGridSkeleton from '@/Components/Shop/ShopProductGridSkeleton.vue';
import ShopEmptyState from '@/Components/Shop/ShopEmptyState.vue';
import ShopCartPanel from '@/Components/Shop/ShopCartPanel.vue';
import { useShopCart } from '@/composables/useShopCart';
import { useShopAddToCart } from '@/composables/useShopAddToCart';
import { useShopCheckout } from '@/composables/useShopCheckout';

const props = defineProps({
  products: Object,
  categories: Array,
  filters: Object,
  shop: Object,
});

const defaultTagline =
  'تخطيط وتنسيق احتفالات صغيرة - حفلات استقبال المولود - أعياد ميلاد - كشف جنس المولود';

const pageTitle = computed(() => {
  if (props.shop?.seo_title) return props.shop.seo_title;
  const name = props.shop?.company_name || 'المتجر';
  return props.shop?.tagline ? `${name} | ${props.shop.tagline}` : name;
});

const pageDescription = computed(
  () => props.shop?.seo_description || props.shop?.tagline || defaultTagline
);

const pageKeywords = computed(() => props.shop?.seo_keywords || null);

const ogImage = computed(() => {
  if (props.shop?.logo) {
    const bases = props.shop.storageBases || [];
    if (bases.length) {
      return `${bases[0].replace(/\/$/, '')}/${String(props.shop.logo).replace(/^\//, '')}`;
    }
  }
  return props.shop?.logo_fallback || null;
});

const toast = useToast();
const { items: cartItems, addItem, setQuantity, removeItem, clearCart, cartCount, apiItems } = useShopCart();
const { addonModalProduct, requestAddToCart, confirmAddonChoice, closeAddonModal } = useShopAddToCart(
  addItem,
  toast,
  { onAdded: () => recalculate() }
);
const {
  pricing,
  pricingLoading,
  couponCode,
  phone,
  notes,
  phoneError,
  submitting,
  recalculate,
  applyCoupon,
  submitOrder,
} = useShopCheckout(apiItems, clearCart);

const mobileCartOpen = ref(false);
const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || null);
const isNavigating = ref(false);

router.on('start', () => { isNavigating.value = true; });
router.on('finish', () => { isNavigating.value = false; });

watch(
  () => props.filters?.category,
  (id) => {
    if (id) selectedCategory.value = id;
  }
);

const filterCategory = (id) => {
  selectedCategory.value = id;
  applyFilters();
};

const applyFilters = () => {
  router.get(
    route('shop.index'),
    { category: selectedCategory.value || undefined, search: search.value || undefined },
    { preserveState: true, preserveScroll: true }
  );
};

const goProduct = (id) => router.visit(route('shop.products.show', id));

const onAddToCart = (product) => {
  requestAddToCart(product);
};

const onAddonConfirm = (withAddon) => {
  confirmAddonChoice(withAddon);
};

const handleSubmit = async () => {
  const ok = await submitOrder();
  if (ok) mobileCartOpen.value = false;
};

onMounted(() => {
  if (apiItems.value.length) recalculate();
});
</script>

<style scoped>
.shop-drawer-enter-active,
.shop-drawer-leave-active {
  transition: opacity 0.2s ease;
}
.shop-drawer-enter-from,
.shop-drawer-leave-to {
  opacity: 0;
}
</style>
