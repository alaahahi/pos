<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.boxes }}</h1>
    </div>
    <!-- End breadcrumb-->
     <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-md-2">
                <button v-if="hasPermission('create order')" class="btn btn-success" @click="openAddSales()">
                  {{ translations.add_to_box }} &nbsp; <i class="bi bi-plus-circle"></i>
                </button>
            </div>
            <div class="col-md-2">
                <button v-if="hasPermission('create order')" class="btn btn-danger" @click="openAddExpenses()">
                  {{ translations.drop_from_box }} &nbsp; <i class="bi bi-stop-circle-fill"></i>
                </button>
            </div>
            <div class="col-md-2">
                <button v-if="hasPermission('create order')" class="btn btn-warning" @click="openConvertDollarDinar()">
                  {{ translations.convert_dinar_dollar }} &nbsp; <i class="bi bi-arrow-left-right"></i>
                </button>
            </div>
            <div class="col-md-2">
                <button v-if="hasPermission('create order')" class="btn btn-warning" @click="openConvertDinarDollar()">
                  {{ translations.convert_dollar_dinar }} &nbsp; <i class="bi bi-arrow-left-right"></i>
                </button>
            </div>
            <div class="col-md-2">
                <button  class="btn btn-info w-100 text-white" >
                 الرصيد بالدولار : <span class="text-bolde">{{ mainBox.wallet.balance ?? 0 }}</span>  <i class="bi bi-currency-dollar"></i>
                </button>
            </div>
            <div class="col-md-2">
                <button  class="btn btn-info w-100 text-white" >
                  الرصيد بالدينار : <span class="text-bold">{{ mainBox.wallet.balanceDinar ?? 0 }}</span>  <span class="text-white">IQD</span>
                </button>
            </div>
            <!-- هنا يمكن إضافة أي أدوات تصفية أو بحث إضافية-->
          </div>
        </div>
        <div class="card-body">
          <form @submit.prevent="Filter">
            <div class="row filter_form">
              <div class="col-md-2">
                <input type="date" class="form-control search_box" v-model="filterForm.start_date" 
                  :placeholder="translations.start_date" />
              </div>
              <div class="col-md-2">
                <input type="date" class="form-control search_box" v-model="filterForm.end_date" 
                  :placeholder="translations.end_date" />
              </div>
              <div class="col-md-2">
                <input type="text" class="form-control search_box" v-model="filterForm.name" 
                  :placeholder="translations.name" />
              </div>
              <div class="col-md-2">
                <input type="text" class="form-control search_box" v-model="filterForm.note" 
                  :placeholder="translations.note" />
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                  {{ translations.search }} &nbsp; <i class="bi bi-search"></i>
                </button>
              </div>
              
            </div>
          </form>

          <div class="table-responsive">
            <table class="table text-center">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">الحساب</th> <!-- اسم العميل -->
                  <th scope="col">سحب</th> <!-- الحالة -->
                  <th scope="col">اضافة</th> <!-- الحالة -->
                  <th scope="col">البيان</th> <!-- إجمالي المبلغ -->
                  <th scope="col">{{ translations.created_at }}</th> <!-- تاريخ الإنشاء -->
                  <th scope="col" v-if="hasPermission('delete order')">{{ translations.delete }}</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(order, index) in transactions.data" :key="order.id">
                <tr  :class="{'bg-red-100 dark:bg-red-900': order.type == 'outUser'||order.type == 'out','bg-green-100 dark:bg-green-900': order.type == 'inUser'||order.type == 'in'}">
                  <th>{{ index + 1 }}</th>
                  <td>{{ order.morphed?.name }}</td> <!-- اسم العميل -->
                  <td><span v-if="order.type == 'outUser'||order.type == 'out'">{{ order.amount }} {{ order.currency }}</span></td> <!-- إجمالي المبلغ -->
                  <td><span v-if="order.type == 'inUser'||order.type == 'in'">{{ order.amount }} {{ order.currency }}</span></td> <!-- إجمالي المبلغ -->
                  <td>{{ order.description }}</td> <!-- الحالة -->
                  <td>{{ formatDate(order.created_at) }}</td> <!-- تاريخ الإنشاء -->
                  <td v-if="hasPermission('delete order')">
                    <button type="button" class="btn btn-danger" @click="Delete(order.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <Pagination :links="transactions?.links" />
    </section>
    <ModalConvertDollarDinar 
            :show="showModalConvertDollarDinar ? true : false"
            :boxes="boxes"
            @a="confirmConvertDollarDinar($event)"
            @close="showModalConvertDollarDinar = false"
            >
          <template #header>
            <h3 class="text-center w-100">تحويل من الدولار الى دينار</h3>
            
           </template>
      </ModalConvertDollarDinar>
      <ModalConvertDinarDollar 
            :show="showModalConvertDinarDollar ? true : false"
            :boxes="boxes"
            @a="confirmConvertDinarDollar ($event)"
            @close="showModalConvertDinarDollar = false"
            >
          <template #header>
            <h3 class="text-center w-100">تحويل من الدينار الى دولار</h3>
            
           </template>
      </ModalConvertDinarDollar>
      <ModalAddToBox
            :show="showModalAddToBox ? true : false"
            :data="users"
            :accounts="accounts"
            @a="refresh();showModalAddToBox = false"
            @close="showModalAddToBox = false"
            >
          <template #header>
            <h3 class="text-center w-100">وصل قبض - اضافة للصندوق</h3>
            
           </template>
      </ModalAddToBox>
      <ModalDropFromBox 
            :show="showModalDropFromBox ? true : false"
            :boxes="boxes"
            @a="showModalDropFromBox = false"
            @close="showModalDropFromBox = false"
            >
          <template #header>
            <h3 class="text-center  w-100">وصل سحب - سحب من الصندوق</h3>
            
           </template>
      </ModalDropFromBox>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';
