import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useCategory = defineStore('categories', () => {
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL
  const categories = ref([])

  async function fetchCategories() {
    try {
      const response = await fetch(`${API_BASE_URL}/categories`)
      const result = await response.json()

      categories.value = result.data || result
    } catch (error) {
      console.error('Failed to fetch categories:', error)
    }
  }

  return { categories, fetchCategories }
})
