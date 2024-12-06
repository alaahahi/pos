<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle">
      <h1> {{ translations.edit_order }} </h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
        
          <li class="breadcrumb-item active"> {{ translations.edit_order }} </li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"> {{ translations.edit_order }} </h5>

              <!-- Invoice Form -->
              <form @submit.prevent="updateInvoice" class="row g-3">
                <!-- Customer Selection -->
                <div class="row mb-3">
                  <label for="customerSelect" class="col-sm-2 col-form-label"> {{ translations.client }} </label>
                  <div class="col-sm-10">
                    <vue-select
                      v-model="selectedCustomer"
                      :options="customers"
                      label="name"
                      track-by="id"
                      :reduce="customer => ({ id: customer.id, name: customer.name })"
                      :clearable="true"
                      :placeholder="translations.select_customer"
                      @mouseleave="selectCustomer(selectedCustomer)"
                    />
                    <InputError :message="form.errors.customer" />
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
                          <th>{{ translations.price }}</th>
                          <th>{{ translations.total }}</th>
                          <th>{{ translations.actions }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in orderItems" :key="index">
                          <vue-select
                            v-model="item.product_id"
                            :options="products"
                            label="name"
                            :disabled="item.canadd != 'true'"
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
                            <input type="number" :value="item.quantity * item.price" class="form-control" disabled>
                          </td>
                          <td>
                            <button type="button" class="btn btn-danger" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <button type="button" class="btn btn-primary" v-if="products?.length" @click="addProduct">{{ translations.add_product }}</button>
                  </div>
                </div>

                <!-- Total Row -->
                <div class="row">
                  <div class="col-md-6">
                    <strong>{{ translations.total }}:</strong>
                  </div>
                  <div class="col-md-6">
                    <input type="number" v-model="totalAmount" class="form-control" readonly />
                  </div>
                </div>

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
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { ref, reactive, computed, watch } from 'vue';
import VueSelect from 'vue-select'; // Import vue-select component
import 'vue-select/dist/vue-select.css'; // Import vue-select styles

const show_loader = ref(false);

const props = defineProps({
  order: Object,  // existing order data
  products: Array,
  customers: Array,
  translations: Object,
});
const selectedCustomer = ref(
  props.customers.find((customer) => customer.id === props.order.customer_id) || null
);
const form = useForm({
  id: props.order.id,
  customer_id: props.order.customer_id,
  items: props.order.items || [],
  total_amount: props.order.total_amount || 0
});

const orderItems = reactive(form.items);
const orderLogs = reactive([]);



const updatePrice = (item) => {
  const selectedProduct = props.products.find(product => product.id === item.product_id);
  if (selectedProduct) {
    item.price = selectedProduct.price;
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
const totalAmount = computed(() => {
  return orderItems.reduce((total, item) => {
    return total + (item.quantity * item.price);
  }, 0);
});

watch(totalAmount, (newTotal) => {
  form.total_amount = newTotal;
});

const addProduct = () => {
  orderItems.push({
    product_id: '',
    quantity: 1,
    price: 0,
    canadd:'true',
  });
};

const removeItem = (index) => {
  orderItems.splice(index, 1);
};

const selectCustomer = (value) => {
  selectedCustomer.value = value;
  form.customer_id = value ? value.id : null;
};

const updateInvoice = () => {
  show_loader.value = true;
  form.items = orderItems;
  form.put(route('orders.update', form.id), {
    onSuccess: () => {
      show_loader.value = false;
    },
    onError: () => {
      show_loader.value = false;
    },
  });
};
</script>
