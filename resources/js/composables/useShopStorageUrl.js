/**
 * Resolve shop category/product image URLs (settings + public storefront).
 */
export function useShopStorageUrl(storageBases = []) {
  const bases = Array.isArray(storageBases) ? storageBases.filter(Boolean) : [];

  const pathFrom = (item) => {
    if (!item) return null;
    if (item.image) return String(item.image).replace(/^\//, '');
    const images = item.images;
    if (Array.isArray(images) && images[0]) {
      return String(images[0]).replace(/^\//, '');
    }
    return null;
  };

  const src = (item) => {
    if (item?.image_url) {
      return item.image_url;
    }
    const path = pathFrom(item);
    if (!path || !bases.length) {
      return null;
    }
    return `${bases[0].replace(/\/$/, '')}/${path}`;
  };

  const onError = (event, item, placeholder = null) => {
    const img = event?.target;
    if (!img) return;

    const path = pathFrom(item);
    if (path && bases.length) {
      for (const base of bases) {
        const alt = `${base.replace(/\/$/, '')}/${path}`;
        if (img.src !== alt) {
          img.src = alt;
          return;
        }
      }
    }

    if (placeholder && img.src !== placeholder) {
      img.src = placeholder;
      return;
    }

    img.style.display = 'none';
  };

  return { src, onError };
}
