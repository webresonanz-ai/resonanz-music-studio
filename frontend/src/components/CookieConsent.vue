<template>
  <Transition name="cookie-slide">
    <div v-if="visible" class="cookie-consent">
      <div class="cookie-consent__container">
        <div class="cookie-consent__body">
          <i class="bi bi-shield-check cookie-consent__icon"></i>
          <p class="cookie-consent__text">
            This site uses essential cookies for authentication and core functionality.
            By continuing, you agree to our use of cookies.
          </p>
        </div>
        <div class="cookie-consent__actions">
          <button class="cookie-consent__btn cookie-consent__btn--decline" @click="$emit('decline')">
            Decline
          </button>
          <button class="cookie-consent__btn cookie-consent__btn--accept" @click="$emit('accept')">
            Accept
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
export default {
  name: 'CookieConsent',
  props: {
    visible: { type: Boolean, default: false }
  },
  emits: ['accept', 'decline']
}
</script>

<style scoped>
.cookie-consent {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  display: flex;
  justify-content: center;
  padding: 0 1rem 1.25rem;
  pointer-events: none;
}

.cookie-consent__container {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  width: min(100%, 720px);
  padding: 0.9rem 1.25rem;
  border-radius: 10px;
  border-top: 2px solid var(--gold-color, #c8a45d);
  background: linear-gradient(180deg, #1a2030 0%, #0f111b 100%);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
  pointer-events: auto;
}

.cookie-consent__body {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.cookie-consent__icon {
  font-size: 1.25rem;
  color: var(--gold-color, #c8a45d);
  flex-shrink: 0;
}

.cookie-consent__text {
  margin: 0;
  font-size: 0.83rem;
  color: rgba(234, 220, 194, 0.78);
  line-height: 1.45;
}

.cookie-consent__actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.cookie-consent__btn {
  padding: 0.4rem 1rem;
  border: 1px solid transparent;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cookie-consent__btn--accept {
  color: #17130a;
  background: linear-gradient(180deg, #d6b66c 0%, var(--gold-color, #c8a45d) 100%);
  box-shadow: 0 4px 16px rgba(200, 164, 93, 0.2);
}

.cookie-consent__btn--accept:hover {
  background: linear-gradient(180deg, #e1c47f 0%, #b99245 100%);
  transform: translateY(-1px);
}

.cookie-consent__btn--decline {
  color: rgba(234, 220, 194, 0.55);
  background: transparent;
  border-color: rgba(234, 220, 194, 0.12);
}

.cookie-consent__btn--decline:hover {
  color: rgba(234, 220, 194, 0.85);
  border-color: rgba(234, 220, 194, 0.25);
}

.cookie-slide-enter-active {
  transition: transform 0.5s ease, opacity 0.5s ease;
}

.cookie-slide-leave-active {
  transition: transform 0.35s ease, opacity 0.35s ease;
}

.cookie-slide-enter-from,
.cookie-slide-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

@media (max-width: 576px) {
  .cookie-consent {
    padding: 0 0.5rem 0.75rem;
  }

  .cookie-consent__container {
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
  }

  .cookie-consent__body {
    flex-direction: column;
    text-align: center;
  }

  .cookie-consent__actions {
    width: 100%;
  }

  .cookie-consent__btn {
    flex: 1;
    text-align: center;
  }
}

@media (prefers-reduced-motion: reduce) {
  .cookie-slide-enter-active,
  .cookie-slide-leave-active {
    transition: none;
  }

  .cookie-slide-enter-from,
  .cookie-slide-leave-to {
    transform: none;
    opacity: 0;
  }
}
</style>
