<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.barcode_generation }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">{{ translations.barcode_generation }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="row">
        <!-- Search and Filters -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <form @submit.prevent="searchProducts">
                <div class="row">
                  <div class="col-md-4">
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="searchForm.search" 
                      :placeholder="translations.search"
                    />
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                      {{ translations.search }} <i class="bi bi-search"></i>
                    </button>
                  </div>
                  <div class="col-md-3">
                    <button 
                      type="button" 
                      class="btn btn-success" 
                      @click="showBatchPrintModal = true"
                      :disabled="selectedProducts.length === 0"
                    >
                      {{ translations.batch_print }} ({{ selectedProducts.length }})
                    </button>
                  </div>
                  <div class="col-md-3">
                    <button 
                      type="button" 
                      class="btn btn-info" 
                      @click="showPrinterSettings = true"
                    >
                      {{ translations.printer_settings }}
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Products List -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table text-center">
                  <thead>
                    <tr>
                      <th>
                        <input 
                          type="checkbox" 
                          @change="toggleAllProducts"
                          :checked="selectedProducts.length === products.data.length && products.data.length > 0"
                        />
                      </th>
                      <th>{{ translations.name }}</th>
                      <th>{{ translations.model }}</th>
                      <th>{{ translations.barcode }}</th>
                      <th>{{ translations.quantity }}</th>
                      <th>{{ translations.price }}</th>
                      <th>{{ translations.actions }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="product in products.data" :key="product.id">
                      <td>
                        <input 
                          type="checkbox" 
                          :value="product.id"
                          v-model="selectedProducts"
                        />
                      </td>
                      <td>{{ product.name }}</td>
                      <td>{{ product.model }}</td>
                      <td>
                        <span v-if="product.barcode" class="badge bg-success">{{ product.barcode }}</span>
                        <span v-else class="badge bg-warning">{{ translations.no_barcode }}</span>
                      </td>
                      <td>{{ product.quantity }}</td>
                      <td>{{ product.price }} {{ translations.dinar }}</td>
                      <td>
                        <div class="btn-group" role="group">
                          <!-- Generate button - only show if no barcode -->
                          <button 
                            v-if="!product.barcode"
                            class="btn btn-sm btn-primary" 
                            @click="generateBarcode(product)"
                            :disabled="loading"
                          >
                            <i class="bi bi-qr-code"></i> {{ translations.generate }}
                          </button>
                          
                          <!-- Print button - only show if has barcode -->
                          <BarcodePrinter 
                            v-if="product.barcode"
                            :barcode-data="product.barcode"
                            :product-name="product.name"
                            :translations="translations"
                            :printer-settings="printSettings"
                          />
                          
                          <!-- Preview button - only show if has barcode -->
                          <button 
                            v-if="product.barcode"
                            class="btn btn-sm btn-info" 
                            @click="previewBarcode(product)"
                          >
                            <i class="bi bi-eye"></i> {{ translations.preview }}
                          </button>
                          
                          <!-- Download button - only show if has barcode -->
                          <button 
                            v-if="product.barcode"
                            class="btn btn-sm btn-secondary" 
                            @click="downloadBarcode(product)"
                          >
                            <i class="bi bi-download"></i> {{ translations.download }}
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="d-flex justify-content-center mt-3">
                <nav>
                  <ul class="pagination">
                    <li class="page-item" :class="{ disabled: !products.prev_page_url }">
                      <Link class="page-link" :href="products.prev_page_url" v-if="products.prev_page_url">
                        {{ translations.previous }}
                      </Link>
                      <span class="page-link" v-else>{{ translations.previous }}</span>
                    </li>
                    <li class="page-item" :class="{ disabled: !products.next_page_url }">
                      <Link class="page-link" :href="products.next_page_url" v-if="products.next_page_url">
                        {{ translations.next }}
                      </Link>
                      <span class="page-link" v-else>{{ translations.next }}</span>
                    </li>
                  </ul>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Generate Barcode Modal -->
      <div class="modal fade" :class="{ show: showGenerateModal }" :style="{ display: showGenerateModal ? 'block' : 'none' }">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.generate_barcode }}</h5>
              <button type="button" class="btn-close" @click="showGenerateModal = false"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="confirmGenerateBarcode">
                <div class="mb-3">
                  <label class="form-label">{{ translations.product_name }}</label>
                  <input type="text" class="form-control" :value="selectedProduct?.name" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.barcode_type }}</label>
                  <select class="form-select" v-model="barcodeSettings.type">
                    <option value="PNG">PNG</option>
                    <option value="SVG">SVG</option>
                    <option value="JPG">JPG</option>
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.width }}</label>
                      <input type="number" class="form-control" v-model="barcodeSettings.width" min="1" max="10">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.height }}</label>
                      <input type="number" class="form-control" v-model="barcodeSettings.height" min="10" max="200">
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showGenerateModal = false">
                {{ translations.cancel }}
              </button>
              <button type="button" class="btn btn-primary" @click="confirmGenerateBarcode" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ translations.generate }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Print Barcode Modal -->
      <div class="modal fade" :class="{ show: showPrintModal }" :style="{ display: showPrintModal ? 'block' : 'none' }">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.print_barcode }}</h5>
              <button type="button" class="btn-close" @click="showPrintModal = false"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="confirmPrintBarcode">
                <div class="mb-3">
                  <label class="form-label">{{ translations.product_name }}</label>
                  <input type="text" class="form-control" :value="selectedProduct?.name" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.barcode }}</label>
                  <input type="text" class="form-control" :value="selectedProduct?.barcode" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.print_quantity }}</label>
                  <input type="number" class="form-control" v-model="printQuantity" min="1" max="100" value="1">
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.barcode_type }}</label>
                  <select class="form-select" v-model="printSettings.type">
                    <option value="PNG">PNG</option>
                    <option value="SVG">SVG</option>
                    <option value="JPG">JPG</option>
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.width }}</label>
                      <input type="number" class="form-control" v-model="printSettings.width" min="1" max="10">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.height }}</label>
                      <input type="number" class="form-control" v-model="printSettings.height" min="10" max="200">
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showPrintModal = false">
                {{ translations.cancel }}
              </button>
              <BarcodePrinter 
                v-if="selectedProduct?.barcode"
                :barcode-data="selectedProduct.barcode"
                :product-name="selectedProduct.name"
                :translations="translations"
                :printer-settings="printSettings"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Batch Print Modal -->
      <div class="modal fade" :class="{ show: showBatchPrintModal }" :style="{ display: showBatchPrintModal ? 'block' : 'none' }">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.batch_print }}</h5>
              <button type="button" class="btn-close" @click="showBatchPrintModal = false"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">{{ translations.selected_products }} ({{ selectedProducts.length }})</label>
                <div class="border p-2" style="max-height: 200px; overflow-y: auto;">
                  <div v-for="productId in selectedProducts" :key="productId" class="mb-1">
                    {{ getProductName(productId) }}
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">{{ translations.quantity_per_product }}</label>
                <input type="number" class="form-control" v-model="batchPrintQuantity" min="1" max="10" value="1">
              </div>
              
              <!-- Barcode Settings Panel - Same as BarcodePrinter -->
              <div class="barcode-settings mt-3 p-3" style="background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                <h6 class="mb-3"><i class="bi bi-sliders"></i> إعدادات الباركود</h6>
                
                <!-- Paper Size Settings -->
                <div class="row mb-2">
                  <div class="col-md-6">
                    <label class="form-label"><strong>📏 عرض الورق (mm)</strong></label>
                    <input 
                      type="number" 
                      class="form-control form-control-sm" 
                      min="20" 
                      max="80" 
                      step="1" 
                      v-model.number="batchPrintSettings.pageWidth"
                    >
                  </div>
                  <div class="col-md-6">
                    <label class="form-label"><strong>📏 ارتفاع الورق (mm)</strong></label>
                    <input 
                      type="number" 
                      class="form-control form-control-sm" 
                      min="20" 
                      max="80" 
                      step="1" 
                      v-model.number="batchPrintSettings.pageHeight"
                    >
                  </div>
                </div>
                
                <!-- Quick Size Buttons -->
              <div class="mb-3">
                  <small class="text-muted">أحجام شائعة:</small>
                  <div class="btn-group btn-group-sm mt-1" role="group">
                    <button type="button" class="btn btn-outline-primary" @click="batchPrintSettings.pageWidth = 38; batchPrintSettings.pageHeight = 26">
                      <strong>38×26</strong>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="batchPrintSettings.pageWidth = 35; batchPrintSettings.pageHeight = 28">
                      35×28
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="batchPrintSettings.pageWidth = 38; batchPrintSettings.pageHeight = 28">
                      38×28
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="batchPrintSettings.pageWidth = 40; batchPrintSettings.pageHeight = 30">
                      40×30
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="batchPrintSettings.pageWidth = 50; batchPrintSettings.pageHeight = 30">
                      50×30
                    </button>
              </div>
                </div>
                
                <hr>
                
              <div class="row">
                <div class="col-md-6">
                    <label class="form-label">ارتفاع الباركود</label>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="30" 
                      max="150" 
                      step="5" 
                      v-model="batchPrintSettings.height"
                    >
                    <small class="text-muted">{{ batchPrintSettings.height }}px</small>
                  </div>
                <div class="col-md-6">
                    <label class="form-label">حجم الخط</label>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="6" 
                      max="20" 
                      step="1" 
                      v-model="batchPrintSettings.fontSize"
                    >
                    <small class="text-muted">{{ batchPrintSettings.fontSize }}px</small>
                </div>
                </div>
                
                <div class="row mt-3">
                  <div class="col-md-4">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="batchLandscapeMode"
                        v-model="batchPrintSettings.landscape"
                      >
                      <label class="form-check-label" for="batchLandscapeMode">
                        طباعة بالعرض (Landscape)
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="batchHighQuality"
                        v-model="batchPrintSettings.highQuality"
                      >
                      <label class="form-check-label" for="batchHighQuality">
                        جودة عالية (SVG)
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="showBarcodeNumber"
                        v-model="batchPrintSettings.showBarcodeNumber"
                      >
                      <label class="form-check-label" for="showBarcodeNumber">
                        عرض رقم الباركود
                      </label>
                    </div>
                  </div>
                </div>
                
                <!-- Price Settings -->
                <div class="row mt-3" v-if="batchPrintSettings.showPrice">
                <div class="col-md-6">
                    <label class="form-label"><strong><i class="bi bi-currency-dollar"></i> سعر البيع</strong></label>
                    <input 
                      type="number" 
                      class="form-control" 
                      min="0" 
                      step="0.01" 
                      v-model.number="batchPrintSettings.price"
                      placeholder="أدخل سعر البيع"
                    >
                  </div>
                  <div class="col-md-6">
                    <div class="form-check mt-4">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="showPrice"
                        v-model="batchPrintSettings.showPrice"
                      >
                      <label class="form-check-label" for="showPrice">
                        عرض سعر البيع
                      </label>
                    </div>
                  </div>
                </div>
                
                <!-- Show Price Checkbox (when price is not shown) -->
                <div class="row mt-2" v-if="!batchPrintSettings.showPrice">
                  <div class="col-md-6">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="showPrice"
                        v-model="batchPrintSettings.showPrice"
                      >
                      <label class="form-check-label" for="showPrice">
                        عرض سعر البيع
                      </label>
                    </div>
                  </div>
                </div>
                
                <div class="alert alert-info mt-3 mb-0" style="font-size: 0.9em;">
                  <strong><i class="bi bi-info-circle"></i> للطابعة الحرارية MHT-L58G:</strong>
                  <ul class="mb-0 mt-2" style="font-size: 0.85em;">
                    <li><strong>حجم الورق المحدد:</strong> {{ batchPrintSettings.pageWidth }}mm × {{ batchPrintSettings.pageHeight }}mm</li>
                    <li><strong>اتجاه:</strong> {{ batchPrintSettings.landscape ? 'أفقي (Landscape)' : 'عمودي (Portrait)' }}</li>
                    <li><strong>حجم الطباعة:</strong> {{ batchPrintSettings.landscape ? `${batchPrintSettings.pageWidth}mm × ${batchPrintSettings.pageHeight}mm` : `${batchPrintSettings.pageHeight}mm × ${batchPrintSettings.pageWidth}mm` }}</li>
                    <li>⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة</li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showBatchPrintModal = false">
                {{ translations.cancel }}
              </button>
              <button type="button" class="btn btn-success" @click="confirmBatchPrint" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-printer"></i> {{ translations.print_all }} ({{ selectedProducts.length * batchPrintQuantity }})
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Printer Settings Modal -->
      <div class="modal fade" :class="{ show: showPrinterSettings }" :style="{ display: showPrinterSettings ? 'block' : 'none' }">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.printer_settings }}</h5>
              <button type="button" class="btn-close" @click="showPrinterSettings = false"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                {{ translations.printer_settings_info }}
              </div>
              <div class="mb-3">
                <label class="form-label">{{ translations.default_barcode_type }}</label>
                <select class="form-select" v-model="printerSettings.type">
                  <option value="PNG">PNG</option>
                  <option value="SVG">SVG</option>
                  <option value="JPG">JPG</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.default_width }}</label>
                    <input type="number" class="form-control" v-model="printerSettings.width" min="1" max="10">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.default_height }}</label>
                    <input type="number" class="form-control" v-model="printerSettings.height" min="10" max="200">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showPrinterSettings = false">
                {{ translations.cancel }}
              </button>
              <button type="button" class="btn btn-primary" @click="savePrinterSettings">
                {{ translations.save }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Barcode Preview Modal -->
      <div class="modal fade" :class="{ show: showPreviewModal }" :style="{ display: showPreviewModal ? 'block' : 'none' }">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.barcode_preview }}</h5>
              <button type="button" class="btn-close" @click="showPreviewModal = false"></button>
            </div>
            <div class="modal-body text-center">
              <div v-if="previewLoading" class="text-center">
                <div class="spinner-border" role="status">
                  <span class="visually-hidden">{{ translations.loading }}</span>
                </div>
              </div>
              <div v-else-if="previewImage" class="border p-4" style="max-width: 400px; margin: 0 auto;">
                <!-- Product Name -->
                <div class="mb-3">
                  <h4 class="fw-bold text-center">{{ selectedProduct?.name || 'Product' }}</h4>
                </div>
                
                <!-- Barcode Image -->
                <div class="mb-3">
                  <img :src="previewImage" :alt="translations.barcode_preview" class="img-fluid" style="width: 100%; padding: 10px; background: white;">
                </div>
                
                <!-- Barcode Number -->
                <div class="mb-3">
                  <div class="text-muted small">{{ translations.barcode }}:</div>
                  <div class="fw-bold font-monospace fs-5">{{ selectedProduct?.barcode }}</div>
                </div>
                
                <!-- Print Preview Note -->
                <div class="alert alert-info small">
                  <i class="bi bi-info-circle"></i> هذه معاينة لما سيتم طباعته
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showPreviewModal = false">
                {{ translations.close }}
              </button>
              <BarcodePrinter 
                v-if="selectedProduct?.barcode"
                :barcode-data="selectedProduct.barcode"
                :product-name="selectedProduct.name"
                :translations="translations"
                :printer-settings="printSettings"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BarcodePrinter from '@/Components/BarcodePrinter.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const props = defineProps({
  products: Object,
  translations: Object,
  filters: Object
})

