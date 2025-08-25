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
              <form @submit.prevent="ShowModalConfirmOrderAndPay=true" class="row g-3">
                <!-- Customer Selection or Add New Customer -->
                <div class="row mb-3">
                  <label for="customerSelect" class="col-sm-2 col-form-label"> {{ translations.client }} </label>
                  <div class="col-sm-10">
                    <!-- Search Input for customers -->
                 
                    <!-- Vue Select Dropdown with filtered customers -->
                    <vue-select
                      v-model="selectedCustomer"
                      :options="filteredCustomers"
                      label="name"
                      track-by="id"
                      :reduce="customer => ({ id: customer.id, name: customer.name })"
                      @blur="mouseleave(selectedCustomer)"
                      :clearable="true"
                      :placeholder="translations.select_customer"
                    />
                    
                    <!-- Button to add new customer 
                    <button type="button" class="btn btn-success mt-2" @click="addNewCustomer">{{ translations.add_customer }}</button>
                    -->
                    <!-- Input Error Message -->
                    <InputError :message="form.errors.customer" />
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputBarcode" class="col-sm-2 col-form-label">{{ translations.barcode }}</label>
                  <div class="col-sm-10">
                    <input
                     autofocus
                      id="inputBarcode"
                      type="text"
                      class="form-control"
                      :placeholder="translations.barcode"
                      @keyup.enter="findBarcode()"
                      v-model="barcode"
                    />
                    <InputError :message="form.errors.name" />
                  </div>
                </div>

                <!-- Products Table -->
                <div class="row mb-3" v-if="selectedCustomer">
                  <label for="productTable" class="col-sm-2 col-form-label"> {{ translations.products }} </label>
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
                          <vue-select
                            v-model="item.product_id"
                            :options="products"
                            label="name"
                            :reduce="product => product.id"
                            :placeholder="translations.select_product"
                            class="form-control"
                            style="min-width: 200px;"
                            @mouseleave="updatePrice(item)" 
                          />
                          <td>
                            <input type="number" @input="updateMax(item)"  v-model="item.quantity" min="1" :max="item.max_quantity" class="form-control" placeholder="Quantity">
                          </td>
                          <td>
                            <input type="number" v-model="item.price" min="0" class="form-control" placeholder="Price">
                          </td>
                          <td>
                            <input type="number" :value="item.quantity * item.price" class="form-control"  disabled>
                          </td>
                          <td>
                            <button type="button" class="btn btn-danger" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <button type="button" class="btn btn-primary" @click="addProduct" v-if="products?.length">{{ translations.add_product }}</button>
                  </div>
                </div>
                <!-- Total Row -->
                <div class="row">
                  <div class="col-md-6">
                    <strong>{{ props.translations.total }}  {{ translations.dollar }}:</strong>
                  </div>
                  <div class="col-md-6">
                    <input type="number" v-model="totalAmount" class="form-control" readonly />
                  </div>
                </div>
                <!-- Log Section 
                <div class="row mb-3">
                  <label for="logSection" class="col-sm-2 col-form-label"> {{ translations.logs }} </label>
                  <div class="col-sm-10">
                    <div v-for="(log, index) in invoiceLogs" :key="index">
                      <p>{{ log.timestamp }}: {{ log.message }}</p>
                    </div>
                  </div>
                </div>
                  -->
                <!-- Submit -->
                <div class="text-center">
                  <button type="submit" class="btn btn-primary" :disabled="show_loader">
                    {{ translations.save }} &nbsp; 
                    <i class="bi bi-save" v-if="!show_loader"></i>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="show_loader"></span>
                  </button>
                </div>
              </form>
              <!-- End Invoice Form -->
            </div>
          </div>
        </div>
      </div>
    <ModalConfirmOrderAndPay :translations="translations" :show="ShowModalConfirmOrderAndPay" :total="totalAmount" @close="ShowModalConfirmOrderAndPay = false" @confirm="saveInvoice($event)" >
    
    </ModalConfirmOrderAndPay>
    </section>

  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { ref, reactive, computed,watch  } from 'vue';
import VueSelect from 'vue-select'; // Import vue-select component
import 'vue-select/dist/vue-select.css'; // Import vue-select styles
import axios from "axios";
import Swal from 'sweetalert2';
import ModalConfirmOrderAndPay from '@/Components/ModalConfirmOrderAndPay.vue';
let searchQuery = ref('');
let show_loader = ref(false);
let barcode = ref("");
let ShowModalConfirmOrderAndPay = ref(false);

