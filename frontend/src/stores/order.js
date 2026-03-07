import { defineStore } from 'pinia'

export const useOrderStore = defineStore('order', () => {
  const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

  function getAuthHeaders() {
    const token = localStorage.getItem('auth_token')
    const headers = {
      'Content-Type': 'application/json',
    }

    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    return headers
  }

  async function placeOrder() {
    const response = await fetch(`${API_BASE_URL}/orders/checkout`, {
      method: 'POST',
      headers: getAuthHeaders(),
      credentials: 'include',
      body: JSON.stringify({}),
    })
    const result = await response.json().catch(() => ({}))

    if (!response.ok) {
      throw new Error(result?.error || `Checkout failed (${response.status})`)
    }

    return result
  }

  async function getMyOrders() {
    const response = await fetch(`${API_BASE_URL}/orders`, {
      method: 'GET',
      headers: {
        ...getAuthHeaders(),
      },
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok) {
      throw new Error(payload?.error || 'Failed to fetch order history')
    }

    return Array.isArray(payload) ? payload : []
  }

  async function getOrderById(orderId) {
    const response = await fetch(`${API_BASE_URL}/orders/${orderId}`, {
      method: 'GET',
      headers: {
        ...getAuthHeaders(),
      },
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok) {
      throw new Error(payload?.error || 'Failed to fetch order detail')
    }

    return payload
  }

  return {
    placeOrder,
    getMyOrders,
    getOrderById,
  }
})
