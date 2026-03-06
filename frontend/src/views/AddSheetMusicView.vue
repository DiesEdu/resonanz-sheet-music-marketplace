<template>
  <section class="container py-5 add-sheet-page">
    <div class="text-center mb-4">
      <h1 class="section-title animate-fade-up">Add New Sheet Music</h1>
      <p class="text-muted animate-fade-up delay-1">
        {{
          isEditMode
            ? 'Update your score from marketplace catalog.'
            : 'Publish a new score in your marketplace catalog.'
        }}
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
              <option v-for="instrument in instruments" :key="instrument.id" :value="instrument.id">
                {{ instrument.name }}
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
              <option value="Professional">Professional</option>
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
            <label for="price" class="form-label">Price (IDR)</label>
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
              <i :class="isEditMode ? 'bi bi-save me-1' : 'bi bi-plus-circle me-1'"></i>
              {{ isEditMode ? 'Update Sheet Music' : 'Publish Sheet Music' }}
            </button>
            <button
              v-if="isEditMode"
              type="button"
              class="btn btn-outline-secondary"
              @click="cancelEdit"
            >
              Cancel Edit
            </button>
            <router-link to="/marketplace" class="btn btn-outline-primary">
              Back to Marketplace
            </router-link>
          </div>
        </form>
      </div>
    </div>

    <div class="card list-card animate-fade-scale delay-2 mt-4">
      <div class="card-body p-4 p-md-5">
        <h2 class="h5 mb-3">My Sheet Music</h2>

        <div v-if="isLoadingList" class="text-muted">Loading your sheet music...</div>

        <div v-else-if="mySheets.length === 0" class="text-muted">
          No sheet music found for your account.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Composer</th>
                <th>Instrument</th>
                <th>Difficulty</th>
                <th class="text-end">Price</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sheet in paginatedMySheets" :key="sheet.id">
                <td>{{ sheet.title }}</td>
                <td>{{ sheet.composer || '-' }}</td>
                <td>{{ sheet.instrument_name || sheet.instrument || '-' }}</td>
                <td>{{ sheet.difficulty || '-' }}</td>
                <td class="text-end">{{ formatPriceIDR(sheet.price) }}</td>
                <td class="text-center">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    title="Edit"
                    @click="startEdit(sheet)"
                  >
                    <i class="bi bi-pen"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="mySheets.length > pageSize"
          class="d-flex justify-content-between align-items-center mt-3"
        >
          <small class="text-muted">Page {{ currentPage }} of {{ totalPages }}</small>
          <div class="d-flex gap-2">
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              :disabled="currentPage === 1"
              @click="goToPage(currentPage - 1)"
            >
              Prev
            </button>
            <button
              v-for="page in totalPages"
              :key="page"
              type="button"
              class="btn btn-sm"
              :class="page === currentPage ? 'btn-primary' : 'btn-outline-secondary'"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              :disabled="currentPage === totalPages"
              @click="goToPage(currentPage + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { useInstruments } from '../stores/instrument'
import { formatPriceIDR } from '@/utils/priceUtils'

const sheetStore = useSheetMusicStore()
const instrumentStore = useInstruments()

const instruments = computed(() => {
  return instrumentStore.instruments || []
})

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
const isLoadingList = ref(true)
const editingSheetId = ref(null)
const currentPage = ref(1)
const pageSize = 5

const currentUserId = computed(() => {
  try {
    const raw = localStorage.getItem('auth_user')
    if (!raw) return null
    const user = JSON.parse(raw)
    return Number(user?.id) || null
  } catch {
    return null
  }
})

const mySheets = computed(() => {
  if (!currentUserId.value) return []
  return (sheetStore.sheets || []).filter(
    (sheet) => Number(sheet.created_by) === currentUserId.value,
  )
})
const isEditMode = computed(() => editingSheetId.value !== null)
const totalPages = computed(() => Math.max(1, Math.ceil(mySheets.value.length / pageSize)))
const paginatedMySheets = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return mySheets.value.slice(start, start + pageSize)
})

function goToPage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

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

function startEdit(sheet) {
  window.scrollTo({ top: 0, behavior: 'smooth' })
  editingSheetId.value = Number(sheet.id)
  form.title = sheet.title || ''
  form.composer = sheet.composer || ''
  form.instrument = sheet.instrument || sheet.instrument_name || ''
  form.difficulty = sheet.difficulty || ''
  form.format = sheet.format || 'PDF'
  form.price = Number(sheet.price) || 0
  form.pages = Number(sheet.pages) || 1
  form.coverImage = sheet.coverImage || sheet.cover_image || ''
  form.description = sheet.description || ''
  errorMessage.value = ''
  successMessage.value = ''
}

watch(
  mySheets,
  () => {
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }
  },
  { immediate: true },
)

function cancelEdit(clearMessages = true) {
  editingSheetId.value = null
  resetForm()
  if (clearMessages) {
    errorMessage.value = ''
    successMessage.value = ''
  }
}

async function handleSubmit() {
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

  try {
    if (isEditMode.value) {
      await sheetStore.updateSheet(editingSheetId.value, { ...form })
      successMessage.value = 'Sheet music updated successfully.'
      cancelEdit(false)
      return
    }

    await sheetStore.addSheet({ ...form, created_by: currentUserId.value })
    successMessage.value = 'Sheet music published successfully.'
    resetForm()
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to save sheet music.'
  }
}

onMounted(async () => {
  try {
    await sheetStore.fetchSheets()
    await instrumentStore.fetchInstruments()
  } finally {
    isLoadingList.value = false
  }
})
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

.list-card {
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
