import { defineStore } from "pinia";
import { ref } from "vue";
import { api } from "src/boot/axios";

export const useServicesStore = defineStore("services", () => {
  const services = ref([]);
  const loading = ref(false);

  async function fetchServices() {
    loading.value = true;
    try {
      const { data } = await api.get("/services");
      services.value = data;
    } finally {
      loading.value = false;
    }
  }

  async function createService(payload) {
    const { data } = await api.post("/services", payload);
    services.value.push(data);
    return data;
  }

  async function updateService(id, payload) {
    const { data } = await api.put(`/services/${id}`, payload);
    const idx = services.value.findIndex((s) => s.id === id);
    if (idx !== -1) services.value[idx] = data;
    return data;
  }

  async function deleteService(id) {
    await api.delete(`/services/${id}`);
    services.value = services.value.filter((s) => s.id !== id);
  }

  return { services, loading, fetchServices, createService, updateService, deleteService };
});
