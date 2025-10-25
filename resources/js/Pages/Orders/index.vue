<template>
  <AuthenticatedLayout :translations="translations">
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.orders }}</h1>
    </div>

    <section class="section dashboard">
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <!-- Today's Orders -->
        <div class="col-xl-3 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">
                فواتير اليوم
                <span v-if="statistics?.changes?.count !== 0" :class="statistics?.changes?.count > 0 ? 'text-success' : 'text-danger'">
                  | <i :class="statistics?.changes?.count > 0 ? 'bi bi-arrow-up' : 'bi bi-arrow-down'"></i>
                  {{ Math.abs(statistics?.changes?.count || 0) }}%
                </span>
              </h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-receipt"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ statistics?.today?.count || 0 }} فاتورة</h6>
                  <span class="text-success small pt-1 fw-bold">
                    {{ (statistics?.today?.total || 0).toLocaleString() }} IQD
                  </span>
                  <small class="d-block text-muted">
                    مدفوع: {{ (statistics?.today?.paid || 0).toLocaleString() }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Week's Orders -->
        <div class="col-xl-3 col-md-6">
          <div class="card info-card customers-card">
            <div class="card-body">
              <h5 class="card-title">فواتير الأسبوع</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-calendar-week"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ statistics?.week?.count || 0 }} فاتورة</h6>
                  <span class="text-primary small pt-1 fw-bold">
                    {{ (statistics?.week?.total || 0).toLocaleString() }} IQD
                  </span>
                  <small class="d-block text-muted">
                    مدفوع: {{ (statistics?.week?.paid || 0).toLocaleString() }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Month's Orders -->
        <div class="col-xl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">فواتير الشهر</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-calendar-month"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ statistics?.month?.count || 0 }} فاتورة</h6>
                  <span class="text-info small pt-1 fw-bold">
                    {{ (statistics?.month?.total || 0).toLocaleString() }} IQD
                  </span>
                  <small class="d-block text-muted">
                    مدفوع: {{ (statistics?.month?.paid || 0).toLocaleString() }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Yesterday vs Today -->
        <div class="col-xl-3 col-md-6">
          <div class="card info-card">
            <div class="card-body">
              <h5 class="card-title">
                المقارنة مع الأمس
                <span v-if="statistics?.changes?.total !== 0" :class="statistics?.changes?.total > 0 ? 'text-success' : 'text-danger'">
                  | <i :class="statistics?.changes?.total > 0 ? 'bi bi-arrow-up' : 'bi bi-arrow-down'"></i>
                  {{ Math.abs(statistics?.changes?.total || 0) }}%
                </span>
              </h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" 
                     :class="(statistics?.changes?.total || 0) >= 0 ? 'bg-success' : 'bg-danger'">
                  <i class="bi bi-graph-up-arrow text-white"></i>
                </div>
                <div class="ps-3">
                  <h6>الأمس: {{ statistics?.yesterday?.count || 0 }}</h6>
                  <span class="text-muted small">
                    {{ (statistics?.yesterday?.total || 0).toLocaleString() }} IQD
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="d-flex">
            <!-- هنا يمكن إضافة أي أدوات تصفية أو بحث إضافية-->
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
                <Link v-if="hasPermission('create order')" class="btn btn-primary" :href="route('orders.create')">
                  {{ translations.create_invoice }}    <i class="bi bi-plus-circle"></i>
                </Link>
              </div>
            </div>
          </form>
           <div class="table-responsive">
            <table class="table text-center">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">{{ translations.client }}</th> <!-- اسم العميل -->
                  <th scope="col">{{ translations.total }} IQD</th> <!-- إجمالي المبلغ -->
                  <th scope="col">الخصم IQD</th> <!-- الخصم -->
                  <th scope="col">المبلغ النهائي IQD</th> <!-- المبلغ النهائي -->
                  <th scope="col">{{ translations.paid_order }} IQD</th> <!-- إجمالي المبلغ -->
                  <th scope="col">{{ translations.due_order }} IQD</th> <!-- إجمالي المبلغ -->

                  <!-- <th scope="col">{{ translations.paid_order }} {{ translations.dinar }}</th>  
                  <th scope="col">{{ translations.due_order }} {{ translations.dinar }}</th>  -->

                  <th scope="col">{{ translations.statusOrder }}</th> <!-- الحالة -->
                  <th scope="col">{{ translations.created_at }}</th> <!-- تاريخ الإنشاء -->
                  <th scope="col" v-if="hasPermission('update order')"></th>
                  <th scope="col" v-if="hasPermission('delete order')"></th>
                  <th scope="col" v-if="hasPermission('delete order')"></th>
                  <th scope="col" v-if="hasPermission('update order')"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(order, index) in orders?.data" :key="order.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>{{ order.customer?.name }}</td> <!-- اسم العميل -->
                  <td>{{ order.total_amount }}</td> <!-- إجمالي المبلغ -->
                  <td>{{ order.discount_amount || 0 }}</td> <!-- الخصم -->
                  <td>{{ order.final_amount || order.total_amount }}</td> <!-- المبلغ النهائي -->
                  <td>{{ order.total_paid }}</td> <!-- إجمالي المبلغ -->
                  <td>{{ (order.final_amount || order.total_amount) - order.total_paid }}</td> <!-- إجمالي المبلغ -->
                  <!-- <td>{{ order.total_paid_dinar }}</td>
                  <td>{{ order.total_amount_dinar	 - order.total_paid_dinar }}</td> -->
                  <td>{{ order.status }}</td> <!-- الحالة -->
                  <td>{{ formatDate(order.created_at) }}</td> <!-- تاريخ الإنشاء -->
                
             
                  <td v-if="hasPermission('delete order')">
                    <button type="button" class="btn btn-danger" @click="Delete(order.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>

                  <td>
                    <a class="btn btn-secondary" :href="route('order.print', { id: order.id })">
                      <i class="bi bi-printer text-white"></i>
                    </a>
                  </td>
                  <td v-if="hasPermission('update order')&& order.status=='due'">
                    <a class="btn btn-success" :href="route('orders.edit', { order: order.id })">
                      <i class="bi bi-currency-dollar"></i>
                    </a>
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
  translations: Array,
  statistics: Object
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
  if (!date) return '-';
  const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
  return new Date(date).toLocaleDateString('en-US', options);
};
</script>
