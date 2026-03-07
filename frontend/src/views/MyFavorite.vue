<template>
  <section class="my-favorite-page py-5 px-3">
    <div class="container" style="max-width: 960px">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h1 class="h3 mb-0">My Favorite</h1>
            <span class="badge bg-secondary">{{ favorites.length }}</span>
          </div>
          <p class="text-muted mb-4">Your favorite sheet music list.</p>

          <div v-if="errorMessage" class="alert alert-danger py-2" role="alert">
            {{ errorMessage }}
          </div>

          <div v-if="isLoading" class="text-muted">Loading favorites...</div>

          <div v-else-if="favorites.length === 0" class="text-muted">
            You do not have any favorite sheet music yet.
          </div>

          <div v-else class="row g-3">
            <div v-for="sheet in favorites" :key="sheet.id" class="col-12">
              <div
                class="favorite-item border rounded-3 p-3 d-flex gap-3 align-items-start overflow-auto"
              >
                <img
                  :src="sheet.cover_image || fallbackCover"
                  :alt="sheet.title"
                  class="favorite-cover rounded-2"
                />

                <div class="flex-grow-1">
                  <router-link :to="`/sheet/${sheet.id}`" class="text-decoration-none text-dark">
                    <h2 class="h6 mb-1">{{ sheet.title }}</h2>
                  </router-link>
                  <p class="text-muted mb-1">{{ sheet.composer || '-' }}</p>
                  <small class="text-muted">
                    {{ sheet.instrument || '-' }} - {{ sheet.difficulty || '-' }}
                  </small>
                </div>

                <div class="text-end">
                  <div class="fw-semibold mb-2">{{ formatPriceIDR(Number(sheet.price) || 0) }}</div>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary me-2"
                    :disabled="addingId === Number(sheet.id)"
                    @click="addToCart(sheet, $event)"
                  >
                    <span
                      v-if="addingId === Number(sheet.id)"
                      class="spinner-border spinner-border-sm"
                      role="status"
                    ></span>
                    <i v-else class="bi bi-cart-plus me-1"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    :disabled="removingId === Number(sheet.id)"
                    @click="removeFavorite(Number(sheet.id))"
                  >
                    <span
                      v-if="removingId === Number(sheet.id)"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                    ></span>
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useFavoritesStore } from '@/stores/favorites'
import { useCartStore } from '@/stores/cart'
import { formatPriceIDR } from '@/utils/priceUtils'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
const fallbackCover = 'https://via.placeholder.com/96x128?text=No+Cover'

const router = useRouter()
const cartStore = useCartStore()
const favoritesStore = useFavoritesStore()

const isLoading = ref(false)
const errorMessage = ref('')
const favorites = ref([])
const removingId = ref(null)
const addingId = ref(null)

function requireToken() {
  const token = localStorage.getItem('auth_token')
  if (!token) {
    router.push('/login')
    return null
  }
  return token
}

async function loadFavorites() {
  errorMessage.value = ''
  const token = requireToken()
  if (!token) return

  isLoading.value = true
  try {
    const response = await fetch(`${API_BASE_URL}/favorites`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    const payload = await response.json().catch(() => [])
    if (!response.ok) {
      errorMessage.value = payload?.error || 'Unable to load favorite sheet music.'
      return
    }

    favorites.value = Array.isArray(payload) ? payload : []
    await favoritesStore.fetchFavorites(true)
  } catch {
    errorMessage.value = 'Network error while loading favorite sheet music.'
  } finally {
    isLoading.value = false
  }
}

async function removeFavorite(sheetId) {
  errorMessage.value = ''
  const token = requireToken()
  if (!token) return

  removingId.value = Number(sheetId)
  try {
    const response = await fetch(`${API_BASE_URL}/favorites/${sheetId}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    const payload = await response.json().catch(() => ({}))
    if (!response.ok) {
      errorMessage.value = payload?.error || 'Failed to remove favorite.'
      return
    }

    favorites.value = favorites.value.filter((sheet) => Number(sheet.id) !== Number(sheetId))
    await favoritesStore.fetchFavorites(true)
  } catch {
    errorMessage.value = 'Network error while removing favorite.'
  } finally {
    removingId.value = null
  }
}

async function addToCart(sheet, event) {
  const authUser = localStorage.getItem('auth_user')
  if (!authUser) {
    router.push('/login')
    requestAnimationFrame(() => {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    })
    return
  }

  const sheetId = Number(sheet?.id)
  if (!Number.isFinite(sheetId) || sheetId <= 0) {
    errorMessage.value = 'Invalid sheet music data.'
    return
  }

  errorMessage.value = ''
  addingId.value = sheetId
  const isAdded = await cartStore.addToCart(sheet)
  if (!isAdded) {
    errorMessage.value = 'Failed to add this item to your cart.'
  }
  addingId.value = null

  const button = event?.currentTarget
  if (button) {
    button.classList.add('btn-clicked')
    setTimeout(() => {
      button.classList.remove('btn-clicked')
    }, 300)
  }
}

onMounted(() => {
  loadFavorites()
})
</script>

<style scoped>
.my-favorite-page {
  min-height: calc(100vh - 220px);
}

.favorite-cover {
  width: 72px;
  height: 96px;
  object-fit: cover;
  flex-shrink: 0;
}

.favorite-item {
  background: #fff;
}
</style>
