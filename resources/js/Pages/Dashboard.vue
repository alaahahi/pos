<template>
  <AuthenticatedLayout :translations="translations">
    
    <section class="section dashboard">


      <div class="row">
        <!-- Statistics Cards -->
        <div class="col-lg-12">
          <div class="row">
            <!-- الصندوق -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card revenue-card">
                <div class="card-body">
                  <h5 class="card-title">
                    <i class="bi bi-wallet2 me-2"></i>
                    الصندوق
                  </h5>
                  <div class="d-flex flex-column">
                    <div class="d-flex align-items-center mb-2">
                      <div class="badge bg-success me-2" style="width: 45px;">
                        <i class="bi bi-currency-dollar"></i>
                      </div>
                      <h6 class="mb-0">{{ (boxBalanceUSD || 0).toLocaleString() }} $</h6>
                    </div>
                    <div class="d-flex align-items-center">
                      <div class="badge bg-primary me-2" style="width: 45px;">IQD</div>
                      <h6 class="mb-0">{{ (boxBalanceIQD || 0).toLocaleString() }} دينار</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- إجمالي الفواتير -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card sales-card">
                <div class="card-body">
                  <h5 class="card-title">إجمالي الفواتير</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-receipt"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ orderCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- الفواتير المدفوعة -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">الفواتير المدفوعة</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ orderCompletCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- الفواتير المعلقة -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card revenue-card">
                <div class="card-body">
                  <h5 class="card-title">الفواتير المعلقة</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-clock-fill"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ orderDueCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- المنتجات -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card sales-card">
                <div class="card-body">
                  <h5 class="card-title">المنتجات</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-box"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ productCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- المنتجات النشطة -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">المنتجات النشطة</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ activeProductCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- المنتجات منخفضة المخزون -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card revenue-card">
                <div class="card-body">
                  <h5 class="card-title">منخفضة المخزون</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ lowStockCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- الزبائن -->
            <div class="col-xxl-3 col-md-6">
              <div class="card info-card sales-card">
                <div class="card-body">
                  <h5 class="card-title">الزبائن</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ customerCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- طلبات الديكور -->
            <div v-if="hasPermission('read decoration')" class="col-xxl-3 col-md-6">
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">طلبات الديكور</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-palette-fill"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ decorationOrderCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- طلبات الديكور المعلقة -->
            <div v-if="hasPermission('read decoration')" class="col-xxl-3 col-md-6">
              <div class="card info-card revenue-card">
                <div class="card-body">
                  <h5 class="card-title">طلبات الديكور المعلقة</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-clock-fill"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ decorationOrderPendingCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- طلبات الديكور المكتملة -->
            <div v-if="hasPermission('read decoration')" class="col-xxl-3 col-md-6">
              <div class="card info-card sales-card">
                <div class="card-body">
                  <h5 class="card-title">طلبات الديكور المكتملة</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ decorationOrderCompletedCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- طلبات الديكور المدفوعة -->
            <!-- <div class="col-xxl-3 col-md-6">
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">طلبات الديكور المدفوعة</h5>
                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="ps-3">
                      <h6>{{ decorationOrderPaidCount }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div> -->
          </div>
        </div>
      </div>


      <!-- Charts Section -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">إحصائيات تدفق البيع</h5>
              
              <div class="row">
                <!-- إحصائيات الفواتير -->
                <div class="col-md-6">
                  <h6>حالة الفواتير</h6>
                  <Chart :chartData="ordersStatusChartData" chartId="ordersStatusChart" chartType="pie" />
                </div>
                
                <!-- إحصائيات المنتجات -->
                <div class="col-md-6">
                  <h6>حالة المنتجات</h6>
                  <Chart :chartData="productsStatusChartData" chartId="productsStatusChart" chartType="pie" />
                </div>
              </div>

              <div class="row mt-4">
                <!-- إحصائيات الديكور -->
                <div v-if="hasPermission('read decoration')" class="col-md-6">
                  <h6>طلبات الديكور حسب الحالة</h6>
                  <Chart :chartData="decorationOrdersChartData" chartId="decorationOrdersChart" chartType="pie" />
                </div>
                
                <!-- تدفق البيع الشهري -->
                <div class="col-md-6">
                  <h6>تدفق البيع الشهري</h6>
                  <Chart :chartData="monthlySalesChartData" chartId="monthlySalesChart" chartType="bar" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>


    </section>
  </AuthenticatedLayout>
</template>


<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Chart from '@/Components/Chart.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const hasPermission = (permission) => {
  return page.props.auth_permissions && page.props.auth_permissions.includes(permission);
};

defineProps({
  // Basic counts
  orderCount: Number,
  customerCount: Number,
  orderDueCount: Number,
  orderCompletCount: Number,
  productCount: Number,
  activeProductCount: Number,
  lowStockCount: Number,
  boxBalanceUSD: Number,
  boxBalanceIQD: Number,
  
  // Decoration orders
  decorationOrderCount: Number,
  decorationOrderPendingCount: Number,
  decorationOrderCompletedCount: Number,
  decorationOrderPaidCount: Number,
  
  // Chart data
  ordersStatusChartData: Object,
  productsStatusChartData: Object,
  decorationOrdersChartData: Object,
  monthlySalesChartData: Object,
  
  // Translations
  translations: Object,
});
</script>
