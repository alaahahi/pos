<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.products }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">{{ translations.products }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">{{ translations.total_products }} <span>| {{ translations.all }}</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-box-seam"></i>
          </div>
                <div class="ps-3">
                  <h6>{{ props.products?.total || 0 }}</h6>
                  <span class="text-success small pt-1 fw-bold">{{ translations.products }}</span>
        </div>
              </div>
            </div>
          </div>
        </div>



        <div class="col-xl-3 col-md-6">
          <div class="card info-card customers-card">
        <div class="card-body">
              <h5 class="card-title">{{ translations.active_products }} <span>| {{ translations.enabled }}</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-check-circle"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ getActiveCount() }}</h6>
                  <span class="text-success small pt-1 fw-bold">{{ translations.active }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">{{ translations.total_value }} <span>| {{ translations.dollar }}</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ getTotalValueDollar() }} $</h6>
                  <span class="text-primary small pt-1 fw-bold">{{ translations.value }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">{{ translations.total_value }} <span>| {{ translations.dinar }}</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-currency-exchange"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ getTotalValueDinar() }} IQD</h6>
                  <span class="text-primary small pt-1 fw-bold">{{ translations.value }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="bi bi-box-seam me-2"></i>
            {{ translations.products_management }}
          </h5>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" @click="toggleViewMode">
              <i :class="viewMode === 'grid' ? 'bi bi-list' : 'bi bi-grid'"></i>
              {{ viewMode === 'grid' ? translations.list_view : translations.grid_view }}
            </button>
            <Link v-if="hasPermission('create product')" class="btn btn-primary" :href="route('products.create')">
              <i class="bi bi-plus-circle"></i>
              {{ translations.add_product }}
                </Link>
              </div>
            </div>
        
        <div class="card-body">
          <!-- Advanced Search and Filters -->
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="search-box">
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="filterForm.search" 
                  :placeholder="translations.search_by_name_or_barcode"
                  @input="debouncedSearch"
                />
                <i class="bi bi-search search-icon"></i>
              </div>
            </div>
            <div class="col-md-2">
              <select class="form-select" v-model="filterForm.status" @change="Filter">
                <option value="">{{ translations.all_status }}</option>
                <option value="active">{{ translations.active }}</option>
                <option value="inactive">{{ translations.inactive }}</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" v-model="filterForm.stock" @change="Filter">
                <option value="">{{ translations.all_stock }}</option>
                <option value="low">{{ translations.low_stock }}</option>
                <option value="out">{{ translations.out_of_stock }}</option>
                <option value="available">{{ translations.in_stock }}</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" v-model="filterForm.sort" @change="Filter">
                <option value="name">{{ translations.sort_by_name }}</option>
                <option value="price">{{ translations.sort_by_price }}</option>
                <option value="quantity">{{ translations.sort_by_quantity }}</option>
                <option value="best_selling">الأكثر مبيعاً</option>
                <option value="created">{{ translations.sort_by_date }}</option>
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-secondary w-100" @click="clearFilters">
                <i class="bi bi-arrow-clockwise"></i>
                {{ translations.clear }}
              </button>
            </div>
          </div>

          <!-- Products Display -->
          <div v-if="viewMode === 'table'">
          <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-dark">
                  <tr>
                    <th scope="col" class="text-center">
                      <i class="bi bi-image"></i>
                    </th>
                    <th scope="col">{{ translations.product_info }}</th>
                  <th scope="col">{{ translations.barcode }}</th>
                    <th scope="col">{{ translations.stock }}</th>
                  <th scope="col">{{ translations.price }}</th>
                    <th scope="col">{{ translations.status }}</th>
                    <th scope="col" class="text-center">{{ translations.actions }}</th>
                </tr>
              </thead>
              <tbody>
                  <tr v-for="(product, index) in props.products?.data" :key="product.id" class="product-row">
                    <td class="text-center">
                      <div class="product-image-container">
                        <img 
                          :src="getProductImage(product)" 
                          :alt="product.name"
                          class="product-image"
                          @error="handleImageError"
                        />
                        <div v-if="product.quantity <= 5" class="stock-badge low-stock">
                          <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div v-if="product.quantity === 0" class="stock-badge out-of-stock">
                          <i class="bi bi-x-circle"></i>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="product-info">
                        <h6 class="product-name mb-1">{{ product.name }}</h6>
                        <small class="text-muted">{{ product.model }}</small>
                        <br>
                        <small class="text-muted">{{ translations.created }}: {{ formatDate(product.created) }}</small>
                      </div>
                    </td>
                    <td>
                      <div class="barcode-info">
                        <span v-if="product.barcode" class="barcode-text">{{ product.barcode }}</span>
                        <span v-else class="text-muted">{{ translations.no_barcode }}</span>
                  </div>
                  </td>
                    <td>
                      <div class="stock-info">
                        <span class="stock-quantity" :class="getStockClass(product.quantity)">
                          {{ product.quantity }}
                        </span>
                        <small class="d-block text-muted">{{ translations.units }}</small>
                      </div>
                  </td>
                    <td>
                      <div class="price-info">
                        <span class="price-amount">{{ formatPrice(product.price, product.currency) }}</span>
                        <small class="d-block text-muted">{{ translations.selling_price }}</small>
                      </div>
                  </td>
                    <td>
                      <div class="status-toggle">
                        <label class="switch">
                          <input 
                            type="checkbox" 
                            :checked="product.is_active == 1"
                            @change="Activate(product.id)"
                          >
                          <span class="slider round"></span>
                        </label>
                        <small class="d-block mt-1" :class="product.is_active ? 'text-success' : 'text-danger'">
                          {{ product.is_active ? translations.active : translations.inactive }}
                        </small>
                      </div>
                      
                      <!-- Featured Toggle -->
                      <div class="featured-toggle mt-2">
                        <label class="switch">
                          <input 
                            type="checkbox" 
                            :checked="product.is_featured == 1"
                            @change="toggleFeatured(product.id)"
                          >
                          <span class="slider round featured-slider"></span>
                        </label>
                        <small class="d-block mt-1" :class="product.is_featured ? 'text-warning' : 'text-muted'">
                          <i class="bi bi-star-fill" v-if="product.is_featured"></i>
                          {{ product.is_featured ? 'مميز' : 'عادي' }}
                        </small>
                      </div>
                      
                      <!-- Best Selling Badge (based on actual sales) -->
                      <div class="best-selling-badge mt-2" v-if="product.total_sales > 0">
                        <div class="badge bg-warning text-dark">
                          <i class="bi bi-trophy-fill me-1"></i>
                          الأكثر مبيعاً
                        </div>
                        <small class="d-block mt-1 text-muted">
                          <i class="bi bi-cart-fill me-1"></i>
                          {{ product.total_sales }} مبيع
                        </small>
                      </div>
                  </td>
                  <td>
                      <div class="action-buttons">
                        <div class="btn-group-vertical" role="group">
                          <Link 
                            v-if="hasPermission('update product')"
                            class="btn btn-sm btn-outline-primary mb-1" 
                            :href="route('products.edit', { product: product.id })"
                            :title="translations.edit"
                          >
                            <i class="bi bi-pencil"></i>
                          </Link>
                          
                          <button 
                            v-if="hasPermission('update product')"
                            class="btn btn-sm btn-outline-success mb-1" 
                            @click="showModalPurchasesProduct = true; data = product"
                            title="{{ translations.add_stock }}"
                          >
                            <i class="bi bi-plus-circle"></i>
                          </button>
                          
                    <div class="btn-group" role="group">
                      <button 
                        v-if="!product.barcode"
                              class="btn btn-sm btn-outline-info" 
                        @click="generateBarcode(product)"
                        :disabled="loading"
                              title="{{ translations.generate_barcode }}"
                      >
                        <i class="bi bi-qr-code"></i>
                      </button>
                      
                      <button 
                        v-if="product.barcode"
                              class="btn btn-sm btn-outline-secondary" 
                        @click="printBarcode(product)"
                              title="{{ translations.print_barcode }}"
                      >
                        <i class="bi bi-printer"></i>
                      </button>
                          </div>
                      
                      <button 
                            v-if="hasPermission('delete product')"
                            class="btn btn-sm btn-outline-danger mt-1" 
                            @click="Delete(product.id)"
                            title="{{ translations.delete }}"
                          >
                            <i class="bi bi-trash"></i>
                      </button>
                        </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            </div>
          </div>

          <!-- Grid View -->
          <div v-else class="row">
            <div 
              v-for="product in props.products?.data" 
              :key="product.id" 
              class="col-xl-3 col-lg-4 col-md-6 mb-4"
            >
              <div class="card product-card h-100">
                <div class="card-body">
                  <div class="product-image-container mb-3">
                    <img 
                      :src="getProductImage(product)" 
                      :alt="product.name"
                      class="product-image-large"
                      @error="handleImageError"
                    />
                    <div v-if="product.quantity <= 5" class="stock-badge low-stock">
                      <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div v-if="product.quantity === 0" class="stock-badge out-of-stock">
                      <i class="bi bi-x-circle"></i>
                    </div>
                  </div>
                  
                  <h6 class="card-title">{{ product.name }}</h6>
                  <p class="card-text text-muted small">{{ product.model }}</p>
                  
                  <div class="row mb-3">
                    <div class="col-6">
                      <small class="text-muted d-block">{{ translations.stock }}</small>
                      <span class="fw-bold" :class="getStockClass(product.quantity)">
                        {{ product.quantity }}
                      </span>
                    </div>
                    <div class="col-6">
                      <small class="text-muted d-block">{{ translations.price }}</small>
                      <span class="fw-bold text-primary">{{ formatPrice(product.price, product.currency) }}</span>
                    </div>
                  </div>
                  
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="status-toggle">
                      <label class="switch">
                        <input 
                          type="checkbox" 
                          :checked="product.is_active == 1"
                          @change="Activate(product.id)"
                        >
                        <span class="slider round"></span>
                      </label>
                    </div>
                    
                    <div class="btn-group" role="group">
                      <Link 
                        v-if="hasPermission('update product')"
                        class="btn btn-sm btn-outline-primary" 
                        :href="route('products.edit', { product: product.id })"
                        :title="translations.edit"
                      >
                        <i class="bi bi-pencil"></i>
                      </Link>
                      
                      <button 
                        v-if="hasPermission('update product')"
                        class="btn btn-sm btn-outline-success" 
                        @click="showModalPurchasesProduct = true; data = product"
                        title="{{ translations.add_stock }}"
                      >
                        <i class="bi bi-plus-circle"></i>
                      </button>
                      
                      <button 
                        v-if="hasPermission('delete product')"
                        class="btn btn-sm btn-outline-danger" 
                        @click="Delete(product.id)"
                        title="{{ translations.delete }}"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <Pagination :links="props.products?.links" />
    </section>
    <ModalPurchasesProduct 
      :show="showModalPurchasesProduct" 
      :translations="translations" 
      :data="data"
      @close="showModalPurchasesProduct = false" 
      @success="handlePurchaseSuccess"
    />
    
    <!-- Print Barcode Modal -->
    <div class="modal fade" :class="{ show: showPrintModal }" :style="{ display: showPrintModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-printer"></i> طباعة الباركود
            </h5>
            <button type="button" class="btn-close" @click="showPrintModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info mb-3">
              <strong><i class="bi bi-box"></i> المنتج:</strong> {{ printProduct?.name }}
            </div>
            
            <!-- Barcode Settings Panel -->
            <div class="barcode-settings p-3" style="background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
              <h6 class="mb-3"><i class="bi bi-sliders"></i> إعدادات الباركود</h6>
              
              <!-- Paper Size Settings -->
              <div class="row mb-2">
                <div class="col-md-6">
                  <label class="form-label"><strong>📏 عرض الورق (mm)</strong></label>
                  <input 
                    type="number" 
                    class="form-control form-control-sm" 
                    min="20" 
                    max="80" 
                    step="1" 
                    v-model.number="printSettings.pageWidth"
                  >
                </div>
                <div class="col-md-6">
                  <label class="form-label"><strong>📏 ارتفاع الورق (mm)</strong></label>
                  <input 
                    type="number" 
                    class="form-control form-control-sm" 
                    min="20" 
                    max="80" 
                    step="1" 
                    v-model.number="printSettings.pageHeight"
                  >
                </div>
              </div>
              
              <!-- Quick Size Buttons -->
              <div class="mb-3">
                <small class="text-muted">أحجام شائعة:</small>
                <div class="btn-group btn-group-sm mt-1" role="group">
                  <button type="button" class="btn btn-outline-primary" @click="printSettings.pageWidth = 38; printSettings.pageHeight = 26">
                    <strong>38×26</strong>
                  </button>
                  <button type="button" class="btn btn-outline-secondary" @click="printSettings.pageWidth = 35; printSettings.pageHeight = 28">
                    35×28
                  </button>
                  <button type="button" class="btn btn-outline-secondary" @click="printSettings.pageWidth = 38; printSettings.pageHeight = 28">
                    38×28
                  </button>
                  <button type="button" class="btn btn-outline-secondary" @click="printSettings.pageWidth = 40; printSettings.pageHeight = 30">
                    40×30
                  </button>
                </div>
              </div>
              
              <hr>
              
              <div class="row">
                <div class="col-md-6">
                  <label class="form-label">عرض الخط</label>
                  <input 
                    type="range" 
                    class="form-range" 
                    min="1" 
                    max="5" 
                    step="0.1" 
                    v-model="printSettings.width"
                  >
                  <small class="text-muted">{{ printSettings.width }}</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">ارتفاع الباركود</label>
                  <input 
                    type="range" 
                    class="form-range" 
                    min="30" 
                    max="150" 
                    step="5" 
                    v-model="printSettings.height"
                  >
                  <small class="text-muted">{{ printSettings.height }}px</small>
                </div>
              </div>
              
              <div class="row mt-2">
                <div class="col-md-6">
                  <label class="form-label">حجم الخط</label>
                  <input 
                    type="range" 
                    class="form-range" 
                    min="6" 
                    max="20" 
                    step="1" 
                    v-model="printSettings.fontSize"
                  >
                  <small class="text-muted">{{ printSettings.fontSize }}px</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">الهوامش</label>
                  <input 
                    type="range" 
                    class="form-range" 
                    min="0" 
                    max="10" 
                    step="1" 
                    v-model="printSettings.margin"
                  >
                  <small class="text-muted">{{ printSettings.margin }}px</small>
                </div>
              </div>
              
              <div class="row mt-3">
                <div class="col-md-6">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="printLandscapeMode"
                      v-model="printSettings.landscape"
                    >
                    <label class="form-check-label" for="printLandscapeMode">
                      طباعة بالعرض (Landscape)
                    </label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="printHighQuality"
                      v-model="printSettings.highQuality"
                    >
                    <label class="form-check-label" for="printHighQuality">
                      جودة عالية (SVG)
                    </label>
                  </div>
                </div>
              </div>
              
      
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showPrintModal = false">
              {{ translations.cancel || 'إلغاء' }}
            </button>
            <button type="button" class="btn btn-success" @click="confirmPrint" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              <i class="bi bi-printer"></i> {{ translations.print || 'طباعة' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import BarcodePrinter from '@/Components/BarcodePrinter.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { ref } from 'vue';
import ModalPurchasesProduct from '@/Components/ModalPurchasesProduct.vue';
import JsBarcode from 'jsbarcode';
import { useToast } from 'vue-toastification';
import { debounce } from 'lodash';

const props = defineProps({
  products: Object, 
  translations: Array 
});

const page = usePage();
const toast = useToast();

const showModalPurchasesProduct = ref(false);
const data = ref({});
const loading = ref(false);
const previewLoading = ref(false);
const previewImage = ref(null);
const selectedProduct = ref(null);
const showPrintModal = ref(false);
const printProduct = ref(null);

// Barcode print settings
const printSettings = reactive({
  width: 2,
  height: 70,
  fontSize: 10,
  margin: 2,
  landscape: true,
  highQuality: true,
  pageWidth: 38,
  pageHeight: 26
});

// Add barcode validation
const barcodeValidation = ref({
  checking: false,
  isValid: true,
  message: ''
});

const filterForm = reactive({
  search: '',
  status: '',
  stock: '',
  sort: 'created'
});

// View mode (table or grid)
const viewMode = ref('table');

// Barcode validation function

// Barcode validation function
const validateBarcode = async (barcode) => {
  if (!barcode || barcode.length < 3) {
    barcodeValidation.value = {
      checking: false,
      isValid: true,
      message: ''
    };
    return;
  }

  barcodeValidation.value.checking = true;
  
  try {
    const response = await fetch(route('products.checkBarcodeUnique', barcode));
    const data = await response.json();
    
    barcodeValidation.value = {
      checking: false,
      isValid: data.unique,
      message: data.message
    };
    
    if (!data.unique) {
      toast.warning(data.message);
    }
  } catch (error) {
    console.error('Barcode validation error:', error);
    barcodeValidation.value = {
      checking: false,
      isValid: false,
      message: 'خطأ في التحقق من الباركود'
    };
  }
};

// Debounced barcode validation
const debouncedValidateBarcode = debounce(validateBarcode, 500);

// Debounced search
const debouncedSearch = debounce(() => {
  Filter();
}, 500);

// Handle purchase success
const handlePurchaseSuccess = (response) => {
  toast.success('تم تحديث المنتج بنجاح');
  // Refresh the page to show updated data
  window.location.reload();
};

// Toggle view mode
const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'table' ? 'grid' : 'table';
};

// Clear filters
const clearFilters = () => {
  filterForm.search = '';
  filterForm.status = '';
  filterForm.stock = '';
  filterForm.sort = 'created';
  Filter();
};

// Get product image
const getProductImage = (product) => {
  if (product.image && product.image.trim() !== '') {
    return `/public/storage/${product.image}`;
  }
  return '/public/dashboard-assets/img/product-placeholder.svg';
};

// Handle image error
const handleImageError = (event) => {
  event.target.src = '/dashboard-assets/img/product-placeholder.svg';
};

// Get stock class
const getStockClass = (quantity) => {
  if (quantity === 0) return 'text-danger';
  if (quantity <= 5) return 'text-warning';
  return 'text-success';
};

// Format price
const formatPrice = (price, currency = 'IQD') => {
  return parseFloat(price || 0).toFixed(2) + ' ' + currency;
};

// Format date
const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ar-SA');
};

// Get low stock count
const getLowStockCount = () => {
  if (!props.products?.data) return 0;
  return props.products.data.filter(product => product.quantity <= 5).length;
};

// Get active count
const getActiveCount = () => {
  if (!props.products?.data) return 0;
  return props.products.data.filter(product => product.is_active).length;
};

// Get total value in Dollar (only products with USD currency)
const getTotalValueDollar = () => {
  if (!props.products?.data) return '0.00';
  const total = props.products.data
    .filter(product => product.currency === 'USD')
    .reduce((sum, product) => {
      return sum + (parseFloat(product.price || 0) * parseInt(product.quantity || 0));
    }, 0);
  return total.toFixed(2);
};

// Get total value in Dinar (only products with IQD currency)
const getTotalValueDinar = () => {
  if (!props.products?.data) return '0.00';
  const total = props.products.data
    .filter(product => product.currency === 'IQD')
    .reduce((sum, product) => {
      return sum + (parseFloat(product.price || 0) * parseInt(product.quantity || 0));
    }, 0);
  return total.toFixed(2);
};

// Barcode functions
const generateBarcode = async (product) => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.generate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_id: product.id,
        type: 'PNG',
        width: 2,
        height: 30
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success('تم توليد الباركود بنجاح')
      // Refresh the page to show updated barcode
      window.location.reload()
    } else {
      toast.error(data.message || 'حدث خطأ أثناء توليد الباركود')
    }
  } catch (error) {
    console.error('Generate barcode error:', error)
    toast.error('حدث خطأ أثناء توليد الباركود')
  } finally {
    loading.value = false
  }
}

