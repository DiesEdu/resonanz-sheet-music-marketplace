<template>
  <section class="payment-page d-flex align-items-center justify-content-center py-5 px-3">
    <div class="payment-card card animate-fade-scale">
      <div class="card-body p-4 p-md-5 text-center">
        <span class="badge bg-danger mb-3">Payment Failed</span>
        <h1 class="payment-title mb-3">Payment Error</h1>
        <p class="text-muted mb-4">
          We could not complete your payment. Please try again or use another payment method.
        </p>
        <p v-if="orderId" class="order-number mb-4">
          Order Number: <strong>{{ orderId }}</strong>
        </p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <router-link to="/cart" class="btn btn-primary px-4">Back to Cart</router-link>
          <router-link to="/marketplace" class="btn btn-outline-secondary px-4">
            Browse Marketplace
          </router-link>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const orderId = computed(() => {
  const value = route.query.order_id
  return typeof value === 'string' && value.trim() ? value : ''
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
</style>
