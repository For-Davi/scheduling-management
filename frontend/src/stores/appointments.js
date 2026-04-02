import { defineStore } from "pinia"
import { ref } from "vue"
import { api } from "src/boot/axios"

export const useAppointmentsStore = defineStore("appointments", () => {
  const appointments = ref([])
  const loading = ref(false)

  const myAppointments = ref([])
  const myLoading = ref(false)

  async function fetchAppointments(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get("/appointments", { params })
      appointments.value = data
    } finally {
      loading.value = false
    }
  }

  async function fetchMyAppointments(params = {}) {
    myLoading.value = true
    try {
      const { data } = await api.get("/appointments/my", { params })
      myAppointments.value = data
    } finally {
      myLoading.value = false
    }
  }

  async function updateStatus(id, status) {
    const { data } = await api.patch(`/appointments/${id}/status`, { status })
    const idx = appointments.value.findIndex((a) => a.id === id)
    if (idx !== -1) appointments.value[idx] = data
    const myIdx = myAppointments.value.findIndex((a) => a.id === id)
    if (myIdx !== -1) myAppointments.value[myIdx] = data
    return data
  }

  return {
    appointments,
    loading,
    myAppointments,
    myLoading,
    fetchAppointments,
    fetchMyAppointments,
    updateStatus,
  }
})
