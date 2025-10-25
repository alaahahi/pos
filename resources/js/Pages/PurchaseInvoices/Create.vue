<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar">
    

    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bi bi-plus-circle me-2"></i>
            إنشاء فاتورة مشتريات جديدة
          </h5>
        </div>
        
        <div class="card-body">
          <form @submit.prevent="submitForm">
            <!-- Invoice Header -->
            <div class="row mb-4">
              <div class="col-md-6">
                <label class="form-label">المورد</label>
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    v-model="supplierSearch"
                    @input="searchSuppliers"
                    placeholder="ابحث عن المورد..."
                    @focus="showSupplierDropdown = true"
                  />
                  <button type="button" class="btn btn-outline-primary" @click="showAddSupplierModal = true">
                    <i class="bi bi-plus"></i>
                  </button>
                </div>
                
                <!-- Supplier Dropdown -->
                <div v-if="showSupplierDropdown && filteredSuppliers.length > 0" class="dropdown-menu show w-100">
                  <div
                    v-for="supplier in filteredSuppliers"
                    :key="supplier.id"
                    class="dropdown-item cursor-pointer"
                    @click="selectSupplier(supplier)"
                  >
                    <div class="d-flex justify-content-between">
                      <span>{{ supplier.name }}</span>
                      <small class="text-muted">{{ supplier.phone }}</small>
                    </div>
                  </div>
                </div>
                
                <!-- Selected Supplier -->
                <div v-if="selectedSupplier" class="mt-2">
                  <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <div>
                      <strong>{{ selectedSupplier.name }}</strong>
                      <br>
                      <small>{{ selectedSupplier.phone }}</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="clearSupplier">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">تاريخ الفاتورة</label>
                <input
                  type="date"
                  class="form-control"
                  v-model="form.invoice_date"
                  required
                />
              </div>
              
              <div class="col-md-3">
                <label class="form-label">العملة</label>
                <select class="form-select" v-model="form.currency" required>
                  <option value="IQD">دينار عراقي</option>
                  <option value="$">دولار أمريكي</option>
                </select>
              </div>
            </div>

            <!-- Products Section -->
            <div class="row mb-4">
              <div class="col-12">
                <h6 class="mb-3">
                  <i class="bi bi-box-seam me-2"></i>
                  المنتجات
                </h6>
                
                <!-- Product Search -->
                <div class="row mb-3">
                  <div class="col-md-8">
                    <div class="input-group">
                      <input
                        type="text"
                        class="form-control"
                        v-model="productSearch"
                        @input="searchProducts"
                        placeholder="ابحث عن المنتج بالاسم أو الباركود..."
                        @keyup.enter="addProductFromSearch"
                      />
                      <button type="button" class="btn btn-outline-primary" @click="addProductFromSearch">
                        <i class="bi bi-search"></i>
                      </button>
                    </div>
                    
                    <!-- Product Search Results -->
                    <div v-if="showProductDropdown && filteredProducts.length > 0" class="dropdown-menu show w-100">
                      <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="dropdown-item cursor-pointer"
                        @click="addProduct(product)"
                      >
                        <div class="d-flex justify-content-between align-items-center">
                          <div>
                            <strong>{{ product.name }}</strong>
                            <br>
                            <small class="text-muted">
                              الباركود: {{ product.barcode || 'غير محدد' }} | 
                              السعر: {{ product.price }} | 
                              المخزون: {{ product.quantity }}
                            </small>
                          </div>
                          <i class="bi bi-plus-circle text-primary"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="col-md-4">
                    <button type="button" class="btn btn-primary w-100" @click="showProductModal = true">
                      <i class="bi bi-plus-circle me-2"></i>
                      إضافة منتج يدوياً
                    </button>
                  </div>
                </div>

                <!-- Products Table -->
                <div v-if="form.items.length > 0" class="table-responsive">
                  <table class="table table-hover">
                    <thead class="table-dark">
                      <tr>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>سعر التكلفة</th>
                        <th>سعر البيع</th>
                        <th>المجموع</th>
                        <th>الإجراءات</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in form.items" :key="index">
                        <td>
                          <div class="d-flex align-items-center">
                            <img
                              :src="getProductImage(item.product)"
                              :alt="item.product.name"
                              class="product-thumb me-2"
                              @error="handleImageError"
                            />
                            <div>
                              <strong>{{ item.product.name }}</strong>
                              <br>
                              <small class="text-muted">{{ item.product.barcode || 'غير محدد' }}</small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <input
                            type="number"
                            class="form-control form-control-sm"
                            v-model.number="item.quantity"
                            @input="updateItemTotal(index)"
                            min="1"
                            style="width: 80px;"
                          />
                        </td>
                        <td>
                          <input
                            type="number"
                            class="form-control form-control-sm"
                            v-model.number="item.cost_price"
                            @input="updateItemTotal(index)"
                            step="1"
                            min="0"
                            style="width: 100px;"
                          />
                        </td>
                        <td>
                          <input
                            type="number"
                            class="form-control form-control-sm"
                            v-model.number="item.sales_price"
                            step="1"
                            min="0"
                            style="width: 100px;"
                          />
                        </td>
                        <td>
                          <strong>{{ Math.round(item.total) }} {{ form.currency }}</strong>
                        </td>
                        <td>
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            @click="removeItem(index)"
                          >
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="table-light">
                      <tr>
                        <th colspan="4" class="text-end">المجموع الكلي:</th>
                        <th><strong class="text-primary">{{ Math.round(totalAmount) }} {{ form.currency }}</strong></th>
                        <th></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-5">
                  <i class="bi bi-box-seam display-1 text-muted"></i>
                  <h5 class="text-muted mt-3">لم يتم إضافة أي منتجات</h5>
                  <p class="text-muted">ابحث عن المنتجات أو أضفها يدوياً لبدء إنشاء الفاتورة</p>
                </div>
              </div>
            </div>

            <!-- Notes and Options -->
            <div class="row mb-4">
              <div class="col-md-8">
                <label class="form-label">ملاحظات</label>
                <textarea
                  class="form-control"
                  v-model="form.notes"
                  rows="3"
                  placeholder="أي ملاحظات إضافية..."
                ></textarea>
              </div>
              
              <div class="col-md-4">
                <div class="form-check form-switch mb-3">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="withdrawFromCashbox"
                    v-model="form.withdraw_from_cashbox"
                  />
                  <label class="form-check-label" for="withdrawFromCashbox">
                    سحب من الصندوق
                  </label>
                </div>
                
                <div v-if="form.withdraw_from_cashbox" class="alert alert-warning">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  سيتم سحب {{ Math.round(totalAmount) }} {{ form.currency }} من الصندوق الرئيسي
                </div>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex justify-content-between">
              <Link :href="route('purchase-invoices.index')" class="btn btn-secondary">
                <i class="bi bi-arrow-right me-2"></i>
                إلغاء
              </Link>
              
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="form.items.length === 0 || loading"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle me-2"></i>
                {{ loading ? 'جاري الحفظ...' : 'حفظ الفاتورة' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Add Supplier Modal -->
    <div v-if="showAddSupplierModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">إضافة مورد جديد</h5>
            <button type="button" class="btn-close" @click="showAddSupplierModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addSupplier">
              <div class="mb-3">
                <label class="form-label">اسم المورد</label>
                <input type="text" class="form-control" v-model="newSupplier.name" required />
              </div>
              <div class="mb-3">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" class="form-control" v-model="newSupplier.phone" />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showAddSupplierModal = false">
              إلغاء
            </button>
            <button type="button" class="btn btn-primary" @click="addSupplier">
              إضافة المورد
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Product Modal -->
    <div v-if="showProductModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">إضافة منتج يدوياً</h5>
            <button type="button" class="btn-close" @click="showProductModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addManualProduct">
              <div class="mb-3">
                <label class="form-label">اختر المنتج</label>
                <select class="form-select" v-model="manualProduct.product_id" required>
                  <option value="">اختر منتج...</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }} - {{ product.barcode || 'غير محدد' }}
                  </option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">الكمية</label>
                    <input type="number" class="form-control" v-model.number="manualProduct.quantity" min="1" required />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">سعر التكلفة</label>
                    <input type="number" class="form-control" v-model.number="manualProduct.cost_price" step="1" min="0" required />
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">سعر البيع</label>
                <input type="number" class="form-control" v-model.number="manualProduct.sales_price" step="1" min="0" required />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showProductModal = false">
              إلغاء
            </button>
            <button type="button" class="btn btn-primary" @click="addManualProduct">
              إضافة المنتج
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Backdrop -->
    <div v-if="showAddSupplierModal || showProductModal" class="modal-backdrop fade show"></div>
    </div><!-- إغلاق div dir="rtl" -->
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const props = defineProps({
  suppliers: Array,
  products: Array,
  translations: Object,
});

