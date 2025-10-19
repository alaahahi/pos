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
      <div class="row">
        <div class="col-md-3">
          <label class="form-label">عرض الخط</label>
          <input 
            type="range" 
            class="form-range" 
            min="1" 
            max="5" 
            step="0.1" 
            v-model="barcodeSettings.width"
            @input="updateBarcode"
          >
          <small class="text-muted">{{ barcodeSettings.width }}</small>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
          <label class="form-label">الهوامش</label>
          <input 
            type="range" 
            class="form-range" 
            min="0" 
            max="10" 
            step="1" 
            v-model="barcodeSettings.margin"
            @input="updateBarcode"
          >
          <small class="text-muted">{{ barcodeSettings.margin }}px</small>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-md-6">
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
        <div class="col-md-6">
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

// Barcode settings with reactive values
const barcodeSettings = ref({
  width: 2,
  height: 80,
  fontSize: 10,
  margin: 2,
  landscape: true,
  highQuality: true
})

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
    const canvas = document.createElement('canvas')
    
    // Set high resolution canvas for better quality
    const scale = barcodeSettings.value.highQuality ? 3 : 2
    canvas.width = 400 * scale
    canvas.height = 200 * scale
    
    // Scale the context for high DPI
    const ctx = canvas.getContext('2d')
    ctx.scale(scale, scale)
    
    JsBarcode(canvas, props.barcodeData, {
      format: "CODE128",
      width: barcodeSettings.value.width,
      height: barcodeSettings.value.height,
      displayValue: false,
      margin: barcodeSettings.value.margin,
      background: "#ffffff",
      lineColor: "#000000"
    })
    
    return canvas.toDataURL('image/png')
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
  const pageSize = barcodeSettings.value.landscape ? '38mm 28mm' : '28mm 35mm'
  
  return `<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركود</title>
  <style>
    @page { 
      size: ${pageSize}; 
      margin: 0; 
      orientation: ${orientation};
    }
    body {
      margin: 0;
      padding: 0;
      width: ${barcodeSettings.value.landscape ? '38mm' : '28mm'};
      height: ${barcodeSettings.value.landscape ? '28mm' : '35mm'};
      font-family: Arial, sans-serif;
      overflow: hidden;
    }
    .label-container {
      width: ${barcodeSettings.value.landscape ? '38mm' : '28mm'};
      height: ${barcodeSettings.value.landscape ? '28mm' : '35mm'};
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 1mm;
      box-sizing: border-box;
    }
    .product-name {
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
      width: 100%;
      height: auto;
      max-height: ${barcodeSettings.value.landscape ? '20mm' : '25mm'};
      margin: 1mm 0;
      object-fit: contain;
    }
    .barcode-text {
      font-size: ${Math.max(barcodeSettings.value.fontSize - 2, 4)}px;
      font-family: monospace;
      text-align: center;
      margin-top: 1mm;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="label-container">
    <div class="product-name">${productName}</div>
    <img class="barcode-image" src="${barcodeImageUrl}" alt="Barcode">
    <div class="barcode-text">${barcodeData}</div>
  </div>
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
