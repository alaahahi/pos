<template>

  <AuthenticatedLayout :translations="translations">

    <!-- breadcrumb-->
    <div class="pagetitle">
      <h1>{{ translations.users }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link" :href="route('dashboard')">
            {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active">   {{ translations.users }}</li>
          <li class="breadcrumb-item active">   {{ translations.edit }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">

      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"> {{ translations.edit_user_info }}</h5>
              <!-- General Form Elements -->

              <form @submit.prevent="update" class="row g-3" method="POST">
  <!-- Product Name -->
  <div class="row mb-3">
                  <label for="inputName" class="col-sm-2 col-form-label">{{ translations.name }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputName"
                      type="text"
                      class="form-control"
                      :placeholder="translations.name"
                      v-model="form.name"
                    />
                    <InputError :message="form.errors.name" />
                  </div>
                </div>

                <!-- Model -->
                <div class="row mb-3">
                  <label for="inputModel" class="col-sm-2 col-form-label">{{ translations.model }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputModel"
                      type="text"
                      class="form-control"
                      :placeholder="translations.model"
                      v-model="form.model"
                    />
                    <InputError :message="form.errors.model" />
                  </div>
                </div>

                <!-- OE Number -->
                <div class="row mb-3">
                  <label for="inputOENumber" class="col-sm-2 col-form-label">{{ translations.oe_number }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputOENumber"
                      type="text"
                      class="form-control"
                      :placeholder="translations.oe_number"
                      v-model="form.oe_number"
                    />
                    <InputError :message="form.errors.oe_number" />
                  </div>
                </div>

                <!-- Situation -->
                <div class="row mb-3">
                  <label for="inputSituation" class="col-sm-2 col-form-label">{{ translations.situation }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputSituation"
                      type="text"
                      class="form-control"
                      :placeholder="translations.situation"
                      v-model="form.situation"
                    />
                    <InputError :message="form.errors.situation" />
                  </div>
                </div>

                <!-- price_cost -->
                <div class="row mb-3">
                  <label for="inputPrice" class="col-sm-2 col-form-label">{{ translations.price_cost }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputPrice"
                      type="number"
                      class="form-control"
                      :placeholder="translations.price_cost"
                      v-model="form.price_cost"
                    />
                    <InputError :message="form.errors.price_cost" />
                  </div>
                </div>
    

                 <!-- price_cost -->
                 <div class="row mb-3">
                  <label for="inputPrice" class="col-sm-2 col-form-label">{{ translations.price_with_transport }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputPrice"
                      type="number"
                      class="form-control"
                      :placeholder="translations.price_with_transport"
                      v-model="form.price_with_transport"
                    />
                    <InputError :message="form.errors.price_with_transport" />
                  </div>
                </div>

                <!-- price_cost -->

                 <div class="row mb-3">
                  <label for="inputPrice" class="col-sm-2 col-form-label">{{ translations.selling_price }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputPrice"
                      type="number"
                      class="form-control"
                      :placeholder="translations.selling_price"
                      v-model="form.selling_price"
                    />
                    <InputError :message="form.errors.selling_price" />
                  </div>
                </div>
                <!-- Quantity -->
                <div class="row mb-3">
                  <label for="inputQuantity" class="col-sm-2 col-form-label">{{ translations.quantity }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputQuantity"
                      type="number"
                      class="form-control"
                      :placeholder="translations.quantity"
                      v-model="form.quantity"
                    />
                    <InputError :message="form.errors.quantity" />
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputNote" class="col-sm-2 col-form-label">{{ translations.note }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputNote"
                      type="text"
                      class="form-control"
                      :placeholder="translations.note"
                      v-model="form.note"
                    />
                    <InputError :message="form.errors.note" />
                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputdate" class="col-sm-2 col-form-label">{{ translations.date }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputdate"
                      type="date"
                      class="form-control"
                      :placeholder="translations.date"
                      v-model="form.created"
                    />
                    <InputError :message="form.errors.created" />
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputNumber" class="col-sm-2 col-form-label">{{ translations.image }}</label>
                  <div class="col-sm-10">
                    <input type="file" @input="form.image = $event.target.files[0]" />
                    <img v-if="props.user?.image" :src="props.user.image" alt="Current Avatar" class="img-thumbnail"  width="80" />
                    <progress v-if="form.progress" :value="form.progress.percentage" max="100">
                      {{ form.progress.percentage }}%
                    </progress>
                  </div>
                </div>


                <div class="text-center">
                  <button type="submit" class="btn btn-primary" v-bind:disabled="show_loader">{{ translations.update }} &nbsp; <i class="bi bi-save"  v-if="!show_loader"></i>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="show_loader"></span>
                  </button>
                </div>


              </form>
              <!-- End Edit User From -->
            </div>
          </div>

        </div>

      </div>
    </section>

  </AuthenticatedLayout>
</template>



<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';


const props = defineProps({
  product: Object,
  userRoles: Array,
  roles: Object,
  translations:Array
})

const show_loader = ref(false);

const form = useForm({
  name : props.product.name,
  model:props.product.model,
  name:props.product.name,
  note:props.product.note,
  oe_number:props.product.oe_number,
  price_cost:props.product.price_cost,
  price_with_transport:props.product.price_with_transport,
  quantity:props.product.quantity,
  selling_price:props.product.selling_price,
  situation:props.product.situation,
  created: props.product.created,
  selectedRoles: props.userRoles,

})


const update = () => {
  show_loader.value = true; 
  form.post(route('products.update', { product: props.product.id }), {
    onSuccess: () => {
      show_loader.value = false; 
    },
    onError: () => {
      show_loader.value = false; 
    },
  });
};



</script>