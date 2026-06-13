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
                
                <!-- Quick Add Product -->
                <div class="quick-add-box mb-3 p-3 rounded">
                  <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                      <label class="form-label small mb-1">اسم المنتج <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control"
                        v-model="quickProduct.name"
                        placeholder="مثال: Toyota RAV4"
                        @keyup.enter="addQuickProduct"
                      />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small mb-1 text-muted">الكمية</label>
                      <input
                        type="number"
                        class="form-control"
                        v-model.number="quickProduct.quantity"
                        min="1"
                        placeholder="1"
                      />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small mb-1 text-muted">سعر التكلفة</label>
                      <input
                        type="number"
                        class="form-control"
                        v-model.number="quickProduct.cost_price"
                        min="0"
                        placeholder="0"
                      />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label small mb-1 text-muted">سعر البيع</label>
                      <input
                        type="number"
                        class="form-control"
                        v-model.number="quickProduct.sales_price"
                        min="0"
                        placeholder="0"
                      />
                    </div>
                    <div class="col-md-2">
                      <button type="button" class="btn btn-primary w-100" @click="addQuickProduct">
                        <i class="bi bi-plus-circle me-1"></i>
                        إضافة
                      </button>
                    </div>
                  </div>
                  <small class="text-muted mt-2 d-block">
                    <i class="bi bi-info-circle me-1"></i>
                    الاسم فقط إجباري — إذا المنتج غير مسجل يُنشأ تلقائياً
                  </small>
                </div>

                <!-- Search Existing Products (optional) -->
                <div class="row mb-3">
                  <div class="col-12">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text"><i class="bi bi-search"></i></span>
                      <input
                        type="text"
                        class="form-control"
                        v-model="productSearch"
                        @input="searchProducts"
                        placeholder="بحث عن منتج موجود (اختياري)..."
                        @keyup.enter="addProductFromSearch"
                      />
                      <button type="button" class="btn btn-outline-secondary" @click="addProductFromSearch">
                        إضافة
                      </button>
                    </div>
                    
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
                        <th>تتبع شانصي</th>
                        <th>الإجراءات</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="(item, index) in form.items" :key="index">
                      <tr>
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
                              <span v-if="item.is_new" class="badge bg-success ms-1">جديد</span>
                              <br>
                              <small class="text-muted">{{ item.product.barcode || (item.is_new ? 'سيُنشأ عند الحفظ' : 'غير محدد') }}</small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <input
                            type="number"
                            class="form-control form-control-sm"
                            v-model.number="item.quantity"
                            @input="onQuantityChange(index)"
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
                          <div class="form-check form-switch">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              :id="'trackVin' + index"
                              v-model="item.track_by_vin"
                              @change="onTrackVinToggle(index)"
                            />
                          </div>
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
                      <!-- Vehicle details row (optional) -->
                      <tr v-if="item.track_by_vin" class="vehicle-details-row">
                        <td colspan="7">
                          <div class="vehicle-section p-3 rounded">
                            <h6 class="mb-3">
                              <i class="bi bi-car-front me-2"></i>
                              بيانات السيارات ({{ item.vehicles.length }} / {{ item.quantity }})
                            </h6>
                            <div
                              v-for="(vehicle, vIndex) in item.vehicles"
                              :key="vIndex"
                              class="vehicle-row mb-3 p-2 border rounded"
                            >
                              <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                  <label class="form-label small">رقم الشانصي (VIN)</label>
                                  <div class="input-group input-group-sm">
                                    <input
                                      type="text"
                                      class="form-control text-uppercase"
                                      v-model="vehicle.vin"
                                      maxlength="17"
                                      placeholder="17 حرف"
                                    />
                                    <button
                                      type="button"
                                      class="btn btn-outline-info"
                                      @click="decodeVehicleVin(index, vIndex)"
                                      :disabled="vehicle.decoding"
                                      title="التحقق من الشانصي (اختياري)"
                                    >
                                      <span v-if="vehicle.decoding" class="spinner-border spinner-border-sm"></span>
                                      <i v-else class="bi bi-search"></i>
                                    </button>
                                  </div>
                                  <small v-if="vehicle.vin_valid === true" class="text-success">
                                    <i class="bi bi-check-circle"></i> شانصي صالح
                                  </small>
                                  <small v-else-if="vehicle.vin_valid === false" class="text-warning">
                                    {{ vehicle.vin_error || 'تحقق غير متأكد' }}
                                  </small>
                                </div>
                                <div class="col-md-2">
                                  <label class="form-label small">الموديل</label>
                                  <input type="text" class="form-control form-control-sm" v-model="vehicle.vehicle_model" placeholder="مثال: RAV4" />
                                </div>
                                <div class="col-md-2">
                                  <label class="form-label small">اللون</label>
                                  <input type="text" class="form-control form-control-sm" v-model="vehicle.color" placeholder="مثال: أبيض" />
                                </div>
                                <div class="col-md-2">
                                  <label class="form-label small">الشركة</label>
                                  <input type="text" class="form-control form-control-sm" v-model="vehicle.make" placeholder="مثال: Toyota" />
                                </div>
                                <div class="col-md-1">
                                  <label class="form-label small">السنة</label>
                                  <input type="text" class="form-control form-control-sm" v-model="vehicle.year" placeholder="2024" />
                                </div>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      </template>
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
                  <p class="text-muted">أدخل اسم المنتج في الحقل أعلاه واضغط إضافة</p>
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

                <div class="mt-3">
                  <label class="form-label">مرفق الفاتورة</label>
                  <input
                    ref="attachmentInput"
                    type="file"
                    class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*"
                    @change="onAttachmentChange"
                  />
                  <small class="text-muted d-block mt-1">رفع ملف (PDF أو صورة) — اختياري، الحد الأقصى 10 م.ب</small>
                  <div v-if="attachmentFileName" class="mt-2 d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">
                      <i class="bi bi-paperclip me-1"></i>
                      {{ attachmentFileName }}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="clearAttachment">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
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

    <!-- Modal Backdrop -->
    <div v-if="showAddSupplierModal" class="modal-backdrop fade show"></div>
    </div><!-- إغلاق div dir="rtl" -->
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { decodeVin } from '@/utils/vinDecoder';

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
const loading = ref(false);
const attachmentInput = ref(null);
const attachmentFile = ref(null);
const attachmentFileName = ref('');