const printBarcode = (product) => {
  if (!product.barcode) {
    toast.error('لا يوجد باركود للطباعة')
    return
  }
  
  printProduct.value = product
  showPrintModal.value = true
}

const confirmPrint = async () => {
  if (!printProduct.value) return
  
  loading.value = true
  
  try {
    // Generate SVG barcode
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg")
    
    JsBarcode(svg, printProduct.value.barcode, {
      format: "CODE128",
      width: printSettings.width,
      height: printSettings.height,
      displayValue: false,
      margin: printSettings.margin,
      background: "#ffffff",
      lineColor: "#000000",
      xmlDocument: document
    })
    
    const svgData = new XMLSerializer().serializeToString(svg)
    const barcodeImageUrl = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)))

    // Create print window
    const printWindow = window.open('', '_blank', 'width=400,height=600')
    
    if (!printWindow) {
      toast.error('فشل فتح نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة.')
      return
    }
    
    const orientation = printSettings.landscape ? 'landscape' : 'portrait'
    const pageWidth = `${printSettings.pageWidth}mm`
    const pageHeight = `${printSettings.pageHeight}mm`
    const pageSize = printSettings.landscape ? `${pageWidth} ${pageHeight}` : `${pageHeight} ${pageWidth}`
    const maxBarcodeHeight = printSettings.landscape ? '18mm' : '22mm'
    
    const htmlContent = `
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركود</title>
  <style>
    /* Optimized for thermal printers */
    @page { 
      size: ${pageSize}; 
      margin: 0; 
      orientation: ${orientation};
    }
    
    @media print {
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
    }
    
    body {
      margin: 0;
      padding: 0;
      width: ${pageWidth};
      height: ${pageHeight};
      font-family: Arial, sans-serif;
      overflow: hidden;
    }
    
    .label-container {
      width: ${pageWidth};
      height: ${pageHeight};
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 1mm;
      box-sizing: border-box;
    }
    
    .product-name {
      width: 100%;
      font-size: ${printSettings.fontSize}px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 1mm;
      line-height: 1.1;
      max-height: 8mm;
      overflow: hidden;
      word-wrap: break-word;
    }
    
    .barcode-image {
      width: auto;
      max-width: 90%;
      height: auto;
      max-height: ${maxBarcodeHeight};
      margin: 1mm auto;
      display: block;
      object-fit: contain;
    }
    
    .barcode-text {
      width: 100%;
      font-size: ${Math.max(printSettings.fontSize - 2, 4)}px;
      font-family: monospace;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="label-container">
    <div class="product-name">${printProduct.value.name}</div>
    <img class="barcode-image" src="${barcodeImageUrl}" alt="Barcode">
    <div class="barcode-text">${printProduct.value.barcode}</div>
  </div>
  
  <scr` + `ipt>
    window.onload = function() {
      setTimeout(function() {
        window.print();
        setTimeout(function() {
          window.close();
        }, 2000);
      }, 1500);
    }
  </scr` + `ipt>
</body>
</html>
    `
    
    printWindow.document.write(htmlContent)
    printWindow.document.close()
    
    showPrintModal.value = false
    toast.success('تم إرسال الباركود للطباعة')
    
  } catch (error) {
    console.error('Print error:', error)
    toast.error('حدث خطأ أثناء الطباعة')
  } finally {
    loading.value = false
  }
}

