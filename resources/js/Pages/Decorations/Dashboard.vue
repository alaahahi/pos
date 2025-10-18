<template>
  <AuthenticatedLayout :translations="translations">
    <template #header>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ translations.decorations_dashboard }}
        </h2>
        <div class="d-flex gap-2">
          <button v-if="hasPermission('create decoration')" class="btn btn-primary" @click="showCreateModal = true">
            <i class="bi bi-plus-circle"></i> {{ translations.create_decoration }}
          </button>
          <button class="btn btn-success" @click="changeTab('orders')">
            <i class="bi bi-calendar-check"></i> {{ translations.decoration_orders }}
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Navigation Tabs -->
        <div class="card mb-4">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'decorations' }"
                  @click="changeTab('decorations')"
                  type="button"
                >
                  <i class="bi bi-palette"></i> {{ translations.decorations }}
                  <span class="badge bg-primary ms-2">{{ decorations?.data?.length || 0 }}</span>
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'orders' }"
                  @click="changeTab('orders')"
                  type="button"
                >
                  <i class="bi bi-calendar-check"></i> {{ translations.decoration_orders }}
                  <span class="badge bg-success ms-2">{{ orders?.data?.length || 0 }}</span>
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'analytics' }"
                  @click="changeTab('analytics')"
                  type="button"
                >
                  <i class="bi bi-graph-up"></i> {{ translations.statistics }}
                </button>
              </li>
            </ul>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
          <!-- Decorations Tab -->
          <div v-show="activeTab === 'decorations'" class="tab-pane fade" :class="{ 'show active': activeTab === 'decorations' }">
            <DecorationsList 
              :decorations="decorations"
              :customers="customers"
              :translations="translations"
              @createDecoration="showCreateModal = true"
              @refresh="refreshData"
            />
          </div>

          <!-- Orders Tab -->
          <div v-show="activeTab === 'orders'" class="tab-pane fade" :class="{ 'show active': activeTab === 'orders' }">
            <OrdersList 
              :orders="orders"
              :employees="employees"
              :translations="translations"
              @refresh="refreshData"
            />
          </div>


          <!-- Analytics Tab -->
          <div v-show="activeTab === 'analytics'" class="tab-pane fade" :class="{ 'show active': activeTab === 'analytics' }">
            <AnalyticsDashboard 
              :analytics="analytics"
              :translations="translations"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Create Decoration Modal -->
    <div v-if="showCreateModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">
          <div class="modal-header">
            <h3>{{ translations.create_decoration }}</h3>
            <button @click="showCreateModal = false" class="btn-close"></button>
          </div>
          <div class="modal-body">
            <CreateDecorationForm 
              :translations="translations"
              :exchangeRate="exchangeRate"
              :decorationTypes="decorationTypes"
              @success="handleCreateSuccess"
              @cancel="showCreateModal = false"
            />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DecorationsList from '@/Components/Decorations/DecorationsList.vue'
import OrdersList from '@/Components/Decorations/OrdersList.vue'
import AnalyticsDashboard from '@/Components/Decorations/AnalyticsDashboard.vue'
import CreateDecorationForm from '@/Components/Decorations/CreateDecorationForm.vue'

const page = usePage()

const props = defineProps({
  decorations: Object,
  orders: Object,
  customers: Array,
  employees: Array,
  analytics: Object,
  translations: Object,
  exchangeRate: {
    type: Number,
    default: 1500
  },
  decorationTypes: {
    type: Array,
    default: () => []
  },
  activeTabProp: {
    type: String,
    default: 'decorations'
  }
})

// State
const activeTab = ref(props.activeTabProp)
const showCreateModal = ref(false)

// Computed
const translations = computed(() => props.translations || {})

// Methods
const refreshData = () => {
  router.reload({ only: ['decorations', 'orders', 'analytics'] })
}

const handleCreateSuccess = () => {
  showCreateModal.value = false
  refreshData()
}

const hasPermission = (permission) => {
  return page.props.auth_permissions && page.props.auth_permissions.includes(permission)
}

// Watch for tab changes and update URL
const changeTab = (tab) => {
  activeTab.value = tab
  router.get(route('decorations.dashboard'), { tab }, {
    preserveState: true,
    replace: true
  })
}
</script>

<style scoped>
.nav-tabs .nav-link {
  border: none;
  border-radius: 0;
  color: #6c757d;
  font-weight: 500;
  padding: 1rem 1.5rem;
  transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
  border-color: transparent;
  color: #495057;
  background-color: #f8f9fa;
}

.nav-tabs .nav-link.active {
  color: #495057;
  background-color: #fff;
  border-color: #dee2e6 #dee2e6 #fff;
  border-bottom: 2px solid #007bff;
}

.tab-content {
  min-height: 500px;
}

.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: table;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  width: 90%;
  max-width: 800px;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
}

.modal-header {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-bottom: 20px;
}

.modal-header h3 {
  margin: 0;
  color: #42b983;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
}

.btn-close:hover {
  color: #666;
}
</style>
