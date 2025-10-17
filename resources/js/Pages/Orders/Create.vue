<template>
  <AuthenticatedLayout :translations="translations">
    <!-- POS Header -->
    <div class="pos-header">
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="pos-title">
          <i class="bi bi-shop"></i>
          {{ translations.invoice }}
        </h2>
        <div class="pos-actions">
          <button @click="showCashModal = true" class="btn btn-outline-info">
            <i class="bi bi-cash-coin"></i>
            إدارة الكاش
          </button>
          <button @click="clearAll" class="btn btn-outline-danger">
            <i class="bi bi-trash"></i>
            مسح الكل
          </button>
          <button @click="toggleFullscreen" class="btn btn-outline-primary">
            <i class="bi bi-fullscreen"></i>
            ملء الشاشة
          </button>
        </div>
      </div>
    </div>

    <!-- Main POS Layout -->
    <div class="pos-container">
      <!-- Left Panel - Products -->
      <div class="pos-products-panel">
        <!-- Search Bar -->
        <div class="pos-search-section">
          <div class="search-input-group">
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              class="form-control pos-search-input"
              :placeholder="translations.search_by_name_or_barcode"
              @input="searchProducts"
              @keyup.enter="handleSearchEnter"
            />
            <button class="btn btn-primary search-btn" @click="searchProducts">
              <i class="bi bi-search"></i>
            </button>
          </div>
          
          <!-- Barcode Scanner -->
          <div class="barcode-section">
            <input
              ref="barcodeInput"
              v-model="barcode"
              type="text"
              class="form-control barcode-input"
              :placeholder="translations.barcode"
              @keyup="findBarcode"
              @keyup.enter="findBarcode"
            />
            <i class="bi bi-upc-scan barcode-icon"></i>
          </div>
        </div>

        <!-- Product Filters -->
        <div class="pos-filters">
          <button 
            @click="filterByType('featured')"
            :class="['btn', 'filter-btn', { 'active': selectedFilter === 'featured' }]"
          >
            <i class="bi bi-star-fill"></i>
            المميزة
          </button>
          <button 
            @click="filterByType('best_selling')"
            :class="['btn', 'filter-btn', { 'active': selectedFilter === 'best_selling' }]"
          >
            <i class="bi bi-trophy-fill"></i>
            الأكثر مبيعاً
          </button>
        </div>

        <!-- Product Categories (if available) -->
        <div class="pos-categories" v-if="categories && categories.length">
          <button 
            v-for="category in categories" 
            :key="category.id"
            @click="filterByCategory(category.id)"
            :class="['btn', 'category-btn', { 'active': selectedCategory === category.id }]"
          >
            {{ category.name }}
          </button>
          <button 
            @click="clearCategoryFilter"
            :class="['btn', 'category-btn', { 'active': selectedCategory === null }]"
          >
            الكل
          </button>
        </div>

        <!-- Products Grid -->
        <div class="pos-products-grid">
          <div 
            v-for="product in filteredProducts" 
            :key="product.id"
            @click="addProductToCart(product)"
            :class="['product-card', { 'out-of-stock': product.quantity <= 0 }]"
          >
            <div class="product-image">
              <img 
                :src="product.image ?`/public/storage/${product.image}` : '/dashboard-assets/img/product-placeholder.svg'" 
                :alt="product.name"
                @error="handleImageError"
              />
              <div class="product-stock-badge" v-if="product.quantity <= 5">
                {{ product.quantity }}
              </div>
            </div>
            <div class="product-info">
              <h6 class="product-name">{{ product.name }}</h6>
              <p class="product-model">{{ product.model }}</p>
              <div class="product-price">
                {{ defaultCurrency }} {{ product.price }}
              </div>
              <div class="product-stock">
                المخزون: {{ product.quantity }}
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loadingProducts" class="text-center p-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">جاري التحميل...</span>
          </div>
        </div>

        <!-- No Products Found -->
        <div v-if="!loadingProducts && filteredProducts.length === 0" class="no-products">
          <i class="bi bi-box-seam"></i>
          <p>لا توجد منتجات</p>
        </div>
      </div>

      <!-- Right Panel - Cart & Customer -->
      <div class="pos-cart-panel">
        <!-- Customer Selection -->
        <div class="pos-customer-section">
          <label class="form-label text-center w-100">{{ translations.client }}</label>
                    <vue-select
                      v-model="selectedCustomer"
                      :options="customers"
                      label="name"
                      track-by="id"
                      :reduce="customer => customer"
                      @input="selectCustomer"
                      :placeholder="translations.select_customer"
            class="customer-select"
                    />
                    <InputError :message="form.errors.customer_id" />
                  </div>

        <!-- Cart Items -->
        <div class="pos-cart-section">
          <div class="cart-header">
            <h5 class="text-center w-100">
              <i class="bi bi-cart3"></i>
              عربة التسوق
              <span class="cart-count" v-if="invoiceItems.length">{{ invoiceItems.length }}</span>
            </h5>
            <button 
              v-if="invoiceItems.length" 
              @click="clearCart" 
              class="btn btn-sm btn-outline-danger"
            >
              <i class="bi bi-trash"></i>
            </button>
                </div>

          <!-- Cart Items List -->
          <div class="cart-items">
            <div 
              v-for="(item, index) in invoiceItems" 
              :key="index"
              class="cart-item"
            >
              <div class="cart-item-info">
                <h6 class="item-name">{{ getProductName(item.product_id) }}</h6>
                <p class="item-model">{{ getProductModel(item.product_id) }}</p>
                </div>

              <div class="cart-item-controls">
                <div class="quantity-controls">
                  <button 
                    @click="decreaseQuantity(index)" 
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="item.quantity <= 1"
                  >
                    <i class="bi bi-dash"></i>
                  </button>
                            <input 
                    v-model.number="item.quantity" 
                              type="number" 
                              min="1" 
                    class="form-control quantity-input"
                              @input="check_stock(item)" 
                            />
                  <button 
                    @click="increaseQuantity(index)" 
                    class="btn btn-sm btn-outline-secondary"
                  >
                    <i class="bi bi-plus"></i>
                  </button>
                </div>
                
                <div class="price-controls">
                            <input 
                    v-model.number="item.price" 
                              type="number" 
                              min="0" 
                    step="0.01"
                    class="form-control price-input"
                  />
                </div>
                
                <div class="item-total">
                  {{ defaultCurrency }} {{ (item.quantity * item.price).toFixed(2) }}
                </div>
                
                            <button 
                              @click="removeItem(index)"
                  class="btn btn-sm btn-danger remove-btn"
                >
                  <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
          </div>

          <!-- Empty Cart Message -->
          <div v-if="invoiceItems.length === 0" class="empty-cart">
            <i class="bi bi-cart-x"></i>
            <p class="text-center">عربة التسوق فارغة</p>
            <small class="text-center">اضغط على المنتجات لإضافتها</small>
          </div>
        </div>

        <!-- Cart Summary -->
        <div class="pos-summary-section" v-if="invoiceItems.length > 0">
          <div class="summary-row">
            <span>عدد الأصناف:</span>
            <span>{{ invoiceItems.length }}</span>
          </div>
          <div class="summary-row">
            <span>إجمالي الكمية:</span>
            <span>{{ totalQuantity }}</span>
                  </div>
          <div class="summary-row total-row">
            <span>الإجمالي:</span>
            <span class="total-amount">{{ defaultCurrency }} {{ totalAmount.toFixed(2) }}</span>
                  </div>
                </div>

        <!-- Action Buttons -->
        <div class="pos-actions-section" v-if="invoiceItems.length > 0 && selectedCustomer">
          <button 
            @click="openConfirmModal" 
            class="btn btn-success btn-lg checkout-btn"
            :disabled="show_loader"
          >
            <i class="bi bi-credit-card" v-if="!show_loader"></i>
                    <span class="spinner-border spinner-border-sm" v-if="show_loader"></span>
            {{ translations.continue }}
          </button>
          
          <!-- Quick Actions -->
          <div class="quick-actions">
            <button @click="holdOrder" class="btn btn-outline-warning">
              <i class="bi bi-pause-circle"></i>
              تعليق
            </button>
            <button @click="printReceipt" class="btn btn-outline-info">
              <i class="bi bi-printer"></i>
              طباعة
                  </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirm Modal -->
      <ModalConfirmOrderAndPay 
        :translations="translations" 
        :show="ShowModalConfirmOrderAndPay" 
        :total="totalAmount" 
        :defaultCurrency="defaultCurrency"
        @close="ShowModalConfirmOrderAndPay = false" 
        @confirm="saveInvoice"
      />

    <!-- Keyboard Shortcuts Help -->
    <div class="keyboard-shortcuts" v-if="showShortcuts">
      <div class="shortcuts-content">
        <h6>اختصارات لوحة المفاتيح</h6>
        <div class="shortcut-item">
          <kbd>F1</kbd> - البحث
        </div>
        <div class="shortcut-item">
          <kbd>F2</kbd> - الباركود
        </div>
        <div class="shortcut-item">
          <kbd>F9</kbd> - الدفع
        </div>
        <div class="shortcut-item">
          <kbd>Esc</kbd> - مسح الكل
        </div>
      </div>
    </div>

    <!-- Cash Management Modal -->
    <div v-if="showCashModal" class="modal-overlay" @click="showCashModal = false">
      <div class="modal-content cash-modal" @click.stop>
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-cash-coin"></i>
            إدارة الكاش
          </h5>
          <button type="button" class="btn-close" @click="showCashModal = false">
            <i class="bi bi-x"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <!-- Cash Summary -->
          <div class="cash-summary mb-4">
            <div class="row">
              <div class="col-md-6">
                <div class="cash-stat-card">
                  <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                  </div>
                  <div class="stat-info">
                    <h6>إجمالي المنتجات</h6>
                    <span class="stat-value">{{ cashInfo.totalProducts }}</span>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="cash-stat-card">
                  <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                  </div>
                  <div class="stat-info">
                    <h6>إجمالي القيمة</h6>
                    <span class="stat-value">{{ formatPrice(cashInfo.totalValue) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Products List -->
          <div class="cash-products">
            <h6 class="mb-3">
              <i class="bi bi-list-ul"></i>
              المنتجات في الكاش
            </h6>
            
            <div v-if="cashInfo.products.length === 0" class="empty-cash">
              <i class="bi bi-inbox"></i>
              <p>لا توجد منتجات في الكاش</p>
            </div>
            
            <div v-else class="cash-products-list">
              <div 
                v-for="item in cashInfo.products" 
                :key="item.product_id"
                class="cash-product-item"
              >
                <div class="product-info">
                  <h6 class="product-name">{{ getProductName(item.product_id) }}</h6>
                  <small class="text-muted">الكمية: {{ item.quantity }} × {{ formatPrice(item.price) }}</small>
                </div>
                <div class="product-actions">
                  <button 
                    class="btn btn-sm btn-outline-danger"
                    @click="removeProductFromCart(item.product_id)"
                    title="حذف المنتج"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="showCashModal = false">
            إغلاق
          </button>
          <button 
            type="button" 
            class="btn btn-danger" 
            @click="clearAllCash"
            :disabled="cashInfo.products.length === 0"
          >
            <i class="bi bi-trash"></i>
            مسح الكاش بالكامل
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import VueSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import axios from "axios";
import ModalConfirmOrderAndPay from '@/Components/ModalConfirmOrderAndPay.vue';
import debounce from 'lodash/debounce';
import { useToast } from "vue-toastification";
import Swal from 'sweetalert2';

let toast = useToast();

// Refs
let show_loader = ref(false);
let barcode = ref("");
let searchQuery = ref("");
let ShowModalConfirmOrderAndPay = ref(false);
let loadingProducts = ref(false);
let selectedCategory = ref(null);
let selectedFilter = ref('featured');
let showShortcuts = ref(false);
let showCashModal = ref(false);
let cashInfo = ref({
  totalProducts: 0,
  totalValue: 0,
  products: []
});

// Template refs
const searchInput = ref(null);
const barcodeInput = ref(null);

const props = defineProps({
  products: Array,
  customers: Array,
  defaultCustomer: Object,
  translations: Object,
  defaultCurrency: String,
  categories: Array,
});

const selectedCustomer = ref(props.defaultCustomer);
const form = useForm({
  customer_id: props.defaultCustomer?.id || null,
  total_amount: 0,
});

const invoiceItems = reactive([]);
const filteredProducts = ref(props.products.filter(product => product.is_featured));

// Computed Properties
const totalAmount = computed(() => {
  return invoiceItems.reduce((total, item) => total + (item.quantity * item.price), 0);
});

const totalQuantity = computed(() => {
  return invoiceItems.reduce((total, item) => total + item.quantity, 0);
});

// Watchers
watch(totalAmount, (newTotal) => {
  form.total_amount = newTotal;
});

watch(invoiceItems, () => {
  updateCashInfo();
}, { deep: true });

// Remove the watcher to avoid conflicts with @input
// watch(searchQuery, debounce(() => {
//   searchProducts();
// }, 300));

// Methods
const selectCustomer = (value) => {
  selectedCustomer.value = value;
  form.customer_id = value ? value.id : null;
};

const addProductToCart = (product) => {
  if (product.quantity <= 0) {
    toast.warning("المنتج غير متوفر في المخزون", {
      timeout: 3000,
      position: "bottom-right",
      rtl: true
    });
    return;
  }

  const existingItem = invoiceItems.find(item => item.product_id === product.id);
  
  if (existingItem) {
    if (existingItem.quantity < product.quantity) {
      existingItem.quantity += 1;
    } else {
      toast.warning("لا توجد كمية كافية في المخزون", {
        timeout: 3000,
        position: "bottom-right",
        rtl: true
      });
    }
  } else {
    invoiceItems.push({
      product_id: product.id,
      quantity: 1,
      price: product.price,
    });
  }
};

const removeItem = (index) => {
  invoiceItems.splice(index, 1);
};

const removeProductFromCart = (productId) => {
  const index = invoiceItems.findIndex(item => item.product_id === productId);
  if (index !== -1) {
    invoiceItems.splice(index, 1);
    toast.success("تم حذف المنتج من الكاش", {
      timeout: 2000,
      position: "bottom-right",
      rtl: true
    });
  }
};

const clearAllCash = () => {
  if (invoiceItems.length === 0) return;
  
  Swal.fire({
    title: 'تأكيد الحذف',
    text: 'هل أنت متأكد من حذف جميع المنتجات من الكاش؟',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'نعم، احذف الكل',
    cancelButtonText: 'إلغاء'
  }).then((result) => {
    if (result.isConfirmed) {
      clearCart();
      toast.success("تم حذف جميع المنتجات من الكاش", {
        timeout: 2000,
        position: "bottom-right",
        rtl: true
      });
    }
  });
};

const updateCashInfo = () => {
  cashInfo.value = {
    totalProducts: invoiceItems.reduce((total, item) => total + item.quantity, 0),
    totalValue: invoiceItems.reduce((total, item) => total + (item.quantity * item.price), 0),
    products: [...invoiceItems]
  };
};

const formatPrice = (price, currency = 'IQD') => {
  return parseFloat(price || 0).toFixed(2) + ' ' + currency;
};

const increaseQuantity = (index) => {
  const item = invoiceItems[index];
  const product = props.products.find(p => p.id === item.product_id);
  
  if (item.quantity < product.quantity) {
    item.quantity += 1;
  } else {
    toast.warning("لا توجد كمية كافية في المخزون", {
      timeout: 3000,
      position: "bottom-right",
      rtl: true
    });
  }
};

const decreaseQuantity = (index) => {
  const item = invoiceItems[index];
  if (item.quantity > 1) {
    item.quantity -= 1;
  }
};

const clearCart = () => {
  invoiceItems.splice(0);
};

const clearAll = () => {
  clearCart();
  selectedCustomer.value = null;
  form.customer_id = null;
  searchQuery.value = "";
  barcode.value = "";
  selectedFilter.value = 'featured';
  selectedCategory.value = null;
  filteredProducts.value = props.products.filter(product => product.is_featured);
};

const searchProducts = () => {
  console.log('Search query:', searchQuery.value);
  console.log('Available products:', props.products.length);
  
  if (!searchQuery.value.trim()) {
    // Apply current filter when search is cleared
    filterByType(selectedFilter.value);
    return;
  }

  const query = searchQuery.value.toLowerCase();
  let baseProducts = [];
  
  // Apply current filter first
  if (selectedFilter.value === 'featured') {
    baseProducts = props.products.filter(product => product.is_featured);
  } else if (selectedFilter.value === 'best_selling') {
    baseProducts = props.products.filter(product => product.is_best_selling);
  } else {
    // If no filter is selected, search in all products
    baseProducts = [...props.products];
  }
  
  console.log('Base products after filter:', baseProducts.length);
  
  // Then apply search
  const searchResults = baseProducts.filter(product => {
    const nameMatch = product.name && product.name.toLowerCase().includes(query);
    const modelMatch = product.model && product.model.toLowerCase().includes(query);
    const barcodeMatch = product.barcode && product.barcode.toLowerCase().includes(query);
    
    console.log(`Product: ${product.name}, Name match: ${nameMatch}, Model match: ${modelMatch}, Barcode match: ${barcodeMatch}`);
    
    return nameMatch || modelMatch || barcodeMatch;
  });
  
  console.log('Search results:', searchResults.length);
  filteredProducts.value = searchResults;
};

const handleSearchEnter = () => {
  if (filteredProducts.value.length === 1) {
    addProductToCart(filteredProducts.value[0]);
    searchQuery.value = "";
    filterByType(selectedFilter.value);
  }
};

const filterByCategory = (categoryId) => {
  selectedCategory.value = categoryId;
  // Implement category filtering logic here
  // This would require category_id in products
};

const clearCategoryFilter = () => {
  selectedCategory.value = null;
  filterByType(selectedFilter.value);
};

const filterByType = (type) => {
  selectedFilter.value = type;
  
  if (type === 'featured') {
    filteredProducts.value = props.products.filter(product => product.is_featured);
  } else if (type === 'best_selling') {
    filteredProducts.value = props.products.filter(product => product.is_best_selling);
  }
};

const getProductName = (productId) => {
  const product = props.products.find(p => p.id === productId);
  return product ? product.name : '';
};

const getProductModel = (productId) => {
  const product = props.products.find(p => p.id === productId);
  return product ? product.model : '';
};

const handleImageError = (event) => {
  event.target.src = '/dashboard-assets/img/product-placeholder.svg';
};

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
  } else {
    document.exitFullscreen();
  }
};

