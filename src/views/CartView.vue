<template>
  <div class="container py-5">
    <h1 class="mb-4">Shopping Cart</h1>

    <div v-if="cartStore.cartItems.length === 0" class="text-center py-5">
      <i class="bi bi-cart-x" style="font-size: 4rem"></i>
      <h3 class="mt-3">Your cart is empty</h3>
      <router-link to="/marketplace" class="btn btn-primary mt-3">Continue Shopping</router-link>
    </div>

    <div v-else>
      <div class="row">
        <div class="col-lg-8">
          <!-- Cart Items -->
          <div v-for="item in cartStore.cartItems" :key="item.id" class="card mb-3">
            <div class="row g-0">
              <div class="col-md-4">
                <img :src="item.coverImage" :alt="item.title" class="img-fluid rounded-start" />
              </div>
              <div class="col-md-8">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <h5 class="card-title">{{ item.title }}</h5>
                    <button class="btn btn-sm btn-danger" @click="removeItem(item.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                  <p class="card-text text-muted">{{ item.composer }}</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                      <label class="me-2">Qty:</label>
                      <select
                        class="form-select form-select-sm"
                        style="width: auto"
                        :value="item.quantity"
                        @change="updateQuantity(item.id, $event.target.value)"
                      >
                        <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                      </select>
                    </div>
                    <h5 class="text-primary mb-0">
                      ${{ (item.price * item.quantity).toFixed(2) }}
                    </h5>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <!-- Order Summary -->
          <div class="card">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span>${{ cartStore.cartSubtotal.toFixed(2) }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Shipping:</span>
                <span>$0.00</span>
              </div>
              <hr />
              <div class="d-flex justify-content-between mb-3">
                <strong>Total:</strong>
                <strong class="text-primary">${{ cartStore.cartSubtotal.toFixed(2) }}</strong>
              </div>
              <button class="btn btn-primary w-100" @click="checkout">
                <i class="bi bi-credit-card"></i> Proceed to Checkout
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCartStore } from '../stores/cart'

const cartStore = useCartStore()

function removeItem(id) {
  cartStore.removeFromCart(id)
}

function updateQuantity(id, quantity) {
  cartStore.updateQuantity(id, parseInt(quantity))
}

function checkout() {
  alert('Checkout functionality would be implemented here!')
}
</script>
