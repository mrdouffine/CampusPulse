<template>
  <div v-if="showAlert" :class="alertClasses">
    <div class="p-4">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <!-- Icône d'alerte -->
          <svg
            v-if="alertLevel === 'critical'"
            class="h-6 w-6 text-red-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
            />
          </svg>
          <svg
            v-else-if="alertLevel === 'warning'"
            class="h-6 w-6 text-orange-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
            />
          </svg>
          <svg
            v-else
            class="h-6 w-6 text-yellow-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <div class="ml-3 flex-1">
          <h3 :class="titleClasses">
            {{ alertTitle }}
          </h3>
          <div class="mt-2 text-sm">
            <p :class="textClasses">
              {{ alertMessage }}
            </p>
          </div>
          <div class="mt-4">
            <div class="flex items-center space-x-4">
              <!-- Taux de présence visuel -->
              <div class="flex items-center">
                <div class="relative">
                  <svg class="w-16 h-16">
                    <circle
                      cx="32"
                      cy="32"
                      r="28"
                      stroke="#e5e7eb"
                      stroke-width="4"
                      fill="none"
                    />
                    <circle
                      cx="32"
                      cy="32"
                      r="28"
                      :stroke="progressColor"
                      stroke-width="4"
                      fill="none"
                      :stroke-dasharray="circumference"
                      :stroke-dashoffset="circumference - (attendanceRate / 100) * circumference"
                      transform="rotate(-90 32 32)"
                      class="transition-all duration-500"
                    />
                  </svg>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-sm font-bold" :class="progressTextColor">
                      {{ Math.round(attendanceRate) }}%
                    </span>
                  </div>
                </div>
              </div>

              <!-- Statistiques détaillées -->
              <div class="flex-1">
                <div class="grid grid-cols-3 gap-4 text-center">
                  <div>
                    <p class="text-2xl font-bold" :class="presentColor">
                      {{ presentCount }}
                    </p>
                    <p class="text-xs text-gray-500">Présents</p>
                  </div>
                  <div>
                    <p class="text-2xl font-bold" :class="lateColor">
                      {{ lateCount }}
                    </p>
                    <p class="text-xs text-gray-500">Retards</p>
                  </div>
                  <div>
                    <p class="text-2xl font-bold" :class="absentColor">
                      {{ absentCount }}
                    </p>
                    <p class="text-xs text-gray-500">Absences</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="ml-auto pl-3">
          <div class="-mx-1.5 -my-1.5">
            <button
              @click="dismissAlert"
              type="button"
              :class="dismissButtonClasses"
            >
              <span class="sr-only">Ignorer</span>
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions recommandées -->
    <div v-if="showRecommendations" class="px-4 pb-4 border-t" :class="borderColor">
      <div class="mt-3">
        <h4 class="text-sm font-medium" :class="recommendationTitleColor">
          Recommandations :
        </h4>
        <ul class="mt-2 text-sm space-y-1" :class="recommendationTextColor">
          <li v-if="alertLevel === 'critical'" class="flex items-start">
            <svg class="h-4 w-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Contactez immédiatement votre professeur pour discuter de votre situation
          </li>
          <li v-if="alertLevel === 'critical'" class="flex items-start">
            <svg class="h-4 w-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Prévoyez un rattrapage pour les séances manquées
          </li>
          <li v-if="alertLevel === 'warning'" class="flex items-start">
            <svg class="h-4 w-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Assurez-vous d'être présent aux prochaines séances
          </li>
          <li class="flex items-start">
            <svg class="h-4 w-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Consultez votre planning pour éviter les conflits d'horaires
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  attendanceRate: {
    type: Number,
    required: true,
  },
  presentCount: {
    type: Number,
    required: true,
  },
  lateCount: {
    type: Number,
    required: true,
  },
  absentCount: {
    type: Number,
    required: true,
  },
  threshold: {
    type: Number,
    default: 30,
  },
  showRecommendations: {
    type: Boolean,
    default: true,
  },
  dismissible: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['dismissed'])

const isDismissed = ref(false)

