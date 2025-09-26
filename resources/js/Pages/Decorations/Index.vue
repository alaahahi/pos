<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ translations.decorations }}
        </h2>
        <div class="d-flex gap-2">
          <Link v-if="hasPermission('create decoration')" :href="route('decorations.create')" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> {{ translations.create_decoration }}
          </Link>
          <Link :href="route('public.gallery')" class="btn btn-outline-info" target="_blank">
            <i class="bi bi-eye"></i> عرض المعرض العام
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Search and Filters -->
        <div class="card mb-4">
          <div class="card-body">
            <form @submit.prevent="searchDecorations">
              <div class="row">
                <div class="col-md-4">
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="searchForm.search" 
                    :placeholder="translations.search"
                  >
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
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
              </div>
            </form>
          </div>
        </div>

        <!-- Decorations Grid -->
        <div class="row" v-if="decorations.data.length > 0">
          <div class="col-md-4 mb-4" v-for="decoration in decorations.data" :key="decoration.id">
            <div class="card h-100">
              <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                <img 
                  v-if="decoration.thumbnail_url || decoration.image_url" 
                  :src="decoration.thumbnail_url || decoration.image_url" 
                  :alt="decoration.name"
                  class="w-100 h-100 object-cover"
                >
                <div v-else class="w-100 h-100 bg-light d-flex align-items-center justify-content-center">
                  <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                </div>
                
                <!-- Media Indicators -->
                <div class="position-absolute top-0 end-0 p-2">
                  <div v-if="decoration.is_featured" class="badge bg-warning mb-1">
                    <i class="bi bi-star-fill"></i>
                  </div>
                  <div v-if="decoration.video_url" class="badge bg-danger mb-1">
                    <i class="bi bi-play-circle-fill"></i>
                  </div>
                  <div v-if="decoration.images && decoration.images.length > 1" class="badge bg-info">
                    <i class="bi bi-images"></i> {{ decoration.images.length }}
                  </div>
                </div>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">{{ decoration.name }}</h5>
                <p class="card-text text-muted">{{ decoration.description }}</p>
                <div class="mt-auto">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-info">{{ getTypeName(decoration.type) }}</span>
                    <span class="badge" :class="decoration.is_active ? 'bg-success' : 'bg-secondary'">
                      {{ decoration.is_active ? translations.active : translations.inactive }}
                    </span>
                  </div>
                  
                  <!-- Price Display -->
                  <div class="price-section mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="fw-bold text-primary fs-5">
                        {{ formatPrice(decoration) }}
                      </span>
                      <small class="text-muted">
                        <i class="bi bi-clock"></i> {{ decoration.duration_hours }} {{ translations.hours }}
                      </small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted">
                        <i class="bi bi-people"></i> {{ decoration.team_size }} {{ translations.team_members }}
                      </small>
                      <button @click="toggleCurrency(decoration)" class="btn btn-sm btn-outline-info" :title="translations.switch_currency">
                        <i class="bi bi-arrow-repeat"></i>
                      </button>
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
                        <Link v-if="hasPermission('update decoration')" :href="route('decorations.edit', decoration.id)" class="btn btn-outline-warning btn-action w-100">
                          <i class="bi bi-pencil"></i>
                          <span>{{ translations.edit }}</span>
                        </Link>
                      </div>
                      <div class="col-3">
                        <Link :href="route('decorations.show', decoration.id)" class="btn btn-outline-primary btn-action w-100">
                          <i class="bi bi-eye"></i>
                          <span>{{ translations.view }}</span>
                        </Link>
                      </div>
                      <div class="col-3">
                        <Link :href="route('public.decoration.show', decoration.id)" class="btn btn-outline-info btn-action w-100" target="_blank">
                          <i class="bi bi-globe"></i>
                          <span>معرض</span>
                        </Link>
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
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-5">
          <i class="bi bi-palette text-muted" style="font-size: 4rem;"></i>
          <h4 class="text-muted mt-3">{{ translations.no_decorations }}</h4>
          <p class="text-muted">{{ translations.no_decorations_description }}</p>
         
        </div>
        <button v-if="hasPermission('create decoration')" class="btn btn-primary" @click="showCreateModal = true">
            <i class="bi bi-plus-circle"></i> {{ translations.create_first_decoration }}
          </button>
        <!-- Pagination -->
        <div v-if="decorations.data.length > 0" class="d-flex justify-content-center mt-4">
          <Pagination :links="decorations.links" />
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">
          <div class="modal-header">
            <h3>{{ translations.create_decoration }}</h3>
            <button @click="showCreateModal = false" class="btn-close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitCreateForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.decoration_name }} *</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="createForm.name"
                      required
                    >
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.decoration_description }}</label>
                    <input  
                      class="form-control" 
                     type="text"
                      v-model="createForm.description"
                    > 
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.decoration_type }} *</label>
                    <select class="form-select" v-model="createForm.type" required>
                      <option value="">اختر النوع</option>
                      <option value="birthday">{{ translations.birthday }}</option>
                      <option value="gender_reveal">{{ translations.gender_reveal }}</option>
                      <option value="baby_shower">{{ translations.baby_shower }}</option>
                      <option value="wedding">{{ translations.wedding }}</option>
                      <option value="graduation">{{ translations.graduation }}</option>
                      <option value="corporate">{{ translations.corporate }}</option>
                      <option value="religious">{{ translations.religious }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.currency }} *</label>
                    <select class="form-select" v-model="createForm.currency" required>
                      <option value="dinar">{{ translations.dinar }}</option>
                      <option value="dollar">{{ translations.dollar }}</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.base_price }} *</label>
                    <input 
                      type="number" 
                      step="0.01"
                      class="form-control" 
                      v-model="createForm.base_price"
                      required
                    >
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.duration_hours }}</label>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model="createForm.duration_hours"
                    >
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.team_size }}</label>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model="createForm.team_size"
                    >
                  </div>
                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="createForm.is_featured"
                        id="create_is_featured"
                      >
                      <label class="form-check-label" for="create_is_featured">
                        ديكور مميز
                      </label>
                    </div>
                  </div>
                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="createForm.is_active"
                        id="create_is_active"
                      >
                      <label class="form-check-label" for="create_is_active">
                        {{ translations.active }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Media Section -->
              <div class="row">
                <div class="col-12">
                  <h6 class="mt-3 mb-3">الوسائط</h6>
                  
                  <!-- Main Image -->
                  <div class="mb-3">
                    <label class="form-label">الصورة الرئيسية</label>
                    <input 
                      type="file" 
                      class="form-control" 
                      @change="handleMainImage"
                      accept="image/*"
                    >
                    <div v-if="mainImagePreview" class="mt-2">
                      <img :src="mainImagePreview" alt="Preview" class="preview-image">
                    </div>
                  </div>

                  <!-- Additional Images -->
                  <div class="mb-3">
                    <label class="form-label">صور إضافية</label>
                    <input 
                      type="file" 
                      class="form-control" 
                      @change="handleImages"
                      accept="image/*"
                      multiple
                    >
                    <div v-if="imagePreviews.length > 0" class="mt-3">
                      <div class="row">
                        <div v-for="(preview, index) in imagePreviews" :key="index" class="col-md-3 mb-2">
                          <div class="image-preview">
                            <img :src="preview" alt="Preview" class="preview-image">
                            <button type="button" @click="removeImage(index)" class="remove-btn">
                              <i class="bi bi-x"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Video URL -->
                  <div class="mb-3">
                    <label class="form-label">رابط الفيديو</label>
                    <input 
                      type="url" 
                      class="form-control" 
                      v-model="createForm.video_url"
                      placeholder="https://example.com/video.mp4"
                    >
                  </div>
                </div>
              </div>
              
              <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-secondary text-center w-100" @click="showCreateModal = false">
                  {{ translations.cancel }}
                </button>
                <button type="submit" class="btn btn-primary w-100 text-center" :disabled="processing">
                  <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                  {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">
          <div class="modal-header">
            <h3>{{ translations.edit_decoration }}</h3>
            <button @click="showEditModal = false" class="btn-close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitEditForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.decoration_name }} *</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="editForm.name"
                      required
                    >
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.decoration_description }}</label>
                    <input  
                      class="form-control" 
                      type="text"
                      v-model="editForm.description"
                    > 
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.decoration_type }} *</label>
                    <select class="form-select" v-model="editForm.type" required>
                      <option value="">اختر النوع</option>
                      <option value="birthday">{{ translations.birthday }}</option>
                      <option value="gender_reveal">{{ translations.gender_reveal }}</option>
                      <option value="baby_shower">{{ translations.baby_shower }}</option>
                      <option value="wedding">{{ translations.wedding }}</option>
                      <option value="graduation">{{ translations.graduation }}</option>
                      <option value="corporate">{{ translations.corporate }}</option>
                      <option value="religious">{{ translations.religious }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.base_price }} *</label>
                    <input 
                      type="number" 
                      step="0.01"
                      class="form-control" 
                      v-model="editForm.base_price"
                      required
                    >
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.duration_hours }} *</label>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model="editForm.duration_hours"
                      required
                    >
                  </div>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.team_size }} *</label>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model="editForm.team_size"
                      required
                    >
                  </div>
                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="editForm.is_active"
                        id="edit_is_active"
                      >
                      <label class="form-check-label" for="edit_is_active">
                        {{ translations.active }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-secondary text-center w-100" @click="showEditModal = false">
                  {{ translations.cancel }}
                </button>
                <button type="submit" class="btn btn-primary w-100 text-center" :disabled="processing">
                  <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                  {{ translations.update }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Creation Modal -->
    <div v-if="showOrderModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container" style="max-width: 800px;">
          <div class="modal-header">
            <h3>{{ translations.create_order }} - {{ selectedDecoration?.name }}</h3>
            <div class="text-end">
              <small class="text-muted">{{ translations.base_price }}: {{ selectedDecoration?.formatted_price || '0.00 دينار' }}</small>
            </div>
            <button @click="showOrderModal = false" class="btn-close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitOrderForm">
              <!-- Customer Selection -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5>{{ translations.customer_details }}</h5>
                  <div class="mb-3">
                    <label class="form-label">{{ translations.select_customer }}</label>
                    <div class="d-flex gap-2">
                      <select class="form-select" v-model="orderForm.customer_id" @change="onCustomerSelect">
                        <option value="">{{ translations.select_customer }}</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                          {{ customer.name }} - {{ customer.phone }}
                        </option>
                      </select>
                      <button type="button" class="btn btn-outline-primary" @click="showNewCustomerForm = !showNewCustomerForm">
                        {{ translations.create_new_customer }}
                      </button>
                    </div>
                  </div>

                  <!-- New Customer Form -->
                  <div v-if="showNewCustomerForm" class="border p-3 rounded mb-3">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label class="form-label">{{ translations.name }} *</label>
                          <input type="text" class="form-control" v-model="newCustomerForm.name" required>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-3">
                          <label class="form-label">{{ translations.phone }}</label>
                          <input type="text" class="form-control" v-model="newCustomerForm.phone">
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="mb-3">
                          <label class="form-label">{{ translations.address }}</label>
                          <input  class="form-control" type="text"  v-model="newCustomerForm.address"> 
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Customer Info Display -->
                  <div v-if="orderForm.customer_name" class="alert alert-info">
                    <strong>{{ translations.customer_details }}:</strong><br>
                    {{ translations.name }}: {{ orderForm.customer_name }}<br>
                    {{ translations.phone }}: {{ orderForm.customer_phone }}<br>
                    {{ translations.email }}: {{ orderForm.customer_email }}
                  </div>
                </div>
              </div>

              <!-- Event Details -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5>{{ translations.event_details }}</h5>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.event_address }} *</label>
                    <input  class="form-control" type="text"  v-model="orderForm.event_address" required> 
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.event_date }} *</label>
                    <input type="date" class="form-control" v-model="orderForm.event_date" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.event_time }} *</label>
                    <input type="time" class="form-control" v-model="orderForm.event_time" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.guest_count }}</label>
                    <input type="number" class="form-control" v-model="orderForm.guest_count" min="1">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.special_requests }}</label>
                    <input  class="form-control" type="text"  v-model="orderForm.special_requests"> 
                  </div>
                </div>
              </div>

              <!-- Pricing -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5>{{ translations.payment_details }}</h5>
                </div>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.base_price }}</label>
                    <input type="number" step="0.01" class="form-control" v-model="orderForm.base_price" readonly>
                    <small class="text-muted">{{ selectedDecoration?.current_currency || 'دينار' }}</small>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.additional_cost }}</label>
                    <input type="number" step="0.01" class="form-control" v-model="orderForm.additional_cost" @input="calculateTotal">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.discount }}</label>
                    <input type="number" step="0.01" class="form-control" v-model="orderForm.discount" @input="calculateTotal">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.total_price }}</label>
                    <input type="number" step="0.01" class="form-control" v-model="orderForm.total_price" readonly>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-secondary text-center w-100" @click="showOrderModal = false">
                  {{ translations.cancel }}
                </button>
                <button type="submit" class="btn btn-success w-100 text-center" :disabled="processing">
                  <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                  {{ translations.create_order }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  decorations: Object,
  filters: Object,
  translations: Object,
  customers: Array
})

