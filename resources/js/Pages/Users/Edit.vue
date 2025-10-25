<template>

  <AuthenticatedLayout :translations="translations">


    <section class="section dashboard">

      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title"> {{ translations.edit_user_info }}</h5>
              <!-- General Form Elements -->

              <form @submit.prevent="update" class="row g-3" method="POST">

                <div class="row mb-3">
                  <label for="inputText" class="col-sm-2 col-form-label">{{ translations.name }}</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" placeholder="user name" v-model="form.name">
                    <InputError :message="form.errors.name" />

                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputEmail" class="col-sm-2 col-form-label">{{ translations.email }}</label>
                  <div class="col-sm-10">
                    <input type="email" class="form-control" v-model="form.email" placeholder="user Email">
                    <InputError :message="form.errors.email" />

                  </div>
                </div>
                <div class="row mb-3">
                  <label for="inputPassword" class="col-sm-2 col-form-label">{{ translations.password }}</label>
                  <div class="col-sm-10">
                    <input type="password" class="form-control" v-model="form.password" placeholder="Password">
                    <InputError :message="form.errors.password" />

                  </div>
                </div>


                <div class="row mb-3">
                  <label for="inputDate" class="col-sm-2 col-form-label"> {{ translations.role }}</label>
                  <div class="col-sm-10">
                    <select class="form-control" multiple v-model="form.selectedRoles">
                      <option value="" disabled>{{ translations.role }}</option>
                      <option v-for="role in roles" :key="role" :value="role">
                        {{ role }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="row mb-3">
                  <label class="col-sm-2 col-form-label">عمولة الديكورات</label>
                  <div class="col-sm-10">
                    <div class="form-check form-switch mb-2">
                      <input class="form-check-input" type="checkbox" v-model="form.commission_enabled" id="commissionEnabled">
                      <label class="form-check-label" for="commissionEnabled">تفعيل نسبة العمولة</label>
                    </div>
                    <div class="input-group">
                      <input type="number" step="0.01" min="0" max="100" class="form-control" v-model="form.commission_rate_percent" :disabled="!form.commission_enabled" placeholder="% نسبة العمولة (مثال: 5)">
                      <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">تحسب العمولة من سعر المبيع النهائي للديكور.</small>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="inputDate" class="col-sm-2 col-form-label"> {{ translations.created_at }}</label>
                  <div class="col-sm-10">
                    <input type="date" class="form-control" v-model="form.created_at" disabled>
                  </div>
                </div>


                <div class="row mb-3">
                  <label for="inputNumber" class="col-sm-2 col-form-label">{{ translations.avatar }}</label>
                  <div class="col-sm-10">
                    <input type="file" @input="form.avatar = $event.target.files[0]" />
                    <img v-if="props.user.avatar" :src="props.user.avatar" alt="Current Avatar" class="img-thumbnail"  width="80" />
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
  user: Object,
  userRoles: Array,
  roles: Object,
  translations:Array
})

const show_loader = ref(false);

const form = useForm({
  avatar: null,
  name: props.user.name,
  email: props.user.email,
  password: props.user.password,
  created_at: props.user.created_at,
  selectedRoles: props.userRoles,
  commission_enabled: !!props.user.commission_enabled,
  commission_rate_percent: props.user.commission_rate_percent || 0,

})


const update = () => {
  show_loader.value = true; 
  form.post(route('users.update', { user: props.user.id }), {
    onSuccess: () => {
      show_loader.value = false; 
    },
    onError: () => {
      show_loader.value = false; 
    },
  });
};


</script>