<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
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
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <div class="d-flex">
          </div>
        </div>
        <div class="card-body">
          <form @submit.prevent="Filter">
            <div class="row filter_form">
              <div class="col-md-3">
                <input type="text" class="form-control search_box" v-model="filterForm.name" 
                  :placeholder="translations.name" />
              </div>
              <div class="col-md-3">
                <input type="text" class="form-control search_box" v-model="filterForm.model" 
                  :placeholder="translations.model" />
              </div>
              <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                  {{ translations.search }} &nbsp; <i class="bi bi-search"></i>
                </button>
              </div>
              <div class="col-md-3">
                <Link v-if="hasPermission('create products')" class="btn btn-primary" :href="route('products.create')">
                  {{ translations.create }} &nbsp; <i class="bi bi-plus-circle"></i>
                </Link>
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table text-center">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">{{ translations.name }}</th>
                  <th scope="col">{{ translations.model }}</th>
                  <th scope="col">{{ translations.quantity }}</th>
                  <th scope="col">{{ translations.price }}</th>
                  <th scope="col">{{ translations.created_at }}</th>
                  <th scope="col" v-if="hasPermission('update products')">{{ translations.edit }}</th>
                  <th scope="col" v-if="hasPermission('delete products')">{{ translations.delete }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, index) in products?.data" :key="product.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>{{ product.name }}</td>
                  <td>{{ product.model }}</td>
                  <td>{{ product.quantity }}</td>
                  <td>{{ product.price }}</td>
                  <td>{{ product.created_at }}</td>
                  <td v-if="hasPermission('update products')">
                    <a class="btn btn-primary" :href="route('products.edit', { product: product.id })">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                  </td>
                  <td v-if="hasPermission('delete products')">
                    <button type="button" class="btn btn-danger" @click="Delete(product.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <Pagination :links="products?.links" />
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
  products: Object, 
  translations: Array 
});

const page = usePage();

const filterForm = reactive({
  name: '',
  model: ''
});

const Filter = () => {
  router.get(
    route('products.index'),
    filterForm,
    { preserveState: true, preserveScroll: true },
  );
};

const hasPermission = (permission) => {
  return page.props.auth_permissions.includes(permission);
};

const Delete = (id) => {
  Swal.fire({
    title: props.translations.are_you_sure,
    text: props.translations.you_will_not_be_able_to_revert_this,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: props.translations.yes,
    cancelButtonText: props.translations.cancel,
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete('products/' + id, {
        onSuccess: () => {
          Swal.fire({
            title: props.translations.data_deleted_successfully,
            icon: "success"
          });
        },
        onError: () => {
          Swal.fire(
            'Error!',
            'There was an issue deleting the product.',
            'error'
          );
        }
      });
    }
  });
};
</script>
