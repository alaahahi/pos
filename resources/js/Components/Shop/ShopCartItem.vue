<template>
  <li class="flex gap-3 border-b border-slate-100 py-3 last:border-0">
    <img
      v-if="item.image_url"
      :src="item.image_url"
      :alt="item.name"
      class="h-14 w-14 shrink-0 rounded-lg object-cover bg-slate-100"
    />
    <div
      v-else
      class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"
      aria-hidden="true"
    >
      <i class="bi bi-image text-xl" />
    </div>
    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-medium text-slate-900">{{ item.name }}</p>
      <p class="text-xs text-slate-500">{{ formatPrice(item.price) }} × {{ item.quantity }}</p>
      <div class="mt-2 flex items-center justify-between gap-2">
        <ShopQuantityStepper
          :quantity="item.quantity"
          :aria-label="`كمية ${item.name}`"
          @update:quantity="$emit('update-quantity', item.shop_product_id, $event)"
        />
        <ShopButton variant="danger" size="sm" :aria-label="`حذف ${item.name}`" @click="$emit('remove', item.shop_product_id)">
          <i class="bi bi-trash" aria-hidden="true" />
        </ShopButton>
      </div>
    </div>
  </li>
</template>

<script setup>
import ShopButton from './ShopButton.vue';
import ShopQuantityStepper from './ShopQuantityStepper.vue';

defineProps({ item: { type: Object, required: true } });
defineEmits(['update-quantity', 'remove']);

const formatPrice = (n) => parseFloat(n).toLocaleString('en-US', { maximumFractionDigits: 2 });
</script>
