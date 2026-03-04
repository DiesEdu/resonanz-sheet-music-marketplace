<template>
  <section class="container py-5 user-management-page">
    <div class="text-center mb-4">
      <h1 class="section-title animate-fade-up">User Management</h1>
      <p class="text-muted animate-fade-up delay-1">
        Admin panel for updating user roles.
      </p>
      <span class="badge bg-gold mb-3 animate-fade-scale">Admin Only</span>
    </div>

    <div class="card users-card animate-fade-scale delay-2">
      <div class="card-body p-4">
        <div v-if="errorMessage" class="alert alert-danger py-2 mb-3">
          {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success py-2 mb-3">
          {{ successMessage }}
        </div>

        <div v-if="isLoading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status"></div>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Name</th>
                <th scope="col">Username</th>
                <th scope="col">Email</th>
                <th scope="col">Current Role</th>
                <th scope="col">Change Role</th>
                <th scope="col" class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id">
                <td>{{ user.full_name || '-' }}</td>
                <td>{{ user.username }}</td>
                <td>{{ user.email }}</td>
                <td>
                  <span class="text-capitalize">{{ user.role }}</span>
                </td>
                <td>
                  <select v-model="pendingRoles[user.id]" class="form-select">
                    <option value="admin">admin</option>
                    <option value="composer">composer</option>
                    <option value="user">user</option>
                  </select>
                </td>
                <td class="text-end">
                  <button
                    class="btn btn-sm btn-primary"
                    type="button"
                    :disabled="isSaving[user.id] || pendingRoles[user.id] === user.role"
                    @click="saveRole(user)"
                  >
                    <span
                      v-if="isSaving[user.id]"
                      class="spinner-border spinner-border-sm me-1"
                      role="status"
                    ></span>
                    Save
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getUsers, updateUserRole } from '../../api'

const router = useRouter()
const users = ref([])
const isLoading = ref(true)
const errorMessage = ref('')
const successMessage = ref('')
const pendingRoles = reactive({})
const isSaving = reactive({})

function readAuthUser() {
  const raw = localStorage.getItem('auth_user')
  if (!raw) return null

  try {
    return JSON.parse(raw)
  } catch {
    return null
  }
}

async function loadUsers() {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const result = await getUsers()
    users.value = result
    for (const user of result) {
      pendingRoles[user.id] = user.role
    }
  } catch (error) {
    errorMessage.value = error.message
  } finally {
    isLoading.value = false
  }
}

async function saveRole(user) {
  const nextRole = pendingRoles[user.id]
  if (!nextRole || nextRole === user.role) {
    return
  }

  successMessage.value = ''
  errorMessage.value = ''
  isSaving[user.id] = true

  try {
    await updateUserRole(user.id, nextRole)
    user.role = nextRole
    successMessage.value = `Role updated for ${user.username}.`
  } catch (error) {
    errorMessage.value = error.message
    pendingRoles[user.id] = user.role
  } finally {
    isSaving[user.id] = false
  }
}

onMounted(async () => {
  const authUser = readAuthUser()
  if (!authUser || authUser.role !== 'admin') {
    router.replace('/')
    return
  }

  await loadUsers()
})
</script>

<style scoped>
.user-management-page {
  min-height: calc(100vh - 220px);
}

.users-card {
  border-radius: 24px;
}

.table th,
.table td {
  vertical-align: middle;
}

.form-select {
  min-width: 140px;
}

.bg-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
}
</style>