const previewBarcode = async (product) => {
  previewLoading.value = true
  selectedProduct.value = product
  
  try {
    const params = new URLSearchParams({
      code: product.barcode,
      type: 'PNG',
      width: 2,
      height: 30
    })
    
    const response = await fetch(`/barcode/preview?${params}`)
    
    if (response.ok) {
      const blob = await response.blob()
      previewImage.value = URL.createObjectURL(blob)
      
      // Show preview in a simple modal
      Swal.fire({
        title: 'معاينة الباركود',
        html: `
          <div style="text-align: center;">
            <h4 class="text-center" style=" ; margin-bottom: 15px;">${product.name}</h4>
            <img src="${previewImage.value}" style="max-width: 100%; border: 1px solid #ddd; padding: 10px;width: 100%;">
            <div style="margin-top: 15px; font-family: monospace; font-size: 16px;">${product.barcode}</div>
          </div>
        `,
        showConfirmButton: true,
        confirmButtonText: 'إغلاق',
        width: '500px'
      })
    } else {
      toast.error('فشل في تحميل معاينة الباركود')
    }
  } catch (error) {
    console.error('Preview error:', error)
    toast.error('حدث خطأ أثناء معاينة الباركود')
  } finally {
    previewLoading.value = false
  }
}

const downloadBarcode = async (product) => {
  try {
    // Generate barcode image
    const params = new URLSearchParams({
      code: product.barcode,
      type: 'PNG',
      width: 2,
      height: 30
    })
    
    const response = await fetch(`/barcode/preview?${params}`)
    
    if (!response.ok) {
      throw new Error('Failed to generate barcode image')
    }
    
    const blob = await response.blob()
    const barcodeImageUrl = URL.createObjectURL(blob)

    // Create a canvas with product name and barcode for download
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    
    // Set canvas size - smaller to reduce empty spaces
    canvas.width = 300
    canvas.height = 150
    
    // Fill background
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    
    // Draw border
    ctx.strokeStyle = '#000000'
    ctx.lineWidth = 1
    ctx.strokeRect(2, 2, canvas.width - 4, canvas.height - 4)
    
    // Draw product name
    ctx.fillStyle = '#000000'
    ctx.font = 'bold 16px Arial'
    ctx.textAlign = 'center'
    ctx.fillText(product.name || 'Product', canvas.width / 2, 25)
    
    // Load and draw barcode image
    const img = new Image()
    img.onload = function() {
      // Calculate barcode position (centered)
      const barcodeWidth = Math.min(img.width, canvas.width - 20)
      const barcodeHeight = Math.min(img.height, canvas.height - 80)
      const x = (canvas.width - barcodeWidth) / 2
      const y = 40
      
      ctx.drawImage(img, x, y, barcodeWidth, barcodeHeight)
      
      // Draw barcode number
      ctx.font = '14px monospace'
      ctx.fillText(product.barcode, canvas.width / 2, y + barcodeHeight + 20)
      
      // Create download link
      const link = document.createElement('a')
      link.href = canvas.toDataURL('image/png')
      link.download = `barcode_${product.name || 'product'}_${product.barcode}.png`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Clean up
      URL.revokeObjectURL(barcodeImageUrl)
      
      toast.success('تم تحميل الباركود بنجاح')
    }
    
    img.onerror = function() {
      // Fallback: download original image
      const link = document.createElement('a')
      link.href = barcodeImageUrl
      link.download = `barcode_${product.name || 'product'}_${product.barcode}.png`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(barcodeImageUrl)
      
      toast.success('تم تحميل الباركود بنجاح')
    }
    
    img.src = barcodeImageUrl
    
  } catch (error) {
    console.error('Download error:', error)
    toast.error('حدث خطأ أثناء تحميل الباركود')
  }
}

