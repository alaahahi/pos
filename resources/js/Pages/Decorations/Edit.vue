<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ translations.edit_decoration }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <!-- Progress Indicator -->
            <div class="progress-indicator mb-8">
              <div class="progress-steps">
                <div 
                  v-for="(step, index) in steps" 
                  :key="index"
                  :class="['step', { active: currentStep === index, completed: currentStep > index }]"
                >
                  <div class="step-number">
                    <i v-if="currentStep > index" class="bi bi-check"></i>
                    <span v-else>{{ index + 1 }}</span>
                  </div>
                  <div class="step-label">{{ step }}</div>
                  <div class="step-icon">
                    <i :class="getStepIcon(index)"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 1: Basic Information -->
            <div v-if="currentStep === 0" class="step-content">
              <div class="step-header">
                <div class="step-title-icon">
                  <i class="bi bi-info-circle"></i>
                </div>
                <h3 class="step-title">{{ translations.basic_information }}</h3>
                <p class="step-subtitle">أدخل المعلومات الأساسية للديكور</p>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-tag"></i>
                    {{ translations.decoration_name }}
                  </label>
                  <input 
                    type="text" 
                    v-model="form.name"
                    class="form-input"
                    :class="{ 'error': errors.name }"
                    :placeholder="translations.enter_decoration_name"
                  >
                  <div v-if="errors.name" class="error-message">{{ errors.name }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-grid-3x3-gap"></i>
                    {{ translations.decoration_type }}
                  </label>
                  <select 
                    v-model="form.type"
                    class="form-input"
                    :class="{ 'error': errors.type }"
                  >
                    <option value="">{{ translations.select_type }}</option>
                    <option value="birthday">{{ translations.birthday }}</option>
                    <option value="gender_reveal">{{ translations.gender_reveal }}</option>
                    <option value="baby_shower">{{ translations.baby_shower }}</option>
                    <option value="wedding">{{ translations.wedding }}</option>
                    <option value="graduation">{{ translations.graduation }}</option>
                    <option value="corporate">{{ translations.corporate }}</option>
                    <option value="religious">{{ translations.religious }}</option>
                  </select>
                  <div v-if="errors.type" class="error-message">{{ errors.type }}</div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-card-text"></i>
                  {{ translations.decoration_description }}
                </label>
                <textarea 
                  v-model="form.description"
                  class="form-input"
                  rows="4"
                  :placeholder="translations.enter_description"
                ></textarea>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-clock"></i>
                    {{ translations.duration_hours }}
                  </label>
                  <input 
                    type="number" 
                    v-model="form.duration_hours"
                    class="form-input"
                    :class="{ 'error': errors.duration_hours }"
                    min="1"
                    :placeholder="translations.enter_duration"
                  >
                  <div v-if="errors.duration_hours" class="error-message">{{ errors.duration_hours }}</div>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-people"></i>
                    {{ translations.team_size }}
                  </label>
                  <input 
                    type="number" 
                    v-model="form.team_size"
                    class="form-input"
                    :class="{ 'error': errors.team_size }"
                    min="1"
                    :placeholder="translations.enter_team_size"
                  >
                  <div v-if="errors.team_size" class="error-message">{{ errors.team_size }}</div>
                </div>
              </div>
            </div>

            <!-- Step 2: Pricing Details -->
            <div v-if="currentStep === 1" class="step-content">
              <div class="step-header">
                <div class="step-title-icon">
                  <i class="bi bi-currency-exchange"></i>
                </div>
                <h3 class="step-title">{{ translations.pricing_details }}</h3>
                <p class="step-subtitle">حدد الأسعار وتفاصيل التكلفة</p>
              </div>
              
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-currency-exchange"></i>
                  {{ translations.currency }}
                </label>
                <div class="currency-toggle">
                  <button 
                    type="button"
                    @click="form.currency = 'dinar'"
                    :class="['currency-btn', { active: form.currency === 'dinar' }]"
                  >
                    <i class="bi bi-currency-exchange"></i>
                    {{ translations.dinar }}
                  </button>
                  <button 
                    type="button"
                    @click="form.currency = 'dollar'"
                    :class="['currency-btn', { active: form.currency === 'dollar' }]"
                  >
                    <i class="bi bi-currency-dollar"></i>
                    {{ translations.dollar }}
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-tag"></i>
                  {{ translations.base_price }}
                </label>
                <input 
                  type="number" 
                  v-model="form.base_price"
                  class="form-input"
                  :class="{ 'error': errors.base_price }"
                  min="0"
                  step="0.01"
                  :placeholder="translations.enter_base_price"
                >
                <div class="price-inputs">
                  <div class="price-input-group">
                    <label class="price-label">{{ translations.dinar }}</label>
                    <input 
                      type="number" 
                      v-model="form.base_price_dinar"
                      class="form-input"
                      :class="{ 'error': errors.base_price_dinar }"
                      min="0"
                      step="0.01"
                      :placeholder="translations.enter_price_dinar"
                    >
                  </div>
                  <div class="price-input-group">
                    <label class="price-label">{{ translations.dollar }}</label>
                    <input 
                      type="number" 
                      v-model="form.base_price_dollar"
                      class="form-input"
                      :class="{ 'error': errors.base_price_dollar }"
                      min="0"
                      step="0.01"
                      :placeholder="translations.enter_price_dollar"
                    >
                  </div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-box"></i>
                    {{ translations.materials_cost }}
                  </label>
                  <input 
                    type="number" 
                    v-model="form.materials_cost"
                    class="form-input"
                    min="0"
                    step="0.01"
                    :placeholder="translations.enter_materials_cost"
                  >
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-person-workspace"></i>
                    {{ translations.labor_cost }}
                  </label>
                  <input 
                    type="number" 
                    v-model="form.labor_cost"
                    class="form-input"
                    min="0"
                    step="0.01"
                    :placeholder="translations.enter_labor_cost"
                  >
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-truck"></i>
                  {{ translations.transportation_cost }}
                </label>
                <input 
                  type="number" 
                  v-model="form.transportation_cost"
                  class="form-input"
                  min="0"
                  step="0.01"
                  :placeholder="translations.enter_transportation_cost"
                >
              </div>
            </div>

            <!-- Step 3: Additional Details -->
            <div v-if="currentStep === 2" class="step-content">
              <div class="step-header">
                <div class="step-title-icon">
                  <i class="bi bi-gear"></i>
                </div>
                <h3 class="step-title">{{ translations.additional_details }}</h3>
                <p class="step-subtitle">أضف التفاصيل الإضافية والحالة</p>
              </div>
              
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-list-check"></i>
                  {{ translations.included_items }}
                </label>
                <textarea 
                  v-model="form.included_items_text"
                  class="form-input"
                  rows="4"
                  :placeholder="translations.enter_included_items"
                ></textarea>
                <small class="form-help">{{ translations.included_items_help }}</small>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-plus-circle"></i>
                  {{ translations.optional_items }}
                </label>
                <textarea 
                  v-model="form.optional_items_text"
                  class="form-input"
                  rows="4"
                  :placeholder="translations.enter_optional_items"
                ></textarea>
                <small class="form-help">{{ translations.optional_items_help }}</small>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-toggle-on"></i>
                    {{ translations.status }}
                  </label>
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="form.is_active"
                      id="is_active"
                    >
                    <label class="form-check-label" for="is_active">
                      {{ form.is_active ? translations.active : translations.inactive }}
                    </label>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="bi bi-star"></i>
                    {{ translations.featured }}
                  </label>
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="form.is_featured"
                      id="is_featured"
                    >
                    <label class="form-check-label" for="is_featured">
                      {{ form.is_featured ? translations.featured : translations.not_featured }}
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Media Upload -->
            <div v-if="currentStep === 3" class="step-content">
              <div class="step-header">
                <div class="step-title-icon">
                  <i class="bi bi-camera"></i>
                </div>
                <h3 class="step-title">{{ translations.media_upload }}</h3>
                <p class="step-subtitle">{{ translations.upload_images_videos }}</p>
              </div>

              <!-- Main Image -->
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-image"></i>
                  {{ translations.main_image }}
                </label>
                <div class="upload-area" @click="mainImageInput?.click()" @drop="handleMainImageDrop" @dragover.prevent @dragenter.prevent>
                  <input 
                    ref="mainImageInput"
                    type="file" 
                    accept="image/*"
                    @change="handleMainImageChange"
                    class="hidden"
                  >
                  <div v-if="!mainImageFile && !decoration.image_url" class="upload-placeholder">
                    <i class="bi bi-cloud-upload"></i>
                    <p>{{ translations.click_or_drag_image }}</p>
                    <small>{{ translations.supported_formats }}</small>
                  </div>
                  <div v-else class="image-preview-container">
                    <img 
                      :src="mainImageFile ? mainImagePreview : decoration.image_url" 
                      :alt="translations.main_image"
                      class="preview-image"
                    >
                    <button 
                      type="button" 
                      @click.stop="removeMainImage"
                      class="remove-image-btn"
                    >
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Additional Images -->
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-images"></i>
                  {{ translations.additional_images }}
                </label>
                <div class="upload-area" @click="additionalImagesInput?.click()" @drop="handleAdditionalImagesDrop" @dragover.prevent @dragenter.prevent>
                  <input 
                    ref="additionalImagesInput"
                    type="file" 
                    accept="image/*"
                    multiple
                    @change="handleAdditionalImagesChange"
                    class="hidden"
                  >
                  <div v-if="additionalImagesFiles.length === 0 && (!decoration.images_urls || decoration.images_urls.length === 0)" class="upload-placeholder">
                    <i class="bi bi-cloud-upload"></i>
                    <p>{{ translations.click_or_drag_multiple_images }}</p>
                    <small>{{ translations.max_images_limit }}</small>
                  </div>
                  <div v-else class="images-grid">
                    <!-- Existing images -->
                    <div 
                      v-for="(image, index) in decoration.images_urls" 
                      :key="`existing-${index}`"
                      class="image-preview-container"
                    >
                      <img :src="image" :alt="`${translations.image} ${index + 1}`" class="preview-image">
                      <button 
                        type="button" 
                        @click="removeExistingImage(index)"
                        class="remove-image-btn"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                    <!-- New images -->
                    <div 
                      v-for="(file, index) in additionalImagesFiles" 
                      :key="`new-${index}`"
                      class="image-preview-container"
                    >
                      <img :src="getFilePreview(file)" :alt="`${translations.new_image} ${index + 1}`" class="preview-image">
                      <button 
                        type="button" 
                        @click="removeAdditionalImage(index)"
                        class="remove-image-btn"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- YouTube Links -->
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-youtube"></i>
                  {{ translations.youtube_links }}
                </label>
                <div class="youtube-links-container">
                  <div 
                    v-for="(link, index) in form.youtube_links" 
                    :key="index"
                    class="youtube-link-item"
                  >
                    <input 
                      type="url" 
                      v-model="form.youtube_links[index]"
                      class="form-input"
                      :placeholder="`${translations.youtube_link} ${index + 1}`"
                    >
                    <button 
                      type="button" 
                      @click="removeYoutubeLink(index)"
                      class="remove-link-btn"
                      v-if="form.youtube_links.length > 1"
                    >
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                  <button 
                    type="button" 
                    @click="addYoutubeLink"
                    class="add-link-btn"
                    v-if="form.youtube_links.length < 3"
                  >
                    <i class="bi bi-plus"></i>
                    {{ translations.add_youtube_link }}
                  </button>
                </div>
                <small class="form-help">{{ translations.youtube_links_help }}</small>
              </div>

              <!-- Media Summary -->
              <div class="media-summary">
                <h4>{{ translations.media_summary }}</h4>
                <div class="summary-items">
                  <div class="summary-item">
                    <i class="bi bi-image"></i>
                    <span>{{ translations.images }}: {{ getTotalImagesCount() }}</span>
                  </div>
                  <div class="summary-item">
                    <i class="bi bi-camera-video"></i>
                    <span>{{ translations.video }}: {{ getVideoStatus() }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="step-navigation">
              <button 
                type="button" 
                @click="prevStep" 
                v-if="currentStep > 0"
                class="btn btn-secondary"
              >
                {{ translations.previous }}
                <i class="bi bi-arrow-left"></i>
              </button>
              
              <button 
                type="button" 
                @click="nextStep" 
                v-if="currentStep < steps.length - 1"
                class="btn btn-primary"
              >
                <i class="bi bi-arrow-right"></i>
                {{ translations.next }}
              </button>
              
              <button 
                type="button" 
                @click="submitForm" 
                v-if="currentStep === steps.length - 1"
                class="btn btn-success"
                :disabled="processing"
              >
                <i v-if="processing" class="bi bi-arrow-clockwise animate-spin"></i>
                <i v-else class="bi bi-check-circle"></i>
                {{ processing ? translations.updating : translations.update }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  decoration: Object,
  translations: Object,
  errors: Object
})

const processing = ref(false)
const currentStep = ref(0)
const errors = ref(props.errors || {})

const steps = [
  'المعلومات الأساسية',
  'تفاصيل الأسعار', 
  'تفاصيل إضافية',
  'رفع الوسائط'
]

// Form data
const form = reactive({
  name: props.decoration.name || 'ديكور بدون اسم',
  description: props.decoration.description || '',
  type: props.decoration.type || 'birthday',
  currency: props.decoration.currency || 'dinar',
  base_price: props.decoration.base_price || 0,
  base_price_dinar: props.decoration.base_price_dinar || 0,
  base_price_dollar: props.decoration.base_price_dollar || 0,
  duration_hours: props.decoration.duration_hours || 1,
  team_size: props.decoration.team_size || 1,
  materials_cost: props.decoration.pricing_details?.materials || 0,
  labor_cost: props.decoration.pricing_details?.labor || 0,
  transportation_cost: props.decoration.pricing_details?.transportation || 0,
  included_items_text: props.decoration.included_items?.join('\n') || '',
  optional_items_text: props.decoration.optional_items?.join('\n') || '',
  is_active: props.decoration.is_active ?? true,
  is_featured: props.decoration.is_featured ?? false,
  youtube_links: (() => {
    console.log('Edit page - props.decoration.youtube_links:', props.decoration.youtube_links)
    console.log('Edit page - typeof youtube_links:', typeof props.decoration.youtube_links)
    console.log('Edit page - Array.isArray:', Array.isArray(props.decoration.youtube_links))
    
    if (props.decoration.youtube_links && Array.isArray(props.decoration.youtube_links)) {
      const filtered = props.decoration.youtube_links.filter(link => link && link.trim() !== '')
      console.log('Edit page - filtered youtube_links:', filtered)
      return filtered.length > 0 ? filtered : ['']
    }
    
    return ['']
  })()
})

// File handling
const mainImageInput = ref(null)
const additionalImagesInput = ref(null)
const videoInput = ref(null)

const mainImageFile = ref(null)
const mainImagePreview = ref('')
const additionalImagesFiles = ref([])
const videoFile = ref(null)

// Step navigation
const nextStep = () => {
  // Validate current step before proceeding
  if (validateCurrentStep()) {
    if (currentStep.value < steps.length - 1) {
      currentStep.value++
    }
  }
}

const validateCurrentStep = () => {
  errors.value = {}
  let isValid = true

  if (currentStep.value === 0) {
    // Validate basic information
    if (!form.name || form.name.trim() === '') {
      errors.value.name = 'اسم الديكور مطلوب'
      isValid = false
    }
    if (!form.type || form.type === '') {
      errors.value.type = 'نوع الديكور مطلوب'
      isValid = false
    }
    if (!form.duration_hours || form.duration_hours < 1) {
      errors.value.duration_hours = 'مدة التنفيذ مطلوبة'
      isValid = false
    }
    if (!form.team_size || form.team_size < 1) {
      errors.value.team_size = 'حجم الفريق مطلوب'
      isValid = false
    }
  } else if (currentStep.value === 1) {
    // Validate pricing details
    if (!form.base_price || form.base_price < 0) {
      errors.value.base_price = 'السعر الأساسي مطلوب'
      isValid = false
    }
    if (!form.base_price_dinar || form.base_price_dinar < 0) {
      errors.value.base_price_dinar = 'السعر بالدينار مطلوب'
      isValid = false
    }
    if (!form.base_price_dollar || form.base_price_dollar < 0) {
      errors.value.base_price_dollar = 'السعر بالدولار مطلوب'
      isValid = false
    }
  }

  return isValid
}

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
    // Clear errors when going back
    errors.value = {}
  }
}