// New supplier form
const newSupplier = reactive({
  name: '',
  phone: '',
});

// Quick product add (name required, rest optional)
const quickProduct = reactive({
  name: '',
  quantity: null,
  cost_price: null,
  sales_price: null,
});

const emptyVehicle = () => ({
  vin: '',
  color: '',
  vehicle_model: '',
  make: '',
  year: '',
  decoding: false,
  vin_valid: null,
  vin_error: null,
});

const syncVehicleRows = (item) => {
  if (!item.track_by_vin) {
    item.vehicles = [];
    return;
  }
  if (!item.vehicles) item.vehicles = [];
  while (item.vehicles.length < item.quantity) {
    item.vehicles.push(emptyVehicle());
  }
  while (item.vehicles.length > item.quantity) {
    item.vehicles.pop();
  }
};

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

const buildItem = (product, options = {}) => {
  const qty = options.quantity ?? 1;
  const cost = options.cost_price ?? product.price_cost ?? 0;
  const sales = options.sales_price ?? product.price ?? 0;

  return {
    product,
    is_new: options.is_new ?? false,
    quantity: qty,
    cost_price: cost,
    sales_price: sales,
    total: qty * cost,
    track_by_vin: false,
    vehicles: [],
  };
};

const findItemIndex = (product) => {
  if (product.id) {
    return form.items.findIndex(item => item.product.id === product.id);
  }
  return form.items.findIndex(
    item => item.is_new && item.product.name.toLowerCase() === product.name.toLowerCase()
  );
};

const addProduct = (product, options = {}) => {
  const existingIndex = findItemIndex(product);

  if (existingIndex !== -1) {
    form.items[existingIndex].quantity += (options.quantity ?? 1);
    if (options.cost_price != null) form.items[existingIndex].cost_price = options.cost_price;
    if (options.sales_price != null) form.items[existingIndex].sales_price = options.sales_price;
    onQuantityChange(existingIndex);
  } else {
    form.items.push(buildItem(product, options));
  }

  productSearch.value = '';
  showProductDropdown.value = false;
  toast.success('تم إضافة المنتج بنجاح');
};

const addQuickProduct = () => {
  const name = quickProduct.name.trim();
  if (!name) {
    toast.error('اسم المنتج مطلوب');
    return;
  }

  const options = {
    quantity: quickProduct.quantity || 1,
    cost_price: quickProduct.cost_price ?? 0,
    sales_price: quickProduct.sales_price ?? 0,
  };

  const existing = props.products.find(p => p.name.toLowerCase() === name.toLowerCase());

  if (existing) {
    addProduct(existing, options);
  } else {
    addProduct({ id: null, name, barcode: null, image: null, price: 0, price_cost: 0 }, {
      ...options,
      is_new: true,
    });
  }

  quickProduct.name = '';
  quickProduct.quantity = null;
  quickProduct.cost_price = null;
  quickProduct.sales_price = null;
};

