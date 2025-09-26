<template>
  <div>
    <!-- Header with Create Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">
        <i class="bi bi-palette text-primary"></i>
        {{ translations.decorations_list }}
        <span class="badge bg-primary ms-2">{{ decorations?.data?.length || 0 }}</span>
      </h4>
      <div class="d-flex gap-2">
        <button v-if="hasPermission('create decoration')" class="btn btn-primary btn-lg" @click="$emit('createDecoration')">
          <i class="bi bi-plus-circle"></i> {{ translations.create_decoration }}
        </button>
        <a :href="route('public.gallery')" class="btn btn-outline-info btn-lg" target="_blank">
          <i class="bi bi-eye"></i> عرض المعرض العام
        </a>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <form @submit.prevent="searchDecorations">
          <div class="row">
            <div class="col-md-3">
              <input 
                type="text" 
                class="form-control" 
                v-model="searchForm.search" 
                :placeholder="translations.search"
              >
            </div>
            <div class="col-md-2">
              <select class="form-select" v-model="searchForm.type">
                <option value="">{{ translations.all_types }}</option>
                <option value="birthday">{{ translations.birthday }}</option>
                <option value="gender_reveal">{{ translations.gender_reveal }}</option>
                <option value="baby_shower">{{ translations.baby_shower }}</option>
                <option value="wedding">{{ translations.wedding }}</option>
                <option value="graduation">{{ translations.graduation }}</option>
                <option value="corporate">{{ translations.corporate }}</option>
                <option value="religious">{{ translations.religious }}</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" v-model="searchForm.currency">
                <option value="">{{ translations.all_currencies }}</option>
                <option value="dinar">{{ translations.dinar }}</option>
                <option value="dollar">{{ translations.dollar }}</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" v-model="searchForm.is_active">
                <option value="">{{ translations.all_status }}</option>
                <option value="1">{{ translations.active }}</option>
                <option value="0">{{ translations.inactive }}</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> {{ translations.search }}
              </button>
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-outline-secondary w-100" @click="resetFilters">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Decorations Grid -->
    <div class="row" v-if="decorations.data.length > 0">
      <div class="col-xl-4 col-lg-4 col-md-6 mb-4" v-for="decoration in decorations.data" :key="decoration.id">
        <div class="card h-100 decoration-card">
          <!-- Image -->
          <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
            <img 
              v-if="decoration.image_url" 
              :src="decoration.image_url" 
              :alt="decoration.name"
              class="w-100 h-100 object-cover"
            >
            <div v-else class="w-100 h-100 bg-light d-flex align-items-center justify-content-center">
              <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
            </div>
            
            <!-- Currency Badge -->
            <div class="position-absolute top-0 end-0 m-2">
              <span class="badge" :class="decoration.currency === 'dollar' ? 'bg-success' : 'bg-primary'">
                {{ decoration.currency === 'dollar' ? translations.dollar : translations.dinar }}
              </span>
            </div>
            
            <!-- Status Badge -->
            <div class="position-absolute top-0 start-0 m-2">
              <span class="badge" :class="decoration.is_active ? 'bg-success' : 'bg-secondary'">
                {{ decoration.is_active ? translations.active : translations.inactive }}
              </span>
            </div>
          </div>

          <!-- Card Body -->
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">{{ decoration.name }}</h5>
            <p class="card-text text-muted">{{ decoration.description }}</p>
            <div class="mt-auto">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info">{{ getTypeName(decoration.type) }}</span>
                <span class="badge" :class="decoration.is_active ? 'bg-success' : 'bg-secondary'">
                  {{ decoration.is_active ? props.translations.active : props.translations.inactive }}
                </span>
              </div>
              
              <!-- Price Display -->
              <div class="price-section mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold text-primary fs-5">
                    {{ formatPrice(decoration) }}
                  </span>
                  <small class="text-muted">
                    <i class="bi bi-clock"></i> {{ decoration.duration_hours }} {{ props.translations.hours }}
                  </small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <small class="text-muted">
                    <i class="bi bi-people"></i> {{ decoration.team_size }} {{ props.translations.team_members }}
                  </small>
                  <button @click="toggleCurrency(decoration)" class="btn btn-sm btn-outline-info" :title="props.translations.switch_currency">
                    <i class="bi bi-arrow-repeat"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="actions-container">
              <div class="row g-2">
                <div class="col-6">
                  <button @click="openOrderModal(decoration)" class="btn btn-success btn-action w-100">
                    <i class="bi bi-plus-circle"></i>
                    <span>{{ translations.create_order }}</span>
                  </button>
                </div>
                <div class="col-6">
                  <button @click="toggleCurrency(decoration)" class="btn btn-outline-info btn-action w-100" :title="translations.switch_currency">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>{{ translations.switch_currency }}</span>
                  </button>
                </div>
              </div>
              
              <div class="row g-2 mt-2">
                <div class="col-3">
                  <button v-if="hasPermission('update decoration')" @click="openEditModal(decoration)" class="btn btn-outline-warning btn-action w-100">
                    <i class="bi bi-pencil"></i>
                    <span>{{ translations.edit }}</span>
                  </button>
                </div>
                <div class="col-3">
                  <button @click="viewDecoration(decoration)" class="btn btn-outline-primary btn-action w-100">
                    <i class="bi bi-eye"></i>
                    <span>{{ translations.view }}</span>
                  </button>
                </div>
                <div class="col-3">
                  <a :href="route('public.decoration.show', decoration.id)" class="btn btn-outline-info btn-action w-100" target="_blank">
                    <i class="bi bi-globe"></i>
                    <span>معرض</span>
                  </a>
                </div>
                <div class="col-3">
                  <button 
                    v-if="hasPermission('delete decoration')" 
                    @click="deleteDecoration(decoration.id)" 
                    class="btn btn-outline-danger btn-action w-100"
                  >
                    <i class="bi bi-trash"></i>
                    <span>{{ translations.delete }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5 empty-state">
      <div class="empty-state-icon">
        <i class="bi bi-palette"></i>
      </div>
      <h4 class="empty-state-title">{{ translations.no_decorations }}</h4>
      <p class="empty-state-description">{{ translations.no_decorations_description }}</p>
      <div class="empty-state-actions">
        <button v-if="hasPermission('create decoration')" class="btn btn-primary btn-lg" @click="$emit('createDecoration')">
          <i class="bi bi-plus-circle"></i> {{ translations.create_first_decoration }}
        </button>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="decorations.data.length > 0" class="d-flex justify-content-center mt-4">
      <Pagination :links="decorations.links" />
    </div>

    <!-- Order Creation Modal -->
    <OrderCreationModal 
      v-if="showOrderModal"
      :decoration="selectedDecoration"
      :customers="customers"
      :translations="translations"
      @close="showOrderModal = false"
      @success="handleOrderSuccess"
    />

    <!-- Edit Modal -->
    <EditDecorationModal 
      v-if="showEditModal"
      :decoration="selectedDecoration"
      :translations="translations"
      @close="showEditModal = false"
      @success="handleEditSuccess"
    />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'
import OrderCreationModal from '@/Components/Decorations/OrderCreationModal.vue'
import EditDecorationModal from '@/Components/Decorations/EditDecorationModal.vue'

const props = defineProps({
  decorations: Object,
  customers: Array,
  translations: Object
})

const emit = defineEmits(['refresh'])

// State
const showOrderModal = ref(false)
const showEditModal = ref(false)
const selectedDecoration = ref(null)

// Search form
const searchForm = reactive({
  search: '',
  type: '',
  currency: '',
  is_active: ''
})

// Methods
const searchDecorations = () => {
  router.get(route('decorations.dashboard'), { 
    tab: 'decorations',
    ...searchForm 
  }, {
    preserveState: true,
    replace: true
  })
}

const resetFilters = () => {
  searchForm.search = ''
  searchForm.type = ''
  searchForm.currency = ''
  searchForm.is_active = ''
  searchDecorations()
}

const formatPrice = (decoration) => {
  const price = decoration.base_price || decoration.current_price || 0
  const currency = decoration.currency === 'dollar' ? props.translations.dollar : props.translations.dinar
  return `${parseFloat(price).toFixed(2)} ${currency}`
}


const getTypeName = (type) => {
  const typeMap = {
    'birthday': props.translations.birthday,
    'gender_reveal': props.translations.gender_reveal,
    'baby_shower': props.translations.baby_shower,
    'wedding': props.translations.wedding,
    'graduation': props.translations.graduation,
    'corporate': props.translations.corporate,
    'religious': props.translations.religious
  }
  return typeMap[type] || type
}

const toggleCurrency = (decoration) => {
  const newCurrency = decoration.currency === 'dinar' ? 'dollar' : 'dinar'
  
  router.patch(route('decorations.update', decoration.id), {
    currency: newCurrency
  }, {
    preserveState: true,
    onSuccess: () => {
      // The page will automatically refresh due to Inertia redirect
      console.log('Currency updated successfully')
    },
    onError: (errors) => {
      console.error('Error updating currency:', errors)
    }
  })
}

const openOrderModal = (decoration) => {
  selectedDecoration.value = decoration
  showOrderModal.value = true
}

const openEditModal = (decoration) => {
  selectedDecoration.value = decoration
  showEditModal.value = true
}

const viewDecoration = (decoration) => {
  router.get(route('decorations.show', decoration.id))
}

const deleteDecoration = (id) => {
  if (confirm(props.translations.confirm_delete)) {
    router.delete(route('decorations.destroy', id), {
      onSuccess: () => {
        emit('refresh')
      }
    })
  }
}

const handleOrderSuccess = () => {
  showOrderModal.value = false
  emit('refresh')
}

const handleEditSuccess = () => {
  showEditModal.value = false
  emit('refresh')
}

const hasPermission = (permission) => {
  return window.permissions && window.permissions.includes(permission)
}
</script>

<style scoped>
.decoration-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid #e3e6f0;
}

