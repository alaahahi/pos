<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar">
      <section class="section dashboard">
        <!-- Statistics Cards -->
        <div class="row mb-4">
          <div class="col-xl-4 col-md-6">
            <div class="card info-card sales-card">
              <div class="card-body">
                <h5 class="card-title">إجمالي السيارات</h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-car-front"></i>
                  </div>
                  <div class="ps-3">
                    <h6>{{ stats.total }}</h6>
                    <span class="text-muted small">سيارة مسجلة</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-4 col-md-6">
            <div class="card info-card revenue-card">
              <div class="card-body">
                <h5 class="card-title">متاح للبيع</h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <div class="ps-3">
                    <h6>{{ stats.available }}</h6>
                    <span class="text-success small">سيارة متاحة</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-4 col-md-6">
            <div class="card info-card customers-card">
              <div class="card-body">
                <h5 class="card-title">مباعة</h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-bag-check"></i>
                  </div>
                  <div class="ps-3">
                    <h6>{{ stats.sold }}</h6>
                    <span class="text-danger small">سيارة مباعة</span>
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
              <i class="bi bi-car-front me-2"></i>
              مخزون السيارات
            </h5>
          </div>

          <div class="card-body">
            <!-- Filters -->
            <div class="row mb-4">
              <div class="col-md-5">
                <div class="search-box position-relative">
                  <input
                    type="text"
                    class="form-control"
                    v-model="localFilters.search"
                    placeholder="ابحث برقم الشانصي أو الموديل أو اللون..."
                    @input="debouncedSearch"
                  />
                  <i class="bi bi-search search-icon position-absolute"></i>
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="localFilters.status" @change="applyFilters">
                  <option value="">جميع الحالات</option>
                  <option value="available">متاح</option>
                  <option value="sold">مباع</option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" @click="clearFilters">
                  <i class="bi bi-arrow-clockwise"></i>
                  مسح
                </button>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-dark">
                  <tr>
                    <th>رقم الشانصي (VIN)</th>
                    <th>الماركة / الموديل</th>
                    <th>اللون</th>
                    <th>السنة</th>
                    <th>المنتج</th>
                    <th>فاتورة الشراء</th>
                    <th>فاتورة البيع</th>
                    <th>الحالة</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="vehicle in vehicles.data" :key="vehicle.id">
                    <td><strong dir="ltr">{{ vehicle.vin }}</strong></td>
                    <td>
                      <span v-if="vehicle.make || vehicle.vehicle_model">
                        {{ [vehicle.make, vehicle.vehicle_model].filter(Boolean).join(' ') }}
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td>{{ vehicle.color || '—' }}</td>
                    <td>{{ vehicle.year || '—' }}</td>
                    <td>
                      <span v-if="vehicle.product">{{ vehicle.product.name }}</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td>
                      <Link
                        v-if="vehicle.purchase_invoice"
                        :href="route('purchase-invoices.show', vehicle.purchase_invoice.id)"
                        class="text-primary"
                      >
                        {{ vehicle.purchase_invoice.invoice_number }}
                      </Link>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td>
                      <Link
                        v-if="vehicle.order"
                        :href="route('orders.show', vehicle.order.id)"
                        class="text-primary"
                      >
                        #{{ vehicle.order.id }}
                      </Link>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td>
                      <span
                        class="badge"
                        :class="vehicle.status === 'available' ? 'bg-success' : 'bg-secondary'"
                      >
                        {{ vehicle.status === 'available' ? 'متاح' : 'مباع' }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="!vehicles.data?.length">
                    <td colspan="8" class="text-center text-muted py-4">
                      لا توجد سيارات مطابقة للبحث
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <Pagination v-if="vehicles.links" :links="vehicles.links" />
          </div>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
  vehicles: Object,
  filters: Object,
  stats: Object,
  translations: Object,
});

const localFilters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
});

const applyFilters = () => {
  router.get(route('vehicles.index'), localFilters, {
    preserveState: true,
    replace: true,
  });
};

const debouncedSearch = debounce(applyFilters, 400);

const clearFilters = () => {
  localFilters.search = '';
  localFilters.status = '';
  applyFilters();
};
</script>

<style scoped>
.search-box .search-icon {
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  pointer-events: none;
}

.search-box input {
  padding-left: 2.2rem;
}
</style>
