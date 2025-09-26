<template>
  <AuthenticatedLayout :translations="translations">
    <div class="pagetitle">
      <h1>إضافة ديكور جديد</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link :href="route('decorations.index')">الديكورات</Link>
          </li>
          <li class="breadcrumb-item active">إضافة جديد</li>
        </ol>
      </nav>
    </div>

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card decoration-create-card">
            <div class="card-body decoration-create-body">
              <!-- Progress Indicator -->
              <div class="progress-indicator mb-4">
                <div class="progress-steps">
                  <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                    <div class="step-number">1</div>
                    <div class="step-label">المعلومات الأساسية</div>
                  </div>
                  <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
                    <div class="step-number">2</div>
                    <div class="step-label">تفاصيل التسعير</div>
                  </div>
                  <div class="step" :class="{ active: currentStep >= 3, completed: currentStep > 3 }">
                    <div class="step-number">3</div>
                    <div class="step-label">التفاصيل الإضافية</div>
                  </div>
                  <div class="step" :class="{ active: currentStep >= 4, completed: currentStep > 4 }">
                    <div class="step-number">4</div>
                    <div class="step-label">رفع الوسائط</div>
                  </div>
                </div>
              </div>

              <form @submit.prevent="submitForm">
                <!-- Step 1: Basic Information -->
                <div v-show="currentStep === 1" class="form-step">
                  <div class="step-header">
                    <h5 class="step-title">
                      <i class="bi bi-info-circle text-primary"></i>
                      المعلومات الأساسية
                    </h5>
                    <p class="step-description">املأ المعلومات الأساسية للديكور</p>
                  </div>

                <div class="row">
                  <div class="col-md-6">
                      <div class="form-group">
                        <label class="form-label required">
                          <i class="bi bi-tag"></i>
                          اسم الديكور
                        </label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.name"
                          placeholder="أدخل اسم الديكور"
                        required
                      >
                    </div>

                      <div class="form-group">
                        <label class="form-label">
                          <i class="bi bi-file-text"></i>
                          وصف الديكور
                        </label>
                        <textarea 
                          class="form-control" 
                          v-model="form.description"
                          placeholder="أدخل وصف الديكور"
                          rows="4"
                        ></textarea>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label class="form-label required">
                          <i class="bi bi-grid"></i>
                          نوع الديكور
                        </label>
                        <select class="form-select" v-model="form.type" required>
                        <option value="">اختر النوع</option>
                        <option value="birthday">عيد ميلاد</option>
                        <option value="gender_reveal">تحديد جنس المولود</option>
                        <option value="baby_shower">حفلة الولادة</option>
                        <option value="wedding">زفاف</option>
                        <option value="graduation">تخرج</option>
                        <option value="corporate">شركات</option>
                        <option value="religious">ديني</option>
                      </select>
                    </div>

                      <div class="form-group">
                        <label class="form-label">
                          <i class="bi bi-toggle-on"></i>
                          الحالة
                        </label>
                        <div class="form-check form-switch">
                      <input 
                            class="form-check-input" 
                            type="checkbox" 
                            v-model="form.is_active"
                            id="is_active"
                          >
                          <label class="form-check-label" for="is_active">
                            {{ form.is_active ? 'نشط' : 'غير نشط' }}
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
                      تفاصيل التسعير
                    </h5>
                    <p class="step-description">حدد معلومات التسعير والتفاصيل</p>
                    </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label class="form-label required">
                          <i class="bi bi-globe"></i>
                          العملة
                        </label>
                        <select class="form-select" v-model="form.currency" required @change="onCurrencyChange">
                        <option value="dinar">دينار</option>
                          <option value="dollar">دولار</option>
                      </select>
                    </div>

                      <div class="form-group">
                        <label class="form-label required">
                          <i class="bi bi-clock"></i>
                          مدة التنفيذ (ساعات)
                        </label>
                      <input 
                        type="number" 
                        class="form-control" 
                        v-model="form.duration_hours"
                          placeholder="أدخل مدة التنفيذ"
                          min="1"
                          max="24"
                          required
                      >
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label class="form-label required">
                          <i class="bi bi-people"></i>
                          حجم الفريق
                        </label>
                      <input 
                        type="number" 
                        class="form-control" 
                        v-model="form.team_size"
                          placeholder="أدخل حجم الفريق"
                          min="1"
                          max="20"
                          required
                      >
                    </div>

                      <!-- Price Display -->
                      <div class="price-display">
                        <div class="price-card">
                          <div class="price-header">
                            <i class="bi bi-calculator"></i>
                            السعر الحالي
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
                          السعر الأساسي
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
                      </div>
                    </div>
                    <div class="col-md-6">
                      <!-- Currency Converter Display -->
                      <div class="currency-converter">
                        <label class="form-label">
                          <i class="bi bi-calculator"></i>
                          السعر المحول
                        </label>
                        <div class="converter-display">
                          <div class="converted-item">
                            <span class="currency-label">دينار:</span>
                            <span class="currency-value">{{ formatPriceInDinar() }}</span>
                          </div>
                          <div class="converted-item">
                            <span class="currency-label">دولار:</span>
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
                      التفاصيل الإضافية
                    </h5>
                    <p class="step-description">حدد التكاليف الإضافية</p>
                    </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label class="form-label">
                          <i class="bi bi-box"></i>
                          تكلفة المواد
                        </label>
                        <div class="input-group">
                      <input 
                        type="number" 
                            step="0.01"
                        class="form-control" 
                            v-model="form.materials_cost"
                            placeholder="أدخل تكلفة المواد"
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
                          تكلفة العمالة
                        </label>
                        <div class="input-group">
                      <input 
                        type="number" 
                            step="0.01"
                        class="form-control" 
                            v-model="form.labor_cost"
                            placeholder="أدخل تكلفة العمالة"
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
                          تكلفة النقل
                        </label>
                        <div class="input-group">
                      <input 
                            type="number" 
                            step="0.01"
                        class="form-control" 
                            v-model="form.transportation_cost"
                            placeholder="أدخل تكلفة النقل"
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
                      ملخص التكاليف
                    </h6>
                    <div class="summary-items">
                      <div class="summary-item">
                        <span>تكلفة المواد:</span>
                        <span>{{ formatPrice(form.materials_cost) }}</span>
                      </div>
                      <div class="summary-item">
                        <span>تكلفة العمالة:</span>
                        <span>{{ formatPrice(form.labor_cost) }}</span>
                    </div>
                      <div class="summary-item">
                        <span>تكلفة النقل:</span>
                        <span>{{ formatPrice(form.transportation_cost) }}</span>
                      </div>
                      <div class="summary-item total">
                        <span>التكلفة الإجمالية:</span>
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
                      رفع الوسائط
                    </h5>
                    <p class="step-description">ارفع الصور والفيديوهات للديكور</p>
                  </div>

                <div class="row">
                    <!-- Main Image Upload -->
                  <div class="col-md-6">
                      <div class="form-group">
                        <label class="form-label">
                          <i class="bi bi-image"></i>
                          الصورة الرئيسية
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
                            <p>انقر أو اسحب الصورة هنا</p>
                            <small>الصيغ المدعومة: JPG, PNG, GIF, WebP</small>
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
                          صور إضافية
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
                            <p>انقر أو اسحب الصور هنا</p>
                            <small>الحد الأقصى: 10 صور</small>
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

                  <!-- YouTube Links Section -->
                <div class="row">
                  <div class="col-12">
                      <div class="form-group">
                        <label class="form-label">
                          <i class="bi bi-youtube"></i>
                          روابط YouTube
                        </label>
                        <div class="youtube-links-section">
                          <div 
                            v-for="(link, index) in form.youtube_links" 
                            :key="index"
                            class="youtube-link-item"
                          >
                            <div class="input-group">
                              <input 
                                type="url" 
                                v-model="form.youtube_links[index]"
                                class="form-input"
                                :placeholder="`رابط YouTube ${index + 1}`"
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
                          </div>
                          <button 
                            type="button" 
                            @click="addYoutubeLink"
                            class="add-link-btn"
                            v-if="form.youtube_links.length < 3"
                          >
                            <i class="bi bi-plus"></i>
                            إضافة رابط YouTube
                          </button>
                          <small class="form-help">يمكنك إضافة حتى 3 روابط YouTube</small>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Media Summary -->
                  <div class="media-summary">
                    <h6 class="summary-title">
                      <i class="bi bi-collection"></i>
                      ملخص الوسائط
                    </h6>
                    <div class="summary-items">
                      <div class="summary-item">
                        <span>الصورة الرئيسية:</span>
                        <span>{{ mainImagePreview ? 'تم الرفع' : 'لم يتم الرفع' }}</span>
                    </div>
                      <div class="summary-item">
                        <span>صور إضافية:</span>
                        <span>{{ additionalImagesPreviews.length }} صور</span>
                        </div>
                      <div class="summary-item">
                        <span>فيديو:</span>
                        <span>{{ getYoutubeLinksCount() > 0 ? `${getYoutubeLinksCount()} رابط` : 'لم يتم الإضافة' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="card-footer">
            <div class="d-flex justify-content-between">
              <button 
                type="button" 
                class="btn btn-outline-secondary" 
                @click="previousStep"
                :disabled="currentStep === 1"
              >
                <i class="bi bi-arrow-left"></i>
                السابق
              </button>
              
              <div class="d-flex gap-2">
                <button 
                  type="button" 
                  class="btn btn-outline-primary" 
                  @click="nextStep"
                  v-if="currentStep < 4"
                >
                  التالي
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
                        حفظ
                      </button>
                
                <Link :href="route('decorations.index')" class="btn btn-outline-danger">
                  <i class="bi bi-x-circle"></i>
                  إلغاء
                </Link>
                    </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  translations: Object
})

