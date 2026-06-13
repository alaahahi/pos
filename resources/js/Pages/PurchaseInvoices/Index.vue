<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar">
    

    <section class="section dashboard">
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">إجمالي الفواتير <span>| جميع الفواتير</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-receipt"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ purchaseInvoices?.total || 0 }}</h6>
                  <span class="text-success small pt-1 fw-bold">فاتورة</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">إجمالي المشتريات <span>| هذا الشهر</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="ps-3 stat-values">
                  <div class="stat-line">
                    <h6>{{ formatStatAmount(monthlyStats?.IQD?.total) }}</h6>
                    <span class="text-primary small pt-1 fw-bold">دينار</span>
                  </div>
                  <div class="stat-line">
                    <h6>{{ formatStatAmount(monthlyStats?.['$']?.total) }}</h6>
                    <span class="text-info small pt-1 fw-bold">دولار</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card customers-card">
            <div class="card-body">
              <h5 class="card-title">عدد الموردين <span>| نشط</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-people"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ getActiveSuppliersCount() }}</h6>
                  <span class="text-success small pt-1 fw-bold">مورد</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">متوسط الفاتورة <span>| هذا الشهر</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-graph-up"></i>
                </div>
                <div class="ps-3 stat-values">
                  <div class="stat-line">
                    <h6>{{ formatStatAmount(monthlyStats?.IQD?.average) }}</h6>
                    <span class="text-primary small pt-1 fw-bold">دينار</span>
                  </div>
                  <div class="stat-line">
                    <h6>{{ formatStatAmount(monthlyStats?.['$']?.average) }}</h6>
                    <span class="text-info small pt-1 fw-bold">دولار</span>
                  </div>
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
            <i class="bi bi-receipt me-2"></i>
            إدارة فواتير المشتريات
          </h5>
          <div class="d-flex gap-2">
            <Link :href="route('purchase-invoices.create')" class="btn btn-primary">
              <i class="bi bi-plus-circle"></i>
              إنشاء فاتورة جديدة
            </Link>
          </div>
        </div>
        
        <div class="card-body">
          <!-- Filters -->
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="search-box">
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="filters.search" 
                  placeholder="ابحث برقم الفاتورة أو المورد..."
                  @input="debouncedSearch"
                />
              </div>
            </div>
            <div class="col-md-2">
              <select class="form-select" v-model="filters.supplier_id" @change="applyFilters">
                <option value="">جميع الموردين</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                  {{ supplier.name }}
                </option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" v-model="filters.date_from" @change="applyFilters" placeholder="من تاريخ" />
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" v-model="filters.date_to" @change="applyFilters" placeholder="إلى تاريخ" />
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-secondary w-100" @click="clearFilters">
                <i class="bi bi-arrow-clockwise"></i>
                مسح الفلاتر
              </button>
            </div>
          </div>

          <!-- Invoices Table -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-dark">
                <tr>
                  <th>رقم الفاتورة</th>
                  <th>المورد</th>
                  <th>تاريخ الفاتورة</th>
                  <th>عدد المنتجات</th>
                  <th>المبلغ الإجمالي</th>
                  <th>أنشأ بواسطة</th>
                  <th>تاريخ الإنشاء</th>
                  <th class="text-center">مرفق الفاتورة</th>
                  <th class="text-center">الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="invoice in purchaseInvoices?.data" :key="invoice.id" class="invoice-row">
                  <td>
                    <div class="invoice-number">
                      <strong>{{ invoice.invoice_number }}</strong>
                    </div>
                  </td>
                  <td>
                    <div v-if="invoice.supplier" class="supplier-info">
                      <strong>{{ invoice.supplier.name }}</strong>
                      <br>
                      <small class="text-muted">{{ invoice.supplier.phone }}</small>
                    </div>
                    <span v-else class="text-muted">غير محدد</span>
                  </td>
                  <td>
                    <span class="date-text">{{ formatDate(invoice.invoice_date) }}</span>
                  </td>
                  <td>
                    <span class="badge bg-info">{{ invoice.items?.length || 0 }} منتج</span>
                  </td>
                  <td>
                    <div class="amount-info">
                      <strong class="text-success">{{ Math.round(invoice.total_amount) }} دينار</strong>
                    </div>
                  </td>
                  <td>
                    <div class="creator-info">
                      <strong>{{ invoice.creator?.name }}</strong>
                    </div>
                  </td>
                  <td>
                    <span class="date-text">{{ formatDateTime(invoice.created_at) }}</span>
                  </td>
                  <td class="text-center">
                    <a
                      v-if="invoice.attachment_url"
                      :href="invoice.attachment_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="btn btn-sm btn-outline-secondary"
                      :title="isPdfAttachment(invoice.attachment) ? 'عرض PDF' : 'عرض المرفق'"
                    >
                      <i :class="isPdfAttachment(invoice.attachment) ? 'bi bi-file-earmark-pdf' : 'bi bi-image'"></i>
                    </a>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <div class="btn-group" role="group">
                        <Link 
                          :href="route('purchase-invoices.show', invoice.id)" 
                          class="btn btn-sm btn-outline-primary"
                          title="عرض التفاصيل"
                        >
                          <i class="bi bi-eye"></i>
                        </Link>
                        
                        <Link 
                          :href="route('purchase-invoices.edit', invoice.id)" 
                          class="btn btn-sm btn-outline-warning"
                          title="تعديل"
                        >
                          <i class="bi bi-pencil"></i>
                        </Link>
                        
                        <button 
                          @click="deleteInvoice(invoice)"
                          class="btn btn-sm btn-outline-danger"
                          title="حذف"
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

          <!-- Empty State -->
          <div v-if="!purchaseInvoices?.data?.length" class="text-center py-5">
            <i class="bi bi-receipt display-1 text-muted"></i>
            <h5 class="text-muted mt-3">لا توجد فواتير مشتريات</h5>
            <p class="text-muted">ابدأ بإنشاء فاتورة مشتريات جديدة</p>
            <Link :href="route('purchase-invoices.create')" class="btn btn-primary">
              <i class="bi bi-plus-circle me-2"></i>
              إنشاء فاتورة جديدة
            </Link>
          </div>
        </div>
      </div>
      
      <Pagination :links="purchaseInvoices?.links" />
    </section>
    </div><!-- إغلاق div dir="rtl" -->
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';
import { useToast } from 'vue-toastification';

