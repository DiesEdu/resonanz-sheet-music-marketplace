import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'
import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

const app = createApp(App)

// document.addEventListener('contextmenu', function (e) {
//   e.preventDefault()
// })

app.use(createPinia())
app.use(router)

app.mount('#app')