// State
const processing = ref(false)
const currentStep = ref(1)

// File upload refs
const mainImageInput = ref(null)
const additionalImagesInput = ref(null)
const videoInput = ref(null)

// File previews
const mainImagePreview = ref(null)
const additionalImagesPreviews = ref([])
const videoPreview = ref(null)

// File objects
const mainImageFile = ref(null)
const additionalImagesFiles = ref([])
const videoFile = ref(null)

// Form data
const form = reactive({
  name: '',
  type: '',
  description: '',
  base_price: '',
  currency: 'dinar',
  duration_hours: '',
  team_size: '',
  is_featured: false,
  is_active: true,
  youtube_links: [''],
  materials_cost: '',
  labor_cost: '',
  transportation_cost: ''
})

// Exchange rate
const exchangeRate = ref(1500) // 1 USD = 1500 IQD

// Computed properties
const isFormValid = computed(() => {
  return form.name && form.type && form.base_price && form.duration_hours && form.team_size
})

// Navigation methods
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

// Currency methods
const onCurrencyChange = () => {
  // Reset base price when currency changes
  form.base_price = ''
}

const onBasePriceChange = () => {
  // Auto-convert price when base price changes
}

const getCurrentCurrencySymbol = () => {
  return form.currency === 'dinar' ? 'د.ع' : '$'
}

