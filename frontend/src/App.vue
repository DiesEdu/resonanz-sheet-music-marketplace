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
                @click="handleNavItemClick"
              >
                <i :class="item.icon"></i> {{ item.name }}
                <span v-if="item.name === 'Cart'" class="badge bg-primary ms-2">{{
                  cartStore.cartTotal
                }}</span>
              </router-link>
            </li>
            <li v-if="isLoggedIn" class="nav-item dropdown d-flex align-items-center user-menu">
              <button
                class="nav-link btn btn-link dropdown-toggle text-decoration-none auth-user-name"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i class="bi bi-person-circle me-1"></i>{{ displayName }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <router-link class="dropdown-item" to="/profile" @click="handleNavItemClick">
                    <i class="bi bi-person me-2"></i>Profile
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" to="/my-favorite" @click="handleNavItemClick">
                    <i class="bi bi-heart me-2"></i>My Favorite
                  </router-link>
                </li>
                <li v-if="isLoggedIn">
                  <router-link class="dropdown-item" to="/my-orders" @click="handleNavItemClick">
                    <i class="bi bi-bag me-2"></i>My Orders
                  </router-link>
                </li>
                <li>
                  <router-link
                    class="dropdown-item"
                    to="/admin/users"
                    v-if="isAdmin"
                    @click="handleNavItemClick"
                  >
                    <i class="bi bi-people me-2"></i>Users
                  </router-link>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                  <button class="dropdown-item" type="button" @click="logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                  </button>
                </li>
              </ul>
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
            <p class="text-white">
              Your premier destination for classical and contemporary sheet music.
            </p>
          </div>
          <div class="col-md-4 animate-fade-up delay-2">
            <h5 class="text-gold">Our Social Media</h5>
            <ul class="list-unstyled">
              <li v-for="item in socialMediaItems" :key="item.name">
                <a
                  :href="item.url"
                  class="text-white text-decoration-none"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <i :class="item.icon" class="me-2"></i>{{ item.name }}
                </a>
              </li>
            </ul>
          </div>
          <div class="col-md-4 animate-fade-up delay-3">
            <h5 class="text-gold">Newsletter</h5>
            <p class="text-white">Subscribe for exclusive releases and offers</p>
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
        <div class="text-center animate-fade-up delay-4">
          <small>&copy; 2026 The Resonanz Music Studio. All rights reserved.</small>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from './stores/cart'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
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

function closeMobileNavbar() {
  const navbarCollapse = document.getElementById('navbarNav')
  if (!navbarCollapse?.classList.contains('show')) {
    return
  }

  const collapseApi = window.bootstrap?.Collapse
  if (collapseApi) {
    collapseApi.getOrCreateInstance(navbarCollapse).hide()
    return
  }

  navbarCollapse.classList.remove('show')
  const toggler = document.querySelector('.navbar-toggler')
  toggler?.setAttribute('aria-expanded', 'false')
}

function handleNavItemClick() {
  closeMobileNavbar()
  requestAnimationFrame(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  })
}

function clearAuthAndRequireLogin() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  syncAuthUser()

  if (router.currentRoute.value.path !== '/login') {
    router.push('/login')
  }
}

async function validateAuthToken() {
  const token = localStorage.getItem('auth_token')
  if (!token) {
    syncAuthUser()
    return
  }

  try {
    const response = await fetch(`${API_BASE_URL}/auth/profile`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    if (!response.ok) {
      if (response.status === 401 || response.status === 403) {
        clearAuthAndRequireLogin()
      }
      return
    }

    const profile = await response.json()
    if (profile && typeof profile === 'object') {
      localStorage.setItem('auth_user', JSON.stringify(profile))
    }
    syncAuthUser()
  } catch {
    // Keep current session state on transient network errors.
  }
}

function handleAuthChanged() {
  validateAuthToken()
}

function logout() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  syncAuthUser()
  window.location.reload()
}

onMounted(() => {
  validateAuthToken()
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
const displayName = computed(() => {
  const rawName = `${authUser.value?.full_name || authUser.value?.username || ''}`.trim()
  if (!rawName) return 'U'

  const initials = rawName
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')

  return initials || rawName.charAt(0).toUpperCase() || 'U'
})

const navItems = computed(() => [
  { name: 'Home', path: '/', icon: 'bi bi-house-door' },
  { name: 'Marketplace', path: '/marketplace', icon: 'bi bi-shop' },
  ...(isComposer.value || isAdmin.value
    ? [{ name: 'Composer Hub', path: '/composer/hub', icon: 'bi bi-file-earmark-plus' }]
    : []),
  { name: 'Cart', path: '/cart', icon: 'bi bi-cart' },
  ...(isLoggedIn.value
    ? []
    : [{ name: 'Login', path: '/login', icon: 'bi bi-box-arrow-in-right' }]),
])

const socialMediaItems = computed(() => {
  return [
    {
      name: '@theresonanz',
      url: 'https://www.instagram.com/theresonanz/',
      icon: 'bi bi-instagram',
    },
    { name: '@theresonanz', url: 'https://x.com/theresonanz', icon: 'bi bi-twitter-x' },
    {
      name: '@theresonanzmusicstudio',
      url: 'https://www.facebook.com/TheResonanzMusicStudio/',
      icon: 'bi bi-facebook',
    },
    {
      name: '@theresonanz',
      url: 'https://www.youtube.com/channel/UCdb2I2qatGylIK_9oZpRQqg',
      icon: 'bi bi-youtube',
    },
  ]
})
</script>

<style>
@import 'bootstrap-icons/font/bootstrap-icons.css';

.text-white {
  color: #f8f9fa;
}

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
  cursor: pointer;
}

.auth-user-name:hover {
  color: #f5e6cd;
}

.user-menu .dropdown-menu {
  border-radius: 10px;
}
</style>
