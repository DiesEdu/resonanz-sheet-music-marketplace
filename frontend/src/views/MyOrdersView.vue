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
                <div class="d-flex flex-wrap gap-2 order-actions">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    :disabled="!order._orderId || orderDetailsState[order._orderId]?.loading"
                    :aria-label="
                      orderDetailsState[order._orderId]?.open ? 'Hide Items' : 'View Items'
                    "
                    :title="orderDetailsState[order._orderId]?.open ? 'Hide Items' : 'View Items'"
                    @click="toggleOrderItems(order._orderId)"
                  >
                    <span
                      v-if="orderDetailsState[order._orderId]?.loading"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                    ></span>
                    <i
                      v-else
                      :class="
                        orderDetailsState[order._orderId]?.open
                          ? 'bi bi-eye-slash me-sm-1'
                          : 'bi bi-eye me-sm-1'
                      "
                      aria-hidden="true"
                    ></i>
                    <span class="d-none d-sm-inline">{{
                      orderDetailsState[order._orderId]?.open ? 'Hide Items' : 'View Items'
                    }}</span>
                  </button>
                  <button
                    v-if="canProcessPayment(order)"
                    type="button"
                    class="btn btn-sm btn-primary"
                    :disabled="!order._orderId || actionState[order._orderId]?.processing"
                    aria-label="Process"
                    title="Process"
                    @click="processPayment(order)"
                  >
                    <span
                      v-if="actionState[order._orderId]?.processing"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                    ></span>
                    <i v-else class="bi bi-credit-card me-sm-1" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Process</span>
                  </button>
                  <button
                    v-if="canCheckPaymentStatus(order)"
                    type="button"
                    class="btn btn-sm btn-outline-info"
                    :disabled="!order._orderId || actionState[order._orderId]?.checkingStatus"
                    aria-label="Check Payment Status"
                    title="Check Payment Status"
                    @click="checkPaymentStatus(order)"
                  >
                    <span
                      v-if="actionState[order._orderId]?.checkingStatus"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                    ></span>
                    <i v-else class="bi bi-arrow-repeat me-sm-1" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Check Status</span>
                  </button>
                  <button
                    v-if="canCancelOrder(order)"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    :disabled="!order._orderId || actionState[order._orderId]?.cancelling"
                    aria-label="Cancel Order"
                    title="Cancel Order"
                    @click="openCancelConfirm(order)"
                  >
                    <span
                      v-if="actionState[order._orderId]?.cancelling"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                    ></span>
                    <i v-else class="bi bi-x-circle me-sm-1" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Cancel Order</span>
                  </button>
                </div>
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
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                      <small class="text-muted">
                        {{ item.quantity }} x {{ formatCurrency(item.price) }}
                      </small>
                      <button
                        v-if="canDownloadItem(order, item)"
                        type="button"
                        class="btn btn-sm btn-outline-success"
                        :disabled="downloadState[item.sheet_id]?.downloading"
                        @click="downloadItem(order, item)"
                      >
                        <span
                          v-if="downloadState[item.sheet_id]?.downloading"
                          class="spinner-border spinner-border-sm me-1"
                          role="status"
                        ></span>
                        <i v-else class="bi bi-download me-1" aria-hidden="true"></i>
                        Download PDF
                      </button>
                    </div>
                  </li>
                </ul>
                <p v-else class="text-muted mb-0 small">No items found in this order.</p>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>

    <transition name="cancel-success-toast">
      <div
        v-if="cancelSuccessMessage"
        class="cancel-success-toast alert alert-success shadow-sm mb-0"
        role="status"
        aria-live="polite"
      >
        {{ cancelSuccessMessage }}
      </div>
    </transition>

    <transition name="confirm-backdrop">
      <div
        v-if="showCancelConfirm"
        class="cancel-confirm-backdrop"
        @click="closeCancelConfirm"
      ></div>
    </transition>
    <transition name="confirm-dialog">
      <div v-if="showCancelConfirm" class="cancel-confirm-wrap" role="dialog" aria-modal="true">
        <div class="cancel-confirm-card">
          <div class="cancel-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <h2 class="h5 mb-2">Cancel this order?</h2>
          <p class="text-muted mb-4">
            This action cannot be undone and payment for this order will be closed.
          </p>
          <div class="d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="closeCancelConfirm"
            >
              Keep Order
            </button>
            <button
              type="button"
              class="btn btn-danger btn-sm"
              :disabled="isConfirmCancelling"
              @click="confirmCancelOrder"
            >
              <span v-if="isConfirmCancelling" class="spinner-border spinner-border-sm me-2"></span>
              Yes, Cancel
            </button>
          </div>
        </div>
      </div>
    </transition>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/order'

