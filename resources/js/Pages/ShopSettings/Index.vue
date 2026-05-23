<template>
  <AuthenticatedLayout :translations="translations">
    <template #header>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="mb-0">إدارة المتجر</h2>
        <a :href="route('shop.index')" target="_blank" class="btn btn-outline-info btn-sm">
          <i class="bi bi-shop"></i> عرض المتجر
        </a>
      </div>
    </template>

    <div class="py-3" dir="rtl">
      <ul class="nav nav-tabs mb-3">
        <li v-for="t in tabs" :key="t.id" class="nav-item">
          <button type="button" class="nav-link" :class="{ active: tab === t.id }" @click="switchTab(t.id)">{{ t.label }}</button>
        </li>
      </ul>

      <!-- General -->
      <div v-show="tab === 'general'" class="card">
        <div class="card-body">
          <form @submit.prevent="generalForm.put(route('shop-settings.general.update'))">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">اسم المتجر</label>
                <input v-model="generalForm.company_name" type="text" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label">واتساب (أرقام فقط)</label>
                <input v-model="generalForm.whatsapp" type="text" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label">كود الدولة</label>
                <input v-model="generalForm.phone_country_code" type="text" class="form-control" />
              </div>
              <div class="col-md-4">
                <label class="form-label">العملة</label>
                <input v-model="generalForm.default_currency" type="text" class="form-control" />
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                  <input v-model="generalForm.is_enabled" type="checkbox" class="form-check-input" id="enabled" />
                  <label class="form-check-label" for="enabled">المتجر مفعّل</label>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3" :disabled="generalForm.processing">حفظ</button>
          </form>
        </div>
      </div>

      <!-- Categories -->
      <div v-show="tab === 'categories'">
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ editingCategoryId ? 'تعديل فئة' : 'إضافة فئة' }}</span>
            <button
              v-if="editingCategoryId"
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="cancelEditCategory"
            >
              إلغاء التعديل
            </button>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitCategory" class="row g-3">
              <div class="col-md-4">
                <label class="form-label">اسم الفئة *</label>
                <input v-model="catForm.name" type="text" class="form-control" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">صورة الفئة</label>
                <input
                  ref="categoryImageInput"
                  type="file"
                  class="form-control"
                  accept="image/jpeg,image/png,image/jpg,image/webp"
                  @change="onCategoryImage"
                />
                <div v-if="editingCategoryImageUrl" class="mt-2">
                  <img
                    :src="editingCategoryImageUrl"
                    alt="الصورة الحالية"
                    class="rounded"
                    style="width:56px;height:56px;object-fit:cover"
                  />
                  <small class="d-block text-muted">الصورة الحالية — اترك الحقل فارغاً للإبقاء عليها</small>
                </div>
                <small v-else class="text-muted">تظهر في صفحة المتجر</small>
              </div>
              <div class="col-md-4">
                <label class="form-label">الوصف</label>
                <textarea v-model="catForm.description" class="form-control" rows="2" />
              </div>
              <div class="col-md-3">
                <label class="form-label">حزمة: الكمية</label>
                <input v-model.number="catForm.bundle_quantity" type="number" min="2" class="form-control" placeholder="5" />
              </div>
              <div class="col-md-3">
                <label class="form-label">سعر الحزمة</label>
                <input v-model.number="catForm.bundle_price" type="number" step="0.01" class="form-control" />
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                  <input v-model="catForm.is_active" type="checkbox" class="form-check-input" id="cat-active" />
                  <label class="form-check-label" for="cat-active">مفعّلة في المتجر</label>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary" :disabled="catForm.processing">
                  {{ editingCategoryId ? 'حفظ التعديلات' : 'إضافة الفئة' }}
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="row g-3">
          <div v-for="c in categories" :key="c.id" class="col-md-4 col-lg-3">
            <div class="card h-100 overflow-hidden shadow-sm">
              <div class="shop-category-thumb bg-light">
                <img
                  v-if="categoryImageSrc(c)"
                  :src="categoryImageSrc(c)"
                  :alt="c.name"
                  loading="lazy"
                  @error="(e) => onCategoryImageError(e, c)"
                />
                <div v-else class="shop-category-thumb-placeholder text-muted">
                  <i class="bi bi-image fs-1" />
                </div>
              </div>
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                  <h6 class="mb-0">{{ c.name }}</h6>
                  <span class="badge bg-secondary shrink-0">{{ c.products_count ?? 0 }} منتج</span>
                </div>
                <p v-if="c.description" class="small text-muted mb-1 line-clamp-2">{{ c.description }}</p>
                <p v-if="c.bundle_quantity" class="small text-muted mb-2">{{ c.bundle_quantity }} بـ {{ c.bundle_price }}</p>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" @click="startEditCategory(c)">
                    تعديل
                  </button>
                  <button
                    v-if="!(c.products_count > 0)"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="deleteCategory(c.id)"
                  >
                    حذف
                  </button>
                </div>
                <p v-if="c.products_count > 0" class="small text-muted mb-0 mt-2">
                  لا يمكن الحذف — يوجد {{ c.products_count }} منتج مرتبط
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div v-show="tab === 'products'">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <div class="form-check form-switch mb-0">
            <input
              id="show-trashed-products"
              v-model="showTrashedProducts"
              type="checkbox"
              class="form-check-input"
              role="switch"
              @change="toggleTrashedProducts"
            />
            <label class="form-check-label" for="show-trashed-products">عرض المحذوف فقط</label>
          </div>
          <span v-if="showTrashedProducts" class="badge bg-secondary">سلة المحذوفات</span>
        </div>

        <div v-if="!showTrashedProducts" class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ editingProductId ? 'تعديل منتج' : 'إضافة منتج' }}</span>
            <button
              v-if="editingProductId"
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="cancelEditProduct"
            >
              إلغاء التعديل
            </button>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitProduct" class="row g-3">
              <div class="col-md-3">
                <label class="form-label">الاسم *</label>
                <input v-model="prodForm.name" class="form-control" required />
              </div>
              <div class="col-md-2">
                <label class="form-label">الفئة *</label>
                <select
                  v-model="prodForm.shop_category_id"
                  class="form-select"
                  required
                  :disabled="!categories.length"
                >
                  <option value="" disabled>اختر الفئة</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <small v-if="!categories.length" class="text-danger">أضف فئة قبل إضافة منتجات</small>
              </div>
              <div class="col-md-2">
                <label class="form-label">السعر *</label>
                <input v-model.number="prodForm.price" type="number" step="0.01" class="form-control" required />
              </div>
              <div class="col-md-3">
                <label class="form-label">الوصف</label>
                <textarea v-model="prodForm.description" class="form-control" rows="2" />
              </div>
              <div class="col-md-2">
                <label class="form-label">صورة المنتج</label>
                <input
                  ref="productImageInput"
                  type="file"
                  class="form-control"
                  accept="image/jpeg,image/png,image/jpg,image/webp"
                  @change="onProductImage"
                />
                <div v-if="editingProductImageUrl" class="mt-2">
                  <img
                    :src="editingProductImageUrl"
                    alt="الصورة الحالية"
                    class="rounded"
                    style="width:56px;height:56px;object-fit:cover"
                  />
                  <small class="d-block text-muted">الصورة الحالية — اترك الحقل فارغاً للإبقاء عليها</small>
                </div>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <div class="form-check mb-2">
                  <input v-model="prodForm.is_active" type="checkbox" class="form-check-input" id="prod-active" />
                  <label class="form-check-label" for="prod-active">مفعّل في المتجر</label>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary" :disabled="prodForm.processing || !categories.length">
                  {{ editingProductId ? 'حفظ التعديلات' : 'إضافة منتج' }}
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="card">
          <table class="table mb-0 align-middle">
            <thead>
              <tr>
                <th style="width:72px">صورة</th>
                <th>الاسم</th>
                <th>الفئة</th>
                <th>السعر</th>
                <th v-if="showTrashedProducts">تاريخ الحذف</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!products.data?.length">
                <td :colspan="showTrashedProducts ? 6 : 5" class="text-center text-muted py-4">
                  {{ showTrashedProducts ? 'لا توجد منتجات محذوفة' : 'لا توجد منتجات' }}
                </td>
              </tr>
              <tr
                v-for="p in products.data"
                :key="p.id"
                :class="{ 'table-secondary': showTrashedProducts }"
              >
                <td>
                  <img
                    v-if="productImageSrc(p)"
                    :src="productImageSrc(p)"
                    :alt="p.name"
                    class="rounded"
                    style="width:56px;height:56px;object-fit:cover"
                    @error="(e) => onProductImageError(e, p)"
                  />
                  <span v-else class="text-muted small">—</span>
                </td>
                <td>{{ p.name }}</td>
                <td>{{ p.category?.name || '-' }}</td>
                <td>{{ p.price }}</td>
                <td v-if="showTrashedProducts" class="small text-muted">{{ formatDate(p.deleted_at) }}</td>
                <td class="text-nowrap">
                  <template v-if="showTrashedProducts">
                    <button type="button" class="btn btn-sm btn-outline-success" @click="restoreProduct(p.id)">
                      <i class="bi bi-arrow-counterclockwise" /> استعادة
                    </button>
                  </template>
                  <template v-else>
                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="startEditProduct(p)">تعديل</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteProduct(p.id)">حذف</button>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Promotions -->
      <div v-show="tab === 'promotions'">
        <div class="card mb-3">
          <div class="card-body">
            <form @submit.prevent="submitPromotion" class="row g-2 align-items-end">
              <div class="col-md-3"><input v-model="promoForm.name" class="form-control" placeholder="اسم العرض" required /></div>
              <div class="col-md-2"><input v-model.number="promoForm.min_cart_total" type="number" step="0.01" class="form-control" placeholder="حد الفاتورة" required /></div>
              <div class="col-md-2">
                <select v-model="promoForm.discount_type" class="form-select">
                  <option value="fixed">مبلغ ثابت</option>
                  <option value="percent">نسبة %</option>
                </select>
              </div>
              <div class="col-md-2"><input v-model.number="promoForm.discount_value" type="number" step="0.01" class="form-control" placeholder="قيمة الخصم" required /></div>
              <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">إضافة</button></div>
            </form>
          </div>
        </div>
        <div class="card">
          <table class="table mb-0">
            <thead><tr><th>الاسم</th><th>حد الفاتورة</th><th>الخصم</th><th></th></tr></thead>
            <tbody>
              <tr v-for="pr in promotions" :key="pr.id">
                <td>{{ pr.name }}</td>
                <td>{{ pr.min_cart_total }}</td>
                <td>{{ pr.discount_value }} {{ pr.discount_type === 'percent' ? '%' : '$' }}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" @click="deletePromotion(pr.id)">حذف</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Coupons -->
      <div v-show="tab === 'coupons'">
        <div class="card mb-3">
          <div class="card-body">
            <form @submit.prevent="submitCoupon" class="row g-2 align-items-end">
              <div class="col-md-2"><input v-model="couponForm.code" class="form-control" placeholder="الكود" required /></div>
              <div class="col-md-2"><input v-model.number="couponForm.min_cart_total" type="number" step="0.01" class="form-control" placeholder="حد الفاتورة" /></div>
              <div class="col-md-2">
                <select v-model="couponForm.discount_type" class="form-select">
                  <option value="fixed">مبلغ</option>
                  <option value="percent">نسبة</option>
                </select>
              </div>
              <div class="col-md-2"><input v-model.number="couponForm.discount_value" type="number" step="0.01" class="form-control" placeholder="الخصم" required /></div>
              <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">إضافة</button></div>
            </form>
          </div>
        </div>
        <div class="card">
          <table class="table mb-0">
            <thead><tr><th>الكود</th><th>حد الفاتورة</th><th>الخصم</th><th></th></tr></thead>
            <tbody>
              <tr v-for="cp in coupons" :key="cp.id">
                <td>{{ cp.code }}</td>
                <td>{{ cp.min_cart_total }}</td>
                <td>{{ cp.discount_value }} {{ cp.discount_type === 'percent' ? '%' : '' }}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" @click="deleteCoupon(cp.id)">حذف</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Orders -->
      <div v-show="tab === 'orders'">
        <div class="d-flex gap-2 mb-3 flex-wrap">
          <select v-model="orderFilters.status" class="form-select w-auto" @change="reloadOrders">
            <option value="">كل الحالات</option>
            <option value="pending">قيد الانتظار</option>
            <option value="confirmed">مؤكد</option>
            <option value="cancelled">ملغي</option>
          </select>
          <input v-model="orderFilters.phone" type="text" class="form-control w-auto" placeholder="هاتف" @keyup.enter="reloadOrders" />
          <button type="button" class="btn btn-secondary" @click="reloadOrders">بحث</button>
          <a :href="exportUrl" class="btn btn-outline-success">تصدير CSV</a>
        </div>
        <div class="card">
          <table class="table mb-0">
            <thead><tr><th>رقم</th><th>هاتف</th><th>المجموع</th><th>الحالة</th><th>تاريخ</th><th></th></tr></thead>
            <tbody>
              <tr v-for="o in ordersList" :key="o.id">
                <td>{{ o.order_number }}</td>
                <td>{{ o.customer_phone }}</td>
                <td>{{ o.total_amount }} {{ o.currency }}</td>
                <td>{{ o.status }}</td>
                <td>{{ formatDate(o.created_at) }}</td>
                <td>
                  <select class="form-select form-select-sm" :value="o.status" @change="e => updateStatus(o.id, e.target.value)">
                    <option value="pending">pending</option>
                    <option value="confirmed">confirmed</option>
                    <option value="cancelled">cancelled</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useShopStorageUrl } from '@/composables/useShopStorageUrl';

