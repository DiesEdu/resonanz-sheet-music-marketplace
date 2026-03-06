<template>
  <section class="profile-page py-5 px-3">
    <div class="container" style="max-width: 820px">
      <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4 p-md-5">
          <h1 class="h3 mb-1">My Profile</h1>
          <p class="text-muted mb-4">Update your username, review email verification, and change password.</p>

          <div v-if="loadError" class="alert alert-danger py-2" role="alert">
            {{ loadError }}
          </div>

          <div v-else>
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input :value="profile.email || '-'" type="text" class="form-control" readonly />
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Verification</label>
                <div class="pt-2">
                  <span :class="['badge', isEmailVerified ? 'bg-success' : 'bg-warning text-dark']">
                    {{ isEmailVerified ? 'Verified' : 'Not verified' }}
                  </span>
                </div>
                <div v-if="!isEmailVerified" class="mt-3">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="isSendingVerification"
                    @click="resendVerificationEmail"
                  >
                    <span
                      v-if="isSendingVerification"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                    ></span>
                    {{ isSendingVerification ? 'Sending...' : 'Send verification email' }}
                  </button>
                </div>
                <div v-if="verificationError" class="alert alert-danger py-2 mt-3 mb-0" role="alert">
                  {{ verificationError }}
                </div>
                <div v-if="verificationSuccess" class="alert alert-success py-2 mt-3 mb-0" role="alert">
                  {{ verificationSuccess }}
                </div>
              </div>
            </div>

            <form @submit.prevent="updateUsername" class="mb-4" novalidate>
              <h2 class="h5 mb-3">Update Username</h2>
              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input
                  id="username"
                  v-model.trim="usernameForm.username"
                  type="text"
                  class="form-control"
                  required
                />
              </div>

              <div v-if="usernameError" class="alert alert-danger py-2" role="alert">
                {{ usernameError }}
              </div>
              <div v-if="usernameSuccess" class="alert alert-success py-2" role="alert">
                {{ usernameSuccess }}
              </div>

              <button type="submit" class="btn btn-primary" :disabled="isUpdatingUsername">
                <span
                  v-if="isUpdatingUsername"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                ></span>
                {{ isUpdatingUsername ? 'Updating...' : 'Save Username' }}
              </button>
            </form>

            <form @submit.prevent="changePassword" novalidate>
              <h2 class="h5 mb-3">Change Password</h2>
              <div class="mb-3">
                <label for="currentPassword" class="form-label">Current Password</label>
                <input
                  id="currentPassword"
                  v-model="passwordForm.current_password"
                  type="password"
                  class="form-control"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="newPassword" class="form-label">New Password</label>
                <input
                  id="newPassword"
                  v-model="passwordForm.new_password"
                  type="password"
                  class="form-control"
                  minlength="6"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                <input
                  id="confirmPassword"
                  v-model="passwordForm.confirm_password"
                  type="password"
                  class="form-control"
                  minlength="6"
                  required
                />
              </div>

              <div v-if="passwordError" class="alert alert-danger py-2" role="alert">
                {{ passwordError }}
              </div>
              <div v-if="passwordSuccess" class="alert alert-success py-2" role="alert">
                {{ passwordSuccess }}
              </div>

              <button type="submit" class="btn btn-outline-primary" :disabled="isChangingPassword">
                <span
                  v-if="isChangingPassword"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                ></span>
                {{ isChangingPassword ? 'Updating...' : 'Change Password' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
const router = useRouter()

const profile = reactive({
  id: null,
  username: '',
  email: '',
  email_verified: 0,
})

const usernameForm = reactive({
  username: '',
})

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  confirm_password: '',
})

const loadError = ref('')
const usernameError = ref('')
const usernameSuccess = ref('')
const passwordError = ref('')
const passwordSuccess = ref('')
const verificationError = ref('')
const verificationSuccess = ref('')
const isUpdatingUsername = ref(false)
const isChangingPassword = ref(false)
const isSendingVerification = ref(false)

const isEmailVerified = computed(() => Number(profile.email_verified) === 1)

