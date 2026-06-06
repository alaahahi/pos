import { computed, ref, watch } from 'vue';

const STORAGE_KEY = 'shop-theme';
const shopPageCount = ref(0);

const theme = ref('light');

const readStoredTheme = () => {
  if (typeof window === 'undefined') return null;
  const saved = localStorage.getItem(STORAGE_KEY);
  return saved === 'dark' || saved === 'light' ? saved : null;
};

const readSystemTheme = () => {
  if (typeof window === 'undefined') return 'light';
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const syncDocumentClass = () => {
  if (typeof document === 'undefined') return;
  const active = shopPageCount.value > 0 && theme.value === 'dark';
  document.documentElement.classList.toggle('dark', active);
  document.documentElement.style.colorScheme = active ? 'dark' : 'light';
};

export const isShopDark = computed(() => theme.value === 'dark');

export function initShopTheme() {
  theme.value = readStoredTheme() ?? readSystemTheme();
  syncDocumentClass();
}

export function registerShopPage() {
  shopPageCount.value += 1;
  syncDocumentClass();
}

export function unregisterShopPage() {
  shopPageCount.value = Math.max(0, shopPageCount.value - 1);
  syncDocumentClass();
}

export function setShopTheme(mode) {
  if (mode !== 'dark' && mode !== 'light') return;
  theme.value = mode;
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem(STORAGE_KEY, mode);
  }
  syncDocumentClass();
}

export function toggleShopTheme() {
  setShopTheme(theme.value === 'dark' ? 'light' : 'dark');
}

watch(theme, syncDocumentClass);

if (typeof window !== 'undefined') {
  initShopTheme();
}

export function useShopTheme() {
  return {
    theme,
    isDark: isShopDark,
    setTheme: setShopTheme,
    toggleTheme: toggleShopTheme,
    registerShopPage,
    unregisterShopPage,
    initShopTheme,
  };
}
