<template>

  <AuthenticatedLayout :translations="translations">

    <!-- breadcrumb-->
    <div class="pagetitle">
      <h1>{{ translations.customers }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link" :href="route('dashboard')">
            {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active">   {{ translations.customers }}</li>
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
              <h5 class="card-title"> {{ translations.edit_customer_info }}</h5>
              <!-- General Form Elements -->
              <form @submit.prevent="update" class="row g-3" method="POST">
                <div class="row mb-3">
                  <label for="inputName" class="col-sm-2 col-form-label"> {{ translations.name }}</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" :placeholder="translations.name" v-model="form.name">
                    <InputError :message="form.errors.name" />
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputPhone" class="col-sm-2 col-form-label"> {{ translations.phone }}</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" :placeholder="translations.phone" v-model="form.phone">
                    <InputError :message="form.errors.phone" />
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputAddress" class="col-sm-2 col-form-label"> {{ translations.address }}</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" :placeholder="translations.address" v-model="form.address">
                    <InputError :message="form.errors.address" />
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputNotes" class="col-sm-2 col-form-label"> {{ translations.notes }}</label>
                  <div class="col-sm-10">
                    <input  type="text" class="form-control" :placeholder="translations.notes" v-model="form.notes">
                    <InputError :message="form.errors.notes" />
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputAvatar" class="col-sm-2 col-form-label"> {{ translations.image }}</label>
                  <div class="col-sm-10">
                    <input type="file" @input="form.avatar = $event.target.files[0]" />
                    <progress v-if="form.progress" :value="form.progress.percentage" max="100">
                      {{ form.progress.percentage }}%
                    </progress>
                  </div>
                </div>

                <div class="text-center">
                  <button type="submit" class="btn btn-primary" v-bind:disabled="show_loader"> {{ translations.save }} &nbsp; <i class="bi bi-save"
                      v-if="!show_loader"></i>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                      v-if="show_loader"></span>
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
  customer: Object,
  roles: Object,
  translations:Array
})

const show_loader = ref(false);

const form = useForm({
  avatar: null,
  name: props.customer.name,
  phone: props.customer.phone,
  address: props.customer.address,
  notes: props.customer.notes,
})


const update = () => {
  show_loader.value = true; 
  form.post(route('customers.update', { customer: props.customer.id }), {
    onSuccess: () => {
      show_loader.value = false; 
    },
    onError: () => {
      show_loader.value = false; 
    },
  });
};



</script>