// File handling functions
const handleMainImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    mainImageFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      mainImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const handleMainImageDrop = (event) => {
  event.preventDefault()
  const file = event.dataTransfer.files[0]
  if (file && file.type.startsWith('image/')) {
    mainImageFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      mainImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const removeMainImage = () => {
  mainImageFile.value = null
  mainImagePreview.value = ''
  if (mainImageInput.value) {
    mainImageInput.value.value = ''
  }
}

const handleAdditionalImagesChange = (event) => {
  const files = Array.from(event.target.files)
  additionalImagesFiles.value.push(...files)
}

const handleAdditionalImagesDrop = (event) => {
  event.preventDefault()
  const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'))
  additionalImagesFiles.value.push(...files)
}

const removeAdditionalImage = (index) => {
  additionalImagesFiles.value.splice(index, 1)
}

const removeExistingImage = (index) => {
  // This would need backend support to remove existing images
  console.log('Remove existing image:', index)
}

const addYoutubeLink = () => {
  if (form.youtube_links.length < 3) {
    form.youtube_links.push('')
  }
}

const removeYoutubeLink = (index) => {
  if (form.youtube_links.length > 1) {
    form.youtube_links.splice(index, 1)
  }
}

const handleVideoDrop = (event) => {
  event.preventDefault()
  const file = event.dataTransfer.files[0]
  if (file && file.type.startsWith('video/')) {
    videoFile.value = file
  }
}

const removeVideo = () => {
  videoFile.value = null
  if (videoInput.value) {
    videoInput.value.value = ''
  }
}

const getFilePreview = (file) => {
  return URL.createObjectURL(file)
}

const getTotalImagesCount = () => {
  const existingCount = props.decoration.images_urls?.length || 0
  const newCount = additionalImagesFiles.value.length
  return existingCount + newCount
}

const getVideoStatus = () => {
  const youtubeLinksCount = form.youtube_links.filter(link => link && link.trim() !== '').length
  if (youtubeLinksCount > 0) return `${youtubeLinksCount} رابط`
  return translations.not_uploaded
}

const getStepIcon = (index) => {
  const icons = [
    'bi bi-info-circle',
    'bi bi-currency-exchange', 
    'bi bi-gear',
    'bi bi-camera'
  ]
  return icons[index] || 'bi bi-circle'
}

// Form submission
const submitForm = () => {
  // Validate all steps before submission
  if (!validateAllSteps()) {
    return
  }
  
  processing.value = true
  
  // Create FormData for file uploads
  const formData = new FormData()
  
  // Add form fields
  Object.keys(form).forEach(key => {
    // Always append form fields, even if empty
    if (typeof form[key] === 'boolean') {
      formData.append(key, form[key] ? '1' : '0')
    } else {
      formData.append(key, form[key] || '')
    }
  })
  
  // Debug: Log form data
  console.log('Form data being sent:', {
    name: form.name,
    type: form.type,
    description: form.description,
    currency: form.currency,
    base_price: form.base_price,
    duration_hours: form.duration_hours,
    team_size: form.team_size,
    youtube_links: form.youtube_links
  })
  
  // Debug: Log FormData contents
  console.log('FormData contents:')
  for (let [key, value] of formData.entries()) {
    console.log(key, value)
  }
  
  // Add main image
  if (mainImageFile.value) {
    formData.append('main_image', mainImageFile.value)
  }
  
  // Add additional images
  additionalImagesFiles.value.forEach((file, index) => {
    formData.append(`images[${index}]`, file)
  })
  
  // Add YouTube links
  form.youtube_links.forEach((link, index) => {
    if (link && link.trim() !== '') {
      console.log('Adding YouTube link to FormData:', link)
      formData.append(`youtube_links[${index}]`, link)
    }
  })
  
  // Set pricing details
  formData.append('materials_cost', parseFloat(form.materials_cost) || 0)
  formData.append('labor_cost', parseFloat(form.labor_cost) || 0)
  formData.append('transportation_cost', parseFloat(form.transportation_cost) || 0)
  
  router.post(route('decorations.update.post', props.decoration.id), formData, {
    onFinish: () => {
      processing.value = false
    },
    onError: (err) => {
      errors.value = err
      processing.value = false
    }
  })
}

const validateAllSteps = () => {
  errors.value = {}
  let isValid = true

  // Step 0: Basic information
  if (!form.name || form.name.trim() === '') {
    errors.value.name = 'اسم الديكور مطلوب'
    isValid = false
  }
  if (!form.type || form.type === '') {
    errors.value.type = 'نوع الديكور مطلوب'
    isValid = false
  }
  if (!form.duration_hours || form.duration_hours < 1) {
    errors.value.duration_hours = 'مدة التنفيذ مطلوبة'
    isValid = false
  }
  if (!form.team_size || form.team_size < 1) {
    errors.value.team_size = 'حجم الفريق مطلوب'
    isValid = false
  }

  // Step 1: Pricing details
  if (!form.base_price || form.base_price < 0) {
    errors.value.base_price = 'السعر الأساسي مطلوب'
    isValid = false
  }
  if (!form.base_price_dinar || form.base_price_dinar < 0) {
    errors.value.base_price_dinar = 'السعر بالدينار مطلوب'
    isValid = false
  }
  if (!form.base_price_dollar || form.base_price_dollar < 0) {
    errors.value.base_price_dollar = 'السعر بالدولار مطلوب'
    isValid = false
  }

  return isValid
}

onMounted(() => {
  // Debug: Log decoration data
  console.log('Decoration data received:', props.decoration)
  console.log('Decoration name value:', props.decoration.name)
  console.log('Decoration name type:', typeof props.decoration.name)
  console.log('Decoration video data:', {
    youtube_links: props.decoration.youtube_links,
    video_url: props.decoration.video_url,
    video_url_attribute: props.decoration.video_url_attribute
  })
  console.log('Form initialized with:', {
    name: form.name,
    type: form.type,
    description: form.description,
    currency: form.currency,
    base_price: form.base_price,
    duration_hours: form.duration_hours,
    team_size: form.team_size
  })
  
  // Initialize form with decoration data
  if (props.decoration.pricing_details) {
    form.materials_cost = props.decoration.pricing_details.materials || 0
    form.labor_cost = props.decoration.pricing_details.labor || 0
    form.transportation_cost = props.decoration.pricing_details.transportation || 0
  }
})
</script>

<style scoped>
/* Card and Body Styles */

/* Progress Indicator */
.progress-indicator {
  margin-bottom: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
}

.progress-steps::before {
  content: '';
  position: absolute;
  top: 30px;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(255,255,255,0.3);
  z-index: 1;
  border-radius: 2px;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
  background: transparent;
  padding: 0 1rem;
  transition: all 0.3s ease;
}

.step-number {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  margin-bottom: 0.5rem;
  transition: all 0.3s ease;
  border: 3px solid rgba(255,255,255,0.3);
  backdrop-filter: blur(10px);
}

.step.active .step-number {
  background: #ff6b6b;
  border-color: #ff5252;
  transform: scale(1.1);
  box-shadow: 0 5px 20px rgba(255,107,107,0.4);
}

.step.completed .step-number {
  background: #4ecdc4;
  border-color: #26a69a;
  transform: scale(1.05);
  box-shadow: 0 5px 20px rgba(78,205,196,0.4);
}

.step-label {
  font-size: 0.9rem;
  color: white;
  text-align: center;
  font-weight: 600;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
  margin-bottom: 0.5rem;
}

.step-icon {
  font-size: 1.2rem;
  color: rgba(255,255,255,0.7);
  transition: all 0.3s ease;
}

.step.active .step-icon {
  color: #ffeb3b;
  transform: scale(1.2);
}

.step.completed .step-icon {
  color: #4caf50;
}

/* Step Content */
.step-content {
  min-height: 400px;
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  border: 1px solid #e5e7eb;
}

.step-header {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #f3f4f6;
}

.step-title-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  box-shadow: 0 10px 30px rgba(102,126,234,0.3);
}

.step-title-icon i {
  font-size: 2rem;
  color: white;
}

.step-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.step-subtitle {
  color: #6b7280;
  font-size: 1.1rem;
  font-weight: 500;
  margin: 0;
}

/* Form Styles */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 2rem;
  position: relative;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.75rem;
  font-size: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.form-label i {
  color: #667eea;
  font-size: 1.1rem;
}

.form-input {
  width: 100%;
  padding: 1rem 1.25rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #fafafa;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102,126,234,0.1), 0 4px 12px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.form-input.error {
  border-color: #ef4444;
  box-shadow: 0 0 0 4px rgba(239,68,68,0.1);
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.error-message::before {
  content: "⚠";
  font-size: 1rem;
}

.form-help {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.5rem;
  font-style: italic;
}

/* Currency Toggle */
.currency-toggle {
  display: flex;
  gap: 0.75rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 0.5rem;
  border-radius: 15px;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.currency-btn {
  flex: 1;
  padding: 1rem 1.5rem;
  border: none;
  background: transparent;
  border-radius: 12px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  position: relative;
  overflow: hidden;
}

.currency-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
  transition: left 0.5s;
}

.currency-btn:hover::before {
  left: 100%;
}

.currency-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102,126,234,0.3);
  transform: translateY(-2px);
}