// Reactive data
const loading = ref(false)
const previewLoading = ref(false)
const searchForm = reactive({
  search: props.filters?.search || ''
})

const selectedProducts = ref([])
const selectedProduct = ref(null)

// Modal states
const showGenerateModal = ref(false)
const showPrintModal = ref(false)
const showBatchPrintModal = ref(false)
const showPrinterSettings = ref(false)
const showPreviewModal = ref(false)

// Settings
const barcodeSettings = reactive({
  type: 'PNG',
  width: 2,
  height: 30
})

const printSettings = reactive({
  type: 'PNG',
  width: 2,
  height: 30
})

const batchPrintSettings = reactive({
  type: 'SVG',
  width: 2,
  height: 70,
  fontSize: 10,
  margin: 2,
  landscape: true,
  highQuality: true,
  pageWidth: 38,    // mm
  pageHeight: 26,   // mm
  copies: 1,        // عدد النسخ
  showBarcodeNumber: true,  // عرض رقم الباركود تحت الباركود
  showPrice: false,         // عرض سعر البيع
  price: 0                  // سعر البيع
})

const printerSettings = reactive({
  type: 'PNG',
  width: 2,
  height: 30
})

const printQuantity = ref(1)
const batchPrintQuantity = ref(1)
const previewImage = ref('')

