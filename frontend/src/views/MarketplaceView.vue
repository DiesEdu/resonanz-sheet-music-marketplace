<template>
  <div class="container py-5">
    <!-- Page Header -->
    <div class="text-center mb-5">
      <h1 class="section-title animate-fade-up">Sheet Music Marketplace</h1>
      <p class="lead text-muted animate-fade-up delay-1">
        Discover thousands of premium sheet music pieces
      </p>
    </div>

    <!-- Filters with Animations -->
    <div class="filters-wrapper animate-fade-up delay-2">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="filter-group">
            <label class="form-label text-muted">Instrument</label>
            <select class="form-select form-select-lg" v-model="filters.instrument">
              <option value="">All Instruments</option>
              <option v-for="inst in instruments" :key="inst" :value="inst">{{ inst }}</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="filter-group">
            <label class="form-label text-muted">Difficulty</label>
            <select class="form-select form-select-lg" v-model="filters.difficulty">
              <option value="">All Levels</option>
              <option value="Beginner">Beginner</option>
              <option value="Intermediate">Intermediate</option>
              <option value="Advanced">Advanced</option>
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="filter-group">
            <label class="form-label text-muted">Search</label>
            <input
              type="text"
              class="form-control form-control-lg"
              placeholder="Search by title or composer..."
              v-model="filters.search"
            />
          </div>
        </div>
        <div class="col-md-3">
          <div class="filter-group">
            <label class="form-label text-muted">Sort By</label>
            <select class="form-select form-select-lg" v-model="sortBy">
              <option value="title">Title</option>
              <option value="price">Price</option>
              <option value="rating">Rating</option>
              <option value="composer">Composer</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Active Filters -->
      <div class="active-filters mt-3" v-if="hasActiveFilters">
        <span class="me-2 text-muted">Active filters:</span>
        <span class="filter-badge" v-if="filters.instrument">
          {{ filters.instrument }}
          <i class="bi bi-x" @click="filters.instrument = ''"></i>
        </span>
        <span class="filter-badge" v-if="filters.difficulty">
          {{ filters.difficulty }}
          <i class="bi bi-x" @click="filters.difficulty = ''"></i>
        </span>
        <span class="filter-badge" v-if="filters.search">
          "{{ filters.search }}"
          <i class="bi bi-x" @click="filters.search = ''"></i>
        </span>
        <button class="btn btn-link btn-sm" @click="clearAllFilters" v-if="hasActiveFilters">
          Clear all
        </button>
      </div>
    </div>

    <!-- Results Counter -->
    <div class="d-flex justify-content-between align-items-center my-4">
      <p class="text-muted mb-0 animate-fade-up delay-3">
        Showing <strong>{{ filteredSheets.length }}</strong> results
      </p>
      <div class="view-options">
        <button
          class="btn btn-outline-gold btn-sm me-2"
          :class="{ active: viewMode === 'grid' }"
          @click="viewMode = 'grid'"
        >
          <i class="bi bi-grid-3x3-gap-fill"></i>
        </button>
        <button
          class="btn btn-outline-gold btn-sm"
          :class="{ active: viewMode === 'list' }"
          @click="viewMode = 'list'"
        >
          <i class="bi bi-list-ul"></i>
        </button>
      </div>
    </div>

    <!-- Results Grid/List -->
    <transition name="fade" mode="out-in">
      <div v-if="filteredSheets.length > 0" :key="viewMode">
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="row g-4">
          <div
            v-for="(sheet, index) in filteredSheets"
            :key="sheet.id"
            class="col-md-4"
            :class="`animate-fade-scale delay-${(index % 4) + 1}`"
          >
            <SheetCard :sheet="sheet" />
          </div>
        </div>

        <!-- List View -->
        <div v-else class="list-view">
          <div
            v-for="(sheet, index) in filteredSheets"
            :key="sheet.id"
            class="list-item animate-slide-left"
            :class="`delay-${(index % 4) + 1}`"
          >
            <div class="card mb-3">
              <div class="row g-0">
                <div class="col-md-3">
                  <img
                    :src="sheet.cover_image"
                    class="img-fluid rounded-start"
                    :alt="sheet.title"
                  />
                </div>
                <div class="col-md-9">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h5 class="card-title">{{ sheet.title }}</h5>
                        <p class="card-text text-muted">{{ sheet.composer }}</p>
                      </div>
                      <div class="text-end">
                        <h4 class="text-gold">${{ Number(sheet.price).toFixed(2) }}</h4>
                        <button class="btn btn-primary btn-sm" @click="addToCart(sheet)">
                          <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                      </div>
                    </div>
                    <div class="mt-2">
                      <span class="badge bg-gold-light me-2">{{ sheet.instrument }}</span>
                      <span class="badge bg-secondary me-2">{{ sheet.difficulty }}</span>
                      <span class="badge bg-info text-dark">{{ sheet.format }}</span>
                    </div>
                    <p class="card-text mt-2">{{ sheet.description.substring(0, 150) }}...</p>
                    <div class="rating-stars">
                      <i
                        v-for="n in 5"
                        :key="n"
                        :class="n <= sheet.rating ? 'bi bi-star-fill' : 'bi bi-star'"
                        style="color: #c5a572"
                      >
                      </i>
                      <small class="ms-2">({{ sheet.reviews }} reviews)</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state text-center py-5 animate-fade-scale">
        <div class="empty-state-icon mb-4">
          <i class="bi bi-music-note-beamed"></i>
        </div>
        <h3>No sheet music found</h3>
        <p class="text-muted">Try adjusting your filters or search criteria</p>
        <button class="btn btn-gold mt-3" @click="clearAllFilters">
          <i class="bi bi-arrow-repeat"></i> Reset Filters
        </button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import SheetCard from '../components/SheetCard.vue'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { useCartStore } from '../stores/cart'

