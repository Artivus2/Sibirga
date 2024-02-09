import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'


const app = createApp(App)

import { registerPlugins } from '@/plugins'

registerPlugins(app)
app.mount('#app')
