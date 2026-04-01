import { boot } from "quasar/wrappers";
import { useAuthStore } from "src/stores/auth";

export default boot(({ router }) => {
  router.beforeEach((to, _from, next) => {
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
