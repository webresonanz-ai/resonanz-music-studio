import { defineStore } from "pinia";

export const useNavigationStore = defineStore("navigation", {
  state: () => ({
    activeProgram: "trms",
    standalonePage: null,
    sidebarItems: [
      {
        id: "art-director",
        name: "Avip Priatna",
        icon: "bi-person-badge",
        img: "",
        description: "Biography & Achievements",
      },
      {
        id: "trms",
        name: "TRMS",
        icon: "bi-music-note-beamed",
        img: "/trms_white.webp",
        description: "The Resonanz Music Studio",
      },
      {
        id: "bms",
        name: "BMS",
        icon: "bi-people-fill",
        img: "/bms_white.webp",
        description: "Batavia Madrigal Singers",
      },
      {
        id: "jco",
        name: "JCO",
        icon: "bi-vinyl-fill",
        img: "/jco_white.webp",
        description: "Jakarta Concert Orchestra",
      },
      {
        id: "trcc",
        name: "TRCC",
        icon: "bi-trophy-fill",
        img: "/trcc_white.webp",
        description: "The Resonanz Children Choir",
      },
      {
        id: "library",
        name: "Library",
        icon: "bi-collection",
        img: "",
        description: "Concert, Sheet Music and Costumes",
      },
    ],
    navItems: {
      trms: [
        { path: "/trms/home", label: "Home", icon: "bi-house-door" },
        { path: "/trms/schedules", label: "Schedules", icon: "bi-calendar3" },
        {
          label: "Concert",
          icon: "bi-ticket-perforated",
          children: [
            { path: "/trms/concert/select", label: "Registration", icon: "bi-person-plus" },
            {
              path: "/trms/concert/invitation-reg",
              label: "Invitation Registration",
              icon: "bi-person-plus",
              roles: ["admin", "manager"],
            },
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
        { path: "/trms/courses-fees", label: "Courses & Fee", icon: "bi-book" },
        { path: "/trms/facilitation", label: "Facilitation", icon: "bi-building" },
        { path: "/trms/teachers", label: "Teachers", icon: "bi-person-workspace" },
        { path: "/trms/contact", label: "Contact", icon: "bi-envelope" },
      ],
      bms: [
        { path: "/bms/home", label: "Home", icon: "bi-house-door" },
        { path: "/bms/events", label: "Events", icon: "bi-calendar-event" },
        { path: "/bms/members", label: "Members", icon: "bi-people" },
        {
          path: "/bms/attendance",
          label: "Attendance",
          icon: "bi-clipboard-check",
          roles: ["admin", "singer_manager"],
        },
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
      library: [
        { path: "/library/home", label: "Home", icon: "bi-house-door" },
        {
          label: "Library",
          icon: "bi-collection",
          children: [
            { path: "/library/concert-history", label: "Concert History", icon: "bi-calendar-check" },
            { path: "/library/sheet-music", label: "Sheet Music", icon: "bi-music-note" },
            { path: "/library/costumes", label: "Costumes", icon: "bi-person-badge" },
          ],
        },
      ],
    },
  }),
  getters: {
    currentNavItems: (state) => state.navItems[state.activeProgram] || [],
    activeProgramInfo: (state) =>
      state.sidebarItems.find((item) => item.id === state.activeProgram),
  },
  actions: {
    setActiveProgram(program) {
      this.activeProgram = program;
      this.standalonePage = null;
    },
    setStandalonePage(page) {
      this.standalonePage = page;
    },
  },
});
