<template>
  <div class="sheet-detail-view">
    <div class="container py-5" v-if="isLoading">
      <div class="text-center text-muted">Loading sheet detail...</div>
    </div>

    <div class="container py-5" v-else-if="sheet">
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
            <h2 class="text-primary mb-0 me-4">{{ formatPriceIDRWithCommas(sheet.price) }}</h2>
            <button class="btn btn-primary btn-lg" @click="addToCart">
              <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
          </div>

          <transition name="added-message">
            <div
              class="alert alert-success added-message-alert"
              v-if="showAddedMessage"
              role="alert"
            >
              Added to cart successfully!
            </div>
          </transition>
        </div>
      </div>

      <!-- Audio Preview -->
      <div class="row mt-5">
        <div class="col-12">
          <h3>Audio Preview</h3>
          <div v-if="previewAudioUrl" class="audio-preview-wrapper">
            <audio :src="previewAudioUrl" controls preload="none" class="w-100">
              Your browser does not support audio playback.
            </audio>
          </div>
          <div v-else class="bg-light p-4 text-center rounded">
            <i class="bi bi-music-note-beamed" style="font-size: 2rem"></i>
            <p class="text-muted mb-0 mt-2">Sample audio preview is not available for this sheet.</p>
          </div>
        </div>
      </div>

      <!-- Sample Preview -->
      <div class="row mt-5">
        <div class="col-12">
          <h3>Sample Preview</h3>
          <div
            v-if="previewPdfUrl"
            class="secure-preview-wrapper"
            @contextmenu.prevent="preventPreviewAction"
            @copy.prevent="preventPreviewAction"
            @cut.prevent="preventPreviewAction"
            @paste.prevent="preventPreviewAction"
            @dragstart.prevent="preventPreviewAction"
          >
            <iframe
              :src="previewPdfUrl"
              class="pdf-preview-frame"
              title="Sheet music sample preview"
              loading="lazy"
              referrerpolicy="no-referrer"
            ></iframe>
            <div class="preview-shield" @mousedown.prevent @contextmenu.prevent>
              <div class="preview-watermark">SAMPLE PREVIEW</div>
            </div>
          </div>
          <div v-else class="bg-light p-5 text-center rounded">
            <i class="bi bi-file-earmark-pdf" style="font-size: 4rem"></i>
            <p class="text-muted mb-0">Sample PDF preview is not available for this sheet.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="container py-5 text-center" v-else>
      <h3 class="mb-2">Sheet not found</h3>
      <p class="text-muted mb-4">
        {{ loadError || 'The requested sheet may be missing or failed to load.' }}
      </p>
      <router-link to="/marketplace" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Back to Marketplace
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSheetMusicStore } from '../stores/sheetMusic'
import { useCartStore } from '../stores/cart'
import { formatPriceIDRWithCommas } from '../utils/priceUtils'

const route = useRoute()
const router = useRouter()
const sheetStore = useSheetMusicStore()
const cartStore = useCartStore()

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const sheet = ref(null)
const showAddedMessage = ref(false)
const isLoading = ref(true)
const loadError = ref('')

onMounted(loadSheet)

watch(
  () => route.params.id,
  () => {
    loadSheet()
  },
)

async function loadSheet() {
  const id = Number(route.params.id)

  isLoading.value = true
  loadError.value = ''
  sheet.value = null

  if (!Number.isFinite(id)) {
    loadError.value = 'Invalid sheet ID.'
    isLoading.value = false
    return
  }

  if (!sheetStore.sheets.length) {
    await sheetStore.fetchSheetBySearch()
  }

  sheet.value = sheetStore.sheets.find((item) => Number(item.id) === id) || null
  if (!sheet.value) {
    loadError.value = 'Sheet data was not returned by the server.'
  }

  isLoading.value = false
}

const previewPdfUrl = computed(() => {
  const filePath = sheet.value?.file_path || ''
  if (!filePath) return ''

  const assetUrl = buildAssetUrl(filePath)
  if (!assetUrl) return ''

  const hashSeparator = assetUrl.includes('#') ? '&' : '#'
  return `${assetUrl}${hashSeparator}toolbar=0&navpanes=0&scrollbar=0&page=1&view=FitH`
})

const previewAudioUrl = computed(() => {
  const audioPath = sheet.value?.sample_audio || ''
  if (!audioPath) return ''
  return buildAssetUrl(audioPath)
})

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

function preventPreviewAction(event) {
  event.preventDefault()
}

function addToCart(event) {
  event.preventDefault()

  const authUser = localStorage.getItem('auth_user')
  if (!authUser) {
    router.push('/login')
    requestAnimationFrame(() => {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    })
    return
  }

  cartStore.addToCart(sheet.value)

  // Show animation
  showAddedMessage.value = true
  setTimeout(() => {
    showAddedMessage.value = false
  }, 1500)

  // Button click animation
  const button = event.currentTarget
  button.classList.add('btn-clicked')
  setTimeout(() => {
    button.classList.remove('btn-clicked')
  }, 300)
}
</script>

<style scoped>
.added-message-alert {
  box-shadow: 0 10px 24px rgba(25, 135, 84, 0.2);
}

.added-message-enter-active,
.added-message-leave-active {
  transition:
    opacity 0.35s ease,
    transform 0.35s ease;
}

.added-message-enter-from,
.added-message-leave-to {
  opacity: 0;
  transform: translateY(18px);
}

.added-message-enter-to,
.added-message-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.secure-preview-wrapper {
  position: relative;
  border: 1px solid #dee2e6;
  border-radius: 12px;
  overflow: hidden;
  background: #f8f9fa;
}

.audio-preview-wrapper {
  border: 1px solid #dee2e6;
  border-radius: 12px;
  padding: 1rem;
  background: #f8f9fa;
}

.pdf-preview-frame {
  width: 100%;
  height: 520px;
  border: 0;
  pointer-events: none;
}

.preview-shield {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(to bottom, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.1));
}

.preview-watermark {
  font-weight: 700;
  letter-spacing: 0.14em;
  color: rgba(33, 37, 41, 0.35);
  font-size: clamp(1rem, 2.5vw, 1.5rem);
  transform: rotate(-18deg);
  user-select: none;
}
</style>
