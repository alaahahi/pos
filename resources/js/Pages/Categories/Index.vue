<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar">
      <section class="section dashboard">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="bi bi-tags me-2"></i>
              إدارة التصنيفات
            </h5>
            <Link 
              v-if="hasPermission('create category')"
              class="btn btn-primary" 
              :href="route('categories.create')"
            >
              <i class="bi bi-plus-circle"></i>
              إضافة تصنيف جديد
            </Link>
          </div>

          <div class="card-body">
            <div v-if="categories && categories.length > 0" class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>الترتيب</th>
                    <th>الاسم</th>
                    <th>الوصف</th>
                    <th>اللون</th>
                    <th>الأيقونة</th>
                    <th>الحالة</th>
                    <th>عدد المنتجات</th>
                    <th>الإجراءات</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="category in categories" :key="category.id">
                    <tr>
                      <td>{{ category.sort_order }}</td>
                      <td>
                        <strong>{{ category.name }}</strong>
                      </td>
                      <td>
                        <span v-if="category.description" class="text-muted">
                          {{ category.description.length > 50 ? category.description.substring(0, 50) + '...' : category.description }}
                        </span>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td>
                        <span 
                          class="badge" 
                          :style="{ backgroundColor: category.color, color: '#fff', padding: '5px 10px' }"
                        >
                          {{ category.color }}
                        </span>
                      </td>
                      <td>
                        <i :class="category.icon || 'bi bi-tag'" v-if="category.icon"></i>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td>
                        <span :class="category.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                          {{ category.is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                      </td>
                      <td>
                        <button 
                          @click="toggleProducts(category.id)"
                          class="btn btn-sm btn-outline-info"
                          :title="'عرض المنتجات (' + (category.products_count || 0) + ')'"
                        >
                          <i class="bi bi-box-seam"></i>
                          {{ category.products_count || 0 }}
                        </button>
                      </td>
                      <td>
                        <div class="d-flex gap-2">
                          <Link 
                            v-if="hasPermission('update category')"
                            :href="route('categories.edit', category.id)" 
                            class="btn btn-sm btn-outline-primary"
                            title="تعديل"
                          >
                            <i class="bi bi-pencil"></i>
                          </Link>
                          <button 
                            v-if="hasPermission('delete category')"
                            @click="deleteCategory(category.id)" 
                            class="btn btn-sm btn-outline-danger"
                            title="حذف"
                          >
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <!-- Products Row -->
                    <tr v-if="expandedCategories.includes(category.id)">
                    <td colspan="8" class="p-0">
                      <div class="products-container p-3" :style="{ backgroundColor: category.color + '10', borderLeft: '4px solid ' + category.color }">
                        <div v-if="loadingProducts[category.id]" class="text-center py-3">
                          <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                          </div>
                        </div>
                        <div v-else-if="category.products && category.products.length > 0">
                          <h6 class="mb-3">
                            <i :class="category.icon || 'bi bi-tag'"></i>
                            منتجات تصنيف {{ category.name }}
                            <span class="badge bg-info ms-2">{{ category.products.length }}</span>
                          </h6>
                          <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                              <thead>
                                <tr>
                                  <th>الاسم</th>
                                  <th>الموديل</th>
                                  <th>السعر</th>
                                  <th>الكمية</th>
                                  <th>الحالة</th>
                                  <th>الإجراءات</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="product in category.products" :key="product.id">
                                  <td>{{ product.name }}</td>
                                  <td>{{ product.model || '-' }}</td>
                                  <td>{{ product.price ? product.price.toLocaleString() : '-' }} IQD</td>
                                  <td>
                                    <span :class="getStockClass(product.quantity)">
                                      {{ product.quantity || 0 }}
                                    </span>
                                  </td>
                                  <td>
                                    <span :class="product.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                      {{ product.is_active ? 'نشط' : 'غير نشط' }}
                                    </span>
                                  </td>
                                  <td>
                                    <Link 
                                      :href="route('products.edit', product.id)" 
                                      class="btn btn-sm btn-outline-primary"
                                      title="تعديل المنتج"
                                    >
                                      <i class="bi bi-pencil"></i>
                                    </Link>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                        <div v-else class="text-center py-3 text-muted">
                          <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                          <p class="mt-2">لا توجد منتجات مرتبطة بهذا التصنيف</p>
                          <Link :href="route('products.create')" class="btn btn-sm btn-primary mt-2">
                            <i class="bi bi-plus-circle"></i>
                            إضافة منتج جديد
                          </Link>
                        </div>
                      </div>
                    </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center py-5">
              <i class="bi bi-tags" style="font-size: 3rem; color: #ccc;"></i>
              <p class="text-muted mt-3">لا توجد تصنيفات</p>
              <Link 
                v-if="hasPermission('create category')"
                class="btn btn-primary" 
                :href="route('categories.create')"
              >
                <i class="bi bi-plus-circle"></i>
                إضافة تصنيف جديد
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
  categories: Array,
  translations: Object,
});

