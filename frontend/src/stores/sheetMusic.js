import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSheetMusicStore = defineStore('sheetMusic', () => {
  const sheets = ref([])
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL
  const INSTRUMENT_ID_MAP = {
    Piano: 1,
    Violin: 2,
    Guitar: 3,
    Cello: 4,
    Flute: 5,
  }
  const DEFAULT_CATEGORY_ID = 1

  function getAuthHeaders() {
    const token = localStorage.getItem('auth_token')
    return token ? { Authorization: `Bearer ${token}` } : {}
  }

  function mapFormToApiPayload(payload) {
    return {
      title: payload.title,
      composer: payload.composer,
      description: payload.description,
      instrument_id: INSTRUMENT_ID_MAP[payload.instrument] || 1,
      category_id: payload.category_id || DEFAULT_CATEGORY_ID,
      difficulty: payload.difficulty,
      price: Number(payload.price),
      pages: Number(payload.pages),
      format: payload.format,
      file_path: payload.file_path || '',
      cover_image: payload.coverImage || payload.cover_image || '',
      is_featured: 0,
      is_premium: 0,
    }
  }

  async function fetchSheets() {
    try {
      const response = await fetch(`${API_BASE_URL}/sheets`)
      const result = await response.json()

      sheets.value = result.data || []
    } catch (error) {
      console.error('Failed to fetch sheets:', error)
    }
  }

  async function fetchSheetBySearch(instrument, difficulty, search) {
    try {
      const queryParams = new URLSearchParams({
        instrument: instrument || '',
        difficulty: difficulty || '',
        search: search || '',
      })
      const response = await fetch(`${API_BASE_URL}/sheets?${queryParams}`)
      const result = await response.json()

      sheets.value = result.data || []
    } catch (error) {
      console.error('Failed to fetch sheets:', error)
    }
  }

  async function addSheet(payload) {
    const requestBody = mapFormToApiPayload(payload)

    const response = await fetch(`${API_BASE_URL}/sheets`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...getAuthHeaders(),
      },
      body: JSON.stringify(requestBody),
    })

    const result = await response.json().catch(() => ({}))
    if (!response.ok) {
      throw new Error(result?.error || 'Failed to add sheet')
    }

    await fetchSheets()
    return result
  }

  async function updateSheet(id, payload) {
    const requestBody = mapFormToApiPayload(payload)

    const response = await fetch(`${API_BASE_URL}/sheets/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        ...getAuthHeaders(),
      },
      body: JSON.stringify({
        ...requestBody,
        is_featured: payload.is_featured ?? 0,
        is_premium: payload.is_premium ?? 0,
      }),
    })

    const result = await response.json().catch(() => ({}))
    if (!response.ok) {
      throw new Error(result?.error || 'Failed to update sheet')
    }

    await fetchSheets()
    return result
  }

  fetchSheets()

  return { sheets, fetchSheets, fetchSheetBySearch, addSheet, updateSheet }
})
