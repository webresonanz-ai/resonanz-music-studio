import { defineStore } from 'pinia'

/**
 * Global banner store.
 * Any page/component can call setBanner(url) to set a full-page
 * silhouette background on the app shell, and clearBanner() to remove it.
 */
export const useBannerStore = defineStore('banner', {
  state: () => ({
    url: ''
  }),
  actions: {
    setBanner(url) {
      this.url = url || ''
    },
    clearBanner() {
      this.url = ''
    }
  }
})
