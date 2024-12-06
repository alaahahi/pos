<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle">
      <h1>{{ translations.orders }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link" :href="route('dashboard')">
              {{ translations.home }}
            </Link>
          </li>
          <li class="breadcrumb-item active">{{ translations.orders }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <div class="d-flex">
            <!-- هنا يمكن إضافة أي أدوات تصفية أو بحث إضافية-->
          </div>
        </div>
        <div class="card-body">
          <f
          orm @submit.prevent="Filter">
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
                <Link v-if="hasPermission('create order')" class="btn btn-primary" :href="route('orders.create')">
                  {{ translations.create }} &nbsp; <i class="bi bi-plus-circle"></i>
                </Link>
              </div>
            </div>
          </f>

          <div class="table-responsive">
            <table class="table text-center">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">{{ translations.name }}</th> <!-- اسم العميل -->
                  <th scope="col">{{ translations.total }}</th> <!-- إجمالي المبلغ -->
                  <th scope="col">{{ translations.status }}</th> <!-- الحالة -->
                  <th scope="col">{{ translations.created_at }}</th> <!-- تاريخ الإنشاء -->
                  <th scope="col" v-if="hasPermission('update order')">{{ translations.pay }}</th>
                  <th scope="col" v-if="hasPermission('update order')">{{ translations.edit }}</th>
                  <th scope="col" v-if="hasPermission('delete order')">{{ translations.delete }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(order, index) in orders?.data" :key="order.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>{{ order.customer?.name }}</td> <!-- اسم العميل -->
                  <td>{{ order.total_amount }}</td> <!-- إجمالي المبلغ -->
                  <td>{{ order.status }}</td> <!-- الحالة -->
                  <td>{{ formatDate(order.created_at) }}</td> <!-- تاريخ الإنشاء -->
                  <td v-if="hasPermission('update order')&& order.status=='pending'">
                    <a class="btn btn-success" :href="route('orders.edit', { order: order.id })">
                      <i class="bi bi-currency-dollar"></i>
                    </a>
                  </td>
                  <td v-if="hasPermission('update order')">
                    <a class="btn btn-primary" :href="route('orders.edit', { order: order.id })">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                  </td>
                  <td v-if="hasPermission('delete order')">
                    <button type="button" class="btn btn-danger" @click="Delete(order.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <Pagination :links="orders?.links" />
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
  orders: Object, 
  translations: Array 
});

const page = usePage();

const filterForm = reactive({
  name: '',
  model: ''
});

const Filter = () => {
  router.get(
    route('orders.index'),
    filterForm,
    { preserveState: true, preserveScroll: true },
  );
};

const hasPermission = (permission) => {
  return page.props.auth_permissions.includes(permission);
};

const Activate = (id) => {
  Swal.fire({
    title: props.translations.are_your_sure,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#7066e0',
    confirmButtonText: props.translations.yes,
    cancelButtonText: props.translations.cancel,
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(`/orders/${id}/activate`, {
        onSuccess: () => {
          Swal.fire(
            'Updated !',
            'Order status has been updated.',
            'success'
          );
        },
        onError: () => {
          Swal.fire(
            'Error!',
            'There was an issue updating the order status.',
            'error'
          );
        }
      });
    }
  });
}

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
      router.delete('orders/' + id, {
        onSuccess: () => {
          Swal.fire({
            title: props.translations.data_deleted_successfully,
            icon: "success"
          });
        },
        onError: () => {
          Swal.fire(
            'Error!',
            'There was an issue deleting the order.',
            'error'
          );
        }
      });
    }
  });
};

// دالة لتنسيق التاريخ بشكل مناسب
const formatDate = (date) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
  return new Date(date).toLocaleDateString('en-US', options);
};
</script>