const props = defineProps({
  translations: Object,
  activeTab: String,
  settings: Object,
  categories: Array,
  products: Object,
  promotions: Array,
  coupons: Array,
  orders: Object,
  orderFilters: Object,
  storageBases: {
    type: Array,
    default: () => [],
  },
  showTrashedProducts: {
    type: Boolean,
    default: false,
  },
});

const tabs = [
  { id: 'general', label: 'عام' },
  { id: 'categories', label: 'الفئات' },
  { id: 'products', label: 'المنتجات' },
  { id: 'promotions', label: 'خصم تلقائي' },
  { id: 'coupons', label: 'كوبونات' },
  { id: 'orders', label: 'الطلبات' },
];

const tab = ref(props.activeTab || 'general');
const showTrashedProducts = ref(props.showTrashedProducts ?? false);
const orderFilters = ref({ ...props.orderFilters });

const ordersList = computed(() => props.orders?.data || []);

const switchTab = (id) => {
  tab.value = id;
  const query = { tab: id };
  if (id === 'products' && showTrashedProducts.value) {
    query.trashed = 1;
  }
  router.get(route('shop-settings.index'), query, { preserveState: true, preserveScroll: true });
};

const toggleTrashedProducts = () => {
  if (showTrashedProducts.value) {
    cancelEditProduct();
  }
  router.get(route('shop-settings.index'), {
    tab: 'products',
    trashed: showTrashedProducts.value ? 1 : undefined,
  }, { preserveState: true, preserveScroll: true });
};

