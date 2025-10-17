<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.suppliers }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white ">{{ translations.suppliers }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <div class="d-flex"></div>
        </div>
        <div class="card-body">
          <form @submit.prevent="Filter">
            <div class="row filter_form">
              <div class="col-md-2">
                <input
                  type="text"
                  class="form-control search_box"
                  v-model="filterForm.name"
                  :placeholder="translations.name"
                />
              </div>
              <div class="col-md-2">
                <input
                  type="text"
                  class="form-control search_box"
                  v-model="filterForm.phone"
                  :placeholder="translations.phone"
                />
              </div>
              <div class="col-md-2">
                <select class="form-select" v-model="filterForm.is_active">
                  <option value="" selected disabled>
                    {{ translations.status }}
                  </option>
                  <option :value="1">{{ translations.active }}</option>
                  <option :value="0">{{ translations.not_active }}</option>
                </select>
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                  {{ translations.search }} &nbsp; <i class="bi bi-search"></i>
                </button>
              </div>
            </div>
            
            <!-- Action Buttons Row -->
            <div class="row mt-3">
              <div class="col-md-3">
                <Link
                  v-if="hasPermission('create supplier')"
                  class="btn btn-primary w-100"
                  :href="route('suppliers.create')"
                >
                  <i class="bi bi-plus-circle"></i> &nbsp; إضافة مورد
                </Link>
              </div>
              <div class="col-md-3">
                <Link
                  class="btn btn-success w-100"
                  :href="route('purchase-invoices.create')"
                >
                  <i class="bi bi-receipt"></i> &nbsp; إنشاء فاتورة مشتريات
                </Link>
              </div>
              <div class="col-md-3">
                <Link
                  class="btn btn-info w-100"
                  :href="route('purchase-invoices.index')"
                >
                  <i class="bi bi-list-ul"></i> &nbsp; عرض فواتير المشتريات
                </Link>
              </div>
              <div class="col-md-3">
                <Link
                  v-if="hasPermission('read suppliers')"
                  class="btn btn-secondary w-100"
                  :href="route('export.suppliers')"
                >
                  <i class="bi bi-filetype-xls"></i> &nbsp; {{ translations.export }}
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
                  <th scope="col">{{ translations.phone }}</th>
                  <th scope="col">{{ translations.balance_dollar }}</th>
                  <th scope="col">{{ translations.balance_dinar }}</th>
                  <th scope="col">{{ translations.last_purchase_date }}</th>
                  <th scope="col">{{ translations.status }}</th>
                  <th scope="col">{{ translations.status }}</th>
                  <th scope="col" v-if="hasPermission('update supplier')">
                    {{ translations.edit }}
                  </th>
                  <th scope="col" v-if="hasPermission('delete supplier')">
                    {{ translations.delete }}
                  </th>
                </tr>
              </thead>
              <tbody class="text-center">
                <tr v-for="(supplier, index) in suppliers.data" :key="supplier.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>{{ supplier.name }}</td>
                  <td>{{ supplier.phone }}</td>
                  <td>{{ supplier.balance?.balance_dollar }}</td>
                  <td>{{ supplier.balance?.balance_dinar }}</td>
                  <td>{{ supplier.balance?.last_transaction_date }}</td>
                  <td>{{ supplier.is_active == 1 ? translations.active : translations.not_active }}</td>

                  <td>
                    <div>
                      <label class="inline-flex items-center me-5 cursor-pointer">
                        <input
                          type="checkbox"
                          class="sr-only peer"
                          :checked="supplier.is_active == 1"
                          @change="Activate(supplier.id)"
                        />
                        <div
                          class="relative w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"
                        ></div>
                      </label>
                    </div>
                  </td>
                  <td v-if="hasPermission('update supplier')">
                    <a class="btn btn-primary" :href="route('suppliers.edit', { supplier: supplier.id })">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                  </td>
                  <td v-if="hasPermission('delete supplier')">
                    <button type="button" class="btn btn-danger" @click="Delete(supplier.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <Pagination :links="suppliers.links" />
    </section>
    </div><!-- إغلاق div dir="rtl" -->
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ suppliers: Object, translations: Array });
const page = usePage();

const filterForm = reactive({
  name: '',
  phone: '',
  is_active: '',
});

const Filter = () => {
  router.get(
    route('suppliers.index'),
    filterForm,
    { preserveState: true, preserveScroll: true }
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
      router.post(`/suppliers/${id}/activate`, {
        onSuccess: () => {
          Swal.fire('Updated!', 'Client status has been updated.', 'success');
        },
        onError: () => {
          Swal.fire('Error!', 'There was an issue updating supplier status.', 'error');
        },
      });
    }
  });
};

const Delete = (id) => {
  Swal.fire({
    title: props.translations.are_your_sure,
    text: props.translations.You_will_not_be_able_to_revert_this,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: props.translations.yes,
    cancelButtonText: props.translations.cancel,
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete('suppliers/' + id, {
        onSuccess: () => {
          Swal.fire({
            title: props.translations.data_deleted_successfully,
            icon: 'success',
          });
        },
        onError: () => {
          Swal.fire('Error!', 'There was an issue deleting the supplier.', 'error');
        },
      });
    }
  });
};

const exportUsers = () => {
  const url = route('export.suppliers');
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'suppliers.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>
