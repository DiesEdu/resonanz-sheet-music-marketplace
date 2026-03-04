import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const cartItems = ref([])
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

  function getHeaders() {
    const token = localStorage.getItem('auth_token')
    const headers = {
      'Content-Type': 'application/json',
    }

    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    return headers
  }

  async function fetchCart() {
    try {
      const response = await fetch(`${API_BASE_URL}/cart`, {
        method: 'GET',
        headers: getHeaders(),
        credentials: 'include',
      })

      if (!response.ok) {
        throw new Error(`Failed to fetch cart (${response.status})`)
      }

      const data = await response.json()
      const items = Array.isArray(data?.items) ? data.items : []
      cartItems.value = items.map((item) => ({
        ...item,
        id: item.sheet_id,
        coverImage: item.coverImage || item.cover_image || '',
        quantity: Number(item.quantity) || 1,
        price: Number(item.price ?? item.current_price ?? 0),
      }))
    } catch (error) {
      console.error('Failed to fetch cart:', error)
      cartItems.value = []
    }
  }

  const cartTotal = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.quantity, 0)
  })

  const cartSubtotal = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.price * item.quantity, 0)
  })

  async function addToCart(sheet, quantity = 1) {
    const sheetId = Number(sheet?.id ?? sheet?.sheet_id)
    const qty = Number(quantity) || 1

    if (!Number.isFinite(sheetId) || sheetId <= 0 || qty <= 0) {
      return false
    }

    try {
      const response = await fetch(`${API_BASE_URL}/cart/add`, {
        method: 'POST',
        headers: getHeaders(),
        credentials: 'include',
        body: JSON.stringify({ sheet_id: sheetId, quantity: qty }),
      })

      if (!response.ok) {
        throw new Error(`Failed to add item to cart (${response.status})`)
      }

      await fetchCart()
      return true
    } catch (error) {
      console.error('Failed to add item to cart:', error)
      return false
    }
  }

  async function removeFromCart(sheetId) {
    const targetId = Number(sheetId)
    if (!Number.isFinite(targetId) || targetId <= 0) {
      return false
    }

    const index = cartItems.value.findIndex((item) => Number(item.id) === targetId)
    if (index < 0) {
      return false
    }

    const [removedItem] = cartItems.value.splice(index, 1)

    try {
      const response = await fetch(`${API_BASE_URL}/cart/${targetId}`, {
        method: 'DELETE',
        headers: getHeaders(),
        credentials: 'include',
      })

      if (!response.ok) {
        throw new Error(`Failed to remove item from cart (${response.status})`)
      }

      await fetchCart()
      return true
    } catch (error) {
      console.error('Failed to remove item from cart:', error)
      cartItems.value.splice(index, 0, removedItem)
      return false
    }
  }

  async function updateQuantity(sheetId, quantity) {
    const nextQuantity = Number(quantity)
    if (!Number.isFinite(nextQuantity) || nextQuantity < 1) {
      return false
    }

    const item = cartItems.value.find((item) => item.id === sheetId)
    if (!item) {
      return false
    }

    const previousQuantity = item.quantity
    item.quantity = nextQuantity

    try {
      const response = await fetch(`${API_BASE_URL}/cart/update/${sheetId}`, {
        method: 'PUT',
        headers: getHeaders(),
        credentials: 'include',
        body: JSON.stringify({ quantity: nextQuantity }),
      })

      if (!response.ok) {
        throw new Error(`Failed to update cart item (${response.status})`)
      }

      return true
    } catch (error) {
      console.error('Failed to update cart item:', error)
      item.quantity = previousQuantity
      return false
    }
  }

  fetchCart()

  return {
    cartItems,
    cartTotal,
    cartSubtotal,
    fetchCart,
    addToCart,
    removeFromCart,
    updateQuantity,
  }
})
