<template>
  <section class="my-orders-page py-5 px-3">
    <div class="container" style="max-width: 980px">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
              <h1 class="h3 mb-1">My Orders</h1>
              <p class="text-muted mb-0">
                Your complete order history and purchased sheet music items.
              </p>
            </div>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              :disabled="isLoading"
              @click="loadOrders"
            >
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              Refresh
            </button>
          </div>

          <div v-if="loadError" class="alert alert-danger py-2 mb-3" role="alert">
            {{ loadError }}
          </div>

          <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-3 mb-0">Loading your order history...</p>
          </div>

          <div v-else-if="orders.length === 0" class="text-center py-5">
            <i class="bi bi-receipt-cutoff fs-1 text-muted"></i>
            <h2 class="h5 mt-3 mb-2">No Orders Yet</h2>
            <p class="text-muted mb-3">You have not completed any purchases.</p>
            <router-link to="/marketplace" class="btn btn-primary">Browse Marketplace</router-link>
          </div>

          <div v-else class="d-grid gap-3">
            <article
              v-for="order in orders"
              :key="order._orderId || order.order_number"
              class="border rounded-4 p-3 p-md-4 order-card"
            >
              <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                  <p class="mb-1 fw-semibold">{{ order.order_number || `Order #${order.id}` }}</p>
                  <p class="mb-0 text-muted small">{{ formatDate(order.created_at) }}</p>
                </div>
                <div class="text-md-end">
                  <p class="mb-1 fw-semibold">{{ formatCurrency(order.total_amount) }}</p>
                  <div class="d-flex gap-2 justify-content-md-end">
                    <span class="badge text-bg-light border">{{
                      normalizeStatus(order.status)
                    }}</span>
                    <span :class="['badge', paymentStatusClass(order.payment_status)]">
                      {{ normalizeStatus(order.payment_status) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="mt-3">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary"
                  :disabled="!order._orderId || orderDetailsState[order._orderId]?.loading"
                  @click="toggleOrderItems(order._orderId)"
                >
                  <span
                    v-if="orderDetailsState[order._orderId]?.loading"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                  ></span>
                  {{ orderDetailsState[order._orderId]?.open ? 'Hide Items' : 'View Items' }}
                </button>
              </div>

              <div v-if="orderDetailsState[order._orderId]?.open" class="mt-3">
                <p v-if="orderDetailsState[order._orderId]?.error" class="text-danger mb-0 small">
                  {{ orderDetailsState[order._orderId]?.error }}
                </p>
                <ul
                  v-else-if="orderDetailsState[order._orderId]?.items?.length"
                  class="list-group list-group-flush rounded-3 overflow-hidden border"
                >
                  <li
                    v-for="item in orderDetailsState[order._orderId].items"
                    :key="item.id"
                    class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2"
                  >
                    <div>
                      <p class="mb-0 fw-semibold">{{ item.title || 'Untitled Sheet' }}</p>
                      <small class="text-muted">{{ item.composer || 'Unknown composer' }}</small>
                    </div>
                    <small class="text-muted">
                      {{ item.quantity }} x {{ formatCurrency(item.price) }}
                    </small>
                  </li>
                </ul>
                <p v-else class="text-muted mb-0 small">No items found in this order.</p>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/order'

const router = useRouter()
const orderStore = useOrderStore()
const orders = ref([])
const isLoading = ref(false)
const loadError = ref('')
const orderDetailsState = ref({})

function handleUnauthorized(errorMessage) {
  const normalized = `${errorMessage || ''}`.toLowerCase()
  if (normalized.includes('unauthorized') || normalized.includes('forbidden')) {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    window.dispatchEvent(new Event('auth-changed'))
    router.push('/login')
    return true
  }
  return false
}

function normalizeStatus(status) {
  if (!status) return 'Unknown'
  return `${status}`.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function paymentStatusClass(paymentStatus) {
  const normalized = `${paymentStatus || ''}`.toLowerCase()
  if (normalized === 'paid') return 'text-bg-success'
  if (normalized === 'pending') return 'text-bg-warning'
  return 'text-bg-secondary'
}

function formatDate(value) {
  if (!value) return '-'
  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) return value

  return parsed.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatCurrency(amount) {
  const numericAmount = Number(amount || 0)
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(numericAmount)
}

function resolveOrderId(order) {
  const rawId = order?.id ?? order?.order_id ?? order?.orderId ?? null
  if (rawId === null || rawId === undefined || rawId === '') return null

  const numeric = Number(rawId)
  return Number.isInteger(numeric) && numeric > 0 ? numeric : null
}

function ensureDetailsState(orderId) {
  if (!orderDetailsState.value[orderId]) {
    orderDetailsState.value[orderId] = {
      open: false,
      loading: false,
      error: '',
      items: [],
    }
  }
}

async function loadOrders() {
  isLoading.value = true
  loadError.value = ''
  try {
    const payload = await orderStore.getMyOrders()
    orders.value = (Array.isArray(payload) ? payload : []).map((order) => ({
      ...order,
      _orderId: resolveOrderId(order),
    }))
  } catch (error) {
    const message = error?.message || 'Unable to load order history.'
    if (handleUnauthorized(message)) return
    loadError.value = message
  } finally {
    isLoading.value = false
  }
}

async function toggleOrderItems(orderId) {
  if (!orderId) {
    return
  }

  ensureDetailsState(orderId)
  const state = orderDetailsState.value[orderId]

  if (state.open) {
    state.open = false
    return
  }

  state.open = true
  if (state.items.length > 0 || state.loading) {
    return
  }

  state.loading = true
  state.error = ''
  try {
    const orderDetail = await orderStore.getOrderById(orderId)
    state.items = Array.isArray(orderDetail?.items) ? orderDetail.items : []
  } catch (error) {
    const message = error?.message || 'Unable to load order items.'
    if (handleUnauthorized(message)) return
    state.error = message
  } finally {
    state.loading = false
  }
}

onMounted(() => {
  loadOrders()
})
</script>

<style scoped>
.my-orders-page {
  min-height: calc(100vh - 220px);
}

.order-card {
  background: #fff;
}
</style>