function requireToken() {
  const token = localStorage.getItem('auth_token')
  if (!token) {
    router.push('/login')
    return null
  }
  return token
}

async function loadProfile() {
  loadError.value = ''
  const token = requireToken()
  if (!token) return

  try {
    const response = await fetch(`${API_BASE_URL}/auth/profile`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    const payload = await response.json()

    if (!response.ok) {
      if (response.status === 401 || response.status === 403) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
        window.dispatchEvent(new Event('auth-changed'))
        router.push('/login')
        return
      }

      loadError.value = payload.error || 'Unable to load profile.'
      return
    }

    profile.id = payload.id ?? null
    profile.username = payload.username ?? ''
    profile.email = payload.email ?? ''
    profile.email_verified = payload.email_verified ?? 0
    usernameForm.username = payload.username ?? ''

    localStorage.setItem('auth_user', JSON.stringify(payload))
    window.dispatchEvent(new Event('auth-changed'))
  } catch {
    loadError.value = 'Network error while loading profile.'
  }
}

async function updateUsername() {
  usernameError.value = ''
  usernameSuccess.value = ''

  if (!usernameForm.username) {
    usernameError.value = 'Username is required.'
    return
  }

  isUpdatingUsername.value = true
  const token = requireToken()
  if (!token) {
    isUpdatingUsername.value = false
    return
  }

  try {
    const response = await fetch(`${API_BASE_URL}/auth/profile`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        username: usernameForm.username,
      }),
    })

    const payload = await response.json()

    if (!response.ok) {
      usernameError.value = payload.error || 'Failed to update username.'
      return
    }

    const latestUser = payload.user && typeof payload.user === 'object' ? payload.user : { ...profile }
    profile.username = latestUser.username ?? usernameForm.username
    profile.email = latestUser.email ?? profile.email
    profile.email_verified = latestUser.email_verified ?? profile.email_verified

    localStorage.setItem('auth_user', JSON.stringify(latestUser))
    window.dispatchEvent(new Event('auth-changed'))
    usernameSuccess.value = payload.message || 'Username updated successfully.'
  } catch {
    usernameError.value = 'Network error while updating username.'
  } finally {
    isUpdatingUsername.value = false
  }
}

async function resendVerificationEmail() {
  verificationError.value = ''
  verificationSuccess.value = ''

  const token = requireToken()
  if (!token) return

  isSendingVerification.value = true
  try {
    const response = await fetch(`${API_BASE_URL}/auth/resend-verification`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    const payload = await response.json()
    if (!response.ok) {
      verificationError.value = payload.error || 'Failed to send verification email.'
      return
    }

    verificationSuccess.value = payload.message || 'Verification email sent.'
  } catch {
    verificationError.value = 'Network error while sending verification email.'
  } finally {
    isSendingVerification.value = false
  }
}

async function changePassword() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (!passwordForm.current_password || !passwordForm.new_password || !passwordForm.confirm_password) {
    passwordError.value = 'All password fields are required.'
    return
  }

  if (passwordForm.new_password.length < 6) {
    passwordError.value = 'New password must be at least 6 characters.'
    return
  }

  if (passwordForm.new_password !== passwordForm.confirm_password) {
    passwordError.value = 'New password and confirmation do not match.'
    return
  }

  isChangingPassword.value = true
  const token = requireToken()
  if (!token) {
    isChangingPassword.value = false
    return
  }

  try {
    const response = await fetch(`${API_BASE_URL}/auth/password`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        current_password: passwordForm.current_password,
        new_password: passwordForm.new_password,
      }),
    })

    const payload = await response.json()
    if (!response.ok) {
      passwordError.value = payload.error || 'Failed to change password.'
      return
    }

    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.confirm_password = ''
    passwordSuccess.value = payload.message || 'Password updated successfully.'
  } catch {
    passwordError.value = 'Network error while changing password.'
  } finally {
    isChangingPassword.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<style scoped>
.profile-page {
  min-height: calc(100vh - 220px);
}
</style>