.currency-btn:hover:not(.active) {
  background: rgba(102,126,234,0.1);
  color: #667eea;
  transform: translateY(-1px);
}

/* Price Inputs */
.price-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.price-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.price-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
}

/* Form Check Switch */
.form-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-check-input {
  width: 3rem;
  height: 1.5rem;
  background: #d1d5db;
  border: none;
  border-radius: 1rem;
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.form-check-input:checked {
  background: #3b82f6;
}

.form-check-input::before {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 1.25rem;
  height: 1.25rem;
  background: white;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.form-check-input:checked::before {
  transform: translateX(1.5rem);
}

.form-check-label {
  font-weight: 500;
  color: #374151;
  cursor: pointer;
}

/* Upload Area */
.upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #f9fafb;
}

.upload-area:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.upload-placeholder {
  color: #6b7280;
}

.upload-placeholder i {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #9ca3af;
}

.upload-placeholder p {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.upload-placeholder small {
  color: #9ca3af;
}

/* Image Preview */
.image-preview-container {
  position: relative;
  display: inline-block;
  margin: 0.5rem;
}

.preview-image {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 0.5rem;
  border: 2px solid #e5e7eb;
}

.remove-image-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  transition: all 0.3s ease;
}

.remove-image-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

/* Images Grid */
.images-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

/* Video Preview */
.video-preview-container {
  position: relative;
  display: inline-block;
}

.preview-video {
  width: 200px;
  height: 150px;
  object-fit: cover;
  border-radius: 0.5rem;
  border: 2px solid #e5e7eb;
}

.video-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 200px;
  height: 150px;
  background: #f3f4f6;
  border-radius: 0.5rem;
  border: 2px solid #e5e7eb;
  color: #6b7280;
}