const holdOrder = () => {
  // Implement hold order functionality
  toast.info("تم تعليق الطلب", {
    timeout: 3000,
    position: "bottom-right",
    rtl: true
  });
};

const printReceipt = () => {
  // Implement print receipt functionality
  window.print();
};

// Existing methods (updated)
const updatePrice = (item) => {
  const selectedProduct = props.products.find(p => p.id === item.product_id);
   if (selectedProduct) {
    item.price = selectedProduct.price;
  }
};

const openConfirmModal = () => {
  ShowModalConfirmOrderAndPay.value = true;
};

const saveInvoice = async (event) => {
  show_loader.value = true;
 
  const invoiceData = {
    total_amount: form.total_amount,
    total_paid: event.amountDollar ?? 0,
    customer_id: form.customer_id,
    date: event.date,
    notes: event.notes,
    discount_amount: event.discount_amount ?? 0,
    discount_rate: event.discount_rate ?? 0,
    items: invoiceItems.map(i => ({
      product_id: i.product_id,
      quantity: i.quantity,
      price: i.price,
    })),
  };

  try {
    const response = await axios.post('/api/createOrder', invoiceData);
    if (response.status === 200 || response.status === 201) {
      let { id, order_id } = response.data;
      
      toast.success("تم حفظ الفاتورة بنجاح", {
        timeout: 3000,
        position: "bottom-right",
        rtl: true
      });

      if(event.printInvoice){
        window.open(`/api/getIndexAccountsSelas?print=2&transactions_id=${id}&order_id=${order_id}`, '_blank');
      }
      
      // Clear form after successful save but keep customer
      clearCart();
      searchQuery.value = "";
      barcode.value = "";
      filteredProducts.value = [...props.products];
    }
  } catch (error) {
     toast.error("خطأ أثناء حفظ الفاتورة", {
      timeout: 5000,
      position: "bottom-right",
      rtl: true 
    });
  } finally {
    show_loader.value = false;
    ShowModalConfirmOrderAndPay.value = false;
  }
};