const getPricePlaceholder = () => {
  return form.currency === 'dinar' ? 'أدخل السعر بالدينار' : 'أدخل السعر بالدولار'
}

const formatCurrentPrice = () => {
  if (!form.base_price) return '0.00'
  return `${parseFloat(form.base_price).toFixed(2)} ${getCurrentCurrencySymbol()}`
}

const formatPriceInDinar = () => {
  if (!form.base_price) return '0.00 د.ع'
  const price = form.currency === 'dinar' ? form.base_price : (form.base_price * exchangeRate.value)
  return `${parseFloat(price).toFixed(2)} د.ع`
}

const formatPriceInDollar = () => {
  if (!form.base_price) return '0.00 $'
  const price = form.currency === 'dollar' ? form.base_price : (form.base_price / exchangeRate.value)
  return `${parseFloat(price).toFixed(2)} $`
}

const formatPrice = (price) => {
  if (!price) return '0.00'
  return `${parseFloat(price).toFixed(2)} ${getCurrentCurrencySymbol()}`
}

const getTotalCost = () => {
  const materials = parseFloat(form.materials_cost) || 0
  const labor = parseFloat(form.labor_cost) || 0
  const transportation = parseFloat(form.transportation_cost) || 0
  return materials + labor + transportation
}

// File upload methods
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
  mainImagePreview.value = null
  mainImageFile.value = null
  if (mainImageInput.value) {
    mainImageInput.value.value = ''
  }
}

const handleAdditionalImagesChange = (event) => {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    if (file.type.startsWith('image/') && additionalImagesFiles.value.length < 10) {
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
    if (file.type.startsWith('image/') && additionalImagesFiles.value.length < 10) {
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

const getYoutubeLinksCount = () => {
  return form.youtube_links.filter(link => link && link.trim() !== '').length
}

const handleVideoDrop = (event) => {
  const file = event.dataTransfer.files[0]
  if (file && file.type.startsWith('video/')) {
    videoFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      videoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}


const submitForm = () => {
  processing.value = true
  
  // Create FormData for file uploads
  const formData = new FormData()
  
  // Add form fields
  Object.keys(form).forEach(key => {
    if (form[key] !== null && form[key] !== '' && form[key] !== undefined) {
      // Handle boolean values
      if (typeof form[key] === 'boolean') {
        formData.append(key, form[key] ? '1' : '0')
    } else {
      formData.append(key, form[key])
      }
    }
  })
  
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
      formData.append(`youtube_links[${index}]`, link)
    }
  })
  
  router.post(route('decorations.store'), formData, {
    onFinish: () => {
      processing.value = false
    }
  })
}
</script>

<style scoped>
/* Main Card Styles */
.decoration-create-card {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.decoration-create-body {
  flex: 1;
  overflow-y: auto;
  max-height: calc(90vh - 120px);
  padding: 1.5rem;
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
  background: #e9ecef;
  z-index: 1;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
  background: white;
  padding: 0 1rem;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-bottom: 0.5rem;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: #0d6efd;
  color: white;
}

.step.completed .step-number {
  background: #198754;
  color: white;
}

.step-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-align: center;
  font-weight: 500;
}

.step.active .step-label {
  color: #0d6efd;
  font-weight: 600;
}

.step.completed .step-label {
  color: #198754;
  font-weight: 600;
}

/* Form Steps */
.form-step {
  min-height: 400px;
}

.step-header {
  margin-bottom: 2rem;
  text-align: center;
}

.step-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #212529;
  margin-bottom: 0.5rem;
}

