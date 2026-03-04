import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const cartItems = ref([])

  const cartTotal = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.quantity, 0)
  })

  const cartSubtotal = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.price * item.quantity, 0)
  })

  function addToCart(sheet) {
    const existingItem = cartItems.value.find((item) => item.id === sheet.id)
    if (existingItem) {
      existingItem.quantity++
    } else {
      cartItems.value.push({ ...sheet, quantity: 1 })
    }
  }

  function removeFromCart(sheetId) {
    const index = cartItems.value.findIndex((item) => item.id === sheetId)
    if (index > -1) {
      cartItems.value.splice(index, 1)
    }
  }

  function updateQuantity(sheetId, quantity) {
    const item = cartItems.value.find((item) => item.id === sheetId)
    if (item) {
      item.quantity = quantity
    }
  }

  return { cartItems, cartTotal, cartSubtotal, addToCart, removeFromCart, updateQuantity }
})
