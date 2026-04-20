<template>
  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h1 class="text-2xl font-bold text-gray-900">
                  Gestion des présences
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                  {{ course.name }} ({{ course.code }})
                </p>
              </div>
              <Link
                :href="route('teacher.courses.sessions.attendances.create', course.id)"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
              >
                Nouvelle séance
              </Link>
            </div>

            <!-- Statistiques globales -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-blue-900">Total séances</p>
                    <p class="text-2xl font-bold text-blue-600">{{ sessions.length }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-green-900">Taux moyen</p>
                    <p class="text-2xl font-bold text-green-600">{{ averageAttendanceRate }}%</p>
                  </div>
                </div>
              </div>

              <div class="bg-orange-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-orange-900">Retards</p>
                    <p class="text-2xl font-bold text-orange-600">{{ totalLateCount }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-red-50 p-4 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-red-900">Absences</p>
                    <p class="text-2xl font-bold text-red-600">{{ totalAbsentCount }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Liste des séances -->
            <div v-if="sessions.length > 0">
              <div class="overflow-hidden border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Sujet
                      </th>
                      <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Présences
                      </th>
                      <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Taux
                      </th>
                      <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="session in sessions" :key="session.id" class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          {{ formatDate(session.date) }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ session.duration_minutes }} min
                        </div>
                      </td>

                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          {{ session.topic || 'Séance sans titre' }}
                        </div>
                      </td>

                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center justify-center space-x-4 text-sm">
                          <div class="flex items-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                              {{ session.present_count }} ✓
                            </span>
                          </div>
                          <div class="flex items-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                              {{ session.late_count }} ⏱
                            </span>
                          </div>
                          <div class="flex items-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                              {{ session.absent_count }} ✗
                            </span>
                          </div>
                        </div>
                      </td>

                      <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center">
                          <div class="relative">
                            <svg class="w-12 h-12">
                              <circle
                                cx="24"
                                cy="24"
                                r="20"
                                stroke="#e5e7eb"
                                stroke-width="4"
                                fill="none"
                              />
                              <circle
                                cx="24"
                                cy="24"
                                r="20"
                                :stroke="getAttendanceRateColor(session.attendance_rate)"
                                stroke-width="4"
                                fill="none"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="circumference - (session.attendance_rate / 100) * circumference"
                                transform="rotate(-90 24 24)"
                                class="transition-all duration-300"
                              />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                              <span class="text-xs font-semibold" :class="getAttendanceRateTextColor(session.attendance_rate)">
                                {{ Math.round(session.attendance_rate) }}%
                              </span>
                            </div>
                          </div>
                        </div>
                      </td>

                      <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span
                          :class="[
                            'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                            session.is_completed 
                              ? 'bg-green-100 text-green-800' 
                              : 'bg-yellow-100 text-yellow-800'
                          ]"
                        >
                          {{ session.is_completed ? 'Complétée' : 'En cours' }}
                        </span>
                      </td>

                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                          <Link
                            :href="route('teacher.courses.sessions.attendances.show', [course.id, session.id])"
                            class="text-indigo-600 hover:text-indigo-900"
                            title="Voir les détails"
                          >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                          </Link>
                          <Link
                            :href="route('teacher.courses.sessions.attendances.edit', [course.id, session.id])"
                            class="text-blue-600 hover:text-blue-900"
                            title="Modifier"
                          >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                          </Link>
                          <button
                            @click="confirmDelete(session)"
                            class="text-red-600 hover:text-red-900"
                            title="Supprimer"
                          >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune séance</h3>
              <p class="mt-1 text-sm text-gray-500">
                Commencez par créer une nouvelle séance de présence.
              </p>
              <div class="mt-6">
                <Link
                  :href="route('teacher.courses.sessions.attendances.create', course.id)"
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  Nouvelle séance
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  course: Object,
  sessions: Array,
})

const circumference = 2 * Math.PI * 20

const averageAttendanceRate = computed(() => {
  if (props.sessions.length === 0) return 0
  const total = props.sessions.reduce((sum, session) => sum + session.attendance_rate, 0)
  return Math.round(total / props.sessions.length)
})

const totalLateCount = computed(() => {
  return props.sessions.reduce((sum, session) => sum + session.late_count, 0)
})

const totalAbsentCount = computed(() => {
  return props.sessions.reduce((sum, session) => sum + session.absent_count, 0)
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return '#10b981' // green-500
  if (rate >= 75) return '#f59e0b' // amber-500
  return '#ef4444' // red-500
}

const getAttendanceRateTextColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-amber-600'
  return 'text-red-600'
}

const confirmDelete = (session) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer la séance du ${formatDate(session.date)} ?`)) {
    const form = useForm({})
    form.delete(route('teacher.courses.sessions.attendances.destroy', [props.course.id, session.id]))
  }
}
</script>
