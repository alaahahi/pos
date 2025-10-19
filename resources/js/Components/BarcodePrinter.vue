<template>
  <div class="barcode-printer">
    <button 
      class="btn btn-sm btn-success me-2" 
      @click="printBarcode"
      :disabled="loading || !barcodeData"
    >
      <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
      <i class="bi bi-printer"></i> {{ translations.print }}
    </button>

    <!-- Barcode Settings Panel -->
    <div class="barcode-settings mt-2">
      <!-- Copies Input -->
      <div class="mb-3">
        <label class="form-label"><strong><i class="bi bi-files"></i> عدد النسخ</strong></label>
        <input 
          type="number" 
          class="form-control" 
          min="1" 
          max="100" 
          v-model.number="barcodeSettings.copies"
          placeholder="أدخل عدد النسخ المطلوبة"
        >
        <small class="text-muted">سيتم طباعة {{ barcodeSettings.copies }} نسخة من الباركود</small>
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
            v-model="barcodeSettings.height"
            @input="updateBarcode"
          >
          <small class="text-muted">{{ barcodeSettings.height }}px</small>
        </div>
        <div class="col-md-6">
          <label class="form-label">حجم الخط</label>
          <input 
            type="range" 
            class="form-range" 
            min="6" 
            max="20" 
            step="1" 
            v-model="barcodeSettings.fontSize"
            @input="updateBarcode"
          >
          <small class="text-muted">{{ barcodeSettings.fontSize }}px</small>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-md-4">
          <div class="form-check">
            <input 
              class="form-check-input" 
              type="checkbox" 
              id="landscapeMode"
              v-model="barcodeSettings.landscape"
              @change="updateBarcode"
            >
            <label class="form-check-label" for="landscapeMode">
              طباعة بالعرض (Landscape)
            </label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-check">
            <input 
              class="form-check-input" 
              type="checkbox" 
              id="highQuality"
              v-model="barcodeSettings.highQuality"
              @change="updateBarcode"
            >
            <label class="form-check-label" for="highQuality">
              جودة عالية
            </label>
          </div>
        </div>
        <div class="col-md-4">
          <button class="btn btn-sm btn-info" @click="showPrinterInfo">
            <i class="bi bi-info-circle"></i> معلومات الطابعة
          </button>
        </div>
      </div>
      
      <!-- Printer Info Alert -->
      <div class="alert alert-info mt-3" v-if="printerInfo">
        <h6><i class="bi bi-printer"></i> معلومات الطابعة الحرارية:</h6>
        <ul class="mb-0">
          <li><strong>الطابعة:</strong> Thermal Printer (MHT-L58G)</li>
          <li><strong>حجم الورق المحدد:</strong> {{ barcodeSettings.pageWidth }}mm × {{ barcodeSettings.pageHeight }}mm</li>
          <li><strong>الاتجاه:</strong> {{ barcodeSettings.landscape ? 'أفقي (Landscape)' : 'عمودي (Portrait)' }}</li>
          <li><strong>حجم الطباعة:</strong> {{ barcodeSettings.landscape ? `${barcodeSettings.pageWidth}mm × ${barcodeSettings.pageHeight}mm` : `${barcodeSettings.pageHeight}mm × ${barcodeSettings.pageWidth}mm` }}</li>
          <li><strong>دقة الطباعة الموصى بها:</strong> 203 DPI</li>
          <li>⚠️ تأكد من ضبط نفس الحجم في إعدادات الطابعة</li>
        </ul>
      </div>
    </div>
    
    <!-- Preview -->
    <div class="barcode-preview mt-3" v-if="previewUrl">
      <img :src="previewUrl" alt="Barcode Preview" class="img-fluid border">
    </div>
    
    <canvas ref="barcodeCanvas" style="display: none;"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import JsBarcode from 'jsbarcode'

const toast = useToast()

const props = defineProps({
  barcodeData: {
    type: String,
    required: true
  },
  productName: {
    type: String,
    default: ''
  },
  translations: {
    type: Object,
    required: true
  },
  printerSettings: {
    type: Object,
    default: () => ({
      width: 2,
      height: 30,
      type: 'PNG'
    })
  }
})

const loading = ref(false)
const barcodeCanvas = ref(null)
const previewUrl = ref('')
const printerInfo = ref(false)