const Filter = () => {
  router.get(
    route('products.index'),
    filterForm,
    { preserveState: true, preserveScroll: true },
  );
};

const hasPermission = (permission) => {
  return page.props.auth_permissions.includes(permission);
};
const Activate = (id) => {
  Swal.fire({
    title: props.translations.are_your_sure,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#7066e0',
    confirmButtonText: props.translations.yes,
    cancelButtonText: props.translations.cancel,
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(`/products/${id}/activate`, {
        onSuccess: () => {
          Swal.fire(
            'Updated !',
            'product stuaus item has been updated.',
            'success'
          );
        },
        onError: () => {
          Swal.fire(
            'Error!',
            'There was an issue updating product status.',
            'error'
          );
        }
      });
    }
  });
}

const toggleFeatured = (id) => {
  router.post(`/products/${id}/toggle-featured`, {
    onSuccess: () => {
      // Success message will be shown by the redirect
    },
    onError: () => {
      Swal.fire(
        'خطأ!',
        'حدث خطأ أثناء تحديث حالة المنتج المميز.',
        'error'
      );
    }
  });
};

// Best selling is now calculated automatically based on sales
// const toggleBestSelling = (id) => { ... }

const Delete = (id) => {
  Swal.fire({
    title: props.translations.are_you_sure,
    text: props.translations.you_will_not_be_able_to_revert_this,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: props.translations.yes,
    cancelButtonText: props.translations.cancel,
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete('products/' + id, {
        onSuccess: () => {
          Swal.fire({
            title: props.translations.data_deleted_successfully,
            icon: "success"
          });
        },
        onError: () => {
          Swal.fire(
            'Error!',
            'There was an issue deleting the product.',
            'error'
          );
        }
      });
    }
  });
};
</script>