const findBarcode = debounce(() => {
  if (!barcode.value) return;

  // Search only in current products
  const product = props.products.find(p => p.barcode === barcode.value);
  if (product) {
    addProductToCart(product);
    barcode.value = "";
    
    // Focus back to barcode input
    nextTick(() => {
      barcodeInput.value?.focus();
    });
  } else {
    toast.warning("المنتج غير موجود في القائمة الحالية", {
      timeout: 3000,
      position: "bottom-right",
      rtl: true
    });
  }
}, 500);

const check_stock = async (item) => {
  try {
    const response = await axios.get(`/api/check-stock/${item.product_id}`);

    if (item.quantity > response.data.available_quantity) {
      toast.warning(`الكمية المتوفرة فقط: ${response.data.available_quantity}`, {
        timeout: 3000,
          position: "bottom-right",
          rtl: true
      });

      item.quantity = response.data.available_quantity;
    }
  } catch (error) {
    console.error("خطأ عند التحقق من المخزون:", error);
  }
};

// Keyboard shortcuts
const handleKeydown = (event) => {
  // F1 - Focus search
  if (event.key === 'F1') {
    event.preventDefault();
    searchInput.value?.focus();
  }
  
  // F2 - Focus barcode
  if (event.key === 'F2') {
    event.preventDefault();
    barcodeInput.value?.focus();
  }
  
  // F9 - Open payment modal
  if (event.key === 'F9' && invoiceItems.length > 0 && selectedCustomer.value) {
    event.preventDefault();
    openConfirmModal();
  }
  
  // Escape - Clear all
  if (event.key === 'Escape') {
    event.preventDefault();
    clearAll();
  }
  
  // F12 - Toggle shortcuts help
  if (event.key === 'F12') {
    event.preventDefault();
    showShortcuts.value = !showShortcuts.value;
  }
};

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  
  // Focus barcode input on load
  nextTick(() => {
    barcodeInput.value?.focus();
  });
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
/* POS Specific Styles */
.pos-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 1.5rem;
  margin-bottom: 1rem;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.pos-title {
  margin: 0;
  font-weight: 600;
}