// Modal states
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showOrderModal = ref(false)
const showNewCustomerForm = ref(false)
const processing = ref(false)
const selectedDecoration = ref(null)

// Search form
const searchForm = reactive({
  search: props.filters?.search || '',
  type: props.filters?.type || '',
  is_active: props.filters?.is_active !== undefined ? props.filters.is_active.toString() : ''
})

// Create form
const createForm = reactive({
  name: '',
  description: '',
  type: '',
  currency: 'dinar',
  base_price: '',
  duration_hours: '',
  team_size: '',
  video_url: '',
  is_featured: false,
  is_active: true
})

// Media previews
const mainImagePreview = ref(null)
const imagePreviews = ref([])

// Edit form
const editForm = reactive({
  id: null,
  name: '',
  description: '',
  type: '',
  base_price: '',
  duration_hours: '',
  team_size: '',
  is_active: true
})

// Order form
const orderForm = reactive({
  decoration_id: null,
  customer_id: '',
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  event_address: '',
  event_date: '',
  event_time: '',
  guest_count: 50,
  special_requests: '',
  base_price: 0,
  additional_cost: 0,
  discount: 0,
  total_price: 0
})

// New customer form
const newCustomerForm = reactive({
  name: '',
  phone: '',
  address: '',
  notes: ''
})

