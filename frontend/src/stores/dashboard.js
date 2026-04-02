import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/boot/axios'

export const useDashboardStore = defineStore('dashboard', () => {
  const metrics = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchMetrics() {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/dashboard/metrics')
      metrics.value = data
    } catch {
      error.value = 'Erro ao carregar métricas.'
    } finally {
      loading.value = false
    }
  }

  return { metrics, loading, error, fetchMetrics }
})
