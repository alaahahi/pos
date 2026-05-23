<template>
  <section
    class="shop-card flex max-h-[calc(100vh-7rem)] flex-col overflow-hidden"
    aria-labelledby="cart-heading"
  >
    <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
      <h2 id="cart-heading" class="text-base font-semibold text-slate-900">
        السلة
        <span
          v-if="itemCount"
          class="mr-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-shop-100 px-1.5 py-0.5 text-xs font-bold text-shop-700"
        >
          {{ itemCount }}
        </span>
      </h2>
      <button
        v-if="showClose"
        type="button"
        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-shop-500"
        aria-label="إغلاق السلة"
        @click="$emit('close')"
      >
        <i class="bi bi-x-lg" aria-hidden="true" />
      </button>
    </header>

    <div class="flex-1 overflow-y-auto px-4">
      <ShopEmptyState
        v-if="!items.length"
        title="السلة فارغة"
        description="أضف منتجات من المتجر لبدء الطلب"
        icon="bi bi-cart3"
        class="!border-0 !bg-transparent py-10 !shadow-none"
      />
      <ul v-else class="list-none p-0 m-0">
        <ShopCartItem
          v-for="item in items"
          :key="item.shop_product_id"
          :item="item"
          :storage-bases="storageBases"
          @update-quantity="(id, q) => $emit('update-quantity', id, q)"
          @remove="(id) => $emit('remove', id)"
        />
      </ul>
    </div>

    <footer v-if="items.length" class="border-t border-slate-100 bg-slate-50/80 p-4 space-y-3">
      <ShopCartSummary :pricing="pricing" :currency="currency" :loading="pricingLoading" />

      <div class="flex gap-2">
        <input
          :value="couponCode"
          type="text"
          placeholder="كود الخصم"
          class="shop-input flex-1"
          aria-label="كود الخصم"
          @input="$emit('update:couponCode', $event.target.value)"
          @keyup.enter="$emit('apply-coupon')"
        />
        <ShopButton variant="secondary" size="md" :loading="pricingLoading" @click="$emit('apply-coupon')">
          تطبيق
        </ShopButton>
      </div>

      <ShopInput
        id="customer-phone"
        :model-value="phone"
        type="tel"
        label="رقم الهاتف"
        placeholder="07XXXXXXXXX"
        required
        :error="phoneError"
        hint="10–15 رقم"
        @update:model-value="$emit('update:phone', $event)"
      />

      <div>
        <label for="customer-notes" class="shop-label">ملاحظات (اختياري)</label>
        <textarea
          id="customer-notes"
          :value="notes"
          rows="2"
          class="shop-input resize-none"
          placeholder="أي تفاصيل إضافية للطلب..."
          @input="$emit('update:notes', $event.target.value)"
        />
      </div>

      <ShopButton
        variant="success"
        size="lg"
        block
        :loading="submitting"
        loading-text="جاري الإرسال..."
        @click="$emit('submit')"
      >
        <i class="bi bi-whatsapp text-lg" aria-hidden="true" />
        إتمام عبر واتساب
      </ShopButton>
    </footer>
  </section>
</template>

<script setup>
import ShopButton from './ShopButton.vue';
import ShopInput from './ShopInput.vue';
import ShopCartItem from './ShopCartItem.vue';
import ShopCartSummary from './ShopCartSummary.vue';
import ShopEmptyState from './ShopEmptyState.vue';

defineProps({
  items: { type: Array, default: () => [] },
  itemCount: { type: Number, default: 0 },
  pricing: Object,
  pricingLoading: Boolean,
  currency: { type: String, default: 'USD' },
  couponCode: String,
  phone: String,
  notes: String,
  phoneError: String,
  submitting: Boolean,
  showClose: { type: Boolean, default: false },
  storageBases: { type: Array, default: () => [] },
});

defineEmits([
  'close',
  'update-quantity',
  'remove',
  'apply-coupon',
  'submit',
  'update:couponCode',
  'update:phone',
  'update:notes',
]);
</script>