// Methods
const searchProducts = () => {
  router.get(route('barcode.index'), searchForm, {
    preserveState: true,
    replace: true
  })
}

const toggleAllProducts = () => {
  if (selectedProducts.value.length === props.products.data.length) {
    selectedProducts.value = []
  } else {
    selectedProducts.value = props.products.data.map(product => product.id)
  }
}

const getProductName = (productId) => {
  const product = props.products.data.find(p => p.id === productId)
  return product ? product.name : ''
}

const generateBarcode = async (product) => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.generate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_id: product.id,
        type: 'PNG',
        width: 2,
        height: 30
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success('تم توليد الباركود بنجاح')
      // Refresh the page to show updated barcode
      window.location.reload()
    } else {
      toast.error(data.message || 'حدث خطأ أثناء توليد الباركود')
    }
  } catch (error) {
    console.error('Generate barcode error:', error)
    toast.error('حدث خطأ أثناء توليد الباركود')
  } finally {
    loading.value = false
  }
}

const confirmGenerateBarcode = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.generate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_id: selectedProduct.value.id,
        type: barcodeSettings.type,
        width: barcodeSettings.width,
        height: barcodeSettings.height
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      showGenerateModal.value = false
      // Refresh the page to show updated barcode
      router.reload()
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء توليد الباركود')
  } finally {
    loading.value = false
  }
}

