<script setup>
import { ref, computed } from "vue";
import axios from 'axios';
import Uploader  from 'vue-media-upload';
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
  show: Boolean,
  formData: Object,
  client: Array,
});



  function removeMedia(removedImage){
          axios.get('/api/TransactionsImageDel?name='+removedImage.name)
        .then(response => {
          toast.success("تم  حذف الصورة بنجاح", {
              timeout: 5000,
              position: "bottom-right",
              rtl: true

            });
        })
        .catch(error => {
          console.error(error);
        })
}

  </script>
  
  <template>
    <Transition name="modal">
      <div v-if="show" class="modal-mask ">
        <div class="modal-wrapper  max-h-[80vh]">
          <div class="modal-container dark:bg-gray-900 overflow-auto  max-h-[80vh]">
            <div class="modal-header">
              <slot name="header"></slot>
            </div>
            <h2 class="dark:text-gray-300 py-4  text-center">
              {{ formData.description }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-1 lg:gap-2">
              <div class="mb-4">
                <label class="form-label w-100 dark:text-gray-300">الصور</label>
                <div class="mt-3">
                    <Uploader 
                        :server="'/api/TransactionsUpload?transactionsId='+formData.id"
                        :is-invalid="errors?.media ? true : false"
                        @change="changeMedia"
                        @initMedia="media"
                        location="/public/uploadsResized"
                        :media="formData.transactions_images"
                        @add="addMedia"
                        @remove="removeMedia"
                    />
                </div>
                <p v-if="errors?.media" class="text-danger">{{ errors?.media[0] }}</p>
            </div>
             </div>


            <div class="modal-footer my-2">
              <div class="flex flex-row w-100">
                <div class="basis-1/2 px-4 w-100">             <button
                    class="modal-default-button py-3  bg-gray-500 rounded text-center"
                    @click="$emit('close');"
                  >تراجع</button></div>
              <div class="basis-1/2 px-4">              <button
                    class="modal-default-button py-3  bg-rose-500 rounded col-6 text-center"
                    @click="$emit('a',formData);"
                  >نعم</button>
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
    margin-bottom: 25px;
    color: #000 !important;
    margin: 20px;
    padding-bottom: 20px;
    font-size: 24px;
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