const router = useRouter()
const orderStore = useOrderStore()
const orders = ref([])
const isLoading = ref(false)
const loadError = ref('')
const orderDetailsState = ref({})
const actionState = ref({})
const downloadState = ref({})
const showCancelConfirm = ref(false)
const cancelTargetOrder = ref(null)
const cancelSuccessMessage = ref('')
let cancelSuccessTimer = null

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

function ensureActionState(orderId) {
  if (!actionState.value[orderId]) {
    actionState.value[orderId] = {
      processing: false,
      checkingStatus: false,
      cancelling: false,
    }
  }
}

function ensureDownloadState(sheetId) {
  if (!downloadState.value[sheetId]) {
    downloadState.value[sheetId] = { downloading: false }
  }
}

function canProcessPayment(order) {
  const paymentStatus = `${order?.payment_status || ''}`.toLowerCase()
  const orderStatus = `${order?.status || ''}`.toLowerCase()
  return paymentStatus === 'pending' && orderStatus !== 'cancelled'
}

function canCancelOrder(order) {
  return `${order?.status || ''}`.toLowerCase() === 'pending'
}

function canCheckPaymentStatus(order) {
  const paymentStatus = `${order?.payment_status || ''}`.toLowerCase()
  const orderStatus = `${order?.status || ''}`.toLowerCase()
  return paymentStatus === 'pending' && orderStatus !== 'cancelled'
}

function isPaid(order) {
  return `${order?.payment_status || ''}`.toLowerCase() === 'paid'
}

function canDownloadItem(order, item) {
  return isPaid(order) && Boolean(item?.sheet_id)
}

