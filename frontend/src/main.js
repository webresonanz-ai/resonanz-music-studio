import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";

// Import Bootstrap
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import "bootstrap";

// Import custom styles
import "./assets/styles/custom.css";
import { useAuthStore } from "./stores/auth";

const app = createApp(App);
const pinia = createPinia();

// document.addEventListener("contextmenu", function (e) {
//   e.preventDefault();
// });

app.use(pinia);
app.use(router);

const authStore = useAuthStore(pinia);
if (authStore.token) {
  authStore.fetchMe().catch(() => authStore.logout());
}

app.mount("#app");
