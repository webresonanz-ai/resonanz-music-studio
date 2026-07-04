import { createRouter, createWebHistory } from "vue-router";

// TRMS Views
import TRMSHome from "../views/trms/TRMSHome.vue";
import CoursesFees from "../views/trms/CoursesFees.vue";
import Facilitation from "../views/trms/Facilitation.vue";
import Schedules from "../views/trms/Schedules.vue";
import Teachers from "../views/trms/Teachers.vue";
import TRMSNews from "../views/trms/News.vue";
import TRMSContact from "../views/trms/Contact.vue";
import ConcertSelection from "../views/trms/ConcertSelection.vue";
import ConcertRegistration from "../views/trms/ConcertRegistration.vue";
import ConcertAudiences from "../views/trms/ConcertAudiences.vue";
import ConcertScan from "../views/trms/ConcertScan.vue";
import TicketPreview from "../views/trms/TicketPreview.vue";

// BMS Views
import BMSHome from "../views/bms/BMSHome.vue";
import Events from "../views/bms/Events.vue";
import Members from "../views/bms/Members.vue";
import Attendance from "../views/bms/Attendance.vue";
import BMSAboutUs from "../views/bms/AboutUs.vue";

// JCO Views
import JCOHome from "../views/jco/JCOHome.vue";
import Orchestra from "../views/jco/Orchestra.vue";
import OrchestraProfile from "../views/jco/OrchestraProfile.vue";
import OrchestraMembers from "../views/jco/OrchestraMembers.vue";
import Concert from "../views/jco/Concert.vue";
import Gallery from "../views/jco/Gallery.vue";
import JCOAboutUs from "../views/jco/AboutUs.vue";
import JCOContact from "../views/jco/Contact.vue";

// TRCC Views
import TRCCHome from "../views/trcc/TRCCHome.vue";
import Achievements from "../views/trcc/Achievements.vue";
import Testimonial from "../views/trcc/Testimonial.vue";
import TRCCAboutUs from "../views/trcc/AboutUs.vue";
import TRCCContact from "../views/trcc/Contact.vue";
import Auth from "../views/Auth.vue";

const routes = [
  {
    path: "/",
    redirect: "/trms/home",
  },
  // TRMS Routes
  {
    path: "/trms",
    children: [
      { path: "", redirect: "/trms/home" },
      { path: "home", component: TRMSHome },
      { path: "courses-fees", component: CoursesFees },
      { path: "facilitation", component: Facilitation },
      { path: "schedules", component: Schedules },
      { path: "teachers", component: Teachers },
      { path: "news", component: TRMSNews },
      { path: "contact", component: TRMSContact },
      { path: "concert/select", component: ConcertSelection },
      { path: "concert-reg", component: ConcertRegistration, meta: { hideShellNav: true } },
      {
        path: "concert/audiences",
        component: ConcertAudiences,
        meta: { roles: ["admin", "manager"] },
      },
      { path: "concert/scan", component: ConcertScan, meta: { roles: ["admin", "manager"] } },
      {
        path: "concert/ticket/:id",
        component: TicketPreview,
        meta: { roles: ["admin", "manager"] },
      },
    ],
  },
  // BMS Routes
  {
    path: "/bms",
    children: [
      { path: "", redirect: "/bms/home" },
      { path: "home", component: BMSHome },
      { path: "events", component: Events },
      { path: "members", component: Members },
      {
        path: "attendance",
        component: Attendance,
        meta: { roles: ["admin", "manager", "singers_manager"] },
      },
      { path: "about-us", component: BMSAboutUs },
    ],
  },
  // JCO Routes
  {
    path: "/jco",
    children: [
      { path: "", redirect: "/jco/home" },
      { path: "home", component: JCOHome },
      { path: "orchestra", component: Orchestra },
      { path: "orchestra/profile", component: OrchestraProfile },
      { path: "orchestra/members", component: OrchestraMembers },
      { path: "concert", component: Concert },
      { path: "gallery", component: Gallery },
      { path: "about-us", component: JCOAboutUs },
      { path: "contact", component: JCOContact },
    ],
  },
  // TRCC Routes
  {
    path: "/trcc",
    children: [
      { path: "", redirect: "/trcc/home" },
      { path: "home", component: TRCCHome },
      { path: "achievements", component: Achievements },
      { path: "testimonial", component: Testimonial },
      { path: "about-us", component: TRCCAboutUs },
      { path: "contact", component: TRCCContact },
    ],
  },
  // Concert Registration
  {
    path: "/concert-reg/:concertCode",
    component: ConcertRegistration,
    meta: { hideShellNav: true },
  },
  // Auth Route
  {
    path: "/auth",
    name: "Auth",
    component: Auth,
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// Route guard — redirect unauthorized users away from role-restricted pages
router.beforeEach((to, _from) => {
  const requiredRoles = to.meta?.roles;
  if (!requiredRoles) return true;

  const raw = localStorage.getItem("resonanz-user");
  const user = raw ? JSON.parse(raw) : null;
  const userRole = user?.role?.toLowerCase();

  if (!user) {
    // Not logged in — send to auth, then back here after login
    return { path: "/auth", query: { redirect: to.fullPath } };
  }

  if (requiredRoles.includes(userRole)) {
    return true;
  }

  // Logged in but wrong role — send to their home with a flag
  return { path: "/bms/home", query: { unauthorized: "1" } };
});

export default router;
