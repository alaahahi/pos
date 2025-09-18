<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';  


const props = defineProps({
  show: Boolean,
  data: Array,
  accounts: Array,
  translations:Array
});
const form = ref({
  date:getTodayDate(),
  price_cost:0,
  quantity:0, 
  price:0,
});
function getTodayDate() {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
const restform =()=>{
  form.value = {
  date:getTodayDate(),
};
}
const submit = (form) => {
  axios.post('/api/drop-from-box', form)
  .then(response => {
   })
  .catch(error => {
    $emit('close');
  });
}
</script>
  
  <template>
    <Transition name="modal">
      <div v-if="show" class="modal-mask ">
        <div class="modal-wrapper  max-h-[80vh]">
          <div class="modal-container">
            <div class="modal-header">
              <slot name="header"></slot>
            </div>
            <div class="modal-body">
       
              <div class="row g-3">
                <!-- Amount in Dollar -->
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
                  </div>
                </div>
    


                <!-- price_cost -->

                 <div class="row mb-3">
                  <label for="inputPrice" class="col-sm-2 col-form-label">{{ translations.price }}</label>
                  <div class="col-sm-10">
                    <input
                      id="inputPrice"
                      type="number"
                      class="form-control"
                      :placeholder="translations.price"
                      v-model="form.price"
                    />
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
                  </div>
                </div>
                <!-- Date -->
                <div class="row mb-3">
                  <label for="inputQuantity" class="col-sm-2 col-form-label">{{ translations.date }}</label>
                  <div class="col-sm-10">
                  <input
                    id="date"
                    type="date"
                    class="form-control"
                    v-model="form.date"
                  />
                  </div>
                </div>
                
              </div>
            </div>

            <div class="modal-footer">
              <div class="d-flex w-100">
                <!-- Cancel Button -->
                <div class="px-2 flex-fill">
                  <button
                    class="btn btn-secondary w-100  text-center"
                    @click="$emit('close');"
                  >
                    تراجع
                  </button>
                </div>

                <!-- Confirm Button -->
                <div class="px-2 flex-fill">
                  <button
                    class="btn btn-danger w-100 text-center"
                    @click="submit(form);$emit('a'); restform();"
                    :disabled="!form.amountDollar && !form.amountDinar"
                  >
                    نعم
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </template>
  
  <style>
  .modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: table;
    transition: opacity 0.3s ease;
  }
  
  .modal-wrapper {
    display: table-cell;
    vertical-align: middle;
  }
  
  .modal-container {
    width: 50%;
    min-width: 350px;
    margin: 0px auto;
    padding: 20px  30px;
    padding-bottom: 60px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    transition: all 0.3s ease;
    border-radius: 10px;
  }
  
  .modal-header h3 {
    margin-top: 0;
    color: #42b983;
  }
  
  .modal-body {
    margin: 20px 0;
  }
  
  .modal-default-button {
    float: right;
    width: 100%;
    color: #fff;
  }
  
  /*
   * The following styles are auto-applied to elements with
   * transition="modal" when their visibility is toggled
   * by Vue.js.
   *
   * You can easily play with the modal transition by editing
   * these styles.
   */
  
  .modal-enter-from {
    opacity: 0;
  }
  
  .modal-leave-to {
    opacity: 0;
  }
  
  .modal-enter-from .modal-container,
  .modal-leave-to .modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
  }
  </style>