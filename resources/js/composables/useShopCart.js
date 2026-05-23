import { ref, computed, watch } from 'vue';

const STORAGE_KEY = 'shop_cart';

export function shopCartKey(productId, withAddon = false) {
  return `${productId}:${withAddon ? '1' : '0'}`;
}

function loadCart() {
  try {
    const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    return raw.map((item) => ({
      ...item,
      cart_key: item.cart_key || shopCartKey(item.shop_product_id, !!item.with_addon),
      with_addon: !!item.with_addon,
    }));
  } catch {
    return [];
  }
}

const items = ref(loadCart());

watch(items, (val) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(val));
}, { deep: true });

export function useShopCart() {
  const addItem = (product, qty = 1, { withAddon = false } = {}) => {
    const useAddon = withAddon && product.has_addon;
    const cartKey = shopCartKey(product.id, useAddon);
    const unitPrice = useAddon
      ? parseFloat(product.price) + parseFloat(product.addon_price)
      : parseFloat(product.price);

    const existing = items.value.find((i) => i.cart_key === cartKey);
    if (existing) {
      existing.quantity += qty;
    } else {
      items.value.push({
        cart_key: cartKey,
        shop_product_id: product.id,
        name: product.name,
        price: unitPrice,
        base_price: parseFloat(product.price),
        with_addon: useAddon,
        addon_name: useAddon ? product.addon_name : null,
        addon_price: useAddon ? parseFloat(product.addon_price) : null,
        image_url: product.image_url,
        image: product.image,
        images: product.images,
        quantity: qty,
      });
    }
  };

  const setQuantity = (cartKey, quantity) => {
    const item = items.value.find((i) => i.cart_key === cartKey);
    if (!item) return;
    if (quantity <= 0) {
      removeItem(cartKey);
    } else {
      item.quantity = quantity;
    }
  };

  const removeItem = (cartKey) => {
    items.value = items.value.filter((i) => i.cart_key !== cartKey);
  };

  const clearCart = () => {
    items.value = [];
  };

  const cartCount = computed(() => items.value.reduce((s, i) => s + i.quantity, 0));

  const apiItems = computed(() =>
    items.value.map((i) => ({
      shop_product_id: i.shop_product_id,
      quantity: i.quantity,
      with_addon: !!i.with_addon,
    }))
  );

  return {
    items,
    addItem,
    setQuantity,
    removeItem,
    clearCart,
    cartCount,
    apiItems,
  };
}
