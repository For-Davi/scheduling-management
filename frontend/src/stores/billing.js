import { defineStore } from "pinia"
import { ref } from "vue"
import { api } from "src/boot/axios"

export const useBillingStore = defineStore("billing", () => {
  const info = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const upgrading = ref(false)

  async function fetchInfo() {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get("/billing")
      info.value = data
    } catch {
      error.value = "Erro ao carregar informações de assinatura."
    } finally {
      loading.value = false
    }
  }

  async function changePlan(plan) {
    upgrading.value = true
    try {
      const { data } = await api.post("/billing/plan", { plan })
      info.value = data
    } finally {
      upgrading.value = false
    }
  }

  return { info, loading, error, upgrading, fetchInfo, changePlan }
})
