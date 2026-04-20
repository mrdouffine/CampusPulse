<template>
  <AppLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h1 class="text-2xl font-bold text-gray-900">
                  Nouvelle séance de présence
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                  {{ course.name }} ({{ course.code }})
                </p>
              </div>
              <Link
                :href="route('teacher.courses.sessions.attendances.index', course.id)"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
              >
                Retour
              </Link>
            </div>

            <form @submit.prevent="submit">
              <!-- Informations de la séance -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    Date de la séance *
                  </label>
                  <input
                    v-model="form.date"
                    type="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    :class="{ 'border-red-500': form.errors.date }"
                    required
                  />
                  <p v-if="form.errors.date" class="mt-1 text-sm text-red-600">
                    {{ form.errors.date }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    Durée (minutes) *
                  </label>
                  <input
                    v-model="form.duration_minutes"
                    type="number"
                    min="1"
                    max="480"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    :class="{ 'border-red-500': form.errors.duration_minutes }"
                    required
                  />
                  <p v-if="form.errors.duration_minutes" class="mt-1 text-sm text-red-600">
                    {{ form.errors.duration_minutes }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    Sujet de la séance
                  </label>
                  <input
                    v-model="form.topic"
                    type="text"
                    placeholder="Ex: Chapitre 3 - Les boucles"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    :class="{ 'border-red-500': form.errors.topic }"
                  />
                  <p v-if="form.errors.topic" class="mt-1 text-sm text-red-600">
                    {{ form.errors.topic }}
                  </p>
                </div>
              </div>

              <!-- Actions rapides -->
              <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-sm font-medium text-blue-900">
                      Actions rapides
                    </h3>
                    <p class="text-sm text-blue-700">
                      Utilisez ces boutons pour modifier le statut de plusieurs étudiants
                    </p>
                  </div>
                  <div class="flex space-x-2">
                    <button
                      type="button"
                      @click="setAllStatus('present')"
                      class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700"
                    >
                      Tous présents
                    </button>
                    <button
                      type="button"
                      @click="setAllStatus('absent')"
                      class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700"
                    >
                      Tous absents
                    </button>
                    <button
                      type="button"
                      @click="setAllStatus('late')"
                      class="px-3 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700"
                    >
                      Tous en retard
                    </button>
                  </div>
                </div>
              </div>

              <!-- Liste des étudiants -->
              <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">
                    Liste des étudiants ({{ students.length }})
                  </h3>
                  <div class="text-sm text-gray-600">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {{ presentCount }} présents
                    </span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 ml-2">
                      {{ lateCount }} retards
                    </span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                      {{ absentCount }} absents
                    </span>
                  </div>
                </div>

                <div class="overflow-hidden border border-gray-200 rounded-lg">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Étudiant
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Note
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="(student, index) in students" :key="student.id" 
                          class="hover:bg-gray-50" :class="{ 'bg-yellow-50': student.note }">
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                              <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-medium text-indigo-600">
                                  {{ student.name.charAt(0).toUpperCase() }}
                                </span>
                              </div>
                            </div>
                            <div class="ml-4">
                              <div class="text-sm font-medium text-gray-900">
                                {{ student.name }}
                              </div>
                              <div class="text-sm text-gray-500">
                                {{ student.student_number }}
                              </div>
                            </div>
                          </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                          <div class="flex items-center justify-center space-x-2">
                            <button
                              type="button"
                              @click="setStudentStatus(student, 'present')"
                              :class="[
                                'px-3 py-1 text-xs rounded-full font-medium transition-colors',
                                student.status === 'present' 
                                  ? 'bg-green-100 text-green-800 ring-2 ring-green-500' 
                                  : 'bg-gray-100 text-gray-600 hover:bg-green-50'
                              ]"
                            >
                              ✓ Présent
                            </button>
                            <button
                              type="button"
                              @click="setStudentStatus(student, 'late')"
                              :class="[
                                'px-3 py-1 text-xs rounded-full font-medium transition-colors',
                                student.status === 'late' 
                                  ? 'bg-orange-100 text-orange-800 ring-2 ring-orange-500' 
                                  : 'bg-gray-100 text-gray-600 hover:bg-orange-50'
                              ]"
                            >
                              ⏱ Retard
                            </button>
                            <button
                              type="button"
                              @click="setStudentStatus(student, 'absent')"
                              :class="[
                                'px-3 py-1 text-xs rounded-full font-medium transition-colors',
                                student.status === 'absent' 
                                  ? 'bg-red-100 text-red-800 ring-2 ring-red-500' 
                                  : 'bg-gray-100 text-gray-600 hover:bg-red-50'
                              ]"
                            >
                              ✗ Absent
                            </button>
                          </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                          <input
                            v-model="student.note"
                            type="text"
                            placeholder="Ajouter une note..."
                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            @input="updateNote(student, $event.target.value)"
                          />
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Boutons d'action -->
              <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                  <span v-if="hasChanges" class="text-orange-600 font-medium">
                    ⚠️ Vous avez des modifications non enregistrées
                  </span>
                </div>
                <div class="flex space-x-3">
                  <Link
                    :href="route('teacher.courses.sessions.attendances.index', course.id)"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Annuler
                  </Link>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                  >
                    <span v-if="form.processing">Enregistrement...</span>
                    <span v-else>Valider la présence</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  course: Object,
  students: Array,
})

// Initialiser les étudiants avec le statut "présent" par défaut
const students = ref(props.students.map(student => ({
  ...student,
  status: 'present',
  note: '',
})))

const form = useForm({
  date: new Date().toISOString().split('T')[0],
  topic: '',
  duration_minutes: 120,
  attendances: computed(() => students.value.map(student => ({
    user_id: student.id,
    status: student.status,
    note: student.note || null,
  }))),
})

// Computed properties pour les statistiques
const presentCount = computed(() => 
  students.value.filter(s => s.status === 'present').length
)

const lateCount = computed(() => 
  students.value.filter(s => s.status === 'late').length
)

const absentCount = computed(() => 
  students.value.filter(s => s.status === 'absent').length
)

const hasChanges = computed(() => {
  return JSON.stringify(form.attendances) !== JSON.stringify(
    props.students.map(s => ({ user_id: s.id, status: 'present', note: null }))
  )
})

// Méthodes
const setStudentStatus = (student, status) => {
  student.status = status
}

const setAllStatus = (status) => {
  students.value.forEach(student => {
    student.status = status
  })
}

const updateNote = (student, note) => {
  student.note = note
}

const submit = () => {
  form.post(route('teacher.courses.sessions.attendances.store', props.course.id), {
    onSuccess: () => {
      // Réinitialiser le formulaire si nécessaire
    },
  })
}
</script>