const addProductFromSearch = () => {
  if (filteredProducts.value.length > 0) {
    addProduct(filteredProducts.value[0]);
    return;
  }

  const name = productSearch.value.trim();
  if (name.length >= 2) {
    quickProduct.name = name;
    addQuickProduct();
  }
};

const updateItemTotal = (index) => {
  const item = form.items[index];
  const qty = item.quantity || 1;
  const cost = item.cost_price ?? 0;
  item.quantity = qty;
  item.total = qty * cost;
};

const onQuantityChange = (index) => {
  updateItemTotal(index);
  syncVehicleRows(form.items[index]);
};

const onTrackVinToggle = (index) => {
  syncVehicleRows(form.items[index]);
};

const decodeVehicleVin = async (itemIndex, vehicleIndex) => {
  const vehicle = form.items[itemIndex].vehicles[vehicleIndex];
  if (!vehicle.vin || vehicle.vin.length < 17) {
    toast.warning('أدخل رقم شانصي كامل (17 حرف)');
    return;
  }

  vehicle.decoding = true;
  vehicle.vin_valid = null;
  vehicle.vin_error = null;

  const result = await decodeVin(vehicle.vin);
  vehicle.decoding = false;

  if (result.make) vehicle.make = result.make;
  if (result.vehicle_model) vehicle.vehicle_model = result.vehicle_model;
  if (result.year) vehicle.year = result.year;

  vehicle.vin_valid = result.valid;
  vehicle.vin_error = result.error;

  if (result.valid) {
    toast.success('تم التحقق من الشانصي بنجاح');
  } else if (result.error) {
    toast.info(result.error + ' — يمكنك المتابعة بدون تحقق');
  }
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

const onAttachmentChange = (event) => {
  const file = event.target.files?.[0] || null;
  attachmentFile.value = file;
  attachmentFileName.value = file ? file.name : '';
};

const clearAttachment = () => {
  attachmentFile.value = null;
  attachmentFileName.value = '';
  if (attachmentInput.value) {
    attachmentInput.value.value = '';
  }
};

const submitForm = () => {
  if (form.items.length === 0) {
    toast.error('يجب إضافة منتج واحد على الأقل');
    return;
  }

  loading.value = true;
  
  for (const item of form.items) {
    if (item.track_by_vin) {
      const filled = item.vehicles.filter(v => v.vin?.trim());
      if (filled.length !== item.quantity) {
        toast.error(`يجب إدخال ${item.quantity} رقم شانصي للمنتج: ${item.product.name}`);
        loading.value = false;
        return;
      }
      for (const v of filled) {
        if (v.vin.trim().length !== 17) {
          toast.error(`رقم الشانصي يجب أن يكون 17 حرفاً: ${v.vin}`);
          loading.value = false;
          return;
        }
      }
    }
  }

  // Transform items to match backend expectations
  const formData = {
    supplier_id: form.supplier_id,
    invoice_date: form.invoice_date,
    notes: form.notes,
    withdraw_from_cashbox: form.withdraw_from_cashbox ? 1 : 0,
    currency: form.currency,
    items: form.items.map(item => {
      const mapped = {
        product_id: item.product.id || null,
        product_name: item.product.id ? null : item.product.name,
        quantity: item.quantity || 1,
        cost_price: item.cost_price ?? 0,
        sales_price: item.sales_price ?? 0,
      };
      if (item.track_by_vin && item.vehicles?.length) {
        mapped.vehicles = item.vehicles.map(v => ({
          vin: v.vin.trim().toUpperCase(),
          color: v.color || null,
          vehicle_model: v.vehicle_model || null,
          make: v.make || null,
          year: v.year || null,
        }));
      }
      return mapped;
    }),
  };
  
  const hasAttachment = !!attachmentFile.value;

  form.transform(() => ({
    ...formData,
    attachment: attachmentFile.value,
  })).post(route('purchase-invoices.store'), {
    forceFormData: hasAttachment,
    onSuccess: () => {
      loading.value = false;
      clearAttachment();
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

.vehicle-details-row td {
  background: #f8f9fa;
  border-top: none;
}

.vehicle-section {
  background: #fff;
  border: 1px dashed #dee2e6;
}

.quick-add-box {
  background: #f0f7ff;
  border: 1px solid #cfe2ff;
}
</style>
