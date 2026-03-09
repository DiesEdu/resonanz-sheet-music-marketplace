import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSheetMusicStore = defineStore('sheetMusic', () => {
  const sheets = ref([])
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL
  const DEFAULT_CATEGORY_ID = 1

  function getAuthHeaders() {
    const token = localStorage.getItem('auth_token')
    return token ? { Authorization: `Bearer ${token}` } : {}
  }

  function mapFormToApiPayload(payload) {
    return {
      title: payload.title,
      subtitle: payload.subtitle,
      composer: payload.composer,
      arranger: payload.arranger,
      description: payload.description,
      instrument_id: payload.instrument,
      category_id: Number(payload.category) || DEFAULT_CATEGORY_ID,
      difficulty: payload.difficulty,
      price: Number(payload.price),
      pages: Number(payload.pages),
      format: payload.format,
      pdf_name: payload.pdf_name || '',
      file_path: payload.file_path || '',
      sample_audio: payload.sample_audio || '',
      cover_image: payload.coverImage || payload.cover_image || '',
      is_featured: 0,
      is_premium: 0,
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
    const hasPdfFile = payload?.pdfFile instanceof File
    const hasSampleAudioFile = payload?.sampleAudioFile instanceof File

    const headers = { ...getAuthHeaders() }
    let body

    if (hasPdfFile || hasSampleAudioFile) {
      const formData = new FormData()
      Object.entries(requestBody).forEach(([key, value]) => {
        formData.append(key, value ?? '')
      })
      if (hasPdfFile) {
        formData.append('sheet_file', payload.pdfFile)
      }
      if (hasSampleAudioFile) {
        formData.append('sample_audio_file', payload.sampleAudioFile)
      }
      body = formData
    } else {
      headers['Content-Type'] = 'application/json'
      body = JSON.stringify(requestBody)
    }

    const response = await fetch(`${API_BASE_URL}/sheets`, {
      method: 'POST',
      headers,
      body,
    })

    const result = await response.json().catch(() => ({}))
    if (!response.ok) {
      throw new Error(result?.error || 'Failed to add sheet')
    }

    await fetchSheetBySearch()
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

    await fetchSheetBySearch()
    return result
  }

  return { sheets, fetchSheetBySearch, addSheet, updateSheet }
})