const toast = useToast();

// Form data
const form = useForm({
  supplier_id: null,
  invoice_date: new Date().toISOString().split('T')[0],
  notes: '',
  items: [],
  withdraw_from_cashbox: false,
  currency: 'IQD',
});

// Search states
const supplierSearch = ref('');
const productSearch = ref('');
const showSupplierDropdown = ref(false);
const showProductDropdown = ref(false);
const filteredSuppliers = ref([]);
const filteredProducts = ref([]);
const selectedSupplier = ref(null);

// Modal states
const showAddSupplierModal = ref(false);
const showProductModal = ref(false);
const loading = ref(false);

// New supplier form
const newSupplier = reactive({
  name: '',
  phone: '',
});

// Manual product form
const manualProduct = reactive({
  product_id: '',
  quantity: 1,
  cost_price: 0,
  sales_price: 0,
});

// Computed properties
const totalAmount = computed(() => {
  return form.items.reduce((total, item) => total + item.total, 0);
});

// Methods
const searchSuppliers = async () => {
  if (supplierSearch.value.length < 2) {
    filteredSuppliers.value = [];
    return;
  }

  try {
    const response = await axios.get(route('purchase-invoices.search-suppliers'), {
      params: { q: supplierSearch.value }
    });
    filteredSuppliers.value = response.data;
    showSupplierDropdown.value = true;
  } catch (error) {
    console.error('Error searching suppliers:', error);
  }
};