.decoration-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.object-cover {
  object-fit: cover;
}

.btn-group .btn {
  flex: 1;
}

.badge {
  font-size: 0.75em;
}

.fs-5 {
  font-size: 1.25rem;
}

/* تحسين رسالة "لا توجد ديكورات" في الوضع الليلي */
.empty-state {
  padding: 3rem 1rem;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin: 2rem 0;
}

.empty-state-icon {
  margin-bottom: 1.5rem;
}

.empty-state-icon i {
  font-size: 5rem;
  color: #6b7280;
  transition: all 0.3s ease;
}

.empty-state-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #6b7280;
}

.empty-state-description {
  font-size: 1rem;
  color: #9ca3af;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.empty-state-actions .btn {
  padding: 0.75rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
}

.empty-state-actions .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

/* الوضع الليلي - تحسينات خاصة */
.dark .empty-state {
  background: linear-gradient(135deg, rgba(31, 41, 55, 0.8) 0%, rgba(17, 24, 39, 0.9) 100%);
  border: 2px solid rgba(59, 130, 246, 0.2);
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(59, 130, 246, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.dark .empty-state-icon i {
  color: #60a5fa;
  text-shadow: 0 0 20px rgba(96, 165, 250, 0.5);
  animation: pulse 2s infinite;
}

.dark .empty-state-title {
  color: #dbeafe;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.dark .empty-state-description {
  color: #9ca3af;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.dark .empty-state-actions .btn {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border: 1px solid #3b82f6;
  color: #ffffff;
  box-shadow: 
    0 4px 12px rgba(59, 130, 246, 0.4),
    0 0 0 1px rgba(59, 130, 246, 0.2);
}

.dark .empty-state-actions .btn:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  border-color: #2563eb;
  box-shadow: 
    0 6px 16px rgba(59, 130, 246, 0.5),
    0 0 0 1px rgba(59, 130, 246, 0.3);
  transform: translateY(-3px);
}

/* تأثير النبض للأيقونة */
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.8;
  }
}

