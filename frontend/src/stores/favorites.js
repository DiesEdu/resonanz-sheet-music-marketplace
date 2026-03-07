import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useFavoritesStore = defineStore('favorites', () => {
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
  const favoriteIds = ref(new Set())
  const loaded = ref(false)
  const loading = ref(false)

  function getHeaders() {
    const token = localStorage.getItem('auth_token')
    if (!token) return null
    return {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    }
  }

  const count = computed(() => favoriteIds.value.size)

  function isFavorite(sheetId) {
    const id = Number(sheetId)
    if (!Number.isFinite(id) || id <= 0) return false
    return favoriteIds.value.has(id)
  }

  async function fetchFavorites(force = false) {
    const headers = getHeaders()
    if (!headers) {
      favoriteIds.value = new Set()
      loaded.value = false
      return []
    }

    if (loaded.value && !force) {
      return Array.from(favoriteIds.value)
    }

    loading.value = true
    try {
      const response = await fetch(`${API_BASE_URL}/favorites`, {
        method: 'GET',
        headers,
      })

      if (!response.ok) {
        throw new Error(`Failed to fetch favorites (${response.status})`)
      }

      const items = await response.json()
      const ids = Array.isArray(items)
        ? items
            .map((item) => Number(item?.id ?? item?.sheet_id))
            .filter((id) => Number.isFinite(id) && id > 0)
        : []

      favoriteIds.value = new Set(ids)
      loaded.value = true
      return ids
    } finally {
      loading.value = false
    }
  }

  async function addFavorite(sheetId) {
    const id = Number(sheetId)
    const headers = getHeaders()

    if (!headers || !Number.isFinite(id) || id <= 0) {
      return false
    }

    const response = await fetch(`${API_BASE_URL}/favorites/add`, {
      method: 'POST',
      headers,
      body: JSON.stringify({ sheet_id: id }),
    })

    if (!response.ok) {
      return false
    }

    favoriteIds.value.add(id)
    loaded.value = true
    return true
  }

  async function removeFavorite(sheetId) {
    const id = Number(sheetId)
    const headers = getHeaders()

    if (!headers || !Number.isFinite(id) || id <= 0) {
      return false
    }

    const response = await fetch(`${API_BASE_URL}/favorites/${id}`, {
      method: 'DELETE',
      headers,
    })

    if (!response.ok) {
      return false
    }

    favoriteIds.value.delete(id)
    loaded.value = true
    return true
  }

  async function toggleFavorite(sheetId) {
    if (isFavorite(sheetId)) {
      return removeFavorite(sheetId)
    }
    return addFavorite(sheetId)
  }

  function reset() {
    favoriteIds.value = new Set()
    loaded.value = false
    loading.value = false
  }

  return {
    favoriteIds,
    loaded,
    loading,
    count,
    isFavorite,
    fetchFavorites,
    addFavorite,
    removeFavorite,
    toggleFavorite,
    reset,
  }
})
