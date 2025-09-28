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
                      
                      <!-- Best Selling Toggle -->
                      <div class="best-selling-toggle mt-2">
                        <label class="switch">
                          <input 
                            type="checkbox" 
                            :checked="product.is_best_selling == 1"
                            @change="toggleBestSelling(product.id)"
                          >
                          <span class="slider round best-selling-slider"></span>
                        </label>
                        <small class="d-block mt-1" :class="product.is_best_selling ? 'text-info' : 'text-muted'">
                          <i class="bi bi-trophy-fill" v-if="product.is_best_selling"></i>
                          {{ product.is_best_selling ? 'الأكثر مبيعاً' : 'عادي' }}
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
  sort: 'name'
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
  filterForm.sort = 'name';
  Filter();
};

// Get product image
const getProductImage = (product) => {
  if (product.image && product.image !== 'products/default_product.png') {
    return `/storage/${product.image}`;
  }
  return '/dashboard-assets/img/product-placeholder.svg';
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

const printBarcode = async (product) => {
  if (!product.barcode) {
    toast.error('لا يوجد باركود للطباعة')
    return
  }

  loading.value = true

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

    // Create a temporary div for printing
    const printDiv = document.createElement('div')
    printDiv.id = 'barcode-print-overlay'
    printDiv.style.position = 'fixed'
    printDiv.style.top = '0'
    printDiv.style.left = '0'
    printDiv.style.width = '100%'
    printDiv.style.height = '100%'
    printDiv.style.backgroundColor = 'white'
    printDiv.style.zIndex = '9999'
    printDiv.style.padding = '20px'
    printDiv.style.textAlign = 'center'
    printDiv.style.fontFamily = 'Arial, sans-serif'
    printDiv.style.overflow = 'auto'

    printDiv.innerHTML = `
      <style>
        @media print {
          body { margin: 0; padding: 0; }
          .print-container { 
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            padding: 20px !important; 
            border: none !important;
            box-shadow: none !important;
          }
          .barcode-container { 
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            padding: 30px !important; 
            border: 2px solid #000 !important;
            background: white !important;
          }
          .barcode-image { 
            width: 100% !important; 
            height: auto !important; 
            min-height: 300px !important; 
            max-height: 500px !important;
            border: 1px solid #000 !important;
          }
          .product-name { 
            font-size: 24px !important; 
            font-weight: bold !important; 
            margin-bottom: 20px !important; 
            text-align: center !important;
          }
          .barcode-text { 
            font-size: 18px !important; 
            margin-top: 20px !important; 
            font-family: monospace !important; 
            text-align: center !important;
            font-weight: bold !important;
          }
          .no-print { display: none !important; }
        }
      </style>
      <div class="print-container" style="max-width: 400px; margin: 50px auto; border: 2px solid #333; padding: 30px; background: white; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #333;">طباعة الباركود</h3>
        
        <div class="barcode-container" style="border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9;">
          <div class="product-name" style="font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #2c3e50;">${product.name || 'Product'}</div>
          <img class="barcode-image" src="${barcodeImageUrl}" alt="Barcode" style="max-width: 100%;width: 100%; height: auto; border: 1px solid #ccc;" onload="console.log('Barcode image loaded successfully')" onerror="console.error('Failed to load barcode image')">
          <div class="barcode-text" style="font-size: 14px; margin-top: 15px; font-family: monospace; color: #666;">${product.barcode}</div>
        </div>
        
        <div class="no-print" style="margin-top: 30px;">
          <button onclick="printBarcodeContent()" style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 15px; font-size: 16px;">
            <i class="bi bi-printer"></i> طباعة
          </button>
          <button onclick="closePrintOverlay()" style="padding: 12px 25px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            <i class="bi bi-x"></i> إلغاء
          </button>
        </div>
        
        <div class="no-print" style="margin-top: 20px; font-size: 12px; color: #666;">
          <p>💡 نصيحة: اختر "Microsoft Print to PDF" لطباعة إلى ملف PDF</p>
        </div>
      </div>
    `

    // Add print and close functions to window
    window.printBarcodeContent = function() {
      // Wait for image to load before printing
      const img = printDiv.querySelector('img')
      if (img && !img.complete) {
        img.onload = function() {
          window.print()
        }
        return
      }
      window.print()
    }
    
    window.closePrintOverlay = function() {
      document.body.removeChild(printDiv)
      if (barcodeImageUrl) {
        URL.revokeObjectURL(barcodeImageUrl)
      }
    }

    document.body.appendChild(printDiv)
    toast.success('تم تحضير الباركود للطباعة. اضغط زر الطباعة في الأعلى.')

    // Clean up URL object after a delay
    if (barcodeImageUrl) {
      setTimeout(() => {
        if (document.getElementById('barcode-print-overlay')) {
          URL.revokeObjectURL(barcodeImageUrl)
        }
      }, 30000) // Clean up after 30 seconds
    }

  } catch (error) {
    console.error('Print error:', error)
    toast.error('حدث خطأ أثناء تحضير الطباعة')
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

const toggleBestSelling = (id) => {
  router.post(`/products/${id}/toggle-best-selling`, {
    onSuccess: () => {
      // Success message will be shown by the redirect
    },
    onError: () => {
      Swal.fire(
        'خطأ!',
        'حدث خطأ أثناء تحديث حالة المنتج الأكثر مبيعاً.',
        'error'
      );
    }
  });
};

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