const printBarcode = (product) => {
  selectedProduct.value = product
  showPrintModal.value = true
}

const confirmPrintBarcode = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.print'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_id: selectedProduct.value.id,
        quantity: printQuantity.value,
        printer_settings: {
          type: printSettings.type,
          width: printSettings.width,
          height: printSettings.height
        }
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      showPrintModal.value = false
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء الطباعة')
  } finally {
    loading.value = false
  }
}

const confirmBatchPrint = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.batch.print'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_ids: selectedProducts.value,
        quantity_per_product: batchPrintQuantity.value,
        printer_settings: {
          type: batchPrintSettings.type,
          width: batchPrintSettings.width,
          height: batchPrintSettings.height
        }
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      
      // Print all barcodes
      await printBatchBarcodes(data.results)
      
      showBatchPrintModal.value = false
      selectedProducts.value = []
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء الطباعة المجمعة')
  } finally {
    loading.value = false
  }
}

const printBatchBarcodes = async (results) => {
  try {
    // Import JsBarcode dynamically if needed
    const JsBarcode = (await import('jsbarcode')).default
    
    // Generate SVG barcodes for all products
    const barcodesWithSVG = []
    for (const result of results) {
      if (result.success && result.product) {
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg")
        
        JsBarcode(svg, result.product.barcode, {
          format: "CODE128",
          width: batchPrintSettings.width,
          height: batchPrintSettings.height,
          displayValue: false,
          margin: batchPrintSettings.margin,
          background: "#ffffff",
          lineColor: "#000000",
          xmlDocument: document
        })
        
        const svgData = new XMLSerializer().serializeToString(svg)
        const barcodeImageUrl = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)))
        
        barcodesWithSVG.push({
          ...result,
          svgUrl: barcodeImageUrl
        })
      }
    }
    
    // Create print window
    const printWindow = window.open('', '_blank', 'width=400,height=600')
    
    if (!printWindow) {
      toast.error('فشل فتح نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة.')
      return
    }
    
    let barcodeHtml = ''
    
    for (const result of barcodesWithSVG) {
        barcodeHtml += `
        <div class="label-page">
          <div class="label-container">
            <div class="product-name">${result.product.name}</div>
            <img class="barcode-image" src="${result.svgUrl}" alt="Barcode">
            ${batchPrintSettings.showBarcodeNumber ? `<div class="barcode-text">${result.product.barcode}</div>` : ''}
            ${batchPrintSettings.showPrice && batchPrintSettings.price > 0 ? `<div class="price-text">${batchPrintSettings.price} دينار</div>` : ''}
          </div>
          </div>
        `
    }

    const orientation = batchPrintSettings.landscape ? 'landscape' : 'portrait'
    const pageWidth = `${batchPrintSettings.pageWidth}mm`
    const pageHeight = `${batchPrintSettings.pageHeight}mm`
    const pageSize = batchPrintSettings.landscape ? `${pageWidth} ${pageHeight}` : `${pageHeight} ${pageWidth}`
    const maxBarcodeHeight = batchPrintSettings.landscape ? '18mm' : '22mm'
    
    const htmlContent = `
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركودات</title>
      <style>
    /* Optimized for thermal printers - Batch Print with Dynamic Settings */
    @page { 
      size: ${pageSize}; 
      margin: 0; 
      orientation: ${orientation};
    }
    
        @media print {
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
      body {
        margin: 0;
        padding: 0;
      }
      .label-page {
        width: ${pageWidth};
        height: ${pageHeight};
        page-break-after: always;
      }
    }
    
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }
    
    .label-page {
      width: ${pageWidth};
      height: ${pageHeight};
      margin: 0;
      padding: 0;
      overflow: hidden;
      page-break-after: always;
    }
    
    .label-container {
      width: ${pageWidth};
      height: ${pageHeight};
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 1mm;
      box-sizing: border-box;
    }
    
    .product-name {
      width: 100%;
      font-size: ${batchPrintSettings.fontSize}px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 1mm;
      line-height: 1.1;
      max-height: 8mm;
      overflow: hidden;
      word-wrap: break-word;
    }
    
    .barcode-image {
      width: auto;
      max-width: 90%;
      height: auto;
      max-height: ${maxBarcodeHeight};
      margin: 1mm auto;
      display: block;
      object-fit: contain;
    }
    
    .barcode-text {
      width: 100%;
      font-size: ${Math.max(batchPrintSettings.fontSize - 2, 4)}px;
      font-family: monospace;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
    }
    
    .price-text {
      width: 100%;
      font-size: ${Math.max(batchPrintSettings.fontSize - 1, 5)}px;
      font-family: Arial, sans-serif;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
      color: #dc3545;
    }
    
    @media screen {
      body {
        padding: 20px;
        background: #f5f5f5;
      }
      .label-page {
        border: 1px solid #ccc;
        margin: 10px;
        background: white;
        display: inline-block;
      }
    }
  </style>
</head>
<body>
  ${barcodeHtml}
  
  <scr` + `ipt>
    window.onload = function() {
      setTimeout(function() {
        window.print();
        setTimeout(function() {
          window.close();
        }, 2000);
      }, 1500);
    }
  </scr` + `ipt>
</body>
</html>
    `
    
    printWindow.document.write(htmlContent)
    printWindow.document.close()

  } catch (error) {
    console.error('Batch print error:', error)
    toast.error('حدث خطأ أثناء طباعة الباركودات')
  }
}

