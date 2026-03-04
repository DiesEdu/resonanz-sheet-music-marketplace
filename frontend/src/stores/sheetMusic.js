import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSheetMusicStore = defineStore('sheetMusic', () => {
  const sheets = ref([])

  async function fetchSheets() {
    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/sheets`)
      const result = await response.json()

      sheets.value = result.data
    } catch (error) {
      console.error('Failed to fetch sheets:', error)
    }
  }

  function addSheet(payload) {
    const nextId =
      sheets.value.length > 0 ? Math.max(...sheets.value.map((sheet) => sheet.id || 0)) + 1 : 1

    const newSheet = {
      id: nextId,
      title: payload.title,
      composer: payload.composer,
      instrument: payload.instrument,
      difficulty: payload.difficulty,
      price: Number(payload.price),
      description: payload.description,
      coverImage: payload.coverImage,
      pages: Number(payload.pages),
      format: payload.format,
      rating: 0,
      reviews: 0,
    }

    sheets.value.unshift(newSheet)
  }

  fetchSheets()

  return { sheets, fetchSheets, addSheet }
})