const props = defineProps({
  products: Array,
  customers: Array,
  defaultCustomer: Object,
  translations: Object,
});
const selectedCustomer = ref(props.defaultCustomer.name);
const form = useForm({
  customer_id:props.defaultCustomer.id,
  items: [],
  log: [],
  total_amount: 0 // Add the total_amount field to form data
});

const invoiceItems = reactive([]);
const invoiceLogs = reactive([]);

// Computed property to filter customers based on search query
const filteredCustomers = computed(() => {
  if (!searchQuery.value) {
    return props.customers; // Show all customers if there's no search query
  }
  return props.customers.filter(customer => {
    const query = searchQuery.value.toLowerCase();
    return (
      customer.name.toLowerCase().includes(query) ||
      customer.phone.includes(query)
    );
  });
});

// Function to update the price and enforce a maximum quantity when a product is selected
const updatePrice = (item) => {
  const selectedProduct = props.products.find(product => product.id === item.product_id);
  if (selectedProduct) {
    item.price = selectedProduct.price;
    item.max_quantity = selectedProduct.max_quantity

  }
};
const updateMax = (item) => {
  const selectedProduct = props.products.find(product => product.id === item.product_id);
  if (selectedProduct) {
    if(item.quantity > selectedProduct.max_quantity ){
      item.quantity = selectedProduct.max_quantity
    }   
  }
};


// Add a computed property for the total amount
const totalAmount = computed(() => {
  return invoiceItems.reduce((total, item) => {
    return total + (item.quantity * item.price);
  }, 0);
});

// Watch the computed totalAmount and update form.total_amount
watch(totalAmount, (newTotal) => {
  form.total_amount = newTotal;
});
const logAction = (message) => {
  const timestamp = new Date().toLocaleString();
  invoiceLogs.push({ message, timestamp });
};
const addProduct = () => {
  invoiceItems.push({
    product_id: '',
    quantity: 1,
    price: 0,
  });
  logAction('product_added');
};
const removeItem = (index) => {
  invoiceItems.splice(index, 1);
  logAction('product_removed');
};

const addNewCustomer = () => {
  // Show form for adding new customer
  logAction('addNewCustomer');
};

const selectCustomer = (value) => {
  selectedCustomer.value = value;
  form.customer_id = value ? value.id : null;

  logAction(`customer_selected ${value ? value.name : ''}`);
};




const saveInvoice = async (event) => {
  show_loader.value = true;
  // إنشاء كائن يحتوي على بيانات الفاتورة والعناصر المباعة
  const invoiceData = {
    total_amount: form.total_amount, // المجموع
    total_paid: event.amountDollar, // المبلغ المدفوع
    customer_id:form.customer_id,
    date: event.date, // التاريخ
    notes: event.notes, // الملاحظات
    items: invoiceItems.reduce((acc, item) => {
      let existingItem = acc.find(i => i.product_id === item.product_id);
      if (existingItem) {
        existingItem.quantity += item.quantity; // تحديث الكمية بدلاً من إضافة منتج مكرر
      } else {
        acc.push(item);
      }
      return acc;
    }, [])
  };

  try {
    // إرسال الطلب إلى API باستخدام Axios
    const response = await axios.post('/api/createOrder', invoiceData);

    if (response.status === 200 || response.status === 201) {
      let transaction=response.data.id
      let order_id=response.data.order_id

      window.open(`/api/getIndexAccountsSelas?print=2&transactions_id=${transaction}&order_id=${order_id}`, '_blank');
      logAction('invoice_saved');
    } else {
      logAction('invoice_save_failed');
    }
  } catch (error) {
    console.error('خطأ أثناء حفظ الفاتورة:', error);
    logAction('invoice_save_failed');
  } finally {
    show_loader.value = false;
  }
};


const findBarcode = async () => {
  if (!barcode.value) return;

  try {
    const response = await axios.get(`/api/products/${barcode.value}`);

    if (response.data.id) {
      const existingItem = invoiceItems.find(
        item => item.product_id === response.data.id
      );

      if (existingItem) {
        existingItem.quantity += 1; // فقط زِد الكمية
      } else {
        invoiceItems.push({
          product_id: response.data.id,
          quantity: 1,
          price: response.data.price,
        });
      }

      barcode.value = ''; // تصفير الحقل بعد الإضافة أو التحديث
      console.log("تمت إضافة أو تحديث المنتج:", response.data);
    }
  } catch (error) {
    console.error("خطأ أثناء البحث عن الباركود:", error);
  }
};
</script>
