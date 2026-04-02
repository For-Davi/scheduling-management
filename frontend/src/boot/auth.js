import { boot } from "quasar/wrappers";
import { useAuthStore } from "src/stores/auth";

export default boot(({ router }) => {
  router.beforeEach((to, _from, next) => {
    let auth;
    try {
      auth = useAuthStore();
    } catch {
      // Pinia ainda não está ativa na navegação inicial — deixa passar
      return next();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      return next({ name: "login" });
    }

    if (to.meta.requiresRole && auth.user?.role !== to.meta.requiresRole) {
      return next({ name: "login" });
    }

    next();
  });
});
