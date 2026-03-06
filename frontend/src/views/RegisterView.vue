<template>
  <section class="auth-page d-flex align-items-center justify-content-center py-5 px-3">
    <div class="auth-card card animate-fade-scale">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <span class="badge bg-gold mb-3">Join The Marketplace</span>
          <h1 class="auth-title mb-2">Register</h1>
          <p class="text-muted mb-0">Create your account and start collecting sheet music.</p>
        </div>

        <form @submit.prevent="handleRegister" novalidate>
          <div class="mb-3">
            <label for="fullName" class="form-label">Full Name</label>
            <input
              id="fullName"
              v-model.trim="form.full_name"
              type="text"
              class="form-control"
              placeholder="Your full name"
            />
          </div>

          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input
              id="username"
              v-model.trim="form.username"
              type="text"
              class="form-control"
              placeholder="Choose a username"
              required
            />
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input
              id="email"
              v-model.trim="form.email"
              type="email"
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
              placeholder="Create password"
              required
              minlength="6"
            />
          </div>

          <div v-if="errorMessage" class="alert alert-danger py-2" role="alert">
            {{ errorMessage }}
          </div>

          <div v-if="successMessage" class="alert alert-success py-2" role="alert">
            {{ successMessage }}
          </div>

          <button
            type="submit"
            class="btn btn-primary w-100 mt-2"
            :disabled="isSubmitting || isRegistered"
          >
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ isSubmitting ? 'Creating account...' : isRegistered ? 'Registration complete' : 'Register' }}
          </button>
        </form>

        <p class="text-center mt-4 mb-0">
          Already have an account?
          <router-link to="/login" class="auth-link">Login here</router-link>
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
  full_name: '',
  username: '',
  email: '',
  password: '',
})

const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const isRegistered = ref(false)

async function handleRegister() {
  errorMessage.value = ''
  successMessage.value = ''

  if (!form.username || !form.email || !form.password) {
    errorMessage.value = 'Username, email, and password are required.'
    return
  }

  if (form.password.length < 6) {
    errorMessage.value = 'Password must be at least 6 characters.'
    return
  }

  isSubmitting.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: form.username,
        email: form.email,
        password: form.password,
        full_name: form.full_name,
      }),
    })

    const payload = await response.json()

    if (!response.ok) {
      errorMessage.value = payload.error || 'Unable to create account.'
      return
    }

    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    window.dispatchEvent(new Event('auth-changed'))
    successMessage.value =
      payload.message ||
      `Account created. We sent a verification link to ${form.email}. Please verify your email before login.`
    isRegistered.value = true

    const emailQuery = encodeURIComponent(form.email)
    setTimeout(() => {
      router.push(`/login?verified_email=${emailQuery}`)
    }, 1800)
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
  max-width: 560px;
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
