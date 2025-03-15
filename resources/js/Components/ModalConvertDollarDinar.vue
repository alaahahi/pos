<script setup>
import { ref } from 'vue';

import { useToast } from "vue-toastification";
let toast = useToast();

const props = defineProps({
  show: Boolean,
  boxes: Array,
});
const form = ref({
  user: {
    percentage:0,
  },
  amount: 0,
  exchangeRate:1

});

const restform =()=>{
  form.value = {
  user: {
    percentage:0,
  },
  amount: 0,

};
}

function calculateAmountDollarDinar (){
  validateExchangeRate()
    form.value.amountResultDinar = Math.floor(form.value.amountDollar*(form.value.exchangeRate/100))
}
let exchangeRateError= ref(false);
function validateExchangeRate() {
      const input = form.value.exchangeRate;
      if (/^\d{6}$/.test(input)) {
        exchangeRateError.value = false;
      } else {
        exchangeRateError.value = true;
      }
    }
</script>
  
  <template>  
    <Transition name="modal">
      <div v-if="show" class="modal-mask ">
        <div class="modal-wrapper  max-h-[80vh]">
          <div class="modal-container">
            <div class="modal-header ">
              <slot name="header"></slot>
            </div>
            <div class="modal-body">
          <div>
            <!-- Exchange Rate -->
            <div class="my-4 mx-5">
              <label for="amountDinar" class="form-label float-end">سعر الصرف 100</label>
              <input
                id="amountDinar"
                type="number"
                class="form-control"
                @input="calculateAmountDollarDinar()"
                v-model="form.exchangeRate"
              />
              <div v-if="exchangeRateError" class="text-danger">
                مطلوب رقم من 6 خانة فقط
              </div>
            </div>

            <!-- Amount in Dollar -->
            <div class="my-4 mx-5">
              <label for="amountDollar" class="form-label float-end">
                المبلغ بالدولار (المبلغ المسحوب من الصندوق بالدولار)
              </label>
              <input
                id="amountDollar"
                type="number"
                class="form-control"
                @input="calculateAmountDollarDinar()"
                v-model="form.amountDollar"
              />
            </div>

            <!-- Amount in Dinar -->
            <div class="my-4 mx-5">
              <label for="amountDinar" class="form-label float-end">
                المبلغ بالدينار العراقي (المبلغ المضاف للصندوق بالدينار)
              </label>
              <input
                id="amountDinar"
                type="number"
                class="form-control"
                v-model="form.amountResultDinar"
              />
            </div>
          </div>
        </div>

        <div class="modal-footer my-2">
          <div class="d-flex w-100">
            <!-- Cancel Button -->
            <div class="px-4 flex-fill">
              <button
                class="btn btn-secondary w-100 text-center"
                @click="$emit('close');"
              >
                تراجع
              </button>
            </div>
            <!-- Confirm Button -->
          <div class="px-4 flex-fill">
          <button
          class="btn btn-danger w-100 text-center"
           @click="$emit('a', form); restform();"
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