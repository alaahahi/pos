<template>
  <div>
    <!-- Teams Grid -->
    <div class="row" v-if="teams.length > 0">
      <div class="col-xl-4 col-lg-6 col-md-12 mb-4" v-for="team in teams" :key="team.id">
        <div class="card h-100 team-card">
          <!-- Card Header -->
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0">{{ team.name }}</h6>
              <small class="text-muted">{{ team.members_count }} {{ translations.team_members }}</small>
            </div>
            <span class="badge" :class="team.is_available ? 'bg-success' : 'bg-secondary'">
              {{ team.is_available ? translations.available : translations.unavailable }}
            </span>
          </div>

          <!-- Card Body -->
          <div class="card-body">
            <!-- Description -->
            <p class="card-text text-muted mb-3">{{ team.description }}</p>

            <!-- Team Details -->
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-2">
                <span>{{ translations.hourly_rate }}:</span>
                <strong>{{ team.hourly_rate }} {{ translations.dinar }}/{{ translations.hour }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>{{ translations.max_orders_per_day }}:</span>
                <span>{{ team.max_orders_per_day }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span>{{ translations.current_orders }}:</span>
                <span class="badge bg-info">{{ team.orders?.length || 0 }}</span>
              </div>
            </div>

            <!-- Specialties -->
            <div v-if="team.specialties_list" class="mb-3">
              <small class="text-muted">{{ translations.specialties }}:</small>
              <div class="mt-1">
                <span class="badge bg-light text-dark me-1" v-for="specialty in team.specialties" :key="specialty">
                  {{ specialty }}
                </span>
              </div>
            </div>

            <!-- Working Hours -->
            <div v-if="team.working_hours" class="mb-3">
              <small class="text-muted">{{ translations.working_hours }}:</small>
              <div class="mt-1">
                <small class="text-info">{{ formatWorkingHours(team.working_hours) }}</small>
              </div>
            </div>
          </div>

          <!-- Card Footer -->
          <div class="card-footer">
            <div class="btn-group w-100" role="group">
              <button @click="viewTeam(team)" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye"></i> {{ translations.view }}
              </button>
              <button @click="editTeam(team)" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i> {{ translations.edit }}
              </button>
              <button @click="toggleAvailability(team)" class="btn btn-outline-info btn-sm">
                <i class="bi bi-toggle-on"></i> {{ translations.toggle }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5">
      <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
      <h4 class="text-muted mt-3">{{ translations.no_teams }}</h4>
      <p class="text-muted">{{ translations.no_teams_description }}</p>
      <button class="btn btn-primary" @click="createTeam">
        <i class="bi bi-plus-circle"></i> {{ translations.create_team }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
  teams: Array,
  translations: Object
})

const emit = defineEmits(['refresh'])

// Methods
const formatWorkingHours = (workingHours) => {
  if (!workingHours || typeof workingHours !== 'object') return 'غير محدد'
  
  const start = workingHours.start || '09:00'
  const end = workingHours.end || '17:00'
  return `${start} - ${end}`
}

const viewTeam = (team) => {
  // Navigate to team details page
  console.log('View team:', team)
}

const editTeam = (team) => {
  // Open edit modal
  console.log('Edit team:', team)
}

const toggleAvailability = (team) => {
  // Toggle team availability
  console.log('Toggle availability:', team)
}

const createTeam = () => {
  // Open create team modal
  console.log('Create new team')
}
</script>

<style scoped>
.team-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid #e3e6f0;
}

.team-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.card-header {
  background-color: #f8f9fc;
  border-bottom: 1px solid #e3e6f0;
}

.btn-group .btn {
  flex: 1;
}

.badge {
  font-size: 0.75em;
}
</style>
