import { defineStore } from "pinia";

export const useNavigationStore = defineStore("navigation", {
  state: () => ({
    activeProgram: "trms",
    sidebarItems: [
      {
        id: "trms",
        name: "TRMS",
        icon: "bi-music-note-beamed",
        description: "The Resonanz Music Studio",
      },
      { id: "bms", name: "BMS", icon: "bi-people-fill", description: "Batavia Madrigal Singers" },
      { id: "jco", name: "JCO", icon: "bi-vinyl-fill", description: "Jakarta Concert Orchestra" },
      {
        id: "trcc",
        name: "TRCC",
        icon: "bi-trophy-fill",
        description: "The Resonanz Children Choir",
      },
    ],
    navItems: {
      trms: [
        { path: "/trms/home", label: "Home", icon: "bi-house-door" },
        { path: "/trms/courses-fees", label: "Courses & Fee", icon: "bi-book" },
        { path: "/trms/facilitation", label: "Facilitation", icon: "bi-building" },
        { path: "/trms/schedules", label: "Schedules", icon: "bi-calendar3" },
        { path: "/trms/teachers", label: "Teachers", icon: "bi-person-workspace" },
        {
          label: "Concert",
          icon: "bi-ticket-perforated",
          children: [
            { path: "/trms/concert/select", label: "Registration", icon: "bi-person-plus" },
            {
              path: "/trms/concert/audiences",
              label: "Audiences",
              icon: "bi-people",
              roles: ["admin", "manager"],
            },
            {
              path: "/trms/concert/scan",
              label: "Scan",
              icon: "bi bi-qr-code-scan",
              roles: ["admin", "manager"],
            },
          ],
        },
        { path: "/trms/news", label: "News", icon: "bi-newspaper" },
        { path: "/trms/contact", label: "Contact", icon: "bi-envelope" },
      ],
      bms: [
        { path: "/bms/home", label: "Home", icon: "bi-house-door" },
        { path: "/bms/events", label: "Events", icon: "bi-calendar-event" },
        { path: "/bms/members", label: "Members", icon: "bi-people" },
        { path: "/bms/attendance", label: "Attendance", icon: "bi-clipboard-check" },
        { path: "/bms/about-us", label: "About Us", icon: "bi-info-circle" },
      ],
      jco: [
        { path: "/jco/home", label: "Home", icon: "bi-house-door" },
        {
          label: "Orchestra",
          icon: "bi-music-note-list",
          children: [
            { path: "/jco/orchestra/profile", label: "Profile", icon: "bi-person-badge" },
            { path: "/jco/orchestra/members", label: "Members", icon: "bi-people" },
          ],
        },
        { path: "/jco/concert", label: "Concert", icon: "bi-music-player" },
        { path: "/jco/gallery", label: "Gallery", icon: "bi-images" },
        { path: "/jco/about-us", label: "About Us", icon: "bi-info-circle" },
        { path: "/jco/contact", label: "Contact", icon: "bi-envelope" },
      ],
      trcc: [
        { path: "/trcc/home", label: "Home", icon: "bi-house-door" },
        { path: "/trcc/achievements", label: "Achievements", icon: "bi-star" },
        { path: "/trcc/testimonial", label: "Testimonial", icon: "bi-chat-quote" },
        { path: "/trcc/about-us", label: "About Us", icon: "bi-info-circle" },
        { path: "/trcc/contact", label: "Contact", icon: "bi-envelope" },
      ],
    },
  }),
  getters: {
    currentNavItems: (state) => state.navItems[state.activeProgram],
    activeProgramInfo: (state) =>
      state.sidebarItems.find((item) => item.id === state.activeProgram),
  },
  actions: {
    setActiveProgram(program) {
      this.activeProgram = program;
    },
  },
});
