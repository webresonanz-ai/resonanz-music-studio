import { defineStore } from 'pinia'

/**
 * Admin navigation store.
 * Each item maps to an admin-only page. `roles` are the allowed roles;
 * an empty array means visible to every authenticated admin user.
 */
export const useNavigationStore = defineStore('navigation', {
  state: () => ({
    sections: [
      {
        id: 'trms',
        name: 'TRMS',
        icon: 'bi-music-note-beamed',
        items: [
          {
            path: '/trms/manager',
            label: 'Manager Dashboard',
            icon: 'bi-speedometer2',
            roles: ['admin', 'manager'],
          },
          {
            path: '/trms/concert/audiences',
            label: 'Audiences',
            icon: 'bi-people',
            roles: ['admin', 'manager', 'manager_concert'],
          },
          {
            path: '/trms/concert/invitation-reg',
            label: 'Invitation Registration',
            icon: 'bi-person-plus',
            roles: ['admin', 'manager', 'manager_concert'],
          },
          {
            path: '/trms/concert/scan',
            label: 'Scan',
            icon: 'bi-qr-code-scan',
            roles: ['admin', 'manager', 'manager_concert'],
          },
        ],
      },
      {
        id: 'bms',
        name: 'BMS',
        icon: 'bi-people-fill',
        items: [
          {
            path: '/bms/manager',
            label: 'Manager Dashboard',
            icon: 'bi-speedometer2',
            roles: ['admin', 'singers_manager'],
          },
          {
            path: '/bms/attendance',
            label: 'Attendance',
            icon: 'bi-calendar-check',
            roles: ['admin', 'manager', 'singers_manager'],
          },
        ],
      },
      {
        id: 'library',
        name: 'Library',
        icon: 'bi-collection',
        items: [
          {
            path: '/library/composer-dashboard',
            label: 'Composer Dashboard',
            icon: 'bi-music-note',
            roles: ['composer', 'arranger'],
          },
          {
            path: '/library/orders-dashboard',
            label: 'Orders Dashboard',
            icon: 'bi-receipt',
            roles: ['admin', 'manager', 'manager_scores'],
          },
        ],
      },
    ],
  }),
})