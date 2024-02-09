/**
 * plugins/index.js
 *
 * Automatically included in `./src/main.js`
 */

// Plugins

import store from './store'
import router from './router'


export function registerPlugins (app) {
  app
    .use(router)
    .use(store)
    
}
