<template>
  <div id="app">
    <!-- Floating Music Notes -->
    <div class="music-note music-note-1">♪</div>
    <div class="music-note music-note-2">♫</div>
    <div class="music-note music-note-3">♩</div>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top animate-slide-down">
      <div class="container">
        <router-link class="navbar-brand" to="/">
          <i class="bi bi-music-note-beamed"></i> Resonanz Marketplace
        </router-link>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item" v-for="(item, index) in navItems" :key="item.path">
              <router-link
                class="nav-link"
                :to="item.path"
                :class="{ 'animate-fade-scale': true, [`delay-${index + 1}`]: true }"
              >
                <i :class="item.icon"></i> {{ item.name }}
                <span v-if="item.name === 'Cart'" class="badge bg-primary ms-2">{{
                  cartStore.cartTotal
                }}</span>
              </router-link>
            </li>
            <li v-if="isLoggedIn" class="nav-item d-flex align-items-center">
              <span class="nav-link auth-user-name">
                <i class="bi bi-person-circle me-1"></i>{{ displayName }}
              </span>
            </li>
            <li v-if="isLoggedIn" class="nav-item d-flex align-items-center">
              <button
                class="nav-link btn btn-link text-decoration-none"
                type="button"
                @click="logout"
              >
                <i class="bi bi-box-arrow-right"></i> Logout
              </button>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="pt-5">
      <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
      <div class="container">
        <div class="row">
          <div class="col-md-4 animate-fade-up">
            <h5 class="text-gold">SheetMusic Market</h5>
            <p class="text-muted">
              Your premier destination for classical and contemporary sheet music.
            </p>
          </div>
          <div class="col-md-4 animate-fade-up delay-2">
            <h5 class="text-gold">Quick Links</h5>
            <ul class="list-unstyled">
              <li>
                <router-link to="/" class="text-muted text-decoration-none">Home</router-link>
              </li>
              <li>
                <router-link to="/marketplace" class="text-muted text-decoration-none"
                  >Marketplace</router-link
                >
              </li>
              <li>
                <router-link to="/cart" class="text-muted text-decoration-none">Cart</router-link>
              </li>
              <li>
                <router-link to="/login" class="text-muted text-decoration-none">Login</router-link>
              </li>
              <li>
                <router-link to="/register" class="text-muted text-decoration-none"
                  >Register</router-link
                >
              </li>
            </ul>
          </div>
          <div class="col-md-4 animate-fade-up delay-3">
            <h5 class="text-gold">Newsletter</h5>
            <p class="text-muted">Subscribe for exclusive releases and offers</p>
            <div class="input-group">
              <input
                type="email"
                class="form-control bg-dark text-white border-gold"
                placeholder="Your email"
              />
              <button class="btn btn-gold" type="button">Subscribe</button>
            </div>
          </div>
        </div>
        <hr class="border-secondary" />
        <div class="text-center text-muted animate-fade-up delay-4">
          <small>&copy; 2024 SheetMusic Market. All rights reserved.</small>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from './stores/cart'

const cartStore = useCartStore()
const router = useRouter()
const authUser = ref(null)

function syncAuthUser() {
  const rawUser = localStorage.getItem('auth_user')
  if (!rawUser) {
    authUser.value = null
    return
  }

  try {
    authUser.value = JSON.parse(rawUser)
  } catch {
    authUser.value = null
  }
}

function handleAuthChanged() {
  syncAuthUser()
}

function logout() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  syncAuthUser()
  router.push('/')
}

onMounted(() => {
  syncAuthUser()
  window.addEventListener('auth-changed', handleAuthChanged)
  window.addEventListener('storage', handleAuthChanged)
})

onBeforeUnmount(() => {
  window.removeEventListener('auth-changed', handleAuthChanged)
  window.removeEventListener('storage', handleAuthChanged)
})

const isLoggedIn = computed(() => !!authUser.value)
const isAdmin = computed(() => authUser.value?.role === 'admin')
const isComposer = computed(() => authUser.value?.role === 'composer')
const displayName = computed(() => authUser.value?.full_name || authUser.value?.username || 'User')

const navItems = computed(() => [
  { name: 'Home', path: '/', icon: 'bi bi-house-door' },
  { name: 'Marketplace', path: '/marketplace', icon: 'bi bi-shop' },
  ...(isComposer.value
    ? [{ name: 'Add Sheet', path: '/composer/add-sheet', icon: 'bi bi-file-earmark-plus' }]
    : []),
  ...(isAdmin.value
    ? [
        { name: 'Add Sheet', path: '/composer/add-sheet', icon: 'bi bi-file-earmark-plus' },
        { name: 'Users', path: '/admin/users', icon: 'bi bi-people' },
      ]
    : []),
  { name: 'Cart', path: '/cart', icon: 'bi bi-cart' },
  ...(isLoggedIn.value
    ? []
    : [{ name: 'Login', path: '/login', icon: 'bi bi-box-arrow-in-right' }]),
])
</script>

<style>
@import 'bootstrap-icons/font/bootstrap-icons.css';

.text-gold {
  color: #c5a572;
}

.border-gold {
  border-color: #c5a572;
}

.btn-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
  color: white;
  border: none;
}

.btn-gold:hover {
  background: linear-gradient(135deg, #b59460, #8f7648);
  color: white;
}

.animate-slide-down {
  animation: slideInDown 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes slideInDown {
  from {
    transform: translateY(-100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.page-enter-active,
.page-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}

.auth-user-name {
  color: #e9d4b0;
  cursor: default;
}
</style>