<style scoped>
/* Statistics Cards */
.info-card {
  border-left: 4px solid #3b82f6;
  transition: all 0.3s ease;
}

.info-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.info-card .card-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  width: 60px;
  height: 60px;
  font-size: 1.5rem;
}

.sales-card {
  border-left-color: #10b981;
}

.revenue-card {
  border-left-color: #f59e0b;
}

.customers-card {
  border-left-color: #8b5cf6;
}

/* Search Box */
.search-box {
  position: relative;
}

.search-box .search-icon {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-size: 1.1rem;
}

/* Product Images */
.product-image-container {
  position: relative;
  display: inline-block;
}

.product-image {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
}

.product-image-large {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
}

.stock-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  color: white;
}

.stock-badge.low-stock {
  background: #f59e0b;
}

.stock-badge.out-of-stock {
  background: #ef4444;
}

/* Product Cards */
.product-card {
  transition: all 0.3s ease;
  border: 1px solid #e5e7eb;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  border-color: #3b82f6;
}

/* Featured and Best Selling Toggles */
.featured-toggle .featured-slider {
  background: #f59e0b;
}

.featured-toggle input:checked + .featured-slider {
  background: #f59e0b;
}

.best-selling-toggle .best-selling-slider {
  background: #3b82f6;
}

.best-selling-toggle input:checked + .best-selling-slider {
  background: #3b82f6;
}

