<template>
  <section class="container py-5 add-sheet-page">
    <div class="text-center mb-4">
      <h1 class="section-title animate-fade-up mb-4">Composer Hub</h1>
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
            <label for="sheetFile" class="form-label">Sheet PDF File</label>
            <input
              id="sheetFile"
              type="file"
              class="form-control"
              accept="application/pdf,.pdf"
              @change="handlePdfFileChange"
              :required="!isEditMode"
              :disabled="isEditMode"
            />
            <small class="text-muted d-block mt-1">
              {{
                selectedPdfName ||
                (isEditMode
                  ? 'PDF replacement is not available in edit mode yet.'
                  : 'Upload a PDF file.')
              }}
            </small>
          </div>

          <div v-if="pdfPreviewUrl" class="col-12">
            <label class="form-label">PDF Preview</label>
            <object
              :key="pdfPreviewUrl"
              :data="pdfPreviewUrl"
              type="application/pdf"
              class="pdf-preview-frame"
            >
              <p class="p-3 mb-0">
                Preview is not available in this browser.
                <a :href="pdfPreviewUrl" target="_blank" rel="noopener">Open PDF</a>.
              </p>
            </object>
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

    <div class="card list-card animate-fade-scale delay-2 mt-4">
      <div class="card-body p-4 p-md-5">
        <h2 class="h5 mb-3">Purchased Sheets (My Catalog)</h2>

        <div v-if="isLoadingPurchases" class="text-muted">Loading purchased sheet music...</div>

        <div v-else-if="purchaseErrorMessage" class="alert alert-danger py-2 mb-0">
          {{ purchaseErrorMessage }}
        </div>

        <div v-else-if="ownedPurchasedSheets.length === 0" class="text-muted">
          No purchases found for your sheet music yet.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Composer</th>
                <th>Instrument</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Price</th>
                <th>Purchase Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in ownedPurchasedSheets" :key="item.order_item_id">
                <td>{{ item.title || '-' }}</td>
                <td>{{ item.composer || '-' }}</td>
                <td>{{ getInstrumentName(item.instrument_id) }}</td>
                <td class="text-center">{{ item.quantity || 1 }}</td>
                <td class="text-end">{{ formatPriceIDR(Number(item.sale_price) || 0) }}</td>
                <td>{{ formatPurchaseDate(item.purchase_date) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { useInstruments } from '../stores/instrument'
import { formatPriceIDR } from '@/utils/priceUtils'

const sheetStore = useSheetMusicStore()
const instrumentStore = useInstruments()
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

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
const isLoadingPurchases = ref(true)
const purchaseErrorMessage = ref('')
const selectedPdfFile = ref(null)
const selectedPdfName = ref('')
const pdfPreviewUrl = ref('')
const editingSheetId = ref(null)
const currentPage = ref(1)
const pageSize = 5
const purchasedSheets = ref([])

function setPdfPreviewUrl(nextUrl = '') {
  if (pdfPreviewUrl.value && pdfPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(pdfPreviewUrl.value)
  }
  pdfPreviewUrl.value = nextUrl
}

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
const ownedPurchasedSheets = computed(() => {
  if (!currentUserId.value) return []
  return (purchasedSheets.value || []).filter(
    (item) => Number(item.created_by) === currentUserId.value,
  )
})
const isEditMode = computed(() => editingSheetId.value !== null)
const totalPages = computed(() => Math.max(1, Math.ceil(mySheets.value.length / pageSize)))
const paginatedMySheets = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return mySheets.value.slice(start, start + pageSize)
})

function getAuthHeaders() {
  const token = localStorage.getItem('auth_token')
  return token ? { Authorization: `Bearer ${token}` } : {}
}

function getInstrumentName(instrumentId) {
  const numericId = Number(instrumentId)
  const match = instruments.value.find((instrument) => Number(instrument.id) === numericId)
  return match?.name || '-'
}

function formatPurchaseDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}

async function fetchOwnedPurchases() {
  if (!currentUserId.value) {
    purchasedSheets.value = []
    isLoadingPurchases.value = false
    return
  }

  purchaseErrorMessage.value = ''
  isLoadingPurchases.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/orders/sales`, {
      method: 'GET',
      headers: getAuthHeaders(),
      credentials: 'include',
    })

    const result = await response.json().catch(() => [])
    if (!response.ok) {
      throw new Error(result?.error || 'Failed to load purchased sheet music.')
    }

    purchasedSheets.value = Array.isArray(result) ? result : []
  } catch (error) {
    purchasedSheets.value = []
    purchaseErrorMessage.value = error?.message || 'Failed to load purchased sheet music.'
  } finally {
    isLoadingPurchases.value = false
  }
}

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
  selectedPdfFile.value = null
  selectedPdfName.value = ''
  setPdfPreviewUrl('')
}

function buildAssetUrl(path) {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('blob:')) {
    return path
  }

  try {
    const apiUrl = new URL(API_BASE_URL)
    return `${apiUrl.origin}${path}`
  } catch {
    return path
  }
}

function handlePdfFileChange(event) {
  const file = event?.target?.files?.[0] || null

  if (!file) {
    selectedPdfFile.value = null
    selectedPdfName.value = ''
    setPdfPreviewUrl('')
    return
  }

  if (file.type !== 'application/pdf') {
    errorMessage.value = 'Please upload a PDF file only.'
    selectedPdfFile.value = null
    selectedPdfName.value = ''
    setPdfPreviewUrl('')
    event.target.value = ''
    return
  }

  selectedPdfFile.value = file
  selectedPdfName.value = file.name
  errorMessage.value = ''
  setPdfPreviewUrl(URL.createObjectURL(file))
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
  selectedPdfFile.value = null
  selectedPdfName.value = ''
  setPdfPreviewUrl(buildAssetUrl(sheet.file_path || ''))
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

  if (!isEditMode.value && !selectedPdfFile.value) {
    errorMessage.value = 'Please upload a PDF file.'
    return
  }

  try {
    if (isEditMode.value) {
      await sheetStore.updateSheet(editingSheetId.value, { ...form })
      successMessage.value = 'Sheet music updated successfully.'
      cancelEdit(false)
      return
    }

    await sheetStore.addSheet({
      ...form,
      pdf_name: selectedPdfName.value,
      pdfFile: selectedPdfFile.value,
      created_by: currentUserId.value,
    })
    successMessage.value = 'Sheet music published successfully.'
    resetForm()
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to save sheet music.'
  }
}

onMounted(async () => {
  try {
    await Promise.all([
      sheetStore.fetchSheetBySearch(),
      instrumentStore.fetchInstruments(),
      fetchOwnedPurchases(),
    ])
  } finally {
    isLoadingList.value = false
  }
})

onBeforeUnmount(() => {
  setPdfPreviewUrl('')
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

.pdf-preview-frame {
  width: 100%;
  height: 420px;
  border: 1px solid #dee2e6;
  border-radius: 12px;
  background-color: #fff;
}
</style>