.step-description {
  color: #6c757d;
  font-size: 1rem;
}

/* Form Groups */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-label.required::after {
  content: '*';
  color: #dc3545;
  margin-left: 0.25rem;
}

.form-label i {
  color: #6c757d;
}

/* Price Display */
.price-display {
  margin-top: 1rem;
}

.price-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 2px solid #dee2e6;
  border-radius: 12px;
  padding: 1rem;
  text-align: center;
}

.price-header {
  font-size: 0.875rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.price-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #198754;
}

/* Currency Converter */
.currency-converter {
  margin-top: 1rem;
}

.converter-display {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 1rem;
}

.converted-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e9ecef;
}

.converted-item:last-child {
  border-bottom: none;
}

.currency-label {
  font-weight: 500;
  color: #495057;
}

.currency-value {
  font-weight: 600;
  color: #198754;
}

/* Cost Summary */
.cost-summary {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 2rem;
}

.summary-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
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
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.summary-item.total {
  background: #e7f5e7;
  border-color: #198754;
  font-weight: 600;
  color: #198754;
}

/* Upload Areas */
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
  position: relative;
}

.upload-area:hover {
  border-color: #3b82f6;
  background: #f0f9ff;
}

.upload-area.dragover {
  border-color: #3b82f6;
  background: #eff6ff;
  transform: scale(1.02);
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.upload-placeholder i {
  font-size: 3rem;
  color: #9ca3af;
}

.upload-placeholder p {
  font-size: 1rem;
  color: #6b7280;
  margin: 0;
}

.upload-placeholder small {
  color: #9ca3af;
  font-size: 0.875rem;
}

/* Image Previews */
.image-preview-container {
  position: relative;
  display: inline-block;
}

.preview-image {
  max-width: 100%;
  max-height: 200px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  object-fit: cover;
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
  transition: all 0.3s ease;
}

.remove-image-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

/* Images Grid */
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

/* Video Preview */
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
  transition: all 0.3s ease;
}

.remove-video-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}

/* YouTube Links Section */
.youtube-links-section {
  margin-top: 1rem;
}

.youtube-link-item {
  margin-bottom: 1rem;
}

.input-group {
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
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .decoration-create-body {
    max-height: calc(90vh - 100px);
    padding: 1rem;
  }
  
  .progress-steps {
    flex-direction: column;
    gap: 1rem;
  }
  
  .progress-steps::before {
    display: none;
  }
  
  .step {
    flex-direction: row;
    gap: 0.5rem;
  }
  
  .step-number {
    width: 32px;
    height: 32px;
    margin-bottom: 0;
  }
  
  .images-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
  
  .upload-area {
    min-height: 150px;
    padding: 1rem;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .decoration-create-card {
    background: #1f2937;
    border-color: #374151;
  }
  
  .decoration-create-body {
    background: #1f2937;
  }
  
  .step {
    background: #1f2937;
  }
  
  .step-number {
    background: #374151;
    color: #d1d5db;
  }
  
  .step.active .step-number {
    background: #3b82f6;
  }
  
  .step.completed .step-number {
    background: #10b981;
  }
  
  .step-label {
    color: #d1d5db;
  }
  
  .step-title {
    color: #f9fafb;
  }
  
  .step-description {
    color: #9ca3af;
  }
  
  .form-label {
    color: #d1d5db;
  }
  
  .price-card {
    background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
    border-color: #6b7280;
  }
  
  .price-header {
    color: #9ca3af;
  }
  
  .converter-display {
    background: #374151;
    border-color: #6b7280;
  }
  
  .converted-item {
    border-color: #4b5563;
  }
  
  .currency-label {
    color: #d1d5db;
  }
  
  .cost-summary {
    background: #374151;
    border-color: #6b7280;
  }
  
  .summary-title {
    color: #d1d5db;
  }
  
  .summary-item {
    background: #4b5563;
    border-color: #6b7280;
    color: #d1d5db;
  }
  
  .summary-item.total {
    background: #065f46;
    border-color: #10b981;
    color: #10b981;
  }
  
  .upload-area {
    background: #374151;
    border-color: #6b7280;
  }
  
  .upload-area:hover {
    background: #4b5563;
    border-color: #3b82f6;
  }
  
  .upload-placeholder i {
    color: #6b7280;
  }
  
  .upload-placeholder p {
    color: #9ca3af;
  }
  
  .upload-placeholder small {
    color: #6b7280;
  }
  
  .media-summary {
    background: #374151;
    border-color: #6b7280;
  }
}
</style>
