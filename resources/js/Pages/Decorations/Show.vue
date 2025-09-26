<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ translations.view }} {{ decoration.name }}
        </h2>
        <Link class="btn btn-secondary" :href="route('decorations.index')">
          <i class="bi bi-arrow-left"></i> {{ translations.cancel }}
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div v-if="decoration.image_url" class="mb-3">
                  <img :src="decoration.image_url" :alt="decoration.name" class="img-fluid rounded">
                </div>
                <div v-else class="bg-light p-5 text-center rounded mb-3">
                  <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                  <p class="text-muted mt-2">{{ translations.no_image }}</p>
                </div>
              </div>
              <div class="col-md-8">
                <h3>{{ decoration.name }}</h3>
                <p class="text-muted">{{ decoration.description }}</p>
                
                <div class="row mt-4">
                  <div class="col-md-6">
                    <strong>{{ translations.decoration_type }}:</strong>
                    <span class="badge bg-primary ms-2">{{ decoration.type_name }}</span>
                  </div>
                  <div class="col-md-6">
                    <strong>{{ translations.status }}:</strong>
                    <span class="badge ms-2" :class="decoration.is_active ? 'bg-success' : 'bg-secondary'">
                      {{ decoration.is_active ? translations.active : translations.inactive }}
                    </span>
                  </div>
                </div>

                <div class="row mt-3">
                  <div class="col-md-4">
                    <strong>{{ translations.base_price }}:</strong>
                    <p>{{ decoration.base_price }} دينار</p>
                  </div>
                  <div class="col-md-4">
                    <strong>{{ translations.duration_hours }}:</strong>
                    <p>{{ decoration.duration_hours }} {{ translations.hours }}</p>
                  </div>
                  <div class="col-md-4">
                    <strong>{{ translations.team_size }}:</strong>
                    <p>{{ decoration.team_size }} أشخاص</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  decoration: Object,
  translations: Object
})
</script>