// Functions
const searchDecorations = () => {
  router.get(route('decorations.index'), searchForm, {
    preserveState: true,
    replace: true
  })
}

const resetCreateForm = () => {
  createForm.name = ''
  createForm.description = ''
  createForm.type = ''
  createForm.currency = 'dinar'
  createForm.base_price = ''
  createForm.duration_hours = ''
  createForm.team_size = ''
  createForm.video_url = ''
  createForm.is_featured = false
  createForm.is_active = true
  mainImagePreview.value = null
  imagePreviews.value = []
}

const submitCreateForm = () => {
  processing.value = true
  
  // Create FormData for file uploads
  const formData = new FormData()
  
  // Add form fields
  Object.keys(createForm).forEach(key => {
    formData.append(key, createForm[key])
  })
  
  // Add main image
  const mainImageInput = document.querySelector('input[type="file"]')
  if (mainImageInput && mainImageInput.files[0]) {
    formData.append('main_image', mainImageInput.files[0])
  }
  
  // Add additional images
  const imagesInput = document.querySelector('input[multiple]')
  if (imagesInput && imagesInput.files.length > 0) {
    Array.from(imagesInput.files).forEach((file, index) => {
      formData.append(`images[${index}]`, file)
    })
  }
  
  router.post(route('decorations.store'), formData, {
    onSuccess: () => {
      processing.value = false
      showCreateModal.value = false
      resetCreateForm()
    },
    onError: () => {
      processing.value = false
    }
  })
}