async function loadOrders(options = {}) {
  const silent = Boolean(options?.silent)
  if (!silent) {
    isLoading.value = true
  }
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
    if (!silent) {
      isLoading.value = false
    }
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

async function processPayment(order) {
  const orderId = order?._orderId
  if (!orderId || !canProcessPayment(order)) return

  ensureActionState(orderId)
  const state = actionState.value[orderId]
  if (state.processing) return

  state.processing = true
  loadError.value = ''
  try {
    await orderStore.openPaymentModal({
      ...order,
      order_id: orderId,
      billing_info: {
        name: order.billing_name || '',
        email: order.billing_email || '',
        phone: order.billing_phone || '',
      },
    })
  } catch (error) {
    const message = error?.message || 'Unable to start payment process.'
    if (handleUnauthorized(message)) return
    loadError.value = message
  } finally {
    state.processing = false
  }
}

async function checkPaymentStatus(order) {
  const orderId = order?._orderId
  const orderNumber = `${order?.order_number || ''}`.trim()
  if (!orderId || !orderNumber || !canCheckPaymentStatus(order)) return

  ensureActionState(orderId)
  const state = actionState.value[orderId]
  if (state.checkingStatus) return

  state.checkingStatus = true
  loadError.value = ''
  try {
    await orderStore.updateMidtransPaymentStatus(orderNumber)
    await loadOrders({ silent: true })
  } catch (error) {
    const message = error?.message || 'Unable to check payment status.'
    if (handleUnauthorized(message)) return
    loadError.value = message
  } finally {
    state.checkingStatus = false
  }
}

function openCancelConfirm(order) {
  if (!order?._orderId || !canCancelOrder(order)) return
  cancelTargetOrder.value = order
  showCancelConfirm.value = true
}

function closeCancelConfirm(force = false) {
  if (!force && isConfirmCancelling.value) return
  showCancelConfirm.value = false
  cancelTargetOrder.value = null
}

function showCancelSuccessPopup(orderNumber) {
  const displayOrderNumber = `${orderNumber || ''}`.trim() || '-'
  cancelSuccessMessage.value = `Your order with order number ${displayOrderNumber} is cancelled.`
  if (cancelSuccessTimer) clearTimeout(cancelSuccessTimer)
  cancelSuccessTimer = setTimeout(() => {
    cancelSuccessMessage.value = ''
    cancelSuccessTimer = null
  }, 3500)
}

const isConfirmCancelling = computed(() => {
  const orderId = cancelTargetOrder.value?._orderId
  if (!orderId) return false
  return Boolean(actionState.value[orderId]?.cancelling)
})

async function confirmCancelOrder() {
  const order = cancelTargetOrder.value
  const orderId = order?._orderId
  if (!orderId || !canCancelOrder(order)) return

  ensureActionState(orderId)
  const state = actionState.value[orderId]
  if (state.cancelling) return

  state.cancelling = true
  loadError.value = ''
  try {
    await orderStore.cancelMyOrder(orderId)
    closeCancelConfirm(true)
    orders.value = orders.value.map((item) =>
      item?._orderId === orderId ? { ...item, status: 'cancelled' } : item,
    )
    showCancelSuccessPopup(order.order_number)
    await loadOrders({ silent: true })
  } catch (error) {
    const message = error?.message || 'Unable to cancel order.'
    if (handleUnauthorized(message)) return
    loadError.value = message
  } finally {
    state.cancelling = false
  }
}

async function downloadItem(order, item) {
  const sheetId = item?.sheet_id
  if (!sheetId || !canDownloadItem(order, item)) return

  ensureDownloadState(sheetId)
  const state = downloadState.value[sheetId]
  if (state.downloading) return

  state.downloading = true
  loadError.value = ''
  try {
    await orderStore.downloadSheet(sheetId, item.title || 'sheet-music')
  } catch (error) {
    const message = error?.message || 'Unable to download file.'
    if (handleUnauthorized(message)) return
    loadError.value = message
  } finally {
    state.downloading = false
  }
}

onMounted(() => {
  loadOrders()
})

onBeforeUnmount(() => {
  if (cancelSuccessTimer) clearTimeout(cancelSuccessTimer)
})
</script>

<style scoped>
.my-orders-page {
  min-height: calc(100vh - 220px);
  position: relative;
}

.order-card {
  background: #fff;
}

.cancel-success-toast {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 1080;
  max-width: min(92vw, 460px);
}

.cancel-confirm-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
  z-index: 1050;
}

.cancel-confirm-wrap {
  position: fixed;
  inset: 0;
  display: grid;
  place-items: center;
  padding: 1rem;
  z-index: 1051;
}

.cancel-confirm-card {
  width: min(420px, 100%);
  background: #fff;
  border-radius: 1rem;
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.22);
  border: 1px solid #f0f0f0;
  padding: 1.25rem;
}

.cancel-icon {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 999px;
  background: #fff3cd;
  color: #a15c00;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
}

.confirm-backdrop-enter-active,
.confirm-backdrop-leave-active {
  transition: opacity 0.22s ease;
}

.confirm-backdrop-enter-from,
.confirm-backdrop-leave-to {
  opacity: 0;
}

.confirm-dialog-enter-active,
.confirm-dialog-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}

.confirm-dialog-enter-from,
.confirm-dialog-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.97);
}

.cancel-success-toast-enter-active,
.cancel-success-toast-leave-active {
  transition:
    opacity 0.24s ease,
    transform 0.24s ease;
}

.cancel-success-toast-enter-from,
.cancel-success-toast-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (max-width: 575.98px) {
  .order-actions .btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .order-actions .spinner-border {
    margin-right: 0 !important;
  }
}
</style>
