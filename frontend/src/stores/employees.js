import { defineStore } from "pinia";
import { ref } from "vue";
import { api } from "src/boot/axios";

export const useEmployeesStore = defineStore("employees", () => {
  const employees = ref([]);
  const loading = ref(false);

  async function fetchEmployees() {
    loading.value = true;
    try {
      const { data } = await api.get("/employees");
      employees.value = data;
    } finally {
      loading.value = false;
    }
  }

  async function createEmployee(payload) {
    const { data } = await api.post("/employees", payload);
    employees.value.push(data);
    return data;
  }

  async function updateEmployee(id, payload) {
    const { data } = await api.put(`/employees/${id}`, payload);
    const idx = employees.value.findIndex((e) => e.id === id);
    if (idx !== -1) employees.value[idx] = data;
    return data;
  }

  async function deleteEmployee(id) {
    await api.delete(`/employees/${id}`);
    employees.value = employees.value.filter((e) => e.id !== id);
  }

  async function syncServices(id, serviceIds) {
    const { data } = await api.post(`/employees/${id}/services`, { service_ids: serviceIds });
    const idx = employees.value.findIndex((e) => e.id === id);
    if (idx !== -1) employees.value[idx] = data;
    return data;
  }

  return { employees, loading, fetchEmployees, createEmployee, updateEmployee, deleteEmployee, syncServices };
});
