<template>
  <AuthenticatedLayout :translations="translations">

    <!-- breadcrumb-->
    <div class="pagetitle">
      <h1> {{ translations.invoice }} </h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active"> {{ translations.invoice }} </li>
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

              <!-- Invoice Form -->
              <form @submit.prevent="saveInvoice" class="row g-3">
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
                      @mouseleave="selectCustomer(selectedCustomer)"
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
                    <strong>{{ props.translations.total }}:</strong>
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

const searchQuery = ref('');
const show_loader = ref(false);
const selectedCustomer = ref(null);

const props = defineProps({
  products: Array,
  customers: Array,
  translations: Object,
});

const form = useForm({
  customer_id: "",
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
  console.log(value);
  selectedCustomer.value = value;
  form.customer_id = value ? value.id : null;
  logAction(`customer_selected ${value ? value.name : ''}`);
};

const logAction = (message) => {
  const timestamp = new Date().toLocaleString();
  invoiceLogs.push({ message, timestamp });
};

const saveInvoice = () => {
  show_loader.value = true;
  form.items = invoiceItems;

  form.post(route('orders.store'), {
    onSuccess: () => {
      show_loader.value = false;
      logAction('invoice_saved');
    },
    onError: () => {
      show_loader.value = false;
      logAction('invoice_save_failed');
    },
  });
};
</script>
