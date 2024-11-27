<template>
  <AuthenticatedLayout :translations="translations">
    <!-- Breadcrumb -->
    <div class="pagetitle">
      <h1>{{ translations.products }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link" :href="route('dashboard')">
              {{ translations.home }}
            </Link>
          </li>
          <li class="breadcrumb-item active">{{ translations.products }}</li>
          <li class="breadcrumb-item active">{{ translations.create }}</li>
        </ol>
      </nav>
    </div>
    <!-- End Breadcrumb -->

    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">{{ translations.add_new_product }}</h5>

              <!-- General Form -->
              <form @submit.prevent="store" class="row g-3">
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
                      v-model="form.date"
                    />
                    <InputError :message="form.errors.date" />
                  </div>
                </div>
                <!-- Image -->
                <div class="row mb-3">
                  <label for="inputImage" class="col-sm-2 col-form-label">{{ translations.image }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputImage"
                      type="file"
                      @input="form.image = $event.target.files[0]"
                    />
                    <progress
                      v-if="form.progress"
                      :value="form.progress.percentage"
                      max="100"
                    >
                      {{ form.progress.percentage }}%
                    </progress>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                  <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="show_loader"
                  >
                    {{ translations.save }}
                    <i
                      class="bi bi-save"
                      v-if="!show_loader"
                    ></i>
                    <span
                      class="spinner-border spinner-border-sm"
                      role="status"
                      aria-hidden="true"
                      v-if="show_loader"
                    ></span>
                  </button>
                </div>
              </form>
              <!-- End General Form -->
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
import { ref } from 'vue';

const show_loader = ref(false);

const props = defineProps({
  translations: Object,
});

const form = useForm({
  name: '',
  model: '',
  oe_number: '',
  situation: '',
  price_cost: null,
  price_with_transport: null,
  selling_price: null,
  quantity: null,
  note:'',
  image: null,
});

const store = () => {
  show_loader.value = true;
  form.post(route('products.store'), {
    onSuccess: () => {
      show_loader.value = false;
    },
    onError: () => {
      show_loader.value = false;
    },
  });
};
</script>
  