const searchProducts = async () => {
  if (productSearch.value.length < 2) {
    filteredProducts.value = [];
    return;
  }

  try {
    const response = await axios.get(route('purchase-invoices.search-products'), {
      params: { q: productSearch.value }
    });
    filteredProducts.value = response.data;
    showProductDropdown.value = true;
  } catch (error) {
    console.error('Error searching products:', error);
  }
};

const selectSupplier = (supplier) => {
  selectedSupplier.value = supplier;
  form.supplier_id = supplier.id;
  supplierSearch.value = supplier.name;
  showSupplierDropdown.value = false;
};

const clearSupplier = () => {
  selectedSupplier.value = null;
  form.supplier_id = null;
  supplierSearch.value = '';
};

const addProduct = (product) => {
  // Check if product already exists
  const existingIndex = form.items.findIndex(item => item.product.id === product.id);
  
  if (existingIndex !== -1) {
    // Update quantity
    form.items[existingIndex].quantity += 1;
    updateItemTotal(existingIndex);
  } else {
    // Add new product
    form.items.push({
      product: product,
      quantity: 1,
      cost_price: product.price_cost || 0,
      sales_price: product.price,
      total: product.price_cost || 0,
    });
  }
  
  productSearch.value = '';
  showProductDropdown.value = false;
  toast.success('تم إضافة المنتج بنجاح');
};

