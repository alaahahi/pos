<template>
  <AuthenticatedLayout :translations="translations">

    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white"> {{ translations.invoice }} </h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white"> {{ translations.invoice }} </li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"> {{ translations.create_invoice }} </h5>

              <!-- Invoice Form  -->
              <form class="row g-3" @submit.prevent="openConfirmModal">

                <!-- Customer -->
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label"> {{ translations.client }} </label>
                  <div class="col-sm-10">
                    <vue-select
                      v-model="selectedCustomer"
                      :options="customers"
                      label="name"
                      track-by="id"
                      :reduce="customer => customer"
                      @input="selectCustomer"
                      :placeholder="translations.select_customer"
                    />
                    <InputError :message="form.errors.customer_id" />
                  </div>
                </div>

                <!-- Barcode -->
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ translations.barcode }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputBarcode"
                      type="text"
                      class="form-control"
                      :placeholder="translations.barcode"
                      v-model="barcode"
                      @keyup="findBarcode"
                    />
                  </div>
                </div>

                <!-- Products -->
                <div class="row mb-3" v-if="selectedCustomer">
                  <label class="col-sm-2 col-form-label"> {{ translations.products }} </label>
                  <div class="col-sm-10">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>{{ translations.product }}</th>
                          <th>{{ translations.quantity }}</th>
                          <th>{{ translations.price }} {{ translations.dollar }}</th>
                          <th>{{ translations.total }} {{ translations.dollar }}</th>
                          <th>{{ translations.actions }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in invoiceItems" :key="index">
                          <td style="min-width: 200px;">
                            <vue-select
                              v-model="item.product_id"
                              :options="products"
                              label="name"
                              :reduce="product => product.id"
                              :placeholder="translations.select_product"
                              @mouseleave="updatePrice(item)"
                            />
                          </td>

                          <td>
                            <!-- تحقق عند تغيير الكمية -->
                            <input 
                              type="number" 
                              v-model.number="item.quantity" 
                              min="1" 
                              class="form-control" 
                              @input="check_stock(item)" 
                            />
                          </td>

                          <td>
                            <input 
                              type="number" 
                              v-model.number="item.price" 
                              min="0" 
                              class="form-control" 
                            />
                          </td>

                          <td>
                            <input 
                              type="text" 
                              :value="defaultCurrency + ' ' + (item.quantity * item.price)" 
                              class="form-control" 
                              disabled 
                            />
                          </td>

                          <td>
                            <button 
                              type="button" 
                              class="btn btn-danger" 
                              @click="removeItem(index)"
                            >
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <button type="button" class="btn btn-primary" @click="addProduct" v-if="products?.length">
                      {{ translations.add_product }}
                    </button>
                  </div>
                </div>
                 <!-- Total -->
                <div class="row">
                  <div class="col-md-6">
                    <strong>{{ translations.total }}  {{ translations.dollar }}:</strong>
                  </div>
                  <div class="col-md-6">
                    <input type="text" :value=" defaultCurrency + ' ' + totalAmount" class="form-control" readonly />
                  </div>
                </div>
                <!-- Submit -->
                <div class="text-center mt-3" v-if="invoiceItems.length>0">
                  <button type="submit" class="btn btn-info w-75 text-center text-white" :disabled="show_loader">
                    {{ translations.continue }} &nbsp; 
                    <i class="bi bi-save" v-if="!show_loader"></i>
                    <span class="spinner-border spinner-border-sm" v-if="show_loader"></span>
                  </button>
                </div>
              </form>
              <!-- End Invoice Form -->
            </div>
          </div>
        </div>
      </div>

      <!-- Confirm Modal -->
      <ModalConfirmOrderAndPay 
        :translations="translations" 
        :show="ShowModalConfirmOrderAndPay" 
        :total="totalAmount" 
        @close="ShowModalConfirmOrderAndPay = false" 
        @confirm="saveInvoice"
      />
    </section>

  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { ref, reactive, computed, watch } from 'vue';
import VueSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import axios from "axios";
import ModalConfirmOrderAndPay from '@/Components/ModalConfirmOrderAndPay.vue';
import debounce from 'lodash/debounce';
import { useToast } from "vue-toastification";
let toast = useToast();

let show_loader = ref(false);
let barcode = ref("");
let ShowModalConfirmOrderAndPay = ref(false);

const props = defineProps({
  products: Array,
  customers: Array,
  defaultCustomer: Object,
  translations: Object,
  defaultCurrency: String,
});

const selectedCustomer = ref(props.defaultCustomer);
const form = useForm({
  customer_id: props.defaultCustomer?.id || null,
  total_amount: 0,
});

const invoiceItems = reactive([]);

// حساب الإجمالي
const totalAmount = computed(() => {
  return invoiceItems.reduce((total, item) => total + (item.quantity * item.price), 0);
});
watch(totalAmount, (newTotal) => {
  form.total_amount = newTotal;
});

// اختيار عميل
const selectCustomer = (value) => {
  selectedCustomer.value = value;
  form.customer_id = value ? value.id : null;
};

// تحديث السعر عند اختيار المنتج
const updatePrice = (item) => {
  const selectedProduct = props.products.find(p => p.id === item.product_id);
   if (selectedProduct) {
    item.price = selectedProduct.price;
  }
};

// إضافة منتج
const addProduct = () => {
  invoiceItems.push({ product_id: '', quantity: 1, price: 0 });
};

// حذف منتج
const removeItem = (index) => {
  invoiceItems.splice(index, 1);
};

// فتح المودال
const openConfirmModal = () => {
  ShowModalConfirmOrderAndPay.value = true;
};

// حفظ الفاتورة
const saveInvoice = async (event) => {
  show_loader.value = true;
 
  const invoiceData = {
    total_amount: form.total_amount,
    total_paid: event.amountDollar ?? 0,
    customer_id: form.customer_id,
    date: event.date,
    notes: event.notes,
    items: invoiceItems.map(i => ({
      product_id: i.product_id,
      quantity: i.quantity,
      price: i.price,
    })),
  };

  try {
    const response = await axios.post('/api/createOrder', invoiceData);
    if (response.status === 200 || response.status === 201) {
      let { id, order_id } = response.data;
      if(event.printInvoice){
        window.open(`/api/getIndexAccountsSelas?print=2&transactions_id=${id}&order_id=${order_id}`, '_blank');
      }else{
       window.location.href = `/orders/create`;
      }
    }
  } catch (error) {
     toast.error("خطأ أثناء حفظ الفاتورة", {
      timeout: 5000,
      position: "bottom-right",
      rtl: true 
    })
    
  } finally {
    show_loader.value = false;
    ShowModalConfirmOrderAndPay.value = false;
  }
};

// البحث بالباركود
const findBarcode = debounce(async () => {
  if (!barcode.value) return;

  try {
    // جلب بيانات المنتج
    const response = await axios.get(`/api/products/${barcode.value}`);

    if (response.data.id) {
      // تحقق من توفر الكمية بالمخزون عبر API
      const stockResponse = await axios.get(`/api/check-stock/${response.data.id}`);

      if (stockResponse.data.available_quantity > 0) {
        const existingItem = invoiceItems.find(i => i.product_id === response.data.id);

        if (existingItem) {
          // تحقق من أن الكمية الجديدة لا تتجاوز المخزون
          if (existingItem.quantity + 1 <= stockResponse.data.available_quantity) {
            existingItem.quantity += 1;
          } else {
             toast.warning("لا توجد كمية كافية في المخزون", {
              timeout: 5000,
              position: "bottom-right",
              rtl: true

            });
          }
        } else {
          invoiceItems.push({
            product_id: response.data.id,
            quantity: 1,
            price: response.data.price,
          });
        }

        barcode.value = '';
      } else {
         toast.warning("المادة غير متوفرة في المخزون", {
          timeout: 5000,
          position: "bottom-right",
          rtl: true 
        })
      }
    }else{
      
             toast.error("تعذر تحقق من المنتج عن طريق الباركود", {
              timeout: 5000,
              position: "bottom-right",
              rtl: true

            });
    }
  } catch (error) {
    toast.warning("المادة غير متوفرة في المخزون", {
      timeout: 5000,
      position: "bottom-right",
      rtl: true
    })
  }
}, 500);

const check_stock = async (item) => {
  try {
    const response = await axios.get(`/api/check-stock/${item.product_id}`);

    if (item.quantity > response.data.available_quantity) {
      toast.success(
        `الكمية المتوفرة فقط: ${response.data.available_quantity}`, 
        {
          timeout: 5000,
          position: "bottom-right",
          rtl: true
        }
      );

      // ترجع الكمية للحد المسموح
      item.quantity = response.data.available_quantity;
    }
  } catch (error) {
    console.error("خطأ عند التحقق من المخزون:", error);
  }
};
</script>
