<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white"> {{ translations.edit_invoice }} </h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('orders.index')">
              {{ translations.invoices }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">
            {{ translations.edit_invoice }}
          </li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"> {{ translations.edit_invoice }} </h5>

              <!-- Invoice Form -->
              <form class="row g-3" @submit.prevent="openConfirmModal">
                
                <!-- Customer -->
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">{{ translations.client }}</label>
                  <div class="col-sm-10">
                    <vue-select
                      v-model="selectedCustomer"
                      :options="customers"
                      label="name"
                      track-by="id"
                      :reduce="c => ({ id: c.id, name: c.name })"
                      :clearable="true"
                      :placeholder="translations.select_customer"
                      @input="selectCustomer"
                    />
                    <InputError :message="form.errors.customer_id" />
                  </div>
                </div>

                <!-- Products -->
                <div class="row mb-3" v-if="selectedCustomer">
                  <label class="col-sm-2 col-form-label">{{ translations.products }}</label>
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
                        <tr v-for="(item, index) in orderItems" :key="index">
                          <td style="min-width:200px;">
                            <vue-select
                              v-model="item.product_id"
                              :options="all_products"
                              label="name"
                              track-by="id"
                              :reduce="p => p.id"
                              :placeholder="translations.select_product"
                              class="form-control"
                              @input="updatePrice(item)"
                            />
                          </td>
                          <td>
                            <input type="number" v-model="item.quantity" @input="updateMax(item)" min="1" :max="item.max_quantity" class="form-control" />
                          </td>
                          <td>
                            <input type="number" v-model="item.price" min="0" class="form-control" />
                          </td>
                          <td>
                            <input type="number" :value="item.quantity * item.price" class="form-control"  @input="check_stock(item)"  />
                          </td>
                          <td>
                            <button type="button" class="btn btn-danger" @click="removeItem(index)">
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
                    <strong>{{ translations.total }} {{ translations.dollar }}:</strong>
                  </div>
                  <div class="col-md-6">
                    <input type="number" v-model="totalAmount" class="form-control" readonly />
                  </div>
                </div>

                <!-- Save -->
                <div class="text-center mt-3">
                  <button type="submit" class="btn btn-primary" :disabled="show_loader">
                    {{ translations.save }} &nbsp;
                    <i class="bi bi-save" v-if="!show_loader"></i>
                    <span v-if="show_loader" class="spinner-border spinner-border-sm"></span>
                  </button>
                </div>
              </form>
              <!-- End Invoice Form -->
            </div>
          </div>
        </div>
      </div>

      <!-- Modal -->
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

const props = defineProps({
  products: Array,
  all_products: Array,
  customers: Array,
  order: Object,
  translations: Object,
});

let show_loader = ref(false);
let ShowModalConfirmOrderAndPay = ref(false);

// init form with order data
const form = useForm({
  id: props.order.id,
  customer_id: props.order.customer_id,
  items: props.order.items || [],
  total_amount: props.order.total_amount || 0,
});

const selectedCustomer = ref(
  props.customers.find(c => c.id === props.order.customer_id) || null
);

const orderItems = reactive(
  props.order.items?.map(i => ({
    product_id: i.product_id,
    quantity: i.quantity,
    price: i.price,
    max_quantity: props.products.find(p => p.id === i.product_id)?.max_quantity || 9999,
  })) || []
);

// methods
const selectCustomer = (value) => {
  selectedCustomer.value = value;
  form.customer_id = value ? value.id : null;
};

const updatePrice = (item) => {
  const p = props.products.find(p => p.id === item.product_id);
  if (p) {
    item.price = p.price;
    item.max_quantity = p.max_quantity;
  }
};

const updateMax = (item) => {
  const p = props.products.find(p => p.id === item.product_id);
  if (p && item.quantity > p.max_quantity) {
    item.quantity = p.max_quantity;
  }
};

const addProduct = () => {
  orderItems.push({ product_id: '', quantity: 1, price: 0 });
};

const removeItem = (i) => {
  orderItems.splice(i, 1);
};

// computed
const totalAmount = computed(() =>
  orderItems.reduce((t, i) => t + (i.quantity * i.price), 0)
);

watch(totalAmount, (newTotal) => {
  form.total_amount = newTotal;
});

// modal handler
const openConfirmModal = () => {
  ShowModalConfirmOrderAndPay.value = true;
};

const saveInvoice = async (event) => {
  show_loader.value = true;
  const invoiceData = {
    total_amount: form.total_amount,
    total_paid: event.amountDollar ?? 0,
    customer_id: form.customer_id,
    date: event.date,
    notes: event.notes,
    items: orderItems.map(i => ({
      product_id: i.product_id,
      quantity: i.quantity,
      price: i.price,
    })),
  };

  try {
    const response = await axios.put(route('orders.update', form.id), invoiceData);
    if (response.status === 200 || response.status === 201) {
      window.location.href = '/orders';
    }
  } catch (error) {
    console.error("خطأ أثناء تحديث الفاتورة:", error);
  } finally {
    show_loader.value = false;
    ShowModalConfirmOrderAndPay.value = false;
  }
};

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