import { reactive ,ref} from 'vue';
import ModalConvertDollarDinar from "@/Components/ModalConvertDollarDinar.vue";
import ModalConvertDinarDollar from "@/Components/ModalConvertDinarDollar.vue";
import ModalAddToBox from "@/Components/ModalAddToBox.vue";
import ModalDropFromBox from "@/Components/ModalDropFromBox.vue";
import axios from 'axios';
let showModalConvertDinarDollar = ref(false);
let showModalConvertDollarDinar = ref(false);
let showModalAddToBox = ref(false);
let showModalDropFromBox = ref(false);

const props = defineProps({
  boxes: Object, 
  transactions: Array,
  translations: Array ,
  mainBox: Object,
});

const page = usePage();

const filterForm = reactive({
  name: '',
  model: '',
  start_date: getTodayDate(),
  end_date: getTodayDate()
});
function openConvertDollarDinar(){
  showModalConvertDollarDinar.value = true;
}
function openConvertDinarDollar(){
  showModalConvertDinarDollar.value = true;
}
function openAddSales() {
  showModalAddToBox.value = true;
}
function openAddExpenses(){
  showModalDropFromBox.value = true;
}
function refresh(){
  axios.get('api/boxes/transactions')
 .then(response => {
  // Use router to refresh the page data instead of directly modifying props
  router.reload({ only: ['transactions'] });
 })
 .catch(error => {
  console.log(error);
 }) 
}
function confirmConvertDollarDinar(V) {
  axios.post('/api/convertDollarDinar',V)
  .then(response => {
    refresh();
    showModalConvertDollarDinar.value=false;

  })
  .catch(error => {

    errors.value = error.response.data.errors
  })
}
function confirmConvertDinarDollar(V) {
  axios.post('/api/convertDinarDollar',V)
  .then(response => {
    refresh();
    showModalConvertDinarDollar.value=false;

  })
  .catch(error => {

    errors.value = error.response.data.errors
  })
}
const Filter = () => {
  router.get(
    route('boxes.index'),
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
      router.post(`/boxes/${id}/activate`, {
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
      router.delete('boxes/' + id, {
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
function getTodayDate() {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
 
// دالة لتنسيق التاريخ بشكل مناسب
const formatDate = (date) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
  return new Date(date).toLocaleDateString('en-US', options);
};
</script>
