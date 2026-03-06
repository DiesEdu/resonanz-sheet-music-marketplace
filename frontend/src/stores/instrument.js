import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useInstruments = defineStore('instruments', () => {
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL
  const instruments = ref([])

  async function fetchInstruments() {
    try {
      const response = await fetch(`${API_BASE_URL}/instruments`)
      const result = await response.json()

      instruments.value = result.data || result
    } catch (error) {
      console.error('Failed to fetch instruments:', error)
    }
  }

  return { instruments, fetchInstruments }
})
