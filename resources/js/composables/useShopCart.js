import { ref, computed, watch } from 'vue';

const STORAGE_KEY = 'shop_cart';

function loadCart() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
  } catch {
    return [];
  }
}

const items = ref(loadCart());

watch(items, (val) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(val));
}, { deep: true });

export function useShopCart() {
  const addItem = (product, qty = 1) => {
    const existing = items.value.find((i) => i.shop_product_id === product.id);
    if (existing) {
      existing.quantity += qty;
    } else {
      items.value.push({
        shop_product_id: product.id,
        name: product.name,
        price: parseFloat(product.price),
        image_url: product.image_url,
        quantity: qty,
      });
    }
  };

  const setQuantity = (productId, quantity) => {
    const item = items.value.find((i) => i.shop_product_id === productId);
    if (!item) return;
    if (quantity <= 0) {
      removeItem(productId);
    } else {
      item.quantity = quantity;
    }
  };

  const removeItem = (productId) => {
    items.value = items.value.filter((i) => i.shop_product_id !== productId);
  };

  const clearCart = () => {
    items.value = [];
  };

  const cartCount = computed(() => items.value.reduce((s, i) => s + i.quantity, 0));

  const apiItems = computed(() =>
    items.value.map((i) => ({
      shop_product_id: i.shop_product_id,
      quantity: i.quantity,
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