.featured-toggle, .best-selling-toggle {
  margin-top: 8px;
}

.featured-toggle small, .best-selling-toggle small {
  font-size: 0.75rem;
  font-weight: 500;
}

.product-card .card-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

/* Table Styling */
.product-row {
  transition: all 0.2s ease;
}

.product-row:hover {
  background-color: #f8fafc;
}

.product-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.barcode-text {
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
  background: #f3f4f6;
  padding: 2px 6px;
  border-radius: 4px;
  color: #374151;
}

.stock-quantity {
  font-weight: 700;
  font-size: 1.1rem;
}

.price-amount {
  font-weight: 600;
  color: #059669;
  font-size: 1rem;
}

/* Switch Toggle */
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: .4s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #10b981;
}

input:checked + .slider:before {
  transform: translateX(26px);
}

/* Action Buttons */
.action-buttons .btn {
  border-radius: 6px;
  transition: all 0.2s ease;
}

.action-buttons .btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Responsive Design */
@media (max-width: 768px) {
  .product-image {
    width: 40px;
    height: 40px;
  }
  
  .product-image-large {
    height: 150px;
  }
  
  .info-card .card-icon {
    width: 50px;
    height: 50px;
    font-size: 1.2rem;
  }
}

/* Loading States */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.product-card {
  animation: fadeIn 0.3s ease-out;
}

/* Status Indicators */
.text-success {
  color: #10b981 !important;
}

.text-warning {
  color: #f59e0b !important;
}

.text-danger {
  color: #ef4444 !important;
}

.text-primary {
  color: #3b82f6 !important;
}
</style>
