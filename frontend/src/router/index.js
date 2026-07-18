import { createRouter, createWebHistory } from "vue-router";

const routes = [
  {
    path: "/",
    redirect: "/trms/home",
  },

  // TRMS
  {
    path: "/trms",
    children: [
      { path: "", redirect: "/trms/home" },
      { path: "home",         component: () => import("../views/trms/TRMSHome.vue") },
      { path: "courses-fees", component: () => import("../views/trms/CoursesFees.vue") },
      { path: "facilitation", component: () => import("../views/trms/Facilitation.vue") },
      { path: "schedules",    component: () => import("../views/trms/Schedules.vue") },
      { path: "teachers",     component: () => import("../views/trms/Teachers.vue") },
      { path: "news",         component: () => import("../views/trms/News.vue") },
      { path: "contact",      component: () => import("../views/trms/Contact.vue") },
      { path: "concert/select",    component: () => import("../views/trms/ConcertSelection.vue") },
      { path: "concert-reg",       component: () => import("../views/trms/ConcertRegistration.vue"), meta: { hideShellNav: true } },
      {
        path: "concert/audiences",
        component: () => import("../views/trms/ConcertAudiences.vue"),
        meta: { roles: ["admin", "manager"] },
      },
      {
        path: "concert/invitation-reg",
        component: () => import("../views/trms/InvitationRegistration.vue"),
        meta: { roles: ["admin", "manager"] },
      },
      {
        path: "concert/scan",
        component: () => import("../views/trms/ConcertScan.vue"),
        meta: { roles: ["admin", "manager"] },
      },
      {
        path: "concert/ticket/:id",
        component: () => import("../views/trms/TicketPreview.vue"),
        meta: { roles: ["admin", "manager"] },
      },
    ],
  },

  // BMS
  {
    path: "/bms",
    children: [
      { path: "", redirect: "/bms/home" },
      { path: "home",     component: () => import("../views/bms/BMSHome.vue") },
      { path: "events",   component: () => import("../views/bms/Events.vue") },
      { path: "members",  component: () => import("../views/bms/Members.vue") },
      {
        path: "attendance",
        component: () => import("../views/bms/Attendance.vue"),
        meta: { roles: ["admin", "manager", "singers_manager"] },
      },
      { path: "about-us", component: () => import("../views/bms/AboutUs.vue") },
    ],
  },

  // JCO
  {
    path: "/jco",
    children: [
      { path: "", redirect: "/jco/home" },
      { path: "home",              component: () => import("../views/jco/JCOHome.vue") },
      { path: "orchestra",         component: () => import("../views/jco/Orchestra.vue") },
      { path: "orchestra/profile", component: () => import("../views/jco/OrchestraProfile.vue") },
      { path: "orchestra/members", component: () => import("../views/jco/OrchestraMembers.vue") },
      { path: "concert",           component: () => import("../views/jco/Concert.vue") },
      { path: "gallery",           component: () => import("../views/jco/Gallery.vue") },
      { path: "about-us",          component: () => import("../views/jco/AboutUs.vue") },
      { path: "contact",           component: () => import("../views/jco/Contact.vue") },
    ],
  },

  // TRCC
  {
    path: "/trcc",
    children: [
      { path: "", redirect: "/trcc/home" },
      { path: "home",         component: () => import("../views/trcc/TRCCHome.vue") },
      { path: "achievements", component: () => import("../views/trcc/Achievements.vue") },
      { path: "testimonial",  component: () => import("../views/trcc/Testimonial.vue") },
      { path: "about-us",     component: () => import("../views/trcc/AboutUs.vue") },
      { path: "contact",      component: () => import("../views/trcc/Contact.vue") },
    ],
  },

  // Library
  {
    path: "/library",
    children: [
      { path: "", redirect: "/library/home" },
      { path: "home",        component: () => import("../views/library/LibraryHome.vue") },
      { path: "sheet-music",         component: () => import("../views/library/SheetMusic.vue") },
      { path: "composer-dashboard",  component: () => import("../views/library/ComposerDashboard.vue"), meta: { roles: ["composer", "arranger"] } },
      { path: "my-orders",           component: () => import("../views/library/MyOrders.vue") },
      {
        path: "orders-dashboard",
        component: () => import("../views/library/OrdersDashboard.vue"),
        meta: { roles: ["admin", "manager", "manager_scores"] },
      },
      { path: "costumes",    component: () => import("../views/library/Costumes.vue") },
    ],
  },

  // Concert Registration (public, standalone)
  {
    path: "/concert-reg/:concertCode",
    component: () => import("../views/trms/ConcertRegistration.vue"),
    meta: { hideShellNav: true },
  },
  {
    path: "/concert-reg/:concertCode/seated",
    component: () => import("../views/trms/SeatedRegistration.vue"),
    meta: { hideShellNav: true },
  },

  // Auth
  {
    path: "/auth",
    name: "Auth",
    component: () => import("../views/Auth.vue"),
  },

  // Profile
  {
    path: "/profile",
    name: "Profile",
    component: () => import("../views/Profile.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// Route guard
router.beforeEach((to, _from) => {
  const requiredRoles = to.meta?.roles;
  if (!requiredRoles) return true;

  const raw = localStorage.getItem("resonanz-user");
  const user = raw ? JSON.parse(raw) : null;
  const userRole = user?.role?.toLowerCase();

  if (!user) {
    return { path: "/auth", query: { redirect: to.fullPath } };
  }

  if (requiredRoles.includes(userRole)) {
    return true;
  }

  return { path: "/bms/home", query: { unauthorized: "1" } };
});

export default router;
