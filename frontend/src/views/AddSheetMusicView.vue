<template>
  <section class="container py-5 add-sheet-page">
    <div class="text-center mb-4">
      <h1 class="section-title animate-fade-up">Add New Sheet Music</h1>
      <p class="text-muted animate-fade-up delay-1">
        Publish a new score in your marketplace catalog.
      </p>
      <span class="badge bg-gold mb-3 animate-fade-scale">Seller Portal</span>
    </div>

    <div class="card form-card animate-fade-scale delay-2">
      <div class="card-body p-4 p-md-5">
        <form class="row g-3" @submit.prevent="handleSubmit" novalidate>
          <div class="col-md-6">
            <label for="title" class="form-label">Title</label>
            <input
              id="title"
              v-model.trim="form.title"
              type="text"
              class="form-control"
              placeholder="Moonlight Sonata, Op. 27 No. 2"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="composer" class="form-label">Composer</label>
            <input
              id="composer"
              v-model.trim="form.composer"
              type="text"
              class="form-control"
              placeholder="Ludwig van Beethoven"
              required
            />
          </div>

          <div class="col-md-4">
            <label for="instrument" class="form-label">Instrument</label>
            <select id="instrument" v-model="form.instrument" class="form-select" required>
              <option disabled value="">Select instrument</option>
              <option v-for="instrument in instruments" :key="instrument" :value="instrument">
                {{ instrument }}
              </option>
            </select>
          </div>

          <div class="col-md-4">
            <label for="difficulty" class="form-label">Difficulty</label>
            <select id="difficulty" v-model="form.difficulty" class="form-select" required>
              <option disabled value="">Select level</option>
              <option value="Beginner">Beginner</option>
              <option value="Intermediate">Intermediate</option>
              <option value="Advanced">Advanced</option>
            </select>
          </div>

          <div class="col-md-4">
            <label for="format" class="form-label">Format</label>
            <select id="format" v-model="form.format" class="form-select" required>
              <option value="PDF">PDF</option>
              <option value="PDF + Audio">PDF + Audio</option>
              <option value="PDF + MIDI">PDF + MIDI</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="price" class="form-label">Price (USD)</label>
            <input
              id="price"
              v-model.number="form.price"
              type="number"
              min="0"
              step="0.01"
              class="form-control"
              placeholder="12.99"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="pages" class="form-label">Pages</label>
            <input
              id="pages"
              v-model.number="form.pages"
              type="number"
              min="1"
              step="1"
              class="form-control"
              placeholder="24"
              required
            />
          </div>

          <div class="col-12">
            <label for="coverImage" class="form-label">Cover Image URL</label>
            <input
              id="coverImage"
              v-model.trim="form.coverImage"
              type="url"
              class="form-control"
              placeholder="https://example.com/cover.jpg"
              required
            />
          </div>

          <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea
              id="description"
              v-model.trim="form.description"
              class="form-control"
              rows="4"
              placeholder="Describe arrangement details, edition notes, and target players..."
              required
            ></textarea>
          </div>

          <div v-if="errorMessage" class="col-12">
            <div class="alert alert-danger py-2 mb-0">{{ errorMessage }}</div>
          </div>

          <div v-if="successMessage" class="col-12">
            <div class="alert alert-success py-2 mb-0">{{ successMessage }}</div>
          </div>

          <div class="col-12 d-flex gap-2 pt-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-plus-circle me-1"></i> Publish Sheet Music
            </button>
            <router-link to="/marketplace" class="btn btn-outline-primary">
              Back to Marketplace
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useSheetMusicStore } from '../stores/sheetMusic'

const sheetStore = useSheetMusicStore()

const instruments = ['Piano', 'Violin', 'Guitar', 'Cello', 'Flute']

const form = reactive({
  title: '',
  composer: '',
  instrument: '',
  difficulty: '',
  format: 'PDF',
  price: null,
  pages: null,
  coverImage: '',
  description: '',
})

const errorMessage = ref('')
const successMessage = ref('')

function resetForm() {
  form.title = ''
  form.composer = ''
  form.instrument = ''
  form.difficulty = ''
  form.format = 'PDF'
  form.price = null
  form.pages = null
  form.coverImage = ''
  form.description = ''
}

function handleSubmit() {
  errorMessage.value = ''
  successMessage.value = ''

  if (
    !form.title ||
    !form.composer ||
    !form.instrument ||
    !form.difficulty ||
    !form.format ||
    !form.coverImage ||
    !form.description
  ) {
    errorMessage.value = 'Please complete all fields.'
    return
  }

  if (form.price === null || form.price < 0) {
    errorMessage.value = 'Price must be zero or higher.'
    return
  }

  if (!Number.isInteger(form.pages) || form.pages < 1) {
    errorMessage.value = 'Pages must be a whole number greater than zero.'
    return
  }

  sheetStore.addSheet({ ...form })
  successMessage.value = 'Sheet music published successfully.'
  resetForm()
}
</script>

<style scoped>
.add-sheet-page {
  min-height: calc(100vh - 220px);
}

.form-card {
  max-width: 900px;
  margin: 0 auto;
  border-radius: 24px;
}

.form-label {
  font-weight: 600;
}

.form-control,
.form-select {
  border-radius: 14px;
  padding: 0.7rem 1rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #c5a572;
  box-shadow: 0 0 0 0.2rem rgba(197, 165, 114, 0.2);
}

.bg-gold {
  background: linear-gradient(135deg, #c5a572, #a88c5c);
}
</style>