const props = defineProps({
  purchaseInvoices: Object,
  suppliers: Array,
  monthlyStats: Object,
  filters: Object,
  translations: Object,
});

const toast = useToast();

// Reactive filters
const filters = reactive({
  search: props.filters?.search || '',
  supplier_id: props.filters?.supplier_id || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
});

// Methods
const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

const applyFilters = () => {
  router.get(route('purchase-invoices.index'), filters, {
    preserveState: true,
    replace: true,
  });
};

const clearFilters = () => {
  filters.search = '';
  filters.supplier_id = '';
  filters.date_from = '';
  filters.date_to = '';
  applyFilters();
};

const formatDate = (date) => {
  if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US');
};

const formatDateTime = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleString('en-US');
};

const isPdfAttachment = (path) => {
  if (!path) return false;
  return path.toLowerCase().endsWith('.pdf');
};

const formatStatAmount = (amount) => {
  const value = Math.round(parseFloat(amount) || 0);
  return value.toLocaleString();
};

const getActiveSuppliersCount = () => {
  if (!props.suppliers) return 0;
  return props.suppliers.length;
};

const deleteInvoice = (invoice) => {
  if (confirm(`هل أنت متأكد من حذف فاتورة المشتريات رقم ${invoice.invoice_number}؟`)) {
    router.delete(route('purchase-invoices.destroy', invoice.id), {
      onSuccess: () => {
        toast.success('تم حذف فاتورة المشتريات بنجاح');
      },
      onError: () => {
        toast.error('حدث خطأ أثناء حذف فاتورة المشتريات');
      },
    });
  }
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

.stat-values {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.stat-line {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.stat-line h6 {
  margin: 0;
  font-size: 1.1rem;
  line-height: 1.2;
}

.stat-line:not(:last-child) {
  padding-bottom: 0.15rem;
  border-bottom: 1px dashed rgba(0, 0, 0, 0.08);
}

/* Search Box */
.search-box input {
  padding-right: 0.75rem;
}

/* Table Styling */
.invoice-row {
  transition: all 0.2s ease;
}

.invoice-row:hover {
  background-color: #f8fafc;
}

.invoice-number {
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}

.supplier-info strong {
  color: #1f2937;
}

.amount-info strong {
  font-size: 1rem;
}

.date-text {
  font-size: 0.9rem;
  color: #6b7280;
}

.creator-info strong {
  color: #374151;
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
  .info-card .card-icon {
    width: 50px;
    height: 50px;
    font-size: 1.2rem;
  }
  
  .table-responsive {
    font-size: 0.9rem;
  }
}
</style>