.pos-container {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 1.5rem;
  height: calc(100vh - 200px);
  min-height: 600px;
}

/* Products Panel */
.pos-products-panel {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.pos-search-section {
  margin-bottom: 1.5rem;
}

.search-input-group {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.pos-search-input {
  border-radius: 25px;
  border: 2px solid #e9ecef;
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.pos-search-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.search-btn {
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.barcode-section {
  position: relative;
}

.barcode-input {
  border-radius: 25px;
  border: 2px solid #28a745;
  padding: 0.75rem 1.5rem 0.75rem 3rem;
  font-size: 1.1rem;
  background-color: #f8fff9;
}

.barcode-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #28a745;
  font-size: 1.2rem;
}

.pos-filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.filter-btn {
  border-radius: 20px;
  padding: 0.5rem 1rem;
  border: 2px solid #e9ecef;
  background: white;
  color: #6c757d;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
}

.filter-btn:hover {
  background: #e9ecef;
  border-color: #adb5bd;
  color: #495057;
}

.filter-btn.active {
  background: #007bff;
  border-color: #007bff;
  color: white;
}

.filter-btn i {
  font-size: 1rem;
}

.pos-categories {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.category-btn {
  border-radius: 20px;
  padding: 0.5rem 1rem;
  border: 2px solid #e9ecef;
  background: white;
  color: #6c757d;
  transition: all 0.3s ease;
}

.category-btn.active,
.category-btn:hover {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.pos-products-grid {
  padding:5px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  overflow-y: auto;
  flex: 1;
  padding-right: 0.5rem;
}

.product-card {
  background: white;
  border: 2px solid #e9ecef;
  border-radius: 15px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.product-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.product-card.out-of-stock {
  opacity: 0.6;
  cursor: not-allowed;
}

.product-card.out-of-stock:hover {
  transform: none;
  border-color: #dc3545;
}

.product-image {
  position: relative;
  margin-bottom: 0.75rem;
}

.product-image img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 10px;
}

.product-stock-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #dc3545;
  color: white;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: bold;
}

.product-info {
  text-align: center;
}

.product-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #2c3e50;
  font-size: 0.9rem;
  line-height: 1.2;
}

.product-model {
  color: #6c757d;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
}

.product-price {
  font-weight: bold;
  color: #28a745;
  font-size: 1.1rem;
  margin-bottom: 0.25rem;
}

.product-stock {
  font-size: 0.8rem;
  color: #6c757d;
}

/* Cart Panel */
.pos-cart-panel {
  background: white;
  border-radius: 15px;
  padding: 0.5rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 200px);
}

.pos-customer-section {
  margin-bottom: 1.5rem;
}

.customer-select {
  border-radius: 10px;
}

.pos-cart-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.cart-header {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
}

.cart-header h5 {
  margin: 0;
}

.cart-count {
  background: #667eea;
  color: white;
  border-radius: 50%;
  padding: 0.25rem 0.5rem;
  font-size: 0.8rem;
  margin-left: 0.5rem;
}

.cart-items {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 1rem;
}

.cart-item {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  border: 1px solid #e9ecef;
}

.cart-item-info {
  margin-bottom: 0.75rem;
}

.item-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
  font-size: 0.9rem;
}

.item-model {
  color: #6c757d;
  font-size: 0.8rem;
  margin: 0;
}

.cart-item-controls {
  display: grid;
  grid-template-columns: 1fr 80px 80px 30px;
  gap: 0.5rem;
  align-items: center;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.quantity-input {
  width: 60px;
  text-align: center;
  border-radius: 5px;
  border: 1px solid #ced4da;
  padding: 0.25rem;
  color: #212529;
  background-color: #fff;
}

.quantity-input:focus {
  color: #212529;
  background-color: #fff;
  border-color: #667eea;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.price-input {
  border-radius: 5px;
  border: 1px solid #ced4da;
  padding: 0.25rem;
  text-align: center;
  color: #212529;
  background-color: #fff;
}

.price-input:focus {
  color: #212529;
  background-color: #fff;
  border-color: #667eea;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.item-total {
  font-weight: bold;
  color: #28a745;
  text-align: center;
  font-size: 0.9rem;
}

.remove-btn {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.empty-cart {
  text-align: center;
  color: #6c757d;
  padding: 2rem;
}

.empty-cart i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.pos-summary-section {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 1rem;
  overflow: hidden;
  white-space: nowrap;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  overflow: hidden;
  text-overflow: ellipsis;
}

.total-row {
  border-top: 2px solid #dee2e6;
  padding-top: 0.5rem;
  margin-top: 0.5rem;
  font-weight: bold;
  font-size: 1.1rem;
}

.total-amount {
  color: #28a745;
  font-size: 1.2rem;
}

.pos-actions-section {
  margin-top: auto;
}

.checkout-btn {
  width: 100%;
  padding: 1rem;
  border-radius: 10px;
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.quick-actions .btn {
  border-radius: 8px;
  padding: 0.5rem;
}

.no-products {
  text-align: center;
  color: #6c757d;
  padding: 3rem;
}

.no-products i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.keyboard-shortcuts {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: rgba(0,0,0,0.9);
  color: white;
  padding: 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  z-index: 1000;
}

.shortcuts-content h6 {
  margin-bottom: 0.5rem;
  color: #ffc107;
}

.shortcut-item {
  margin-bottom: 0.25rem;
}

.shortcut-item kbd {
  background: #495057;
  color: white;
  padding: 0.2rem 0.4rem;
  border-radius: 3px;
  font-size: 0.8rem;
  margin-right: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .pos-container {
    grid-template-columns: 1fr 350px;
  }
}

@media (max-width: 992px) {
  .pos-container {
    grid-template-columns: 1fr;
    grid-template-rows: 1fr auto;
  }
  
  .pos-cart-panel {
    max-height: 400px;
  }
  
  .pos-products-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}

@media (max-width: 768px) {
  .pos-header {
    padding: 1rem;
  }
  
  .pos-title {
    font-size: 1.5rem;
  }
  
  .pos-container {
    gap: 1rem;
    height: auto;
  }
  
  .pos-products-panel,
  .pos-cart-panel {
    padding: 1rem;
  }
  
  .pos-products-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }
  
  .product-image img {
    height: 80px;
  }
  
  .cart-item-controls {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  
  .quantity-controls,
  .price-controls {
    justify-content: center;
  }
}

/* Print Styles */
@media print {
  .pos-header,
  .pos-actions-section,
  .keyboard-shortcuts {
    display: none;
  }
  
  .pos-container {
    display: block;
  }
  
  .pos-cart-panel {
    max-height: none;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .pos-products-panel,
  .pos-cart-panel {
    background: #1a1a1a;
    color: #e0e0e0;
    border: 1px solid #333;
  }
  
  .product-card {
    background: #2d2d2d;
    border-color: #444;
    color: #e0e0e0;
  }
  
  .product-card:hover {
    border-color: #667eea;
    background: #3a3a3a;
  }
  
  .cart-item {
    background: #2d2d2d;
    border-color: #444;
    color: #e0e0e0;
  }
  
  .pos-summary-section {
    background: #2d2d2d;
    border: 1px solid #444;
  }
  
  .summary-row {
    color: #e0e0e0;
  }
  
  .total-row {
    border-top-color: #555;
  }
  
  .total-amount {
    color: #4ade80;
  }

  .pos-search-input:focus,
  .barcode-input:focus {
    background: #c3c0c0;
    border-color: #667eea;
  }
  
  .category-btn {
    background: #2d2d2d;
    border-color: #555;
    color: #e0e0e0;
  }
  
  .category-btn.active,
  .category-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
  }
  
 
  
  .empty-cart,
  .no-products {
    color: #999;
  }
}

/* Light Mode Enhancements */
@media (prefers-color-scheme: light) {
  .pos-products-panel,
  .pos-cart-panel {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }
  
  .product-card {
    background: #ffffff;
    border-color: #e5e7eb;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }
  
  .product-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px 0 rgba(102, 126, 234, 0.15);
  }
  
  .cart-item {
    background: #f9fafb;
    border-color: #e5e7eb;
  }
  
  .pos-summary-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
  }
  
  .summary-row {
    color: #374151;
  }
  
  .total-row {
    border-top-color: #d1d5db;
  }
  
  .total-amount {
    color: #059669;
  }
  #vs1__listbox > div {
    background: #000;
  }
  .pos-search-input,
  .barcode-input {
    background: #ffffff;
    border-color: #d1d5db;
    color: #374151;
  }
  
  .pos-search-input:focus,
  .barcode-input:focus {
    background: #ffffff;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }
  
  .category-btn {
    background: #ffffff;
    border-color: #d1d5db;
    color: #6b7280;
  }
  
  .category-btn.active,
  .category-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
  }
  
  .quantity-input,
  .price-input {
    background: #ffffff;
    border-color: #d1d5db;
  }
  
  .empty-cart,
  .no-products {
    color: #6b7280;
  }
}

/* Animation Classes */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
  transition: transform 0.3s;
}

.slide-up-enter-from, .slide-up-leave-to {
  transform: translateY(100%);
}
.vs__selected {
  color: #fff !important;
}

/* Cash Management Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.cash-modal {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #495057;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  color: #495057;
}

.modal-body {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.cash-stat-card {
  display: flex;
  align-items: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.stat-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  margin-left: 1rem;
}

.stat-info h6 {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #495057;
}

.cash-products h6 {
  color: #495057;
  font-weight: 600;
  margin-bottom: 1rem;
}

.empty-cash {
  text-align: center;
  padding: 2rem;
  color: #6c757d;
}

.empty-cash i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.cash-products-list {
  max-height: 300px;
  overflow-y: auto;
}

.cash-product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  margin-bottom: 0.5rem;
  background: #f8f9fa;
}

.product-name {
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
}

.product-actions {
  margin-left: 1rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
</style>