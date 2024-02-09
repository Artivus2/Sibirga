
import { createRouter, createWebHistory } from 'vue-router'


const routes = [
    {
        path: '/order-system',
        name: 'amicum',
        component: () =>
            import('@/views/Home.vue')
    },
    {
        path: '/',
        component: () =>
            import('@/views/Home.vue')
    },
    {
        path: '/uchetagz',
        component: () =>
            import('@/views/Uchetagz.vue')
    },
    
];

// const orderSystem = [
//     // Блок навигации нарядной системы
//     {
//         path: '/order-system/order-system',
//         name: 'order-system',
//         component: () =>
//             import ('@/views/OrderSystem.vue')
//     },
    // {
    //     path: '/order-system/uchetagk',
    //     component: () =>
    //         import('@/views/uchetagz.vue')
    // },
//];




const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
    routes,
  })

export default router