const sheetStore = useSheetMusicStore()
const cartStore = useCartStore()

const viewMode = ref('grid')

const instruments = ['Piano', 'Violin', 'Guitar', 'Cello', 'Flute']

const filters = ref({
  instrument: '',
  difficulty: '',
  search: '',
})

const sortBy = ref('title')
let filterFetchTimeout

const hasActiveFilters = computed(() => {
  return filters.value.instrument || filters.value.difficulty || filters.value.search
})

const filteredSheets = computed(() => {
  let result = [...sheetStore.sheets]

  // Sorting
  result.sort((a, b) => {
    if (sortBy.value === 'price') {
      return a.price - b.price
    } else if (sortBy.value === 'rating') {
      return b.rating - a.rating
    } else if (sortBy.value === 'composer') {
      return a.composer.localeCompare(b.composer)
    } else {
      return a.title.localeCompare(b.title)
    }
  })

  return result
})

function addToCart(sheet) {
  cartStore.addToCart(sheet)
  // Show toast notification
}

function clearAllFilters() {
  filters.value = {
    instrument: '',
    difficulty: '',
    search: '',
  }
}

watch(
  filters,
  (nextFilters) => {
    if (filterFetchTimeout) {
      clearTimeout(filterFetchTimeout)
    }

    filterFetchTimeout = setTimeout(() => {
      sheetStore.fetchSheetBySearch(
        nextFilters.instrument,
        nextFilters.difficulty,
        nextFilters.search,
      )
    }, 300)
  },
  { deep: true },
)

onMounted(() => {
  sheetStore.fetchSheetBySearch(
    filters.value.instrument,
    filters.value.difficulty,
    filters.value.search,
  )
})

onBeforeUnmount(() => {
  if (filterFetchTimeout) {
    clearTimeout(filterFetchTimeout)
  }
})
</script>

<style scoped>
.filters-wrapper {
  background: white;
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.filter-group {
  transition: all 0.3s ease;
}

.filter-group:focus-within {
  transform: translateY(-2px);
}

.filter-group .form-label {
  font-weight: 500;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 1px;
}

.filter-group .form-select,
.filter-group .form-control {
  border: 2px solid #e9ecef;
  border-radius: 15px;
  transition: all 0.3s ease;
}

.filter-group .form-select:focus,
.filter-group .form-control:focus {
  border-color: #c5a572;
  box-shadow: 0 0 0 0.2rem rgba(197, 165, 114, 0.25);
}

.filter-badge {
  display: inline-block;
  padding: 5px 15px;
  background: rgba(197, 165, 114, 0.1);
  border: 1px solid #c5a572;
  border-radius: 50px;
  margin-right: 10px;
  margin-bottom: 10px;
  font-size: 0.9rem;
}

.filter-badge i {
  cursor: pointer;
  margin-left: 5px;
  transition: all 0.2s ease;
}

.filter-badge i:hover {
  color: #dc3545;
  transform: scale(1.2);
}

.view-options .btn {
  border: 2px solid #e9ecef;
  color: #6c757d;
  padding: 0.5rem 1rem;
}

.view-options .btn.active {
  background: #c5a572;
  border-color: #c5a572;
  color: white;
}

.empty-state-icon {
  font-size: 5rem;
  color: #c5a572;
  opacity: 0.5;
  animation: float 3s ease-in-out infinite;
}

.list-item {
  transition: all 0.3s ease;
}

.list-item:hover {
  transform: translateX(10px);
}

.btn-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
  color: white;
  border: none;
  padding: 10px 30px;
  border-radius: 50px;
}

.btn-gold:hover {
  background: linear-gradient(135deg, #b59460, #8f7648);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(197, 165, 114, 0.3);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