.video-info i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.remove-video-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  transition: all 0.3s ease;
}

.remove-video-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

/* YouTube Links */
.youtube-links-container {
  margin-top: 1rem;
}

.youtube-link-item {
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.remove-link-btn {
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.remove-link-btn:hover {
  background: #b91c1c;
  transform: scale(1.1);
}

.add-link-btn {
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.add-link-btn:hover {
  background: #059669;
  transform: translateY(-2px);
}

/* Media Summary */
.media-summary {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-top: 1rem;
}

.media-summary h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.75rem;
}

.summary-items {
  display: flex;
  gap: 1rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
}

/* Navigation Buttons */
.step-navigation {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 2px solid #f3f4f6;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  position: relative;
  overflow: hidden;
  font-size: 1rem;
  min-width: 140px;
  justify-content: center;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.btn:disabled::before {
  display: none;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(102,126,234,0.4);
}

.btn-secondary {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(107,114,128,0.3);
}

.btn-secondary:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(107,114,128,0.4);
}

.btn-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(16,185,129,0.3);
}

.btn-success:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(16,185,129,0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .price-inputs {
    grid-template-columns: 1fr;
  }
  
  .progress-steps {
    flex-direction: column;
    gap: 1rem;
  }
  
  .progress-steps::before {
    display: none;
  }
  
  .step-navigation {
    flex-direction: column;
    gap: 1rem;
  }
  
  .summary-items {
    flex-direction: column;
    gap: 0.5rem;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  
  .step-content {
    background: #374151;
    border-color: #4b5563;
  }
  
  .step-title {
    color: #f9fafb;
  }
  
  .step-subtitle {
    color: #d1d5db;
  }
  
  .form-label {
    color: #d1d5db;
  }
  
  .form-input {
    background: #4b5563;
    border-color: #6b7280;
    color: #f9fafb;
  }
  
  .form-input:focus {
    border-color: #667eea;
    background: #374151;
  }
  
  .form-input::placeholder {
    color: #9ca3af;
  }
  
  .upload-area {
    background: #4b5563;
    border-color: #6b7280;
  }
  
  .upload-area:hover {
    background: #374151;
    border-color: #667eea;
  }
  
  .media-summary {
    background: #4b5563;
    border-color: #6b7280;
  }
  
  .media-summary h4 {
    color: #f9fafb;
  }
  
  .step-navigation {
    background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
    border-color: #6b7280;
  }
  
  .currency-toggle {
    background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
  }
  
  .currency-btn {
    color: #d1d5db;
  }
  
  .currency-btn:hover:not(.active) {
    background: rgba(102,126,234,0.2);
    color: #93c5fd;
  }
}
</style>
