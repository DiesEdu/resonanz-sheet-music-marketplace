<template>
  <section class="auth-page d-flex align-items-center justify-content-center py-5 px-3">
    <div class="auth-card card animate-fade-scale">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <span class="badge bg-gold mb-3">Welcome Back</span>
          <h1 class="auth-title mb-2">Login</h1>
          <p class="text-muted mb-0">Sign in to continue your music journey.</p>
        </div>

        <form @submit.prevent="handleLogin" novalidate>
          <div class="mb-3">
            <label for="username" class="form-label">Username or Email</label>
            <input
              id="username"
              v-model.trim="form.username"
              type="text"
              class="form-control"
              placeholder="yourname@example.com"
              required
            />
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              class="form-control"
              placeholder="Enter your password"
              required
            />
          </div>

          <div v-if="errorMessage" class="alert alert-danger py-2" role="alert">
            {{ errorMessage }}
          </div>

          <div v-if="successMessage" class="alert alert-success py-2" role="alert">
            {{ successMessage }}
          </div>

          <button type="submit" class="btn btn-primary w-100 mt-2" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ isSubmitting ? 'Signing in...' : 'Login' }}
          </button>
        </form>

        <p class="text-center mt-4 mb-0">
          Don't have an account?
          <router-link to="/register" class="auth-link">Create one</router-link>
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
const router = useRouter()

const form = reactive({
  username: '',
  password: '',
})

const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

async function handleLogin() {
  errorMessage.value = ''
  successMessage.value = ''

  if (!form.username || !form.password) {
    errorMessage.value = 'Please fill in username/email and password.'
    return
  }

  isSubmitting.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: form.username,
        password: form.password,
      }),
    })

    const payload = await response.json()

    if (!response.ok) {
      errorMessage.value = payload.error || 'Unable to login.'
      return
    }

    localStorage.setItem('auth_token', payload.token)
    localStorage.setItem('auth_user', JSON.stringify(payload.user))
    window.dispatchEvent(new Event('auth-changed'))
    successMessage.value = 'Login successful.'
    router.push('/')
  } catch {
    errorMessage.value = 'Network error. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - 220px);
}

.auth-card {
  width: 100%;
  max-width: 520px;
  border-radius: 24px;
}

.auth-title {
  font-size: 2.2rem;
  font-weight: 600;
  color: #2c1810;
}

.auth-link {
  color: #a88c5c;
  text-decoration: none;
  font-weight: 600;
}

.auth-link:hover {
  color: #8f7648;
}

.bg-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
}
</style>