const generalForm = useForm({
  is_enabled: props.settings?.is_enabled ?? true,
  company_name: props.settings?.company_name || '',
  whatsapp: props.settings?.whatsapp || '',
  phone_country_code: props.settings?.phone_country_code || '964',
  default_currency: props.settings?.default_currency || 'USD',
});

const categoryImageInput = ref(null);
const productImageInput = ref(null);
const editingCategoryId = ref(null);
const editingCategoryImageUrl = ref(null);
const editingProductId = ref(null);
const editingProductImageUrl = ref(null);

const catForm = useForm({
  name: '', description: '', sort_order: 0, is_active: true,
  bundle_quantity: null, bundle_price: null, bundle_currency: null, image: null,
});
const onCategoryImage = (e) => {
  const file = e.target.files?.[0];
  if (file) catForm.image = file;
};

const resetCategoryForm = () => {
  editingCategoryId.value = null;
  editingCategoryImageUrl.value = null;
  catForm.reset();
  catForm.is_active = true;
  catForm.sort_order = 0;
  if (categoryImageInput.value) categoryImageInput.value.value = '';
};

const startEditCategory = (category) => {
  editingCategoryId.value = category.id;
  editingCategoryImageUrl.value = categoryImageSrc(category);
  catForm.name = category.name;
  catForm.description = category.description || '';
  catForm.sort_order = category.sort_order ?? 0;
  catForm.is_active = category.is_active !== false;
  catForm.bundle_quantity = category.bundle_quantity ?? null;
  catForm.bundle_price = category.bundle_price != null ? Number(category.bundle_price) : null;
  catForm.bundle_currency = category.bundle_currency || null;
  catForm.image = null;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEditCategory = () => resetCategoryForm();

const submitCategory = () => {
  const options = {
    onSuccess: () => resetCategoryForm(),
  };

  catForm.transform((data) => ({ ...data, image: catForm.image }));

  if (editingCategoryId.value) {
    catForm.post(route('shop-settings.categories.update', editingCategoryId.value), options);
  } else {
    catForm.post(route('shop-settings.categories.store'), options);
  }
};

const prodForm = useForm({
  name: '', shop_category_id: '', description: '', price: 0, currency: 'USD', is_active: true, image: null,
});
const onProductImage = (e) => {
  const file = e.target.files?.[0];
  if (file) prodForm.image = file;
};

const resetProductForm = () => {
  editingProductId.value = null;
  editingProductImageUrl.value = null;
  prodForm.reset();
  prodForm.is_active = true;
  prodForm.currency = 'USD';
  if (productImageInput.value) productImageInput.value.value = '';
};

const startEditProduct = (product) => {
  editingProductId.value = product.id;
  editingProductImageUrl.value = productImageSrc(product);
  prodForm.name = product.name;
  prodForm.shop_category_id = product.shop_category_id || '';
  prodForm.description = product.description || '';
  prodForm.price = Number(product.price);
  prodForm.currency = product.currency || 'USD';
  prodForm.is_active = product.is_active !== false;
  prodForm.image = null;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEditProduct = () => resetProductForm();

const submitProduct = () => {
  const options = {
    onSuccess: () => resetProductForm(),
  };

  prodForm.transform((data) => ({ ...data, image: prodForm.image }));

  if (editingProductId.value) {
    prodForm.post(route('shop-settings.products.update', editingProductId.value), options);
  } else {
    prodForm.post(route('shop-settings.products.store'), options);
  }
};

const promoForm = useForm({
  name: '', min_cart_total: 0, discount_type: 'fixed', discount_value: 0, is_active: true, sort_order: 0,
});
const submitPromotion = () => promoForm.post(route('shop-settings.promotions.store'), { onSuccess: () => promoForm.reset() });

const couponForm = useForm({
  code: '', min_cart_total: 0, discount_type: 'fixed', discount_value: 0, is_active: true,
});
const submitCoupon = () => couponForm.post(route('shop-settings.coupons.store'), { onSuccess: () => couponForm.reset() });

const deleteCategory = (id) => {
  if (!confirm('حذف هذه الفئة؟')) return;
  router.delete(route('shop-settings.categories.destroy', id));
};
const deleteProduct = (id) => {
  if (!confirm('حذف هذا المنتج؟ يمكن استعادته لاحقاً من «عرض المحذوف فقط».')) return;
  router.delete(route('shop-settings.products.destroy', id));
};

const restoreProduct = (id) => {
  router.post(route('shop-settings.products.restore', { shopProduct: id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showTrashedProducts.value = false;
    },
  });
};
const deletePromotion = (id) => router.delete(route('shop-settings.promotions.destroy', id));
const deleteCoupon = (id) => router.delete(route('shop-settings.coupons.destroy', id));

const reloadOrders = () => router.get(route('shop-settings.index'), { tab: 'orders', ...orderFilters.value }, { preserveState: true });
const updateStatus = (id, status) => router.patch(route('shop-settings.orders.status', id), { status });
const exportUrl = computed(() => route('shop-settings.orders.export', orderFilters.value));
const formatDate = (d) => d ? new Date(d).toLocaleString('ar') : '';

const { src: categoryImageSrc, onError: onCategoryImageError } = useShopStorageUrl(props.storageBases);
const { src: productImageSrc, onError: onProductImageError } = useShopStorageUrl(props.storageBases);
</script>

<style scoped>
.shop-category-thumb {
  height: 180px;
  overflow: hidden;
  position: relative;
}

.shop-category-thumb img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.shop-category-thumb-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}
</style>

