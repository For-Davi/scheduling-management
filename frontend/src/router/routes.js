const routes = [
  {
    path: "/",
    component: () => import("src/pages/Landing.vue"),
    name: "landing",
  },
  {
    path: "/auth",
    component: () => import("src/layouts/AuthLayout.vue"),
    children: [
      { path: "login", component: () => import("src/pages/auth/Login.vue"), name: "login" },
      { path: "register", component: () => import("src/pages/auth/Register.vue"), name: "register" },
      { path: "forgot-password", component: () => import("src/pages/auth/ForgotPassword.vue"), name: "forgot-password" },
      { path: "reset-password", component: () => import("src/pages/auth/ResetPassword.vue"), name: "reset-password" },
    ],
  },
  {
    path: "/admin",
    component: () => import("src/layouts/AdminLayout.vue"),
    meta: { requiresAuth: true, requiresRole: "admin" },
    children: [
      { path: "", redirect: { name: "admin-dashboard" } },
      { path: "dashboard", component: () => import("src/pages/admin/Dashboard.vue"), name: "admin-dashboard" },
      { path: "services",  component: () => import("src/pages/admin/Services.vue"),  name: "admin-services"  },
      { path: "employees", component: () => import("src/pages/admin/Employees.vue"), name: "admin-employees" },
      { path: "calendar",  component: () => import("src/pages/admin/Calendar.vue"),  name: "admin-calendar"  },
      { path: "billing",   component: () => import("src/pages/admin/Billing.vue"),   name: "admin-billing"   },
    ],
  },
  {
    path: "/my-calendar",
    component: () => import("src/layouts/EmployeeLayout.vue"),
    meta: { requiresAuth: true, requiresRole: "employee" },
    children: [
      { path: "", component: () => import("src/pages/employee/MyCalendar.vue"), name: "employee-calendar" },
    ],
  },
  {
    path: "/:slug",
    component: () => import("src/pages/public/Booking.vue"),
    name: "booking",
  },
  {
    path: "/:catchAll(.*)*",
    component: () => import("src/pages/ErrorNotFound.vue"),
    name: "not-found",
  },
];

export default routes;