const openEditModal = (decoration) => {
  editForm.id = decoration.id
  editForm.name = decoration.name
  editForm.description = decoration.description || ''
  editForm.type = decoration.type
  editForm.base_price = decoration.base_price
  editForm.duration_hours = decoration.duration_hours
  editForm.team_size = decoration.team_size
  editForm.is_active = decoration.is_active
  showEditModal.value = true
}

const submitEditForm = () => {
  processing.value = true
  
  router.put(route('decorations.update', editForm.id), editForm, {
    onSuccess: () => {
      processing.value = false
      showEditModal.value = false
    },
    onError: () => {
      processing.value = false
    }
  })
}

const deleteDecoration = (id) => {
  if (confirm(props.translations.confirm_delete)) {
    router.delete(route('decorations.destroy', id))
  }
}

const hasPermission = (permission) => {
  return window.permissions && window.permissions.includes(permission)
}

// Media functions
const handleMainImage = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      mainImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const handleImages = (event) => {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreviews.value.push(e.target.result)
    }
    reader.readAsDataURL(file)
  })
}

const removeImage = (index) => {
  imagePreviews.value.splice(index, 1)
}

// Currency functions
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

// Order functions
const openOrderModal = (decoration) => {
  selectedDecoration.value = decoration
  orderForm.decoration_id = decoration.id
  
  // تحديد السعر الأساسي حسب العملة
  const basePrice = decoration.current_price || 
                   (decoration.currency === 'dollar' ? decoration.base_price_dollar : decoration.base_price_dinar) ||
                   decoration.base_price || 0
  
  orderForm.base_price = basePrice
  orderForm.total_price = basePrice
  
  console.log('Decoration data:', decoration)
  console.log('Base price set to:', basePrice)
  
  showOrderModal.value = true
}

