<template>
  <div class="decoration-form-container">
    <!-- Modal Header -->
    <div class="modal-header">
      <h4 class="modal-title">
        <i class="bi bi-plus-circle"></i>
        {{ translations.add_decoration }}
      </h4>
    </div>

    <!-- Modal Body -->
    <div class="modal-body decoration-modal-body">
      <!-- Progress Indicator -->
      <div class="progress-indicator mb-4">
        <div class="progress-steps">
          <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
            <div class="step-number">1</div>
            <div class="step-label">{{ translations.basic_information }}</div>
          </div>
          <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
            <div class="step-number">2</div>
            <div class="step-label">{{ translations.pricing_details }}</div>
          </div>
          <div class="step" :class="{ active: currentStep >= 3, completed: currentStep > 3 }">
            <div class="step-number">3</div>
            <div class="step-label">{{ translations.additional_details }}</div>
          </div>
          <div class="step" :class="{ active: currentStep >= 4, completed: currentStep > 4 }">
            <div class="step-number">4</div>
            <div class="step-label">{{ translations.media_upload }}</div>
          </div>
        </div>
      </div>

    <form @submit.prevent="submitForm" class="decoration-form">
      <!-- Step 1: Basic Information -->
      <div v-show="currentStep === 1" class="form-step">
        <div class="step-header">
          <h5 class="step-title">
            <i class="bi bi-info-circle text-primary"></i>
            {{ translations.basic_information }}
          </h5>
          <p class="step-description">{{ translations.fill_basic_info }}</p>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label required">
                <i class="bi bi-tag"></i>
                {{ translations.decoration_name }}
              </label>
              <input 
                type="text" 
                class="form-control" 
                v-model="form.name"
                :placeholder="translations.enter_decoration_name"
                required
                @input="validateField('name')"
              >
              <div class="form-feedback" v-if="errors.name">{{ errors.name }}</div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-file-text"></i>
                {{ translations.decoration_description }}
              </label>
              <input  
                class="form-control" 
                type="text"
                v-model="form.description"
                :placeholder="translations.enter_description"
                @input="validateField('description')"
              > 
              <div class="form-feedback" v-if="errors.description">{{ errors.description }}</div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label required">
                <i class="bi bi-grid"></i>
                {{ translations.decoration_type }}
              </label>
              <select class="form-select" v-model="form.type" required @change="validateField('type')">
                <option value="">{{ translations.select_type }}</option>
                <option value="birthday">{{ translations.birthday }}</option>
                <option value="gender_reveal">{{ translations.gender_reveal }}</option>
                <option value="baby_shower">{{ translations.baby_shower }}</option>
                <option value="wedding">{{ translations.wedding }}</option>
                <option value="graduation">{{ translations.graduation }}</option>
                <option value="corporate">{{ translations.corporate }}</option>
                <option value="religious">{{ translations.religious }}</option>
              </select>
              <div class="form-feedback" v-if="errors.type">{{ errors.type }}</div>
            </div>

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
          </div>
        </div>
      </div>

      <!-- Step 2: Pricing & Details -->
      <div v-show="currentStep === 2" class="form-step">
        <div class="step-header">
          <h5 class="step-title">
            <i class="bi bi-currency-dollar text-success"></i>
            {{ translations.pricing_details }}
          </h5>
          <p class="step-description">{{ translations.set_pricing_info }}</p>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label required">
                <i class="bi bi-globe"></i>
                {{ translations.currency }}
              </label>
              <select class="form-select" v-model="form.currency" required @change="onCurrencyChange">
                <option value="dinar">{{ translations.dinar }}</option>
                <option value="dollar">{{ translations.dollar }}</option>
              </select>
              <div class="form-feedback" v-if="errors.currency">{{ errors.currency }}</div>
            </div>

            <div class="form-group">
              <label class="form-label required">
                <i class="bi bi-clock"></i>
                {{ translations.duration_hours }}
              </label>
              <input 
                type="number" 
                class="form-control" 
                v-model="form.duration_hours"
                :placeholder="translations.enter_duration"
                min="1"
                max="24"
                required
                @input="validateField('duration_hours')"
              >
              <div class="form-feedback" v-if="errors.duration_hours">{{ errors.duration_hours }}</div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label required">
                <i class="bi bi-people"></i>
                {{ translations.team_size }}
              </label>
              <input 
                type="number" 
                class="form-control" 
                v-model="form.team_size"
                :placeholder="translations.enter_team_size"
                min="1"
                max="20"
                required
                @input="validateField('team_size')"
              >
              <div class="form-feedback" v-if="errors.team_size">{{ errors.team_size }}</div>
            </div>

            <!-- Price Display -->
            <div class="price-display">
              <div class="price-card">
                <div class="price-header">
                  <i class="bi bi-calculator"></i>
                  {{ translations.current_price }}
                </div>
                <div class="price-value">
                  {{ formatCurrentPrice() }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label required">
                <i class="bi bi-currency-exchange"></i>
                {{ translations.base_price }}
              </label>
              <div class="input-group">
                <input 
                  type="number" 
                  step="0.01"
                  class="form-control" 
                  v-model="form.base_price"
                  :placeholder="getPricePlaceholder()"
                  min="0"
                  required
                  @input="onBasePriceChange"
                >
                <span class="input-group-text">{{ getCurrentCurrencySymbol() }}</span>
              </div>
              <div class="form-feedback" v-if="errors.base_price">{{ errors.base_price }}</div>
            </div>
          </div>
          <div class="col-md-6">
            <!-- Currency Converter Display -->
            <div class="currency-converter">
              <label class="form-label">
                <i class="bi bi-calculator"></i>
                {{ translations.converted_price }}
              </label>
              <div class="converter-display">
                <div class="converted-item">
                  <span class="currency-label">{{ translations.dinar }}:</span>
                  <span class="currency-value">{{ formatPriceInDinar() }}</span>
                </div>
                <div class="converted-item">
                  <span class="currency-label">{{ translations.dollar }}:</span>
                  <span class="currency-value">{{ formatPriceInDollar() }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Additional Details -->
      <div v-show="currentStep === 3" class="form-step">
        <div class="step-header">
          <h5 class="step-title">
            <i class="bi bi-gear text-info"></i>
            {{ translations.additional_details }}
          </h5>
          <p class="step-description">{{ translations.set_additional_costs }}</p>
        </div>

        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-box"></i>
                {{ translations.materials_cost }}
              </label>
              <div class="input-group">
                <input 
                  type="number" 
                  step="0.01"
                  class="form-control" 
                  v-model="form.materials_cost"
                  :placeholder="translations.enter_materials_cost"
                  min="0"
                >
                <span class="input-group-text">{{ getCurrentCurrencySymbol() }}</span>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-person-workspace"></i>
                {{ translations.labor_cost }}
              </label>
              <div class="input-group">
                <input 
                  type="number" 
                  step="0.01"
                  class="form-control" 
                  v-model="form.labor_cost"
                  :placeholder="translations.enter_labor_cost"
                  min="0"
                >
                <span class="input-group-text">{{ getCurrentCurrencySymbol() }}</span>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-truck"></i>
                {{ translations.transportation_cost }}
              </label>
              <div class="input-group">
                <input 
                  type="number" 
                  step="0.01"
                  class="form-control" 
                  v-model="form.transportation_cost"
                  :placeholder="translations.enter_transportation_cost"
                  min="0"
                >
                <span class="input-group-text">{{ getCurrentCurrencySymbol() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Cost Summary -->
        <div class="cost-summary">
          <h6 class="summary-title">
            <i class="bi bi-calculator"></i>
            {{ translations.cost_summary }}
          </h6>
          <div class="summary-items">
            <div class="summary-item">
              <span>{{ translations.materials_cost }}:</span>
              <span>{{ formatPrice(form.materials_cost) }}</span>
            </div>
            <div class="summary-item">
              <span>{{ translations.labor_cost }}:</span>
              <span>{{ formatPrice(form.labor_cost) }}</span>
            </div>
            <div class="summary-item">
              <span>{{ translations.transportation_cost }}:</span>
              <span>{{ formatPrice(form.transportation_cost) }}</span>
            </div>
            <div class="summary-item total">
              <span>{{ translations.total_cost }}:</span>
              <span>{{ formatPrice(getTotalCost()) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4: Media Upload -->
      <div v-show="currentStep === 4" class="form-step">
        <div class="step-header">
          <h5 class="step-title">
            <i class="bi bi-camera text-warning"></i>
            {{ translations.media_upload }}
          </h5>
          <p class="step-description">{{ translations.upload_images_videos }}</p>
        </div>

        <div class="row">
          <!-- Main Image Upload -->
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-image"></i>
                {{ translations.main_image }}
              </label>
              <div class="upload-area" @click="triggerMainImageUpload" @dragover.prevent @drop.prevent="handleMainImageDrop">
                <input 
                  ref="mainImageInput"
                  type="file" 
                  accept="image/*" 
                  @change="handleMainImageChange"
                  style="display: none"
                >
                <div v-if="!mainImagePreview" class="upload-placeholder">
                  <i class="bi bi-cloud-upload"></i>
                  <p>{{ translations.click_or_drag_image }}</p>
                  <small>{{ translations.supported_formats }}</small>
                </div>
                <div v-else class="image-preview-container">
                  <img :src="mainImagePreview" alt="Main Image Preview" class="preview-image">
                  <button type="button" @click.stop="removeMainImage" class="remove-image-btn">
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Images Upload -->
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-images"></i>
                {{ translations.additional_images }}
              </label>
              <div class="upload-area" @click="triggerAdditionalImagesUpload" @dragover.prevent @drop.prevent="handleAdditionalImagesDrop">
                <input 
                  ref="additionalImagesInput"
                  type="file" 
                  accept="image/*" 
                  multiple
                  @change="handleAdditionalImagesChange"
                  style="display: none"
                >
                <div v-if="additionalImagesPreviews.length === 0" class="upload-placeholder">
                  <i class="bi bi-cloud-upload"></i>
                  <p>{{ translations.click_or_drag_multiple_images }}</p>
                  <small>{{ translations.max_images_limit }}</small>
                </div>
                <div v-else class="images-grid">
                  <div v-for="(preview, index) in additionalImagesPreviews" :key="index" class="image-item">
                    <img :src="preview" alt="Additional Image Preview" class="preview-image">
                    <button type="button" @click.stop="removeAdditionalImage(index)" class="remove-image-btn">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Video Upload Section -->
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-play-circle"></i>
                {{ translations.video_upload }}
              </label>
              <div class="video-upload-section">
                <div class="row">
                  <!-- Video File Upload -->
                  <div class="col-md-6">
                    <div class="upload-area" @click="triggerVideoUpload" @dragover.prevent @drop.prevent="handleVideoDrop">
                      <input 
                        ref="videoInput"
                        type="file" 
                        accept="video/*" 
                        @change="handleVideoChange"
                        style="display: none"
                      >
                      <div v-if="!videoPreview" class="upload-placeholder">
                        <i class="bi bi-cloud-upload"></i>
                        <p>{{ translations.click_or_drag_video }}</p>
                        <small>{{ translations.supported_video_formats }}</small>
                      </div>
                      <div v-else class="video-preview-container">
                        <video :src="videoPreview" controls class="preview-video"></video>
                        <button type="button" @click.stop="removeVideo" class="remove-video-btn">
                          <i class="bi bi-x"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Video URL Input -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">
                        <i class="bi bi-link-45deg"></i>
                        {{ translations.video_url_alternative }}
                      </label>
                      <input 
                        type="url" 
                        class="form-control" 
                        v-model="form.video_url"
                        :placeholder="translations.enter_video_url"
                      >
                      <small class="form-text text-muted">{{ translations.or_enter_video_url }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Media Summary -->
        <div class="media-summary">
          <h6 class="summary-title">
            <i class="bi bi-collection"></i>
            {{ translations.media_summary }}
          </h6>
          <div class="summary-items">
            <div class="summary-item">
              <span>{{ translations.main_image }}:</span>
              <span>{{ mainImagePreview ? translations.uploaded : translations.not_uploaded }}</span>
            </div>
            <div class="summary-item">
              <span>{{ translations.additional_images }}:</span>
              <span>{{ additionalImagesPreviews.length }} {{ translations.images }}</span>
            </div>
            <div class="summary-item">
              <span>{{ translations.video }}:</span>
              <span>{{ videoPreview || form.video_url ? translations.uploaded : translations.not_uploaded }}</span>
            </div>
          </div>
        </div>
      </div>

    </form>
    </div>

    <!-- Modal Footer -->
    <div class="modal-footer">
      <button 
        type="button" 
        class="btn btn-outline-secondary" 
        @click="previousStep"
        :disabled="currentStep === 1"
      >
        <i class="bi bi-arrow-left"></i>
        {{ translations.previous }}
      </button>
      
      <button 
        type="button" 
        class="btn btn-outline-primary" 
        @click="nextStep"
        v-if="currentStep < 4"
      >
        {{ translations.next }}
        <i class="bi bi-arrow-right"></i>
      </button>
      
      <button 
        type="submit" 
        class="btn btn-success" 
        :disabled="processing || !isFormValid"
        v-if="currentStep === 4"
        @click="submitForm"
      >
        <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
        <i class="bi bi-check-circle"></i>
        {{ translations.save }}
      </button>
      
      <button type="button" class="btn btn-outline-danger" @click="$emit('cancel')">
        <i class="bi bi-x-circle"></i>
        {{ translations.cancel }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  translations: Object,
  exchangeRate: {
    type: Number,
    default: 1500
  }
})

const emit = defineEmits(['success', 'cancel'])

// State
const processing = ref(false)
const currentStep = ref(1)
const errors = ref({})

// Media upload refs
const mainImageInput = ref(null)
const additionalImagesInput = ref(null)
const videoInput = ref(null)

// Media previews
const mainImagePreview = ref(null)
const additionalImagesPreviews = ref([])
const videoPreview = ref(null)

// Media files
const mainImageFile = ref(null)
const additionalImagesFiles = ref([])
const videoFile = ref(null)

// Form
const form = reactive({
  name: '',
  description: '',
  type: '',
  currency: 'dinar',
  base_price: '',
  base_price_dinar: '',
  base_price_dollar: '',
  duration_hours: '',
  team_size: '',
  is_active: true,
  materials_cost: 0,
  labor_cost: 0,
  transportation_cost: 0,
  video_url: ''
})

// Computed
const isFormValid = computed(() => {
  if (currentStep.value === 1) {
    return form.name && form.type
  } else if (currentStep.value === 2) {
    return form.base_price && form.duration_hours && form.team_size
  }
  return true
})

// Methods
const nextStep = () => {
  if (currentStep.value < 4) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const validateField = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

const onCurrencyChange = () => {
  // إعادة حساب السعر عند تغيير العملة
  if (form.base_price) {
    onBasePriceChange()
  }
}

const onBasePriceChange = () => {
  const price = parseFloat(form.base_price) || 0
  
  if (form.currency === 'dinar') {
    form.base_price_dinar = price
    form.base_price_dollar = (price / props.exchangeRate).toFixed(2)
  } else {
    form.base_price_dollar = price
    form.base_price_dinar = (price * props.exchangeRate).toFixed(0)
  }
}

const getPricePlaceholder = () => {
  return form.currency === 'dinar' 
    ? props.translations.enter_price_dinar 
    : props.translations.enter_price_dollar
}

const formatCurrentPrice = () => {
  return `${form.base_price || 0} ${getCurrentCurrencySymbol()}`
}

const formatPriceInDinar = () => {
  return `${form.base_price_dinar || 0} ${props.translations.dinar}`
}

const formatPriceInDollar = () => {
  return `${form.base_price_dollar || 0} ${props.translations.dollar}`
}

const getCurrentCurrencySymbol = () => {
  return form.currency === 'dinar' ? props.translations.dinar : props.translations.dollar
}

const formatPrice = (price) => {
  const numPrice = parseFloat(price) || 0
  return `${numPrice.toLocaleString()} ${getCurrentCurrencySymbol()}`
}

const getTotalCost = () => {
  const materials = parseFloat(form.materials_cost) || 0
  const labor = parseFloat(form.labor_cost) || 0
  const transportation = parseFloat(form.transportation_cost) || 0
  return materials + labor + transportation
}

// Media upload methods
const triggerMainImageUpload = () => {
  mainImageInput.value?.click()
}

const triggerAdditionalImagesUpload = () => {
  additionalImagesInput.value?.click()
}

const triggerVideoUpload = () => {
  videoInput.value?.click()
}

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
  const files = event.dataTransfer.files
  if (files.length > 0) {
    const file = files[0]
    if (file.type.startsWith('image/')) {
      mainImageFile.value = file
      const reader = new FileReader()
      reader.onload = (e) => {
        mainImagePreview.value = e.target.result
      }
      reader.readAsDataURL(file)
    }
  }
}

const removeMainImage = () => {
  mainImagePreview.value = null
  mainImageFile.value = null
  if (mainImageInput.value) {
    mainImageInput.value.value = ''
  }
}

const handleAdditionalImagesChange = (event) => {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    if (file.type.startsWith('image/') && additionalImagesPreviews.value.length < 10) {
      additionalImagesFiles.value.push(file)
      const reader = new FileReader()
      reader.onload = (e) => {
        additionalImagesPreviews.value.push(e.target.result)
      }
      reader.readAsDataURL(file)
    }
  })
}

const handleAdditionalImagesDrop = (event) => {
  const files = Array.from(event.dataTransfer.files)
  files.forEach(file => {
    if (file.type.startsWith('image/') && additionalImagesPreviews.value.length < 10) {
      additionalImagesFiles.value.push(file)
      const reader = new FileReader()
      reader.onload = (e) => {
        additionalImagesPreviews.value.push(e.target.result)
      }
      reader.readAsDataURL(file)
    }
  })
}

const removeAdditionalImage = (index) => {
  additionalImagesPreviews.value.splice(index, 1)
  additionalImagesFiles.value.splice(index, 1)
}

const handleVideoChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    videoFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      videoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const handleVideoDrop = (event) => {
  const files = event.dataTransfer.files
  if (files.length > 0) {
    const file = files[0]
    if (file.type.startsWith('video/')) {
      videoFile.value = file
      const reader = new FileReader()
      reader.onload = (e) => {
        videoPreview.value = e.target.result
      }
      reader.readAsDataURL(file)
    }
  }
}

const removeVideo = () => {
  videoPreview.value = null
  videoFile.value = null
  if (videoInput.value) {
    videoInput.value.value = ''
  }
}

const submitForm = () => {
  processing.value = true
  
  // التأكد من حساب الأسعار قبل الإرسال
  if (form.base_price && (!form.base_price_dinar || !form.base_price_dollar)) {
    onBasePriceChange()
  }
  
  // إنشاء FormData لرفع الملفات
  const formData = new FormData()
  
  // إضافة البيانات الأساسية
  Object.keys(form).forEach(key => {
    if (key !== 'video_url' && form[key] !== null && form[key] !== undefined) {
      // Handle boolean values
      if (typeof form[key] === 'boolean') {
        formData.append(key, form[key] ? '1' : '0')
      } else if (key !== 'base_price_dinar' && key !== 'base_price_dollar') {
        formData.append(key, form[key])
      }
    }
  })
  
  // إضافة الصورة الرئيسية
  if (mainImageFile.value) {
    formData.append('main_image', mainImageFile.value)
  }
  
  // إضافة الصور الإضافية
  additionalImagesFiles.value.forEach((file, index) => {
    formData.append(`images[${index}]`, file)
  })
  
  // إضافة الفيديو
  if (videoFile.value) {
    formData.append('video', videoFile.value)
  } else if (form.video_url) {
    formData.append('video_url', form.video_url)
  }
  
  // إعداد البيانات للإرسال - استخدام base_price مباشرة
  formData.append('base_price', parseFloat(form.base_price) || 0)
  formData.append('materials_cost', parseFloat(form.materials_cost) || 0)
  formData.append('labor_cost', parseFloat(form.labor_cost) || 0)
  formData.append('transportation_cost', parseFloat(form.transportation_cost) || 0)
  
  router.post(route('decorations.store'), formData, {
    onSuccess: () => {
      processing.value = false
      emit('success')
    },
    onError: (errors) => {
      processing.value = false
      console.error('Validation errors:', errors)
      // عرض رسائل الأخطاء للمستخدم
      if (errors) {
        Object.keys(errors).forEach(key => {
          console.error(`${key}: ${errors[key]}`)
        })
      }
    }
  })
}
</script>

<style scoped>
/* تحسين تصميم النموذج بشكل عام */
.decoration-form-container {
  background: #ffffff;
  border-radius: 16px;
  padding: 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: hidden;
  max-width: 800px;
  margin: 0 auto;
}

/* Modal Header */
.modal-header {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #ffffff;
  padding: 1.5rem 2rem;
  text-align: center;
  border-bottom: none;
  margin: 0;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.modal-title i {
  font-size: 1.75rem;
  color: #dbeafe;
}

/* Modal Body */
.modal-body {
  padding: 2rem;
  background: #ffffff;
}

/* Progress Indicator */
.progress-indicator {
  margin-bottom: 2rem;
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
  top: 20px;
  left: 0;
  right: 0;
  height: 2px;
  background: #e5e7eb;
  z-index: 1;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
  background: #ffffff;
  padding: 0 1rem;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e5e7eb;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  margin-bottom: 0.5rem;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: #3b82f6;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.step.completed .step-number {
  background: #10b981;
  color: #ffffff;
}

.step-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  text-align: center;
}

.step.active .step-label {
  color: #3b82f6;
  font-weight: 600;
}

.step.completed .step-label {
  color: #10b981;
}

/* Form Steps */
.form-step {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.step-header {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f3f4f6;
  text-align: center;
}

.step-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.step-description {
  color: #6b7280;
  margin: 0;
  font-size: 0.95rem;
}

/* Form Groups */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.form-label.required::after {
  content: '*';
  color: #ef4444;
  margin-left: 0.25rem;
}

.form-label i {
  color: #6b7280;
  font-size: 1rem;
}

/* Form Controls */
.form-control, .form-select {
  border: 2px solid #d1d5db;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: #ffffff;
}

.form-control:focus, .form-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  outline: none;
}

/* Textarea خاص */
textarea.form-control {
  border: 2px solid #d1d5db !important;
  border-radius: 8px !important;
  padding: 0.75rem 1rem !important;
  font-size: 0.95rem !important;
  transition: all 0.3s ease !important;
  background: #ffffff !important;
  resize: vertical;
  min-height: 100px;
}

textarea.form-control:focus {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
  outline: none !important;
}

textarea.form-control:hover {
  border-color: #9ca3af !important;
}

/* Input Groups */
.input-group {
  position: relative;
}

.input-group-text {
  background: #f9fafb;
  border: 2px solid #d1d5db;
  border-left: none;
  color: #6b7280;
  font-weight: 500;
}

.input-group .form-control {
  border-right: none;
}

.input-group .form-control:focus {
  border-right: 2px solid #3b82f6;
}

/* Form Switch */
.form-check-input {
  width: 3rem;
  height: 1.5rem;
}

.form-check-input:checked {
  background-color: #10b981;
  border-color: #10b981;
}

/* Price Display */
.price-display {
  margin-top: 1rem;
}

.price-card {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 2px solid #0ea5e9;
  border-radius: 12px;
  padding: 1.5rem;
  text-align: center;
}

.price-header {
  font-size: 0.875rem;
  color: #0369a1;
  font-weight: 600;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.price-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0c4a6e;
}

/* Currency Converter */
.currency-converter {
  margin-top: 1rem;
}

.converter-display {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 2px solid #0ea5e9;
  border-radius: 12px;
  padding: 1.5rem;
}

.converted-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #bae6fd;
}

.converted-item:last-child {
  border-bottom: none;
}

.currency-label {
  font-size: 0.9rem;
  color: #0369a1;
  font-weight: 600;
}

.currency-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0c4a6e;
}

/* Cost Summary */
.cost-summary {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 2rem;
}

.summary-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item.total {
  font-weight: 700;
  font-size: 1.1rem;
  color: #059669;
  background: #ecfdf5;
  padding: 0.75rem;
  border-radius: 8px;
  border: 2px solid #10b981;
}

/* Modal Footer */
.modal-footer {
  background: #f8fafc;
  border-top: 2px solid #e5e7eb;
  padding: 1.5rem 2rem;
  margin: 0;
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  flex: 1;
  min-width: 120px;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-outline-secondary {
  border: 2px solid #6b7280;
  color: #6b7280;
  background: transparent;
}

.btn-outline-secondary:hover {
  background: #6b7280;
  color: #ffffff;
}

.btn-outline-primary {
  border: 2px solid #3b82f6;
  color: #3b82f6;
  background: transparent;
}

.btn-outline-primary:hover {
  background: #3b82f6;
  color: #ffffff;
}

.btn-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: 2px solid #10b981;
  color: #ffffff;
}

.btn-success:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-color: #059669;
}

.btn-outline-danger {
  border: 2px solid #ef4444;
  color: #ef4444;
  background: transparent;
}

.btn-outline-danger:hover {
  background: #ef4444;
  color: #ffffff;
}

/* Form Feedback */
.form-feedback {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

/* الوضع الليلي */
.dark .decoration-form-container {
  background: #1f2937;
  border-color: #374151;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.dark .modal-header {
  background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
}

.dark .modal-title {
  color: #dbeafe;
}

.dark .modal-title i {
  color: #93c5fd;
}

.dark .modal-body {
  background: #1f2937;
}

.dark .modal-footer {
  background: #374151;
  border-top-color: #4b5563;
}

.dark .step {
  background: #1f2937;
}

.dark .step-number {
  background: #374151;
  color: #9ca3af;
}

.dark .step.active .step-number {
  background: #3b82f6;
  color: #ffffff;
}

.dark .step.completed .step-number {
  background: #10b981;
  color: #ffffff;
}

.dark .step-label {
  color: #9ca3af;
}

.dark .step.active .step-label {
  color: #60a5fa;
}

.dark .step.completed .step-label {
  color: #34d399;
}

.dark .step-header {
  border-bottom-color: #374151;
}

.dark .step-title {
  color: #f9fafb;
}

.dark .step-description {
  color: #9ca3af;
}

.dark .form-label {
  color: #d1d5db;
}

.dark .form-label i {
  color: #9ca3af;
}

.dark .form-control, .dark .form-select {
  background: #374151;
  border-color: #4b5563;
  color: #f9fafb;
}

.dark .form-control:focus, .dark .form-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

/* Textarea في الوضع الليلي */
.dark textarea.form-control {
  background: #374151 !important;
  border-color: #4b5563 !important;
  color: #f9fafb !important;
}

.dark textarea.form-control:focus {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

.dark textarea.form-control:hover {
  border-color: #6b7280 !important;
}

.dark .input-group-text {
  background: #4b5563;
  border-color: #4b5563;
  color: #d1d5db;
}

.dark .price-card {
  background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
  border-color: #3b82f6;
}

.dark .price-header {
  color: #93c5fd;
}

.dark .price-value {
  color: #dbeafe;
}

.dark .cost-summary {
  background: #374151;
  border-color: #4b5563;
}

.dark .summary-title {
  color: #f9fafb;
}

.dark .summary-item {
  color: #d1d5db;
  border-bottom-color: #4b5563;
}

.dark .summary-item.total {
  background: #064e3b;
  border-color: #10b981;
  color: #34d399;
}

/* Media Upload Styles */
.upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #f9fafb;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-area:hover {
  border-color: #3b82f6;
  background: #f0f9ff;
}

.upload-placeholder {
  color: #6b7280;
}

.upload-placeholder i {
  font-size: 3rem;
  color: #9ca3af;
  margin-bottom: 1rem;
}

.upload-placeholder p {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #374151;
}

.upload-placeholder small {
  color: #6b7280;
  font-size: 0.875rem;
}

.image-preview-container {
  position: relative;
  display: inline-block;
}

.preview-image {
  max-width: 100%;
  max-height: 200px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.remove-image-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

.remove-image-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.image-item {
  position: relative;
  display: inline-block;
}

.preview-video {
  max-width: 100%;
  max-height: 200px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.video-preview-container {
  position: relative;
  display: inline-block;
}

.remove-video-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

.remove-video-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.video-upload-section {
  margin-top: 1rem;
}

.media-summary {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 2rem;
}

.form-text {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

/* Dark mode for media upload */
.dark .upload-area {
  background: #374151;
  border-color: #4b5563;
}

.dark .upload-area:hover {
  border-color: #3b82f6;
  background: #1e3a8a;
}

.dark .upload-placeholder {
  color: #9ca3af;
}

.dark .upload-placeholder i {
  color: #6b7280;
}

.dark .upload-placeholder p {
  color: #d1d5db;
}

.dark .upload-placeholder small {
  color: #9ca3af;
}

.dark .media-summary {
  background: #374151;
  border-color: #4b5563;
}

.dark .form-text {
  color: #9ca3af;
}

/* Modal Scroll Fix */
.decoration-form-container {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.decoration-modal-body {
  flex: 1;
  overflow-y: auto;
  max-height: calc(90vh - 120px);
  padding: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .decoration-modal-body {
    max-height: calc(90vh - 100px);
    padding: 1rem;
  }
  
  .modal-footer {
    padding: 1rem;
    flex-direction: column;
  }
  
  .progress-steps {
    flex-direction: column;
    gap: 1rem;
  }
  
  .progress-steps::before {
    display: none;
  }
  
  .btn {
    flex: none;
    width: 100%;
  }
  
  .upload-area {
    min-height: 150px;
    padding: 1rem;
  }
  
  .images-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
  }
}
</style>
