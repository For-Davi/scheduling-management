import { boot } from "quasar/wrappers";
import { getActivePinia } from "pinia";
import { useAuthStore } from "src/stores/auth";

export default boot(({ router }) => {
  router.beforeEach((to, _from, next) => {
    // Pinia ainda não está ativa na navegação inicial — deixa passar
    if (!getActivePinia()) return next();

    const auth = useAuthStore();

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      return next({ name: "login" });
    }

    if (to.meta.requiresRole && auth.user?.role !== to.meta.requiresRole) {
      return next({ name: "login" });
    }

    next();
  });
});
