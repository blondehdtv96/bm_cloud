import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/',
        redirect: '/dashboard'
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/LoginView.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/',
        component: () => import('../components/layout/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: 'dashboard', name: 'Dashboard', component: () => import('../views/DashboardView.vue') },
            { path: 'drive/:folderId?', name: 'Drive', component: () => import('../views/DriveView.vue') },
            { path: 'drive-monitor', name: 'DriveMonitor', component: () => import('../views/DriveMonitorView.vue'), meta: { requiresMonitor: true } },
            { path: 'shared', name: 'Shared', component: () => import('../views/SharedView.vue') },
            { path: 'favorites', name: 'Favorites', component: () => import('../views/FavoritesView.vue') },
            { path: 'trash', name: 'Trash', component: () => import('../views/TrashView.vue') },
            { path: 'search', name: 'Search', component: () => import('../views/SearchView.vue') },
            { path: 'profile', name: 'Profile', component: () => import('../views/ProfileView.vue') },
            
            // Admin routes
            { path: 'admin', name: 'AdminDashboard', component: () => import('../views/admin/AdminDashboard.vue'), meta: { requiresAdmin: true } },
            { path: 'admin/users', name: 'AdminUsers', component: () => import('../views/admin/AdminUsers.vue'), meta: { requiresAdmin: true } },
            { path: 'admin/roles', name: 'AdminRoles', component: () => import('../views/admin/AdminRoles.vue'), meta: { requiresAdmin: true } },
            { path: 'admin/logs', name: 'AdminLogs', component: () => import('../views/admin/AdminLogs.vue'), meta: { requiresAdmin: true } },
            { path: 'admin/backup', name: 'AdminBackup', component: () => import('../views/admin/AdminBackup.vue'), meta: { requiresAdmin: true } }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    if (!authStore.initialized) {
        await authStore.init();
    }

    const isAuthenticated = authStore.isAuthenticated;
    const isAdmin = authStore.isAdmin;

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.meta.guestOnly && isAuthenticated) {
        next('/dashboard');
    } else if (to.meta.requiresAdmin && !isAdmin) {
        next('/dashboard');
    } else if (to.meta.requiresMonitor && !authStore.canMonitorDrives) {
        next('/dashboard');
    } else {
        next();
    }
});

export default router;
