<template>
  <main>
    <!-- Hero Section with Parallax -->
    <div class="hero-section text-white py-5 position-relative overflow-hidden">
      <div class="container position-relative" style="z-index: 2">
        <div class="row align-items-center min-vh-50">
          <div class="col-lg-6 animate-slide-left">
            <span class="badge bg-gold mb-3 animate-fade-scale">Welcome to Excellence</span>
            <h1 class="hero-title display-3 fw-bold">Discover Beautiful<br />Sheet Music</h1>
            <p class="lead mb-4 text-gold-light animate-fade-up delay-2">
              Thousands of classical, jazz, and contemporary pieces available for instant download.
              Experience music like never before.
            </p>
            <div class="d-flex gap-3 animate-fade-up delay-3">
              <router-link to="/marketplace" class="btn btn-primary btn-lg">
                <i class="bi bi-shop"></i> Browse Marketplace
              </router-link>
              <button class="btn btn-outline-gold btn-lg" @click="playDemo">
                <i class="bi bi-play-circle"></i> Watch Demo
              </button>
            </div>
          </div>
          <div class="col-lg-6 animate-slide-right">
            <div class="img-hover-zoom rounded-circle shadow-gold">
              <img
                src="https://images.unsplash.com/photo-1507838153414-b4b713384a76?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80"
                alt="Sheet Music Preview"
                class="img-fluid rounded-circle"
                style="animation: float 6s ease-in-out infinite"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Categories with Hover Effects -->
    <div class="container py-5">
      <h2 class="section-title text-center animate-fade-up">Browse by Instrument</h2>
      <div class="row g-4">
        <div
          v-for="(instrument, index) in instruments"
          :key="instrument.name"
          class="col-md-4"
          :class="`animate-fade-scale delay-${index + 1}`"
        >
          <div class="instrument-card card h-100 text-center">
            <div class="card-body">
              <div class="icon-wrapper mb-3">
                <component :is="instrument.icon" size="48" class="text-gold" />
              </div>
              <h5 class="card-title">{{ instrument.name }}</h5>
              <p class="card-text text-muted">{{ instrument.count }} pieces</p>
              <div class="progress mb-3" style="height: 5px">
                <div
                  class="progress-bar bg-gold"
                  :style="{ width: instrument.popularity + '%' }"
                ></div>
              </div>
              <router-link
                to="/marketplace"
                class="btn btn-outline-gold stretched-link"
                @click="handleInstrumentItemClick"
              >
                Explore <i class="bi bi-arrow-right"></i>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Sheets with Counter -->
    <div class="container py-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0 animate-fade-up">Featured Sheet Music</h2>
        <div class="featured-counter animate-fade-up delay-2">
          <span class="badge bg-gold p-3">
            <i class="bi bi-music-note"></i> {{ featuredSheets.length }} Premium Pieces
          </span>
        </div>
      </div>

      <div v-if="featuredSheets.length" class="row g-4">
        <div
          v-for="(sheet, index) in featuredSheets"
          :key="sheet.id"
          class="col-md-4"
          :class="`animate-fade-scale delay-${index + 2}`"
        >
          <SheetCard :sheet="sheet" />
        </div>
      </div>
      <div v-else class="premium-coming-soon animate-fade-up">
        <div class="coming-soon-icon">
          <i class="bi bi-stars"></i>
        </div>
        <p class="mb-0">Premium Pieces will coming soon</p>
      </div>
    </div>

    <!-- Testimonials Section -->
    <div class="bg-light py-5">
      <div class="container">
        <h2 class="section-title text-center animate-fade-up">What Musicians Say</h2>
        <div class="row">
          <div
            v-for="(testimonial, index) in testimonials"
            :key="index"
            class="col-md-4 mb-4"
            :class="`animate-fade-up delay-${index + 1}`"
          >
            <div class="testimonial-card card h-100">
              <div class="card-body">
                <div class="mb-3 rating-stars">
                  <i v-for="n in 5" :key="n" class="bi bi-star-fill"></i>
                </div>
                <p class="card-text fst-italic">"{{ testimonial.text }}"</p>
                <div class="d-flex align-items-center mt-3">
                  <img
                    :src="testimonial.avatar"
                    class="rounded-circle me-3"
                    width="50"
                    height="50"
                  />
                  <div>
                    <h6 class="mb-0">{{ testimonial.name }}</h6>
                    <small class="text-muted">{{ testimonial.title }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import SheetCard from '../components/SheetCard.vue'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { Piano, Guitar, Music } from 'lucide-vue-next'

const sheetStore = useSheetMusicStore()

onMounted(() => {
  sheetStore.fetchSheetBySearch()
})

const instruments = ref([
  { name: 'Piano', icon: Piano, count: 245, popularity: 85 },
  { name: 'Violin', icon: Music, count: 189, popularity: 70 },
  { name: 'Guitar', icon: Guitar, count: 156, popularity: 60 },
])

const featuredSheets = computed(() => sheetStore.sheets.slice(0, 3))

const testimonials = ref([
  {
    text: 'The quality of sheet music is exceptional. Perfect for my students!',
    name: 'Dr. Sarah Chen',
    title: 'Piano Professor',
    avatar: 'https://randomuser.me/api/portraits/women/44.jpg',
  },
  {
    text: 'Beautiful editions and instant download. My go-to for classical pieces.',
    name: 'James Rodriguez',
    title: 'Concert Violinist',
    avatar: 'https://randomuser.me/api/portraits/men/32.jpg',
  },
  {
    text: 'The collection of jazz standards is incredible. Highly recommended!',
    name: 'Michael Thompson',
    title: 'Jazz Pianist',
    avatar: 'https://randomuser.me/api/portraits/men/46.jpg',
  },
])

function handleInstrumentItemClick() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function playDemo() {
  // Demo video modal would go here
  alert('Demo video would play here')
}
</script>

<style scoped>
.bg-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
}

.text-gold-light {
  color: #e9d4b0;
}

.btn-outline-gold {
  border: 2px solid #c5a572;
  color: #c5a572;
  background: transparent;
}

.btn-outline-gold:hover {
  background: #c5a572;
  color: white;
}

.shadow-gold {
  box-shadow: 0 20px 40px rgba(197, 165, 114, 0.3);
}

.instrument-card {
  transition: all 0.3s ease;
  cursor: pointer;
}

.instrument-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 30px rgba(197, 165, 114, 0.2);
}

.instrument-card:hover .icon-wrapper {
  animation: bounce 0.5s ease;
}

@keyframes bounce {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.progress-bar {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
}

.testimonial-card {
  border: none;
  background: white;
  transition: all 0.3s ease;
}

.testimonial-card:hover {
  transform: scale(1.02);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.min-vh-50 {
  min-height: 50vh;
}

.premium-coming-soon {
  min-height: 240px;
  border: 2px dashed rgba(197, 165, 114, 0.45);
  border-radius: 20px;
  background: linear-gradient(135deg, rgba(197, 165, 114, 0.08), rgba(168, 140, 92, 0.12));
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  color: #8b6f4c;
  font-size: 1.2rem;
  font-weight: 600;
  text-align: center;
}

.coming-soon-icon {
  font-size: 2.2rem;
  color: #c5a572;
  animation: iconFloat 1.8s ease-in-out infinite;
}

@keyframes iconFloat {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6px);
  }
}
</style>