/* Actions Container - تصميم الأزرار المحسن */
.actions-container {
  margin-top: 1rem;
}

.btn-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.3s ease;
  border-width: 2px;
  position: relative;
  overflow: hidden;
}

.btn-action::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.btn-action:hover::before {
  left: 100%;
}

.btn-action i {
  font-size: 1rem;
  transition: transform 0.3s ease;
}

.btn-action:hover i {
  transform: scale(1.1);
}

.btn-action span {
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Button Colors */
.btn-success.btn-action {
  background: linear-gradient(135deg, #28a745, #20c997);
  border-color: #28a745;
  color: white;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-success.btn-action:hover {
  background: linear-gradient(135deg, #218838, #1e7e34);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.btn-outline-info.btn-action {
  border-color: #17a2b8;
  color: #17a2b8;
  background: rgba(23, 162, 184, 0.1);
}

.btn-outline-info.btn-action:hover {
  background: #17a2b8;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(23, 162, 184, 0.3);
}

.btn-outline-warning.btn-action {
  border-color: #ffc107;
  color: #ffc107;
  background: rgba(255, 193, 7, 0.1);
}

.btn-outline-warning.btn-action:hover {
  background: #ffc107;
  color: #212529;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 193, 7, 0.3);
}

.btn-outline-primary.btn-action {
  border-color: #007bff;
  color: #007bff;
  background: rgba(0, 123, 255, 0.1);
}

.btn-outline-primary.btn-action:hover {
  background: #007bff;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
}

.btn-outline-danger.btn-action {
  border-color: #dc3545;
  color: #dc3545;
  background: rgba(220, 53, 69, 0.1);
}

.btn-outline-danger.btn-action:hover {
  background: #dc3545;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .btn-outline-info.btn-action {
    background: rgba(23, 162, 184, 0.2);
  }
  
  .btn-outline-warning.btn-action {
    background: rgba(255, 193, 7, 0.2);
  }
  
  .btn-outline-primary.btn-action {
    background: rgba(0, 123, 255, 0.2);
  }
  
  .btn-outline-danger.btn-action {
    background: rgba(220, 53, 69, 0.2);
  }
}
</style>