const absenceRate = computed(() => 100 - props.attendanceRate)
const alertLevel = computed(() => {
  if (absenceRate.value >= props.threshold) return 'critical'
  if (absenceRate.value >= props.threshold - 10) return 'warning'
  return 'notice'
})

const showAlert = computed(() => {
  if (isDismissed.value) return false
  return alertLevel.value !== 'none'
})

const circumference = 2 * Math.PI * 28

// Classes dynamiques selon le niveau d'alerte
const alertClasses = computed(() => {
  const base = 'rounded-lg shadow-lg border'
  switch (alertLevel.value) {
    case 'critical':
      return `${base} bg-red-50 border-red-200`
    case 'warning':
      return `${base} bg-orange-50 border-orange-200`
    default:
      return `${base} bg-yellow-50 border-yellow-200`
  }
})

const titleClasses = computed(() => {
  const base = 'text-sm font-medium'
  switch (alertLevel.value) {
    case 'critical':
      return `${base} text-red-800`
    case 'warning':
      return `${base} text-orange-800`
    default:
      return `${base} text-yellow-800`
  }
})

const textClasses = computed(() => {
  const base = 'text-sm'
  switch (alertLevel.value) {
    case 'critical':
      return `${base} text-red-700`
    case 'warning':
      return `${base} text-orange-700`
    default:
      return `${base} text-yellow-700`
  }
})

const dismissButtonClasses = computed(() => {
  const base = 'inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2'
  switch (alertLevel.value) {
    case 'critical':
      return `${base} bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50`
    case 'warning':
      return `${base} bg-orange-50 text-orange-500 hover:bg-orange-100 focus:ring-orange-600 focus:ring-offset-orange-50`
    default:
      return `${base} bg-yellow-50 text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600 focus:ring-offset-yellow-50`
  }
})

const borderColor = computed(() => {
  switch (alertLevel.value) {
    case 'critical': return 'border-red-200'
    case 'warning': return 'border-orange-200'
    default: return 'border-yellow-200'
  }
})

const recommendationTitleColor = computed(() => {
  switch (alertLevel.value) {
    case 'critical': return 'text-red-800'
    case 'warning': return 'text-orange-800'
    default: return 'text-yellow-800'
  }
})

const recommendationTextColor = computed(() => {
  switch (alertLevel.value) {
    case 'critical': return 'text-red-700'
    case 'warning': return 'text-orange-700'
    default: return 'text-yellow-700'
  }
})

// Couleurs pour les indicateurs
const progressColor = computed(() => {
  if (props.attendanceRate >= 90) return '#10b981' // green-500
  if (props.attendanceRate >= 75) return '#f59e0b' // amber-500
  return '#ef4444' // red-500
})

const progressTextColor = computed(() => {
  if (props.attendanceRate >= 90) return 'text-green-600'
  if (props.attendanceRate >= 75) return 'text-amber-600'
  return 'text-red-600'
})

const presentColor = computed(() => 'text-green-600')
const lateColor = computed(() => 'text-orange-600')
const absentColor = computed(() => 'text-red-600')

// Messages d'alerte
const alertTitle = computed(() => {
  switch (alertLevel.value) {
    case 'critical':
      return '⚠️ Alerte critique : Taux d\'absence élevé'
    case 'warning':
      return '⚠️ Attention : Taux d\'absence à surveiller'
    default:
      return 'ℹ️ Information : Taux d\'absence à surveiller'
  }
})

const alertMessage = computed(() => {
  switch (alertLevel.value) {
    case 'critical':
      return `Votre taux d'absence est de ${absenceRate.value.toFixed(1)}%, ce qui dépasse le seuil critique de ${props.threshold}%. Votre participation au cours est compromise.`
    case 'warning':
      return `Votre taux d'absence est de ${absenceRate.value.toFixed(1)}%, ce qui approche le seuil d'alerte de ${props.threshold}%. Veillez à améliorer votre assiduité.`
    default:
      return `Votre taux d'absence est de ${absenceRate.value.toFixed(1)}%. Continuez vos efforts pour maintenir une bonne présence.`
  }
})

const dismissAlert = () => {
  if (props.dismissible) {
    isDismissed.value = true
    emit('dismissed')
  }
}
</script>
