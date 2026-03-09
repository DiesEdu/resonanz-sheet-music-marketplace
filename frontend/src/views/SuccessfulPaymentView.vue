<template>
  <section class="payment-page d-flex align-items-center justify-content-center py-5 px-3">
    <div class="payment-card card animate-fade-scale">
      <div class="card-body p-4 p-md-5 text-center">
        <span class="badge bg-success mb-3">Payment Complete</span>
        <h1 class="payment-title mb-3">Payment Successful</h1>
        <p class="text-muted mb-4">
          Thank you. Your payment was received successfully and your order is being processed.
        </p>
        <p v-if="orderNumber" class="order-number mb-4">
          Order Number: <strong>{{ orderNumber }}</strong>
        </p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <router-link to="/my-orders" class="btn btn-primary px-4">View My Orders</router-link>
          <router-link to="/marketplace" class="btn btn-outline-secondary px-4">
            Continue Shopping
          </router-link>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOrderStore } from '@/stores/order'

const { updateMidtransPaymentStatus } = useOrderStore()

const route = useRoute()
const orderNumber = computed(() => {
  const value = route.query.order_id
  return typeof value === 'string' && value.trim() ? value : ''
})

async function checkUpdatedOrderStatus(orderNumber) {
  // Update payment status before checking it, to ensure we have the latest info from Midtrans
  await updateMidtransPaymentStatus(orderNumber)
}

onMounted(() => {
  if (orderNumber.value) {
    checkUpdatedOrderStatus(orderNumber.value)
  }
})
</script>

<style scoped>
.payment-page {
  min-height: calc(100vh - 220px);
}

.payment-card {
  width: 100%;
  max-width: 640px;
  border-radius: 24px;
}

.payment-title {
  font-size: 2.1rem;
  font-weight: 600;
  color: #2c1810;
}

.order-number {
  color: #2c1810;
}
</style>
