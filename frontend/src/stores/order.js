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
    const user = localStorage.getItem('auth_user')
    let paresdUser
    if (user) {
      try {
        paresdUser = JSON.parse(user)
      } catch (error) {
        console.error('Failed to parse user data:', error)
        paresdUser = null
      }
    }
    console.log('Placing order for user:', paresdUser || 'Guest')
    const response = await fetch(`${API_BASE_URL}/orders/checkout`, {
      method: 'POST',
      headers: getAuthHeaders(),
      credentials: 'include',
      body: JSON.stringify({
        name: paresdUser ? paresdUser.full_name : '',
        email: paresdUser ? paresdUser.email : '',
        phone: '',
        address: '',
        notes: '',
      }),
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

  async function cancelMyOrder(orderId) {
    const response = await fetch(`${API_BASE_URL}/orders/${orderId}/cancel`, {
      method: 'PUT',
      headers: {
        ...getAuthHeaders(),
      },
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok) {
      throw new Error(payload?.error || 'Failed to cancel order')
    }

    return payload
  }

  // Modal implementation
  const openPaymentModal = async (order) => {
    const parsedUser = (() => {
      try {
        return JSON.parse(localStorage.getItem('auth_user') || 'null')
      } catch {
        return null
      }
    })()

    const resolvedOrderId = order.order_id || order.id || order.order_number
    const billing = order.billing_info || {}
    const inputOrder = {
      transaction_details: {
        order_id: order.order_number || resolvedOrderId,
        gross_amount: order.total_amount,
      },
      credit_card: {
        secure: true,
      },
      customer_details: {
        first_name: billing.name || order.billing_name || parsedUser?.full_name || 'Customer',
        last_name: '',
        email: billing.email || order.billing_email || parsedUser?.email || '',
        phone: billing.phone || order.billing_phone || '',
      },
    }

    let redirectUrl
    try {
      const response = await fetch(`${API_BASE_URL}/payments/checkout`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: JSON.stringify(inputOrder),
      })
      const result = await response.json().catch(() => ({}))

      if (!response.ok) {
        throw new Error(result?.error || `Payment failed (${response.status})`)
      }

      redirectUrl = result.redirect_url
      if (!redirectUrl) {
        throw new Error('Payment redirect URL is missing')
      }
    } catch (error) {
      console.error('Payment error:', error)
      throw error
    }

    // Create modal container
    const modal = document.createElement('div')
    modal.id = 'payment-modal'
    modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  `

    // Modal content
    modal.innerHTML = `
    <div style="background: white; width: 90%; max-width: 500px; border-radius: 8px; overflow: hidden;">
      <div style="padding: 16px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 18px;">Complete Payment</h3>
        <button id="close-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
      </div>
      <iframe
        id="payment-iframe"
        src="${redirectUrl}"
        style="width: 100%; height: 600px; border: none;"
        title="Payment Gateway"
      ></iframe>
      <div style="padding: 16px; text-align: center; background: #f8f9fa; border-top: 1px solid #dee2e6;">
        <small style="color: #666;">Payment securely processed by Midtrans</small>
      </div>
    </div>
  `

    document.body.appendChild(modal)

    // Close button handler
    document.getElementById('close-modal').onclick = () => {
      closePaymentModal(resolvedOrderId)
    }

    // Listen for messages from iframe
    window.addEventListener('message', handlePaymentMessage, false)

    // Store orderId for later reference
    modal.dataset.orderId = resolvedOrderId
  }

  const closePaymentModal = (orderId) => {
    const modal = document.getElementById('payment-modal')
    if (modal) {
      modal.remove()
      window.removeEventListener('message', handlePaymentMessage)

      // Check payment status
      checkPaymentStatus(orderId)
    }
  }

  const checkPaymentStatus = async (orderId) => {
    try {
      const response = await fetch(`/api/order/${orderId}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Error checking payment status:', error)
      throw error
    }
  }

  const handlePaymentMessage = (event) => {
    // Security check - verify origin
    if (
      event.origin !== 'https://app.sandbox.midtrans.com' &&
      event.origin !== 'https://app.midtrans.com'
    ) {
      return
    }

    if (event.data && event.data.status) {
      const modal = document.getElementById('payment-modal')
      const orderId = modal?.dataset.orderId

      if (orderId) {
        closePaymentModal(orderId)

        switch (event.data.status) {
          case 'success':
            window.location.href = `/checkout/success?order_id=${orderId}`
            break
          case 'pending':
            window.location.href = `/checkout/pending?order_id=${orderId}`
            break
          default:
            window.location.href = `/checkout/failed?order_id=${orderId}`
        }
      }
    }
  }

  return {
    placeOrder,
    getMyOrders,
    getOrderById,
    cancelMyOrder,
    openPaymentModal,
  }
})
