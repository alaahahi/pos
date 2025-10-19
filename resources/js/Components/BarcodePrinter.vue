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
    
    JsBarcode(canvas, props.barcodeData, {
      format: "CODE128",
      width: 1.5,
      height: 60,
      displayValue: true,
      fontSize: 8,
      textMargin: 2,
      margin: 5,
      background: "#ffffff",
      lineColor: "#000000"
    })
    
    return canvas.toDataURL('image/png')
  } catch (error) {
    console.error('JsBarcode generation error:', error)
    throw error
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
  return `<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طباعة الباركود</title>
  <style>
    @page { size: 28mm 35mm; margin: 0; }
    body {
      margin: 0;
      padding: 0;
    width: 38mm;
        height: 28mm;
      font-family: Arial, sans-serif;
      overflow: hidden;
    }
    .label-container {
      width: 28mm;
      height: 35mm;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 1mm;
      box-sizing: border-box;
    }
    .product-name {
      font-size: 6px;
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
      max-height: 20mm;
      margin: 1mm 0;
      object-fit: contain;
    }
    .barcode-text {
      font-size: 5px;
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
  // Initialize any required setup
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
</style>