const previewBarcode = async (product) => {
  selectedProduct.value = product
  previewLoading.value = true
  showPreviewModal.value = true
  
  try {
    const params = new URLSearchParams({
      code: product.barcode,
      type: printerSettings.type,
      width: printerSettings.width,
      height: printerSettings.height
    })
    
    const response = await fetch(`${route('barcode.preview')}?${params}`)
    
    if (response.ok) {
      const blob = await response.blob()
      previewImage.value = URL.createObjectURL(blob)
    } else {
      toast.error('فشل في تحميل معاينة الباركود')
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء تحميل المعاينة')
  } finally {
    previewLoading.value = false
  }
}

const printFromPreview = () => {
  showPreviewModal.value = false
  printBarcode(selectedProduct.value)
}

const downloadBarcode = (product) => {
  const params = new URLSearchParams({
    type: printerSettings.type,
    width: printerSettings.width,
    height: printerSettings.height
  })
  
  window.open(`${route('barcode.download', product.id)}?${params}`, '_blank')
}

const savePrinterSettings = () => {
  // Save settings to localStorage
  localStorage.setItem('printerSettings', JSON.stringify(printerSettings))
  toast.success('تم حفظ إعدادات الطابعة')
  showPrinterSettings.value = false
}

// Load printer settings on mount
onMounted(() => {
  const savedSettings = localStorage.getItem('printerSettings')
  if (savedSettings) {
    const settings = JSON.parse(savedSettings)
    Object.assign(printerSettings, settings)
    Object.assign(barcodeSettings, settings)
    Object.assign(printSettings, settings)
    Object.assign(batchPrintSettings, settings)
  }
})
</script>

<style scoped>
.modal.show {
  background-color: rgba(0, 0, 0, 0.5);
}

.btn-group .btn {
  margin-right: 2px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

.table th, .table td {
  vertical-align: middle;
}

.badge {
  font-size: 0.8em;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>
