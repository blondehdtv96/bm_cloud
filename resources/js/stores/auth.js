import { defineStore } from 'pinia';
import { api } from '../composables/useApi';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('bmclouds_token') || null,
        loading: false,
        initialized: false,
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.token && !!state.user,
        isAdmin: (state) => {
            const slugs = state.user?.roles?.map(r => r.slug) || [];
            return slugs.includes('super_admin') || slugs.includes('ict');
        },
        userRole: (state) => state.user?.roles?.[0]?.slug || 'guest',
        canMonitorDrives: (state) => {
            if (!state.user) return false;
            const slugs = state.user.roles?.map(r => r.slug) || [];
            if (slugs.includes('super_admin') || slugs.includes('ict')) return true;
            const permissionSlugs = (state.user.roles || [])
                .flatMap(r => r.permissions || [])
                .map(p => p.slug);
            return permissionSlugs.includes('drive.monitor');
        },
        storagePercent: (state) => {
            if (!state.user || !state.user.storage_quota) return 0;
            return Math.min(100, Math.round((state.user.storage_used / state.user.storage_quota) * 100));
        }
    },
    
    actions: {
        async init() {
            if (this.token) {
                try {
                    api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                    await this.fetchUser();
                } catch (error) {
                    // Token is invalid/expired: clear it locally only.
                    // Do NOT call logout() here, it would fire a POST /logout
                    // request using the same invalid token and fail with 401 again.
                    this.clearSession();
                }
            }
            this.initialized = true;
        },
        
        async login(email, password) {
            this.loading = true;
            try {
                const response = await api.post('/login', { email, password });
                this.token = response.data.access_token;
                this.user = response.data.user;
                localStorage.setItem('bmclouds_token', this.token);
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                return { success: true };
            } catch (error) {
                return { success: false, message: error.response?.data?.message || 'Login failed' };
            } finally {
                this.loading = false;
            }
        },
        
        async fetchUser() {
            try {
                const response = await api.get('/me');
                this.user = response.data;
            } catch (error) {
                console.error("Failed to fetch user", error);
                throw error;
            }
        },
        
        async logout() {
            try {
                if (this.token) await api.post('/logout');
            } catch (e) {}
            this.clearSession();
        },

        /**
         * Clear local auth state only, without calling the API.
         * Used when the token is already known to be invalid (e.g. after a 401
         * response), so we don't trigger another authenticated request that
         * would itself fail with 401 and risk a logout -> 401 -> logout loop.
         */
        clearSession() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('bmclouds_token');
            delete api.defaults.headers.common['Authorization'];
        }
    }
});
