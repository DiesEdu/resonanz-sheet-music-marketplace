<template>
  <div class="container py-5" v-if="sheet">
    <div class="row">
      <div class="col-md-5">
        <img :src="sheet.cover_image" :alt="sheet.title" class="img-fluid rounded shadow" />
      </div>
      <div class="col-md-7">
        <h1>{{ sheet.title }}</h1>
        <h3 class="text-muted mb-4">{{ sheet.composer }}</h3>

        <div class="mb-4">
          <span class="badge bg-primary me-2">{{ sheet.instrument }}</span>
          <span class="badge bg-secondary">{{ sheet.difficulty }}</span>
        </div>

        <div class="mb-4">
          <div class="d-flex align-items-center">
            <div class="text-warning me-2">
              <i
                v-for="n in 5"
                :key="n"
                :class="n <= sheet.rating ? 'bi bi-star-fill' : 'bi bi-star'"
              ></i>
            </div>
            <span>{{ sheet.rating }} ({{ sheet.reviews }} reviews)</span>
          </div>
        </div>

        <p class="lead mb-4">{{ sheet.description }}</p>

        <div class="row mb-4">
          <div class="col-md-6"><strong>Pages:</strong> {{ sheet.pages }}</div>
          <div class="col-md-6"><strong>Format:</strong> {{ sheet.format }}</div>
        </div>

        <div class="d-flex align-items-center mb-4">
          <h2 class="text-primary mb-0 me-4">${{ Number(sheet.price).toFixed(2) }}</h2>
          <button class="btn btn-primary btn-lg" @click="addToCart">
            <i class="bi bi-cart-plus"></i> Add to Cart
          </button>
        </div>

        <div class="alert alert-success" v-if="showAddedMessage" role="alert">
          Added to cart successfully!
        </div>
      </div>
    </div>

    <!-- Sample Preview -->
    <div class="row mt-5">
      <div class="col-12">
        <h3>Sample Preview</h3>
        <div class="bg-light p-5 text-center rounded">
          <i class="bi bi-music-note" style="font-size: 4rem"></i>
          <p class="text-muted">First page preview would appear here</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { useCartStore } from '../stores/cart'

const route = useRoute()
const sheetStore = useSheetMusicStore()
const cartStore = useCartStore()

const sheet = ref(null)
const showAddedMessage = ref(false)

onMounted(() => {
  const id = parseInt(route.params.id)
  sheet.value = sheetStore.sheets.find((s) => s.id === id)
})

function addToCart() {
  cartStore.addToCart(sheet.value)
  showAddedMessage.value = true
  setTimeout(() => {
    showAddedMessage.value = false
  }, 3000)
}
</script>
