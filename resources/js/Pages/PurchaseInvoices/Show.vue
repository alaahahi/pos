<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar" class="purchase-show">
      <section class="section dashboard">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">
              <i class="bi bi-receipt me-2"></i>
              فاتورة مشتريات — {{ invoice.invoice_number }}
            </h5>
            <div class="d-flex gap-2 no-print">
              <button type="button" class="btn btn-outline-primary" @click="printInvoice">
                <i class="bi bi-printer me-1"></i>
                طباعة
              </button>
              <Link :href="route('purchase-invoices.index')" class="btn btn-secondary">
                <i class="bi bi-arrow-right me-1"></i>
                رجوع
              </Link>
            </div>
          </div>

          <div class="card-body">
            <!-- Header Info -->
            <div class="row mb-4 invoice-meta">
              <div class="col-md-3">
                <label class="text-muted small">رقم الفاتورة</label>
                <div class="fw-bold">{{ invoice.invoice_number }}</div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small">تاريخ الفاتورة</label>
                <div>{{ formatDate(invoice.invoice_date) }}</div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small">المورد</label>
                <div>{{ invoice.supplier?.name || 'غير محدد' }}</div>
                <small v-if="invoice.supplier?.phone" class="text-muted">{{ invoice.supplier.phone }}</small>
              </div>
              <div class="col-md-3">
                <label class="text-muted small">أنشأ بواسطة</label>
                <div>{{ invoice.creator?.name || '-' }}</div>
              </div>
            </div>

            <div v-if="invoice.notes" class="alert alert-light mb-4">
              <strong>ملاحظات:</strong> {{ invoice.notes }}
            </div>

            <div v-if="invoice.attachment_url" class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
              <div>
                <strong><i class="bi bi-paperclip me-1"></i>مرفق الفاتورة</strong>
              </div>
              <div class="d-flex gap-2 no-print">
                <a
                  :href="invoice.attachment_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="btn btn-sm btn-outline-primary"
                >
                  <i class="bi bi-eye me-1"></i>
                  عرض
                </a>
                <a
                  :href="invoice.attachment_url"
                  download
                  class="btn btn-sm btn-outline-secondary"
                >
                  <i class="bi bi-download me-1"></i>
                  تحميل
                </a>
              </div>
            </div>

            <!-- Items -->
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="table-dark">
                  <tr>
                    <th>#</th>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>سعر التكلفة</th>
                    <th>سعر البيع</th>
                    <th>المجموع</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(item, index) in invoice.items" :key="item.id">
                    <tr>
                      <td>{{ index + 1 }}</td>
                      <td>
                        <strong>{{ item.product?.name }}</strong>
                        <br v-if="item.product?.barcode">
                        <small v-if="item.product?.barcode" class="text-muted">باركود: {{ item.product.barcode }}</small>
                      </td>
                      <td>{{ item.quantity }}</td>
                      <td>{{ formatAmount(item.cost_price) }}</td>
                      <td>{{ formatAmount(item.sales_price) }}</td>
                      <td><strong>{{ formatAmount(item.total) }}</strong></td>
                    </tr>
                    <!-- Vehicle details -->
                    <tr v-if="item.vehicles?.length" class="vehicle-sub-row">
                      <td></td>
                      <td colspan="5">
                        <div class="vehicle-block">
                          <h6 class="mb-2">
                            <i class="bi bi-car-front me-1"></i>
                            السيارات المسجلة ({{ item.vehicles.length }})
                          </h6>
                          <table class="table table-sm table-striped mb-0 vehicle-inner-table">
                            <thead>
                              <tr>
                                <th>الشانصي (VIN)</th>
                                <th>الموديل</th>
                                <th>اللون</th>
                                <th>الشركة</th>
                                <th>السنة</th>
                                <th>الحالة</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="vehicle in item.vehicles" :key="vehicle.id">
                                <td class="vin-cell">{{ vehicle.vin }}</td>
                                <td>{{ vehicle.vehicle_model || '-' }}</td>
                                <td>{{ vehicle.color || '-' }}</td>
                                <td>{{ vehicle.make || '-' }}</td>
                                <td>{{ vehicle.year || '-' }}</td>
                                <td>
                                  <span :class="vehicle.status === 'sold' ? 'badge bg-danger' : 'badge bg-success'">
                                    {{ vehicle.status === 'sold' ? 'مباعة' : 'متاحة' }}
                                  </span>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="5" class="text-end">المجموع الكلي:</th>
                    <th class="text-primary">{{ formatAmount(invoice.total_amount) }} دينار</th>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div v-if="totalVehicles > 0" class="mt-3 text-muted small">
              <i class="bi bi-info-circle me-1"></i>
              إجمالي السيارات المسجلة في هذه الفاتورة: {{ totalVehicles }}
            </div>
          </div>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  invoice: Object,
  translations: Object,
});

const totalVehicles = computed(() => {
  if (!props.invoice?.items) return 0;
  return props.invoice.items.reduce((sum, item) => sum + (item.vehicles?.length || 0), 0);
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ar-IQ');
};

const formatAmount = (amount) => {
  return Math.round(parseFloat(amount || 0)).toLocaleString();
};

const printInvoice = () => {
  window.print();
};
</script>

<style scoped>
.vehicle-sub-row td {
  background: #f8f9fa;
  border-top: none;
}

.vehicle-block {
  padding: 0.5rem 0;
}

.vehicle-inner-table {
  font-size: 0.85rem;
}

.vin-cell {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.invoice-meta label {
  display: block;
  margin-bottom: 0.15rem;
}

@media print {
  .no-print {
    display: none !important;
  }

  .purchase-show {
    padding: 0;
  }

  .card {
    border: none;
    box-shadow: none;
  }

  .vehicle-sub-row td {
    background: #fff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>

<style>
@media print {
  #header,
  #sidebar,
  .sidebar-overlay,
  .alert {
    display: none !important;
  }

  #main {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }
}
</style>
