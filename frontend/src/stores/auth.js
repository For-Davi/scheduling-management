import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { api } from "src/boot/axios";

export const useAuthStore = defineStore("auth", () => {
  const token = ref(localStorage.getItem("token") || null);
  const user = ref(null);

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.role === "admin");
  const isEmployee = computed(() => user.value?.role === "employee");

  async function register(payload) {
    const { data } = await api.post("/auth/register", payload);
    token.value = data.token;
    user.value = data.user;
    localStorage.setItem("token", data.token);
  }

  async function login(credentials) {
    const { data } = await api.post("/auth/login", credentials);
    token.value = data.token;
    user.value = data.user;
    localStorage.setItem("token", data.token);
  }

  async function fetchUser() {
    if (!token.value) return;
    const { data } = await api.get("/auth/me");
    user.value = data;
  }

  async function logout() {
    await api.post("/auth/logout");
    token.value = null;
    user.value = null;
    localStorage.removeItem("token");
  }

  async function forgotPassword(email) {
    await api.post("/auth/forgot-password", { email });
  }

  async function resetPassword(payload) {
    await api.post("/auth/reset-password", payload);
  }

  async function updateProfile(payload) {
    const { data } = await api.put("/auth/profile", payload);
    user.value = { ...user.value, name: data.user.name, email: data.user.email };
    return data;
  }

  async function updateCompany(payload) {
    const { data } = await api.put("/auth/company", payload);
    user.value = {
      ...user.value,
      company: { ...user.value.company, name: data.company.name, slug: data.company.slug },
    };
    return data;
  }

  return { token, user, isAuthenticated, isAdmin, isEmployee, register, login, fetchUser, logout, forgotPassword, resetPassword, updateProfile, updateCompany };
});
