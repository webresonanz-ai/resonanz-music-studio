<template>
  <Transition name="notif-enter">
    <div v-if="visible" class="notification-prompt">
      <button class="notification-prompt__close" @click="$emit('dismiss')" aria-label="Close">
        &times;
      </button>
      <div class="notification-prompt__icon">&#128276;</div>
      <p class="notification-prompt__title">Stay Updated</p>
      <p class="notification-prompt__desc">
        Get notified about upcoming concerts, events, and news from The Resonanz.
      </p>
      <div class="notification-prompt__actions">
        <button class="notif-btn notif-btn--later" @click="$emit('dismiss')">
          Not now
        </button>
        <button class="notif-btn notif-btn--allow" @click="$emit('allow')">
          Allow
        </button>
      </div>
    </div>
  </Transition>
</template>

<script>
export default {
  name: 'NotificationPrompt',
  props: {
    visible: { type: Boolean, default: false }
  },
  emits: ['allow', 'dismiss']
}
</script>

<style scoped>
.notification-prompt {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 9998;
  width: 290px;
  padding: 1.4rem 1.5rem 1.2rem;
  border-radius: 12px;
  border: 1px solid rgba(200, 164, 93, 0.18);
  background: linear-gradient(160deg, #1a2030 0%, #0f111b 100%);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  text-align: center;
}

.notification-prompt__close {
  position: absolute;
  top: 0.5rem;
  right: 0.75rem;
  border: none;
  background: none;
  color: rgba(234, 220, 194, 0.3);
  font-size: 1.3rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  transition: color 0.2s;
}

.notification-prompt__close:hover {
  color: rgba(234, 220, 194, 0.65);
}

.notification-prompt__icon {
  font-size: 2.4rem;
  margin-bottom: 0.4rem;
  filter: drop-shadow(0 0 14px rgba(200, 164, 93, 0.12));
}

.notification-prompt__title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-weight: 600;
  font-size: 1.3rem;
  color: var(--gold-color, #c8a45d);
  margin: 0 0 0.35rem;
}

.notification-prompt__desc {
  font-size: 0.82rem;
  color: rgba(234, 220, 194, 0.58);
  line-height: 1.45;
  margin: 0 0 1rem;
}

.notification-prompt__actions {
  display: flex;
  gap: 0.5rem;
}

.notif-btn {
  flex: 1;
  padding: 0.45rem 0.75rem;
  border: 1px solid transparent;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.notif-btn--allow {
  color: #17130a;
  background: linear-gradient(180deg, #d6b66c 0%, var(--gold-color, #c8a45d) 100%);
  box-shadow: 0 4px 16px rgba(200, 164, 93, 0.2);
}

.notif-btn--allow:hover {
  background: linear-gradient(180deg, #e1c47f 0%, #b99245 100%);
  transform: translateY(-1px);
}

.notif-btn--later {
  color: rgba(234, 220, 194, 0.5);
  background: transparent;
  border-color: rgba(234, 220, 194, 0.1);
}

.notif-btn--later:hover {
  color: rgba(234, 220, 194, 0.75);
  border-color: rgba(234, 220, 194, 0.2);
}

.notif-enter-enter-active {
  transition: transform 0.45s ease, opacity 0.45s ease;
}

.notif-enter-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.notif-enter-enter-from {
  transform: translateY(20px) scale(0.95);
  opacity: 0;
}

.notif-enter-leave-to {
  transform: translateY(10px) scale(0.96);
  opacity: 0;
}

@media (max-width: 576px) {
  .notification-prompt {
    right: 0.75rem;
    left: 0.75rem;
    bottom: 0.75rem;
    width: auto;
  }
}

@media (prefers-reduced-motion: reduce) {
  .notif-enter-enter-active,
  .notif-enter-leave-active {
    transition: none;
  }

  .notif-enter-enter-from,
  .notif-enter-leave-to {
    transform: none;
    opacity: 0;
  }
}
</style>