const page = usePage();

// Check permissions
const hasPermission = (permission) => {
  return page.props.auth_permissions?.includes(permission) || false;
};

const expandedCategories = ref([]);
const loadingProducts = ref({});

const toggleProducts = async (categoryId) => {
  const index = expandedCategories.value.indexOf(categoryId);
  
  if (index > -1) {
    // Collapse
    expandedCategories.value.splice(index, 1);
  } else {
    // Expand - Load products if not already loaded
    expandedCategories.value.push(categoryId);
    
    // Check if products are already loaded
    const category = props.categories.find(c => c.id === categoryId);
    if (!category.products) {
      loadingProducts.value[categoryId] = true;
      try {
        const response = await axios.get(route('categories.products', categoryId));
        // Add products to category object
        category.products = response.data;
      } catch (error) {
        console.error('Error loading products:', error);
        Swal.fire('خطأ!', 'حدث خطأ أثناء جلب المنتجات', 'error');
        // Remove from expanded if error
        const errorIndex = expandedCategories.value.indexOf(categoryId);
        if (errorIndex > -1) {
          expandedCategories.value.splice(errorIndex, 1);
        }
      } finally {
        loadingProducts.value[categoryId] = false;
      }
    }
  }
};

const getStockClass = (quantity) => {
  if (quantity === 0) return 'badge bg-danger';
  if (quantity <= 5) return 'badge bg-warning';
  return 'badge bg-success';
};

const deleteCategory = (id) => {
  const category = props.categories.find(c => c.id === id);
  const productsCount = category?.products_count || 0;
  
  Swal.fire({
    title: 'تأكيد الحذف',
    html: `
      <p>هل أنت متأكد من حذف هذا التصنيف؟</p>
      ${productsCount > 0 ? `<p class="text-warning"><strong>تحذير:</strong> يوجد ${productsCount} منتج مرتبط بهذا التصنيف. سيتم إزالة التصنيف من هذه المنتجات.</p>` : ''}
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'نعم، احذف',
    cancelButtonText: 'إلغاء'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('categories.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire('تم الحذف!', 'تم حذف التصنيف بنجاح', 'success');
        },
        onError: () => {
          Swal.fire('خطأ!', 'حدث خطأ أثناء حذف التصنيف', 'error');
        }
      });
    }
  });
};
</script>

<style scoped>
.table th {
  background-color: #f8f9fa;
  font-weight: 600;
}

.products-container {
  transition: all 0.3s ease;
  border-radius: 0.5rem;
  margin: 0.5rem 0;
}

.products-container:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
  
  .products-container {
    padding: 1rem !important;
  }
  
  .table-sm th,
  .table-sm td {
    padding: 0.5rem;
    font-size: 0.8rem;
  }
}

/* Loading Animation */
.spinner-border {
  width: 2rem;
  height: 2rem;
  border-width: 0.2em;
}

/* Smooth Transitions */
.table tbody tr {
  transition: background-color 0.2s ease;
}

.table tbody tr:hover {
  background-color: #f8f9fa;
}

/* Badge Styles */
.badge {
  font-weight: 500;
  padding: 0.35em 0.65em;
}

/* Button Hover Effects */
.btn-outline-info:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-outline-primary:hover,
.btn-outline-danger:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>