// Barcode settings with reactive values
const barcodeSettings = ref({
  width: 2,
  height: 70,
  fontSize: 10,
  margin: 2,
  landscape: true,
  highQuality: true,
  pageWidth: 38,    // mm
  pageHeight: 26,   // mm
  copies: 1         // عدد النسخ
})

// Show printer information
const showPrinterInfo = () => {
  printerInfo.value = !printerInfo.value
}

const printBarcode = async () => {
  if (!props.barcodeData) {
    toast.error('لا يوجد بيانات باركود للطباعة')
    return
  }

  loading.value = true

  try {
    const barcodeImageUrl = generateBarcodeWithJsBarcode()
    await printBarcodeDirectly(barcodeImageUrl)
  } catch (error) {
    console.error('Print error:', error)
    toast.error('حدث خطأ أثناء الطباعة')
  } finally {
    loading.value = false
  }
}

const generateBarcodeWithJsBarcode = () => {
  try {
    // Use SVG instead of Canvas for perfect quality
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg")
    
    JsBarcode(svg, props.barcodeData, {
      format: "CODE128",
      width: barcodeSettings.value.width,
      height: barcodeSettings.value.height,
      displayValue: false,
      margin: barcodeSettings.value.margin,
      background: "#ffffff",
      lineColor: "#000000",
      xmlDocument: document
    })
    
    // Convert SVG to data URL
    const svgData = new XMLSerializer().serializeToString(svg)
    const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' })
    const svgUrl = URL.createObjectURL(svgBlob)
    
    // For compatibility, also return as data URL
    return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)))
  } catch (error) {
    console.error('JsBarcode generation error:', error)
    throw error
  }
}

// Update barcode preview when settings change
const updateBarcode = () => {
  if (props.barcodeData) {
    try {
      previewUrl.value = generateBarcodeWithJsBarcode()
    } catch (error) {
      console.error('Preview update error:', error)
    }
  }
}

const printBarcodeDirectly = async (barcodeImageUrl) => {
  try {
    const printWindow = window.open('', '_blank', 'width=300,height=400')
    
    const productName = props.productName || 'Product'
    const barcodeData = props.barcodeData
    const htmlContent = createPrintHTML(barcodeImageUrl, productName, barcodeData)
    
    printWindow.document.write(htmlContent)
    printWindow.document.close()
    toast.success('تم إرسال الباركود للطباعة')

  } catch (error) {
    console.error('Direct print error:', error)
    throw error
  }
}

const createPrintHTML = (barcodeImageUrl, productName = 'Product', barcodeData = '') => {
  const orientation = barcodeSettings.value.landscape ? 'landscape' : 'portrait'
  const pageWidth = `${barcodeSettings.value.pageWidth}mm`
  const pageHeight = `${barcodeSettings.value.pageHeight}mm`
  const pageSize = barcodeSettings.value.landscape ? `${pageWidth} ${pageHeight}` : `${pageHeight} ${pageWidth}`
  const maxBarcodeHeight = barcodeSettings.value.landscape ? '18mm' : '22mm'
  
  // Generate HTML for multiple copies
  let labelsHtml = ''
  for (let i = 0; i < barcodeSettings.value.copies; i++) {
    labelsHtml += `
  <div class="label-page">
    <div class="label-container">
      <div class="product-name">${productName}</div>
      <img class="barcode-image" src="${barcodeImageUrl}" alt="Barcode">
      <div class="barcode-text">${barcodeData}</div>
    </div>
  </div>
    `
  }
  
  return `<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركود</title>
  <style>
    /* Optimized for thermal printers like MHT-L58G */
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
      font-size: ${barcodeSettings.value.fontSize}px;
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
      font-size: ${Math.max(barcodeSettings.value.fontSize - 2, 4)}px;
      font-family: monospace;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
    }
  </style>
</head>
<body>
  ${labelsHtml}
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
</html>`;
};

onMounted(() => {
  // Initialize barcode preview
  if (props.barcodeData) {
    updateBarcode()
  }
})
</script>

<style scoped>
.barcode-printer {
  display: inline-block;
}

.btn {
  transition: all 0.3s ease;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.barcode-settings {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

.barcode-settings .form-label {
  font-weight: bold;
  color: #495057;
  margin-bottom: 5px;
}

.barcode-settings .form-range {
  margin-bottom: 5px;
}

.barcode-preview {
  text-align: center;
  padding: 10px;
  background: white;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

.barcode-preview img {
  max-width: 100%;
  height: auto;
}
</style>
