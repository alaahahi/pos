<script setup>
import { ref, watch } from 'vue';



const props = defineProps({
  show: Boolean,
  total: Number,
  translations:Array
});
const form = ref([{
  date:getTodayDate(),
  printInvoice:false
}]);
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
                <div class="col-lg-6">
                  <label for="amountDollar" class="form-label">{{ translations.total }} {{ translations.dollar }} </label>
                  <input
                    id="amountDollar"
                    type="number"
                    class="form-control"
                    :value="total"
                  />
                </div>

                <!-- Amount in Dollar -->
                <div class="col-lg-6">
                  <label for="amountDollar" class="form-label">{{ translations.total }} {{ translations.paid }} {{ translations.dollar }}</label>
                  <input
                    id="amountDollar"
                    type="number"
                    class="form-control"
                    @focus="form.amountDollar=total"
                    v-model="form.amountDollar"
                  />
                </div>

                <!-- Notes -->
                <div class="col-lg-6">
                  <label for="notes" class="form-label">{{ translations.note }}</label>
                  <input
                    id="notes"
                    type="text"
                    class="form-control"
                    v-model="form.notes"
                  />
                </div>

                <!-- Date -->
                <div class="col-lg-6">
                  <label for="date" class="form-label">{{ translations.date }}</label>
                  <input
                    id="date"
                    type="date"
                    class="form-control"
                    v-model="form.date"
                  />
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
                    {{translations.cancel}}
                  </button>
                </div>

                <!-- Confirm Button -->
                <div class="px-2 flex-fill">
                  <button
                    class="btn btn-danger w-100 text-center"
                    @click="form.printInvoice=false;$emit('confirm', form); restform();"
                  >
                    {{ translations.save_without_invoice }}
                  </button>
                </div>

                <div class="px-2 flex-fill">
                  <button
                    class="btn btn-warning w-100 text-center"
                    @click="form.printInvoice=true;$emit('confirm', form); restform();"
                  >
                    {{translations.save_with_invoice}}
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </template>
  
  