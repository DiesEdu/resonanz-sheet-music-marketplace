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

    const billing = order.billing_info || {}
    const inputOrder = {
      transaction_details: {
        order_id: order.order_number,
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
        <button id="close-modal" style="background: none; border: none; font-size: 48px; cursor: pointer; color: #666;">&times;</button>
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
      closePaymentModal(order.order_id)
    }

    // Listen for messages from iframe
    window.addEventListener('message', handlePaymentMessage, false)

    // Store orderId for later reference
    modal.dataset.orderId = order.order_id
    modal.dataset.orderNumber = order.order_number || ''
  }

  const closePaymentModal = async (orderId) => {
    const modal = document.getElementById('payment-modal')
    if (modal) {
      modal.remove()
      window.removeEventListener('message', handlePaymentMessage)

      // Check payment status
      await checkPaymentStatus(orderId)
    }
  }

  const checkPaymentStatus = async (orderId) => {
    try {
      const response = await fetch(`${API_BASE_URL}/orders/${orderId}`, {
        method: 'GET',
        headers: getAuthHeaders(),
      })
      const payload = await response.json().catch(() => null)
      if (!response.ok) {
        throw new Error(payload?.error || 'Failed to fetch order detail')
      }
      return payload
    } catch (error) {
      console.error('Error checking payment status:', error)
      throw error
    }
  }

  const updateMidtransPaymentStatus = async (orderNumber) => {
    try {
      // First, get the order ID using the order number
      const response_order = await fetch(
        `${API_BASE_URL}/orders/order-number?order_number=${orderNumber}`,
        {
          method: 'GET',
          headers: getAuthHeaders(),
        },
      )
      const orders = await response_order.json().catch(() => [])
      if (!response_order.ok || orders.length === 0) {
        throw new Error(`Failed to fetch order with number ${orderNumber}`)
      }
      const orderId = orders.id

      // Then, get the payment status from Midtrans
      const response = await fetch(
        `${API_BASE_URL}/payment/midtrans-status?order_id=${orderNumber}`,
        {
          method: 'GET',
          headers: getAuthHeaders(),
        },
      )
      const payload = await response.json().catch(() => null)
      if (!response.ok) {
        throw new Error(payload?.error || `Failed to get payment status (${response.status})`)
      }

      if (
        payload?.transaction_status === 'settlement' ||
        payload?.transaction_status === 'capture'
      ) {
        await fetch(`${API_BASE_URL}/orders/update-payment-status`, {
          method: 'POST',
          headers: getAuthHeaders(),
          body: JSON.stringify({
            order_id: Number.parseInt(orderId, 10),
            payment_status: 'paid',
          }),
        })
        return payload
      } else if (
        payload?.transaction_status === 'cancel' ||
        payload?.transaction_status === 'deny' ||
        payload?.transaction_status === 'expire' ||
        payload?.transaction_status === 'failure'
      ) {
        await fetch(`${API_BASE_URL}/orders/update-payment-status`, {
          method: 'POST',
          headers: getAuthHeaders(),
          body: JSON.stringify({
            order_id: Number.parseInt(orderId, 10),
            payment_status: 'failed',
          }),
        })
      }

      return payload
    } catch (error) {
      console.error('Error updating payment status:', error)
      throw error
    }
  }

  const downloadSheet = async (sheetId, filename = 'sheet-music.pdf') => {
    if (!sheetId) throw new Error('Sheet ID is required')

    const response = await fetch(`${API_BASE_URL}/orders/${sheetId}/download`, {
      method: 'GET',
      headers: getAuthHeaders(),
    })

    if (!response.ok) {
      const payload = await response.json().catch(() => null)
      throw new Error(payload?.error || `Failed to download sheet (${response.status})`)
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename?.toLowerCase().endsWith('.pdf') ? filename : `${filename}.pdf`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
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
    updateMidtransPaymentStatus,
    downloadSheet,
  }
})
