<template>
  <AuthenticatedLayout :translations="translations">
    

    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">
                <i class="bi bi-pencil-square me-2"></i>
                {{ translations.edit_product }}
              </h5>
              <p class="text-muted mb-0">{{ translations.edit_product_description }}</p>
            </div>
            <div class="card-body">
              <!-- Progress Steps -->
              <div class="row mb-4">
                <div class="col-12">
                  <div class="progress-steps">
                    <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                      <div class="step-number">1</div>
                      <div class="step-label">{{ translations.basic_info }}</div>
                    </div>
                    <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
                      <div class="step-number">2</div>
                      <div class="step-label">{{ translations.pricing }}</div>
                    </div>
                    <div class="step" :class="{ active: currentStep >= 3, completed: currentStep > 3 }">
                      <div class="step-number">3</div>
                      <div class="step-label">{{ translations.inventory }}</div>
                    </div>
                    <div class="step" :class="{ active: currentStep >= 4, completed: currentStep > 4 }">
                      <div class="step-number">4</div>
                      <div class="step-label">{{ translations.additional }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- General Form -->
              <form @submit.prevent="update" class="needs-validation" novalidate>
                <!-- Step 1: Basic Information -->
                <div class="form-section" v-show="currentStep >= 1">
                  <h6 class="section-title">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ translations.basic_info }}
                  </h6>
                  
                  <!-- Product Name -->
                  <div class="row mb-3">
                    <label for="inputName" class="col-sm-2 col-form-label required">{{ translations.name }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-box-seam"></i>
                        </span>
                        <input
                          id="inputName"
                          type="text"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.name }"
                          :placeholder="translations.enter_product_name"
                          v-model="form.name"
                          required
                          @input="updateCurrentStep"
                        />
                      </div>
                      <div class="form-text">{{ translations.product_name_help }}</div>
                      <InputError :message="form.errors.name" />
                    </div>
                  </div>

                  <!-- Barcode -->
                  <div class="row mb-3">
                    <label for="inputBarcode" class="col-sm-2 col-form-label">{{ translations.barcode }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-upc-scan"></i>
                        </span>
                        <input
                          id="inputBarcode"
                          type="text"
                          class="form-control"
                          :class="{ 
                            'is-invalid': barcodeValidation.checking === false && !barcodeValidation.isValid,
                            'is-valid': form.barcode && barcodeValidation.isValid
                          }"
                          :placeholder="translations.enter_barcode_or_auto_generate"
                          v-model="form.barcode"
                          @input="debouncedValidateBarcode(form.barcode)"
                        />
                        <button 
                          class="btn btn-outline-secondary" 
                          type="button"
                          @click="generateBarcode"
                          :disabled="barcodeValidation.checking"
                          :title="translations.auto_generate_barcode"
                        >
                          <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <div v-if="barcodeValidation.checking" class="input-group-text">
                          <span class="spinner-border spinner-border-sm" role="status"></span>
                        </div>
                        <div v-else-if="!barcodeValidation.isValid" class="input-group-text text-danger">
                          <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div v-else-if="form.barcode && barcodeValidation.isValid" class="input-group-text text-success">
                          <i class="bi bi-check-circle"></i>
                        </div>
                      </div>
                      <div v-if="barcodeValidation.message" class="form-text" :class="barcodeValidation.isValid ? 'text-success' : 'text-danger'">
                        {{ barcodeValidation.message }}
                      </div>
                      <div class="form-text">{{ translations.barcode_help }}</div>
                      <InputError :message="form.errors.barcode" />
                    </div>
                  </div>

                  <!-- Model -->
                  <div class="row mb-3">
                    <label for="inputModel" class="col-sm-2 col-form-label">{{ translations.model }} ({{ translations.optional }})</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-tag"></i>
                        </span>
                        <input
                          id="inputModel"
                          type="text"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.model }"
                          :placeholder="translations.enter_model_number"
                          v-model="form.model"
                        />
                      </div>
                      <div class="form-text">{{ translations.model_help }}</div>
                      <InputError :message="form.errors.model" />
                    </div>
                  </div>

                  <!-- OE Number -->
                  <div class="row mb-3">
                    <label for="inputOENumber" class="col-sm-2 col-form-label">{{ translations.oe_number }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-hash"></i>
                        </span>
                        <input
                          id="inputOENumber"
                          type="text"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.oe_number }"
                          :placeholder="translations.enter_oe_number"
                          v-model="form.oe_number"
                        />
                      </div>
                      <div class="form-text">{{ translations.oe_number_help }}</div>
                      <InputError :message="form.errors.oe_number" />
                    </div>
                  </div>

                  <!-- Category -->
                  <div class="row mb-3">
                    <label for="inputCategory" class="col-sm-2 col-form-label">التصنيف</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-tags"></i>
                        </span>
                        <select
                          id="inputCategory"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.category_id }"
                          v-model="form.category_id"
                        >
                          <option value="">اختر تصنيف (اختياري)</option>
                          <option 
                            v-for="category in categories" 
                            :key="category.id" 
                            :value="category.id"
                          >
                            {{ category.name }}
                          </option>
                        </select>
                      </div>
                      <div class="form-text">اختر تصنيف للمنتج لتسهيل البحث والفلترة</div>
                      <InputError :message="form.errors.category_id" />
                    </div>
                  </div>
                </div>

                <!-- Step 2: Pricing -->
                <div class="form-section" v-show="currentStep >= 2">
                  <h6 class="section-title">
                    <i class="bi bi-currency-dollar me-2"></i>
                    {{ translations.pricing }}
                  </h6>
                  
                  <!-- Cost Price -->
                  <div class="row mb-3">
                    <label for="inputPriceCost" class="col-sm-2 col-form-label">{{ translations.price_cost }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-cart-plus"></i>
                        </span>
                        <input
                          id="inputPriceCost"
                          type="number"
                          step="0.01"
                          min="0"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.price_cost }"
                          :placeholder="translations.enter_cost_price"
                          v-model="form.price_cost"
                          @input="calculateProfit"
                        />
                        <span class="input-group-text">{{ translations.dinar }}</span>
                      </div>
                      <div class="form-text">{{ translations.cost_price_help }}</div>
                      <InputError :message="form.errors.price_cost" />
                    </div>
                  </div>

                  <!-- Selling Price -->
                  <div class="row mb-3">
                    <label for="inputPrice" class="col-sm-2 col-form-label required">{{ translations.price }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-cash-stack"></i>
                        </span>
                        <input
                          id="inputPrice"
                          type="number"
                          step="0.01"
                          min="0"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.price }"
                          :placeholder="translations.enter_selling_price"
                          v-model="form.price"
                          required
                          @input="calculateProfit"
                        />
                        <span class="input-group-text">{{ translations.dinar }}</span>
                      </div>
                      <div class="form-text">{{ translations.selling_price_help }}</div>
                      <InputError :message="form.errors.price" />
                    </div>
                  </div>

                  <!-- Profit Display -->
                  <div class="row mb-3" v-if="profitMargin !== null">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-10">
                      <div class="alert alert-info">
                        <i class="bi bi-graph-up me-2"></i>
                        <strong>{{ translations.profit_margin }}:</strong> 
                        {{ profitMargin ? profitMargin.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).replace(/\.00$/, '') : '0' }} {{ translations.dinar }}
                        ({{ profitPercentage ? profitPercentage.toFixed(1) : '0' }}%)
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Step 3: Inventory -->
                <div class="form-section" v-show="currentStep >= 3">
                  <h6 class="section-title">
                    <i class="bi bi-boxes me-2"></i>
                    {{ translations.inventory }}
                  </h6>
                  
                  <!-- Quantity -->
                  <div class="row mb-3">
                    <label for="inputQuantity" class="col-sm-2 col-form-label required">{{ translations.quantity }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-123"></i>
                        </span>
                        <input
                          id="inputQuantity"
                          type="number"
                          min="0"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.quantity }"
                          :placeholder="translations.enter_initial_quantity"
                          v-model="form.quantity"
                          required
                        />
                        <span class="input-group-text">{{ translations.units }}</span>
                      </div>
                      <div class="form-text">{{ translations.quantity_help }}</div>
                      <InputError :message="form.errors.quantity" />
                    </div>
                  </div>
                </div>

                <!-- Step 4: Additional Information -->
                <div class="form-section" v-show="currentStep >= 4">
                  <h6 class="section-title">
                    <i class="bi bi-info-square me-2"></i>
                    {{ translations.additional }}
                  </h6>
                  
                  <!-- Notes -->
                  <div class="row mb-3">
                    <label for="inputNote" class="col-sm-2 col-form-label">{{ translations.note }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-journal-text"></i>
                        </span>
                        <textarea
                          id="inputNote"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.notes }"
                          :placeholder="translations.enter_notes_optional"
                          v-model="form.notes"
                          rows="3"
                        ></textarea>
                      </div>
                      <div class="form-text">{{ translations.notes_help }}</div>
                      <InputError :message="form.errors.notes" />
                    </div>
                  </div>

                  <!-- Date -->
                  <div class="row mb-3">
                    <label for="inputdate" class="col-sm-2 col-form-label">{{ translations.date }}</label>
                    <div class="col-sm-10">
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-calendar"></i>
                        </span>
                        <input
                          id="inputdate"
                          type="date"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.created }"
                          v-model="form.created"
                        />
                      </div>
                      <div class="form-text">{{ translations.date_help }}</div>
                      <InputError :message="form.errors.created" />
                    </div>
                  </div>

                  <!-- Image -->
                  <div class="row mb-3">
                    <label for="inputImage" class="col-sm-2 col-form-label">{{ translations.image }}</label>
                    <div class="col-sm-10">
                      <div class="image-upload-area" @click="triggerFileInput" :class="{ 'has-image': imagePreview || currentImage }">
                        <input
                          ref="fileInput"
                          id="inputImage"
                          type="file"
                          accept="image/*"
                          @input="handleImageUpload"
                          style="display: none;"
                        />
                        <div v-if="!imagePreview && !currentImage" class="upload-placeholder">
                          <i class="bi bi-cloud-upload"></i>
                          <p>{{ translations.click_to_upload_image }}</p>
                          <small class="text-muted">{{ translations.supported_formats }}</small>
                        </div>
                        <div v-else class="image-preview">
                          <img :src="imagePreview || currentImage" :alt="translations.product_image" />
                          <button type="button" class="btn btn-sm btn-danger remove-image" @click.stop="removeImage">
                            <i class="bi bi-x"></i>
                          </button>
                        </div>
                      </div>
                      <progress
                        v-if="form.progress"
                        :value="form.progress.percentage"
                        max="100"
                        class="w-100 mt-2"
                      >
                        {{ form.progress.percentage }}%
                      </progress>
                      <div class="form-text">{{ translations.image_help }}</div>
                    </div>
                  </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="row mt-4">
                  <div class="col-12">
                    <div class="d-flex justify-content-between">
                      <button
                        type="button"
                        class="btn btn-outline-secondary"
                        @click="previousStep"
                        :disabled="currentStep === 1"
                      >
                        <i class="bi bi-arrow-left me-2"></i>
                        {{ translations.previous }}
                      </button>
                      
                      <div>
                        <button
                          v-if="currentStep < 4"
                          type="button"
                          class="btn btn-primary me-2"
                          @click="nextStep"
                        >
                          {{ translations.next }}
                          <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                        
                        <button
                          type="submit"
                          class="btn btn-success"
                          :disabled="show_loader || !isFormValid"
                        >
                          <i class="bi bi-check-circle me-2" v-if="!show_loader"></i>
                          <span class="spinner-border spinner-border-sm me-2" v-if="show_loader"></span>
                          {{ translations.update }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
              <!-- End General Form -->
            </div>
          </div>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>


<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import { useToast } from 'vue-toastification';

const show_loader = ref(false);
const toast = useToast();
const currentStep = ref(1);
const imagePreview = ref(null);
const fileInput = ref(null);

// Add barcode validation
const barcodeValidation = ref({
  checking: false,
  isValid: true,
  message: ''
});

// Profit calculation
const profitMargin = ref(null);
const profitPercentage = ref(null);

const props = defineProps({
  product: Object,
  productRoles: Array,
  roles: Object,
  translations: Object,
  categories: {
    type: Array,
    default: () => []
  }
});

// Current image for display
const currentImage = computed(() => {
  if (props.product?.image && props.product.image.trim() !== '') {
    return `/public/storage/${props.product.image}`;
  }
  return '/public/dashboard-assets/img/product-placeholder.svg';
});

const form = useForm({
  name: props.product.name,
  model: props.product.model,
  notes: props.product.notes,
  oe_number: props.product.oe_number,
  price_cost: props.product.price_cost,
  quantity: props.product.quantity,
  price: props.product.price,
  created: props.product.created,
  barcode: props.product.barcode,
  selectedRoles: props.productRoles,
  image: null,
  category_id: props.product.category_id || null,
});

// Form validation
const isFormValid = computed(() => {
  return form.name && form.price && form.quantity;
});

// Step navigation
const nextStep = () => {
  if (currentStep.value < 4) {
    currentStep.value++;
  }
};

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const updateCurrentStep = () => {
  // Auto-advance to next step when required fields are filled
  if (currentStep.value === 1 && form.name) {
    currentStep.value = 2;
  }
};

// Barcode generation
const generateBarcode = () => {
  const timestamp = Date.now().toString();
  const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
  form.barcode = `BC${timestamp.slice(-6)}${random}`;
  debouncedValidateBarcode(form.barcode);
  toast.success('تم توليد باركود جديد');
};

// Profit calculation
const calculateProfit = () => {
  if (form.price_cost && form.price) {
    profitMargin.value = parseFloat(form.price) - parseFloat(form.price_cost);
    profitPercentage.value = (profitMargin.value / parseFloat(form.price_cost)) * 100;
  } else {
    profitMargin.value = null;
    profitPercentage.value = null;
  }
};

// Image handling
const triggerFileInput = () => {
  fileInput.value.click();
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.image = file;
    
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const removeImage = () => {
  form.image = null;
  imagePreview.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

// Barcode validation function
const validateBarcode = async (barcode) => {
  if (!barcode || barcode.length < 3) {
    barcodeValidation.value = {
      checking: false,
      isValid: true,
      message: ''
    };
    return;
  }

  // Skip validation if barcode hasn't changed from original
  if (barcode === props.product.barcode) {
    barcodeValidation.value = {
      checking: false,
      isValid: true,
      message: ''
    };
    return;
  }

  barcodeValidation.value.checking = true;
  
  try {
    const response = await fetch(route('products.checkBarcodeUnique', barcode));
    const data = await response.json();
    
    barcodeValidation.value = {
      checking: false,
      isValid: data.unique,
      message: data.message
    };
    
    if (!data.unique) {
      toast.warning(data.message);
    }
  } catch (error) {
    console.error('Barcode validation error:', error);
    barcodeValidation.value = {
      checking: false,
      isValid: false,
      message: 'خطأ في التحقق من الباركود'
    };
  }
};

// Debounced barcode validation
const debouncedValidateBarcode = debounce(validateBarcode, 500);

const update = () => {
  // Check barcode validation before submitting
  if (form.barcode && !barcodeValidation.value.isValid) {
    toast.error('يجب إدخال باركود صحيح ومتاح');
    return;
  }

  if (!isFormValid.value) {
    toast.error('يرجى ملء جميع الحقول المطلوبة');
    return;
  }

  show_loader.value = true;
  form.post(route('products.update', { product: props.product.id }), {
    onSuccess: () => {
      show_loader.value = false;
      toast.success('تم تحديث المنتج بنجاح');
    },
    onError: () => {
      show_loader.value = false;
      toast.error('حدث خطأ أثناء تحديث المنتج');
    },
  });
};

// Watch for form changes to update steps
watch([() => form.name, () => form.price, () => form.quantity], () => {
  updateCurrentStep();
});

// Initialize profit calculation
calculateProfit();
</script>

<style scoped>
/* Progress Steps */
.progress-steps {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
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

.step-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-align: center;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: #0d6efd;
  color: white;
}

.step.active .step-label {
  color: #0d6efd;
  font-weight: 600;
}

.step.completed .step-number {
  background: #198754;
  color: white;
}

.step.completed .step-label {
  color: #198754;
  font-weight: 600;
}

/* Form Sections */
.form-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background: #f8f9fa;
}

.section-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 1.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #dee2e6;
}

/* Required field indicator */
.required::after {
  content: ' *';
  color: #dc3545;
}

/* Input groups with icons */
.input-group-text {
  background: #f8f9fa;
  border-color: #ced4da;
}

/* Form text styling */
.form-text {
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

/* Image upload area */
.image-upload-area {
  border: 2px dashed #ced4da;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #f8f9fa;
}

.image-upload-area:hover {
  border-color: #0d6efd;
  background: #e7f1ff;
}

.image-upload-area.has-image {
  border-style: solid;
  border-color: #198754;
  background: #f0fff4;
}

.upload-placeholder i {
  font-size: 3rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.image-preview {
  position: relative;
  display: inline-block;
}

.image-preview img {
  max-width: 200px;
  max-height: 200px;
  border-radius: 0.5rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.remove-image {
  position: absolute;
  top: -10px;
  right: -10px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Alert styling */
.alert {
  border: none;
  border-radius: 0.5rem;
}

/* Button styling */
.btn {
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Responsive design */
@media (max-width: 768px) {
  .progress-steps {
    flex-direction: column;
    gap: 1rem;
  }
  
  .progress-steps::before {
    display: none;
  }
  
  .step {
    flex-direction: row;
    gap: 1rem;
  }
  
  .step-number {
    margin-bottom: 0;
  }
  
  .form-section {
    padding: 1rem;
  }
  
  .image-upload-area {
    padding: 1rem;
  }
  
  .upload-placeholder i {
    font-size: 2rem;
  }
}

/* Animation for form sections */
.form-section {
  animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Loading states */
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Validation states */
.is-invalid {
  border-color: #dc3545;
}

.is-valid {
  border-color: #198754;
}

/* Card styling */
.card {
  border: none;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  border-radius: 1rem;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 1rem 1rem 0 0 !important;
  border: none;
}

.card-header h5 {
  color: white;
}

.card-header p {
  color: rgba(255,255,255,0.8);
}
</style>