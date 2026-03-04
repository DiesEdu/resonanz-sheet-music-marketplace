<template>
  <div class="card h-100 sheet-card">
    <div class="position-relative">
      <router-link :to="'/sheet/' + sheet.id" class="text-decoration-none">
        <div class="image-wrapper">
          <img :src="sheet.coverImage" class="card-img-top" :alt="sheet.title" />
          <div class="image-overlay">
            <div class="overlay-content">
              <i class="bi bi-eye"></i>
              <span>View Details</span>
            </div>
          </div>
        </div>
      </router-link>

      <!-- Premium Badge -->
      <div class="position-absolute top-0 end-0 m-3">
        <span class="badge bg-gold" v-if="sheet.price > 15">
          <i class="bi bi-star-fill"></i> Premium
        </span>
      </div>
    </div>

    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <router-link :to="'/sheet/' + sheet.id" class="text-decoration-none text-dark flex-grow-1">
          <h5 class="card-title mb-1">{{ sheet.title }}</h5>
        </router-link>
        <button
          class="btn btn-icon btn-favorite"
          @click="toggleFavorite"
          :class="{ active: isFavorite }"
        >
          <i class="bi" :class="isFavorite ? 'bi-heart-fill' : 'bi-heart'"></i>
        </button>
      </div>

      <p class="card-text text-muted mb-2">{{ sheet.composer }}</p>

      <div class="mb-3">
        <span class="badge bg-gold-light me-1">
          <i class="bi bi-music-note"></i> {{ sheet.instrument }}
        </span>
        <span class="badge bg-secondary">
          <i class="bi bi-bar-chart"></i> {{ sheet.difficulty }}
        </span>
      </div>

      <div class="d-flex align-items-center mb-3">
        <div class="rating-stars me-2">
          <i
            v-for="n in 5"
            :key="n"
            :class="n <= sheet.rating ? 'bi bi-star-fill' : 'bi bi-star'"
            :style="{ animationDelay: n * 0.1 + 's' }"
          >
          </i>
        </div>
        <small class="text-muted">({{ sheet.reviews }})</small>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div class="price-wrapper">
          <span class="price-tag">${{ sheet.price.toFixed(2) }}</span>
          <small class="text-muted ms-2">PDF</small>
        </div>
        <button class="btn btn-primary btn-sm rounded-pill px-4" @click="addToCart">
          <i class="bi bi-cart-plus"></i>
          <span class="ms-2">Add</span>
        </button>
      </div>
    </div>

    <!-- Add to Cart Animation -->
    <transition name="cart-animation">
      <div v-if="showAddedAnimation" class="cart-animation-overlay">
        <i class="bi bi-check-circle-fill text-success"></i>
        <span>Added to cart!</span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useCartStore } from '../stores/cart'

const props = defineProps({
  sheet: {
    type: Object,
    required: true,
  },
})

const cartStore = useCartStore()
const isFavorite = ref(false)
const showAddedAnimation = ref(false)

function addToCart(event) {
  event.preventDefault()
  cartStore.addToCart(props.sheet)

  // Show animation
  showAddedAnimation.value = true
  setTimeout(() => {
    showAddedAnimation.value = false
  }, 1500)

  // Button click animation
  const button = event.currentTarget
  button.classList.add('btn-clicked')
  setTimeout(() => {
    button.classList.remove('btn-clicked')
  }, 300)
}

function toggleFavorite(event) {
  event.preventDefault()
  isFavorite.value = !isFavorite.value
}
</script>

<style scoped>
.sheet-card {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border: none;
  background: white;
  position: relative;
}

.sheet-card:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 30px 40px rgba(197, 165, 114, 0.2);
}

.image-wrapper {
  position: relative;
  overflow: hidden;
  border-radius: 15px 15px 0 0;
}

.image-wrapper img {
  transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

.sheet-card:hover .image-wrapper img {
  transform: scale(1.1);
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.sheet-card:hover .image-overlay {
  opacity: 1;
}

.overlay-content {
  color: white;
  text-align: center;
  transform: translateY(20px);
  transition: transform 0.3s ease;
}

.sheet-card:hover .overlay-content {
  transform: translateY(0);
}

.overlay-content i {
  font-size: 2rem;
  margin-bottom: 10px;
}

.bg-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
  color: white;
}

.bg-gold-light {
  background: rgba(197, 165, 114, 0.2);
  color: #8b6f4c;
}

.btn-icon {
  width: 35px;
  height: 35px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: none;
  background: transparent;
  transition: all 0.3s ease;
}

.btn-icon:hover {
  background: rgba(197, 165, 114, 0.1);
  transform: scale(1.1);
}

.btn-icon.active {
  color: #c5a572;
}

.btn-icon.active i {
  animation: heartBeat 0.3s ease;
}

@keyframes heartBeat {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.3);
  }
}

.btn-clicked {
  animation: buttonPop 0.3s ease;
}

@keyframes buttonPop {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(0.95);
  }
}

.cart-animation-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  padding: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.cart-animation-leave-active {
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(100%);
  }
}

.price-wrapper {
  position: relative;
}

.price-tag {
  position: relative;
  display: inline-block;
  padding: 5px 15px;
  background: linear-gradient(135deg, #f8f4ed, #f0e8dd);
  color: #8b6f4c;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1.1rem;
}

.price-tag::before {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(135deg, #c5a572, #a88c5c);
  border-radius: 52px;
  z-index: -1;
  opacity: 0.3;
}
</style>
