import axios from 'axios';
import { useAuthStore } from '../stores/auth';

export const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('bmclouds_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const requestUrl = error.config?.url || '';

        // Never react to 401s coming from the auth endpoints themselves.
        // Reacting here would call authStore.logout() -> POST /logout with the
        // same (already invalid) token -> another 401 -> another logout() call,
        // causing an infinite loop of network requests on every page refresh
        // once the stored token has expired or been revoked.
        const isAuthEndpoint = requestUrl.includes('/login') || requestUrl.includes('/logout');

        if (status === 401 && !isAuthEndpoint) {
            const authStore = useAuthStore();
            // Only act once: if there's no token left, session was already cleared.
            if (authStore.token) {
                authStore.clearSession();
                if (!window.location.pathname.startsWith('/login')) {
                    window.location.href = '/login';
                }
            }
        }

        return Promise.reject(error);
    }
);
