import { ref } from 'vue';

/**
 * @param {Function} addItem - useShopCart().addItem
 * @param {import('vue-toastification').Toast} toast
 * @param {{ onAdded?: () => void }} [options]
 */
export function useShopAddToCart(addItem, toast, options = {}) {
  const addonModalProduct = ref(null);

  const finishAdd = (product, withAddon) => {
    addItem(product, 1, { withAddon });
    const label = withAddon && product.has_addon
      ? `أُضيف ${product.name} مع ${product.addon_name}`
      : `أُضيف ${product.name} إلى السلة`;
    toast.success(label, { timeout: 2500 });
    options.onAdded?.();
  };

  const requestAddToCart = (product) => {
    if (product?.has_addon) {
      addonModalProduct.value = product;
      return;
    }
    finishAdd(product, false);
  };

  const confirmAddonChoice = (withAddon) => {
    const product = addonModalProduct.value;
    if (!product) return;
    addonModalProduct.value = null;
    finishAdd(product, withAddon);
  };

  const closeAddonModal = () => {
    addonModalProduct.value = null;
  };

  return {
    addonModalProduct,
    requestAddToCart,
    confirmAddonChoice,
    closeAddonModal,
  };
}
