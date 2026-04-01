import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { api } from "src/boot/axios";

export const useAuthStore = defineStore("auth", () => {
  const token = ref(localStorage.getItem("token") || null);
  const user = ref(null);

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.role === "admin");
  const isEmployee = computed(() => user.value?.role === "employee");

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

  return { token, user, isAuthenticated, isAdmin, isEmployee, login, fetchUser, logout };
});
