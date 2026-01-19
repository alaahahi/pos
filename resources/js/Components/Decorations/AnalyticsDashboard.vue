<template>
  <div>
    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                  {{ translations.decorations }}
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                  {{ analytics.total_decorations }}
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-palette fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                  {{ translations.total_orders }}
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                  {{ analytics.total_orders }}
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-calendar-check fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                  {{ translations.total_revenue }}
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                  {{ formatCurrency(analytics.total_revenue) }}
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                  {{ translations.monthly_orders }}
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                  {{ analytics.monthly_orders }}
                </div>
              </div>
              <div class="col-auto">
                <i class="bi bi-graph-up fa-2x text-gray-300"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
      <!-- Orders by Status -->
      <div class="col-xl-6 col-lg-7">
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ translations.orders_by_status }}</h6>
          </div>
          <div class="card-body">
            <div class="chart-pie pt-4 pb-2">
              <canvas id="ordersStatusChart"></canvas>
            </div>
            <div class="mt-4 text-center small">
              <span class="mr-2">
                <i class="fas fa-circle text-primary"></i> {{ translations.created }}
              </span>
              <span class="mr-2">
                <i class="fas fa-circle text-success"></i> {{ translations.completed }}
              </span>
              <span class="mr-2">
                <i class="fas fa-circle text-info"></i> {{ translations.executing }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="col-xl-6 col-lg-5">
        <div class="card shadow mb-4">
          <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">{{ translations.recent_activity }}</h6>
          </div>
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="d-flex align-items-center">
                <div class="icon-circle bg-primary">
                  <i class="bi bi-plus-circle text-white"></i>
                </div>
                <div class="ms-3">
                  <div class="small text-gray-500">{{ translations.pending_orders }}</div>
                  <div class="font-weight-bold">{{ analytics.pending_orders }}</div>
                </div>
              </div>
            </div>
            
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="d-flex align-items-center">
                <div class="icon-circle bg-success">
                  <i class="bi bi-check-circle text-white"></i>
                </div>
                <div class="ms-3">
                  <div class="small text-gray-500">{{ translations.completed_orders }}</div>
                  <div class="font-weight-bold">{{ analytics.completed_orders }}</div>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <div class="icon-circle bg-info">
                  <i class="bi bi-palette text-white"></i>
                </div>
                <div class="ms-3">
                  <div class="small text-gray-500">{{ translations.active }} {{ translations.decorations }}</div>
                  <div class="font-weight-bold">{{ analytics.active_decorations }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

const props = defineProps({
  analytics: Object,
  translations: Object
})

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EGP',
    minimumFractionDigits: 2
  }).format(amount || 0)
}

// Initialize charts on mount
onMounted(() => {
  // You can add Chart.js initialization here if needed
  console.log('Analytics loaded:', props.analytics)
})
</script>

<style scoped>
.border-left-primary {
  border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
  border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
  border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
  border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
  font-size: 0.7rem;
}

.text-gray-300 {
  color: #dddfeb !important;
}

.text-gray-500 {
  color: #858796 !important;
}

.text-gray-800 {
  color: #5a5c69 !important;
}

.icon-circle {
  height: 2.5rem;
  width: 2.5rem;
  border-radius: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.shadow {
  box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.card {
  border: 1px solid #e3e6f0;
  border-radius: 0.35rem;
}

.card-header {
  background-color: #f8f9fc;
  border-bottom: 1px solid #e3e6f0;
}

.fa-2x {
  font-size: 2em;
}
</style>