const addProductFromSearch = () => {
  if (filteredProducts.value.length > 0) {
    addProduct(filteredProducts.value[0]);
  }
};

const addManualProduct = () => {
  const product = props.products.find(p => p.id == manualProduct.product_id);
  if (!product) return;

  // Check if product already exists
  const existingIndex = form.items.findIndex(item => item.product.id === product.id);
  
  if (existingIndex !== -1) {
    // Update existing item
    form.items[existingIndex].quantity += manualProduct.quantity;
    form.items[existingIndex].cost_price = manualProduct.cost_price;
    form.items[existingIndex].sales_price = manualProduct.sales_price;
    updateItemTotal(existingIndex);
  } else {
    // Add new item
    form.items.push({
      product: product,
      quantity: manualProduct.quantity,
      cost_price: manualProduct.cost_price,
      sales_price: manualProduct.sales_price,
      total: manualProduct.quantity * manualProduct.cost_price,
    });
  }

  // Reset form
  manualProduct.product_id = '';
  manualProduct.quantity = 1;
  manualProduct.cost_price = 0;
  manualProduct.sales_price = 0;
  
  showProductModal.value = false;
  toast.success('تم إضافة المنتج بنجاح');
};

const updateItemTotal = (index) => {
  const item = form.items[index];
  item.total = item.quantity * item.cost_price;
};

const removeItem = (index) => {
  form.items.splice(index, 1);
  toast.success('تم حذف المنتج');
};

const addSupplier = async () => {
  try {
 
    const response = await axios.post(route('suppliers.store'), {
      name: newSupplier.name,
      phone: newSupplier.phone,
    });

    const supplier = response.data.supplier || response.data;
    selectSupplier(supplier);
    
    // Reset form
    newSupplier.name = '';
    newSupplier.phone = '';
    showAddSupplierModal.value = false;
    
    toast.success('تم إضافة المورد بنجاح');
  } catch (error) {
    toast.error('حدث خطأ أثناء إضافة المورد');
    console.error('Error adding supplier:', error);
  }
};

const getProductImage = (product) => {
  if (product.image && product.image !== 'products/default_product.png') {
    return `/public/storage/${product.image}`;
  }
  return '/public/dashboard-assets/img/product-placeholder.svg';
};

const handleImageError = (event) => {
  event.target.src = '/dashboard-assets/img/product-placeholder.svg';
};

const submitForm = () => {
  if (form.items.length === 0) {
    toast.error('يجب إضافة منتج واحد على الأقل');
    return;
  }

  loading.value = true;
  
  // Transform items to match backend expectations
  const formData = {
    supplier_id: form.supplier_id,
    invoice_date: form.invoice_date,
    notes: form.notes,
    withdraw_from_cashbox: form.withdraw_from_cashbox,
    currency: form.currency,
    items: form.items.map(item => ({
      product_id: item.product.id,
      quantity: item.quantity,
      cost_price: item.cost_price,
      sales_price: item.sales_price,
    })),
  };
  
  form.transform(() => formData).post(route('purchase-invoices.store'), {
    onSuccess: () => {
      loading.value = false;
      toast.success('تم إنشاء فاتورة المشتريات بنجاح');
    },
    onError: (errors) => {
      loading.value = false;
      console.error('Form errors:', errors);
      toast.error('حدث خطأ في حفظ الفاتورة');
    },
  });
};

// Close dropdowns when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.input-group')) {
      showSupplierDropdown.value = false;
      showProductDropdown.value = false;
    }
  });
});
</script>

<style scoped>
.product-thumb {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
}

.dropdown-menu {
  position: absolute;
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
}

.cursor-pointer {
  cursor: pointer;
}

.modal-backdrop {
  z-index: 1040;
}

.table th {
  border-top: none;
}

.table tfoot th {
  border-bottom: none;
}
</style>
