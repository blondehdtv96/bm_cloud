import { defineStore } from 'pinia';
import { api } from '../composables/useApi';

/** Interval polling jumlah notifikasi belum dibaca (ms). */
const POLL_INTERVAL = 60000;

let pollTimer = null;

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        items: [],
        unreadCount: 0,
        loading: false,
        loadingMore: false,
        currentPage: 0,
        lastPage: 1,
        filter: 'all', // 'all' | 'unread'
        initialized: false,
    }),

    getters: {
        hasUnread: (state) => state.unreadCount > 0,
        hasMore: (state) => state.currentPage < state.lastPage,
        /** Angka untuk badge; di atas 99 ditampilkan sebagai 99+. */
        badgeLabel: (state) => (state.unreadCount > 99 ? '99+' : String(state.unreadCount)),
    },

    actions: {
        async fetch({ append = false } = {}) {
            if (append && !this.hasMore) return;

            const page = append ? this.currentPage + 1 : 1;
            if (append) this.loadingMore = true;
            else this.loading = true;

            try {
                const { data } = await api.get('/notifications', {
                    params: { page, filter: this.filter, per_page: 15 },
                });

                this.items = append ? [...this.items, ...data.data] : data.data;
                this.currentPage = data.meta.current_page;
                this.lastPage = data.meta.last_page;
                this.unreadCount = data.unread_count;
                this.initialized = true;
            } catch (error) {
                console.error('Failed to fetch notifications', error);
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },

        async setFilter(filter) {
            if (this.filter === filter) return;
            this.filter = filter;
            await this.fetch();
        },

        /**
         * Ambil hanya jumlah belum dibaca. Dipakai untuk polling agar tidak
         * menarik seluruh daftar setiap menit.
         * @returns {number} selisih dibanding hitungan sebelumnya
         */
        async fetchUnreadCount() {
            try {
                const { data } = await api.get('/notifications/unread-count');
                const delta = data.count - this.unreadCount;
                this.unreadCount = data.count;
                return delta;
            } catch (error) {
                return 0;
            }
        },

        async markAsRead(notification) {
            if (notification.read_at) return;

            // Optimistic: tandai dulu di UI supaya terasa responsif.
            const readAt = new Date().toISOString();
            notification.read_at = readAt;
            this.unreadCount = Math.max(0, this.unreadCount - 1);

            try {
                const { data } = await api.post(`/notifications/${notification.id}/read`);
                this.unreadCount = data.unread_count;
                if (this.filter === 'unread') {
                    this.items = this.items.filter((item) => item.id !== notification.id);
                }
            } catch (error) {
                notification.read_at = null;
                this.unreadCount += 1;
                console.error('Failed to mark notification as read', error);
            }
        },

        async markAllRead() {
            if (!this.unreadCount) return;

            try {
                await api.post('/notifications/read-all');
                const readAt = new Date().toISOString();
                this.items.forEach((item) => {
                    if (!item.read_at) item.read_at = readAt;
                });
                this.unreadCount = 0;
                if (this.filter === 'unread') this.items = [];
            } catch (error) {
                console.error('Failed to mark all notifications as read', error);
            }
        },

        async remove(notification) {
            try {
                const { data } = await api.delete(`/notifications/${notification.id}`);
                this.items = this.items.filter((item) => item.id !== notification.id);
                this.unreadCount = data.unread_count;
            } catch (error) {
                console.error('Failed to delete notification', error);
            }
        },

        async clearAll() {
            try {
                await api.delete('/notifications');
                this.items = [];
                this.unreadCount = 0;
                this.currentPage = 1;
                this.lastPage = 1;
            } catch (error) {
                console.error('Failed to clear notifications', error);
            }
        },

        startPolling(onNew) {
            this.stopPolling();
            this.fetchUnreadCount();
            pollTimer = setInterval(async () => {
                const delta = await this.fetchUnreadCount();
                if (delta > 0 && typeof onNew === 'function') onNew(delta);
            }, POLL_INTERVAL);
        },

        stopPolling() {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        },

        reset() {
            this.stopPolling();
            this.items = [];
            this.unreadCount = 0;
            this.currentPage = 0;
            this.lastPage = 1;
            this.filter = 'all';
            this.initialized = false;
        },
    },
});
