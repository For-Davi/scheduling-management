import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

// Instância axios isolada — sem token Bearer (fluxo público)
const publicApi = axios.create({ baseURL: '/api' })

export const useBookingStore = defineStore('booking', () => {
  // Dados da empresa carregados uma vez
  const company = ref(null)
  const loading = ref(false)
  const loadError = ref(null)

  // Progresso do stepper (1–5)
  const step = ref(1)

  // Seleções do fluxo
  const selectedService = ref(null)
  const selectedEmployee = ref(null)
  const selectedDate = ref(null)   // formato q-date: YYYY/MM/DD
  const selectedSlot = ref(null)   // { starts_at: 'HH:MM', ends_at: 'HH:MM' }

  // Disponibilidade
  const availableSlots = ref([])
  const loadingSlots = ref(false)

  // Dados do cliente
  const clientData = ref({ name: '', email: '', phone: '' })

  // LGPD
  const lgpdConsent = ref(false)

  // Estado do submit
  const submitting = ref(false)
  const submitError = ref(null)
  const fieldErrors = ref({})
  const confirmedAppointment = ref(null)

  // Funcionários que realizam o serviço selecionado
  const filteredEmployees = computed(() => {
    if (!company.value || !selectedService.value) return []
    return company.value.employees.filter((emp) =>
      emp.services.some((s) => s.id === selectedService.value.id)
    )
  })

  // -------------------------------------------------------------------------

  async function loadCompany(slug) {
    loading.value = true
    loadError.value = null
    try {
      const { data } = await publicApi.get(`/public/${slug}`)
      company.value = data
    } catch (err) {
      loadError.value =
        err.response?.status === 404
          ? 'Empresa não encontrada.'
          : 'Erro ao carregar dados. Tente novamente.'
    } finally {
      loading.value = false
    }
  }

  function selectService(service) {
    selectedService.value = service
    // Limpa seleções posteriores
    selectedEmployee.value = null
    selectedDate.value = null
    selectedSlot.value = null
    availableSlots.value = []
  }

  function selectEmployee(emp) {
    selectedEmployee.value = emp
    selectedDate.value = null
    selectedSlot.value = null
    availableSlots.value = []
  }

  // Retorna true para datas a partir de hoje (q-date formato YYYY/MM/DD)
  function isDateAllowed(dateStr) {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const d = new Date(dateStr.replace(/\//g, '-') + 'T00:00:00')
    return d >= today
  }

  async function fetchSlots(date) {
    if (!date || !selectedEmployee.value || !selectedService.value || !company.value) return
    selectedSlot.value = null
    loadingSlots.value = true
    availableSlots.value = []
    try {
      const { data } = await publicApi.get(`/public/${company.value.slug}/availability`, {
        params: {
          employee_id: selectedEmployee.value.id,
          service_id: selectedService.value.id,
          date: date.replace(/\//g, '-'), // YYYY-MM-DD
        },
      })
      availableSlots.value = data.slots ?? []
    } catch {
      availableSlots.value = []
    } finally {
      loadingSlots.value = false
    }
  }

  async function submit(slug) {
    submitting.value = true
    submitError.value = null
    fieldErrors.value = {}

    const startsAt = `${selectedDate.value.replace(/\//g, '-')} ${selectedSlot.value.starts_at}`

    try {
      const { data } = await publicApi.post(`/public/${slug}/appointments`, {
        employee_id:  selectedEmployee.value.id,
        service_id:   selectedService.value.id,
        starts_at:    startsAt,
        client_name:  clientData.value.name,
        client_email: clientData.value.email,
        client_phone: clientData.value.phone || null,
        lgpd_consent: lgpdConsent.value,
      })
      confirmedAppointment.value = data
    } catch (err) {
      const status = err.response?.status
      if (status === 422) {
        const errors = err.response.data.errors ?? {}
        Object.keys(errors).forEach((k) => {
          fieldErrors.value[k] = errors[k][0]
        })
        submitError.value = err.response.data.message
        // Volta para a etapa de dados se o erro for nos campos do cliente
        if (errors.client_name || errors.client_email) step.value = 4
      } else if (status === 409) {
        submitError.value = err.response.data.message
        // Volta para seleção de horário
        selectedSlot.value = null
        step.value = 3
      } else {
        submitError.value = 'Erro ao realizar agendamento. Tente novamente.'
      }
    } finally {
      submitting.value = false
    }
  }

  return {
    company, loading, loadError,
    step,
    selectedService, selectedEmployee, selectedDate, selectedSlot,
    availableSlots, loadingSlots, filteredEmployees,
    clientData, lgpdConsent,
    submitting, submitError, fieldErrors, confirmedAppointment,
    loadCompany, selectService, selectEmployee, isDateAllowed, fetchSlots, submit,
  }
})
