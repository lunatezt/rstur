<template>
    <div class="fixed top-14 right-4 z-50">
        <button @click="togglePanel" :aria-label="$t('Notifications')" :aria-expanded="panelOpen"
            class="relative flex h-10 w-10 items-center justify-center rounded-full bg-white text-gray-700 shadow-lg ring-1 ring-gray-200 hover:bg-gray-50">
            <Bell class="h-5 w-5" />
            <span v-if="unreadCount" class="absolute -top-1 -right-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs font-bold leading-5 text-white">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <section v-if="panelOpen" class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl bg-white text-gray-800 shadow-xl ring-1 ring-black/10 sm:w-96">
            <header class="flex items-center justify-between gap-3 border-b px-4 py-3">
                <div>
                    <h2 class="text-sm font-bold">{{ $t('Notifications') }}</h2>
                    <p class="text-xs text-gray-500">{{ unreadCount }} {{ $t('unread') }}</p>
                </div>
                <button v-if="unreadCount" @click="markAllRead" :disabled="saving"
                    class="text-xs font-medium text-red-600 hover:text-red-700 disabled:opacity-50">
                    {{ $t('Mark all as read') }}
                </button>
            </header>

            <p v-if="loadFailed" class="px-4 py-3 text-sm text-red-600">{{ $t('Unable to load notifications.') }}</p>
            <div v-else-if="loading && !notifications.length" class="px-4 py-8 text-center text-sm text-gray-500">
                {{ $t('Loading notifications...') }}
            </div>
            <div v-else-if="!notifications.length" class="px-4 py-8 text-center text-sm text-gray-500">
                {{ $t('No notifications yet') }}
            </div>
            <div v-else class="max-h-[min(70vh,32rem)] divide-y overflow-y-auto">
                <article v-for="notification in notifications" :key="notification.id"
                    :class="['px-4 py-3', notification.read_at ? 'bg-white' : 'bg-orange-50']">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold">
                            {{ $t(notification.data.event === 'item_ready' ? 'Item ready' : 'Order ready') }}
                        </h3>
                        <span class="shrink-0 text-[11px] text-gray-400">{{ formatDate(notification.created_at) }}</span>
                    </div>
                    <p class="mt-1 text-xs leading-relaxed text-gray-600">{{ notificationDetails(notification) }}</p>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <router-link :to="`/orders/${notification.data.order_id}`" @click="panelOpen = false"
                            class="text-xs font-semibold text-orange-600 hover:underline">
                            {{ $t('View order') }}
                        </router-link>
                        <button v-if="!notification.read_at" @click="markRead(notification)" :disabled="saving"
                            class="text-xs font-medium text-gray-500 hover:text-gray-800 disabled:opacity-50">
                            {{ $t('Mark as read') }}
                        </button>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { toast } from 'vue3-toastify'
import { Bell } from 'lucide-vue-next'
import api from '@/api/index.js'
import { locale } from '@/localization.js'

const notifications = ref([])
const unreadCount = ref(0)
const panelOpen = ref(false)
const loading = ref(false)
const saving = ref(false)
const loadFailed = ref(false)
const initialized = ref(false)
let refreshTimer = null

function notificationDetails(notification) {
    const data = notification.data
    const meals = Array.isArray(data.meals)
        ? data.meals.map(meal => [meal.name_en, meal.name_fa].filter(Boolean).join(' / '))
        : [[data.item_name_en, data.item_name_fa].filter(Boolean).join(' / ')]
    const mealNames = [...new Set(meals.filter(Boolean))]

    return [mealNames.join(', '), data.table_name].filter(Boolean).join(' · ')
}

function notificationMessage(notification) {
    return notificationDetails(notification)
}

function formatDate(value) {
    if (!value) return ''
    const dateLocale = locale.value === 'prs' ? 'prs-AF' : 'en'
    return new Date(value).toLocaleString(dateLocale, { dateStyle: 'short', timeStyle: 'short' })
}

async function loadNotifications() {
    if (loading.value) return
    loading.value = true
    try {
        const { data } = await api.get('/notifications')
        const incoming = data.notifications ?? []

        if (initialized.value) {
            const knownIds = new Set(notifications.value.map(notification => notification.id))
            incoming.filter(notification => !knownIds.has(notification.id) && !notification.read_at)
                .reverse()
                .forEach(notification => toast.info(notificationMessage(notification)))
        }

        const hasListChanged = incoming.length !== notifications.value.length
            || incoming.some((notification, index) => {
                const current = notifications.value[index]
                return notification.id !== current?.id || notification.read_at !== current?.read_at
            })
        if (hasListChanged) notifications.value = incoming

        const nextUnreadCount = data.unread_count ?? 0
        if (unreadCount.value !== nextUnreadCount) unreadCount.value = nextUnreadCount
        loadFailed.value = false
        initialized.value = true
    } catch {
        if (!loadFailed.value) toast.error(t('Unable to load notifications.'))
        loadFailed.value = true
    } finally {
        loading.value = false
    }
}

async function togglePanel() {
    panelOpen.value = !panelOpen.value
    if (panelOpen.value) await loadNotifications()
}

async function markRead(notification) {
    saving.value = true
    try {
        await api.patch(`/notifications/${notification.id}/read`)
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch {
        toast.error(t('Unable to mark notification as read.'))
    } finally {
        saving.value = false
    }
}

async function markAllRead() {
    saving.value = true
    try {
        await api.patch('/notifications/read-all')
        const readAt = new Date().toISOString()
        notifications.value.forEach(notification => {
            if (!notification.read_at) notification.read_at = readAt
        })
        unreadCount.value = 0
    } catch {
        toast.error(t('Unable to mark notifications as read.'))
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadNotifications()
    refreshTimer = setInterval(loadNotifications, 10000)
})

onUnmounted(() => clearInterval(refreshTimer))
</script>