const onCustomerSelect = () => {
  if (orderForm.customer_id) {
    const customer = props.customers.find(c => c.id == orderForm.customer_id)
    if (customer) {
      orderForm.customer_name = customer.name
      orderForm.customer_phone = customer.phone
      orderForm.customer_email = customer.email || ''
    }
  } else {
    orderForm.customer_name = ''
    orderForm.customer_phone = ''
    orderForm.customer_email = ''
  }
}

const calculateTotal = () => {
  const base = parseFloat(orderForm.base_price) || 0
  const additional = parseFloat(orderForm.additional_cost) || 0
  const discount = parseFloat(orderForm.discount) || 0
  orderForm.total_price = base + additional - discount
}

const resetOrderForm = () => {
  orderForm.decoration_id = null
  orderForm.customer_id = ''
  orderForm.customer_name = ''
  orderForm.customer_phone = ''
  orderForm.customer_email = ''
  orderForm.event_address = ''
  orderForm.event_date = ''
  orderForm.event_time = ''
  orderForm.guest_count = 50
  orderForm.special_requests = ''
  orderForm.base_price = 0
  orderForm.additional_cost = 0
  orderForm.discount = 0
  orderForm.total_price = 0
  showNewCustomerForm.value = false
  newCustomerForm.name = ''
  newCustomerForm.phone = ''
  newCustomerForm.address = ''
  newCustomerForm.notes = ''
}

const submitOrderForm = async () => {
  processing.value = true
  
  // التحقق من الصلاحيات
  if (!hasPermission('create decoration')) {
    alert('ليس لديك صلاحية لإنشاء طلبات الديكورات')
    processing.value = false
    return
  }
  
  try {
    // Create customer if new customer form is filled
    if (showNewCustomerForm.value && newCustomerForm.name) {
      const customerResponse = await router.post('/customers', {
        ...newCustomerForm,
        is_active: true,
        notes: props.translations.created_from_decoration
      }, {
        preserveState: true,
        onSuccess: (page) => {
          // Get the created customer ID from response
          const createdCustomer = page.props.flash?.customer
          if (createdCustomer) {
            orderForm.customer_id = createdCustomer.id
            orderForm.customer_name = createdCustomer.name
            orderForm.customer_phone = createdCustomer.phone
            orderForm.customer_email = createdCustomer.email || ''
          }
        }
      })
    }
    
    // Create the order
    router.post(route('decoration.orders.store'), orderForm, {
      onSuccess: (page) => {
        processing.value = false
        showOrderModal.value = false
        resetOrderForm()
        console.log('Order created successfully:', page.props.flash)
      },
      onError: (errors) => {
        processing.value = false
        console.error('Error creating order:', errors)
        // عرض رسالة الخطأ للمستخدم
        if (errors.message) {
          alert('خطأ: ' + errors.message)
        } else if (errors.event_date) {
          alert('خطأ في التاريخ: ' + errors.event_date[0])
        } else {
          alert('حدث خطأ أثناء إنشاء الطلب')
        }
      }
    })
  } catch (error) {
    processing.value = false
    console.error('Error creating order:', error)
  }
}
</script>

<style scoped>
/* Price Section */
.price-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #dee2e6;
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

/* Card Enhancements */
.card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid #e3e6f0;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .price-section {
    background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
    border-color: #4a5568;
  }
  
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

/* Media Preview Styles */
.preview-image {
  max-width: 200px;
  max-height: 200px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.image-preview {
  position: relative;
  display: inline-block;
}

.remove-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
}

.remove-btn:hover {
  background: #c82333;
}
</style>
