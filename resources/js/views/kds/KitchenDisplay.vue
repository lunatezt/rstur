<template>
    <div class="min-h-screen bg-gray-900 text-white p-2 sm:p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-orange-400">{{ $t('Kitchen Display System') }}</h1>
                <p class="text-gray-400 text-xs sm:text-sm">{{ new Date().toLocaleString('en-IN') }}</p>
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-4">
            <select :value="locale" @change="setLocale($event.target.value)" :aria-label="$t('Language')"
                class="border border-gray-600 rounded px-2 py-1 text-xs bg-gray-800 text-white">
                <option value="en">English</option>
                <option value="prs">دری</option>
            </select>
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-green-400 animate-pulse shrink-0"></div>
                    <span class="text-gray-400">{{ $t('Live') }}</span>
                </div>
                <router-link v-if="auth.user?.role?.slug !== 'kitchen_staff'" to="/" class="text-gray-400 hover:text-white text-sm">← {{ $t('Back') }}</router-link>
                <button v-else @click="handleLogout" class="text-gray-400 hover:text-white text-sm">{{ $t('Logout') }}</button>
            </div>
        </div>

        <div v-if="!orders.length" class="flex items-center justify-center h-64">
            <div class="text-center text-gray-500">
                <ChefHat class="w-16 h-16 mx-auto mb-4 opacity-30" />
                <p class="text-xl">{{ $t('No orders in queue') }}</p>
                <p class="text-sm mt-2">{{ $t('New orders will appear here automatically') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-3 sm:gap-4">
            <div v-for="order in orders" :key="order.id"
                :class="['bg-gray-800 rounded-xl overflow-hidden border-t-4', orderBorderColor(order)]">
                <!-- Order header -->
                <div class="p-3 sm:p-4 bg-gray-750">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="min-w-0">
                            <p class="font-bold text-base sm:text-lg text-white break-words">{{ order.order_number }}</p>
                            <p class="text-gray-400 text-sm">{{ order.order_type.replace('_', ' ') }}
                                <span v-if="order.table">- {{ order.table.name }}</span>
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-orange-400 font-bold text-base sm:text-lg">{{ orderTime(order.created_at) }}</p>
                            <p class="text-gray-500 text-xs">{{ $t('minutes ago') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button v-if="order.status === 'placed'" @click="markPreparing(order)"
                            class="flex-1 min-h-11 sm:min-h-9 px-3 py-2 bg-yellow-500 text-black rounded-lg text-xs font-bold hover:bg-yellow-400">
                            {{ $t('START PREPARING') }}
                        </button>
                    </div>
                </div>

                <!-- Items -->
                <div class="divide-y divide-gray-700">
                    <div v-for="item in order.items" :key="item.id"
                        :class="['p-3 flex items-start gap-3', item.item_status === 'ready' ? 'opacity-50' : '']">
                        <div :class="['w-3 h-3 rounded-full mt-1 shrink-0', item.product?.is_veg ? 'bg-green-400' : 'bg-red-400']"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-medium break-words">{{ item.product?.name_en ?? item.product_name }}</p>
                            <p v-if="item.product?.name_fa" dir="rtl" class="text-gray-300 text-sm break-words">{{ item.product.name_fa }}</p>
                            <p v-if="item.variant_name" class="text-gray-400 text-xs break-words">{{ item.variant_name }}</p>
                            <p v-if="item.special_instructions" class="text-yellow-400 text-xs italic">
                                "{{ item.special_instructions }}"
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="bg-gray-700 text-white px-2 py-1 rounded text-sm font-bold">×{{ item.quantity }}</span>
                            <button @click="markItemReady(item)"
                                v-if="item.item_status !== 'ready'"
                                class="w-11 h-11 sm:w-8 sm:h-8 bg-green-600 rounded flex items-center justify-center hover:bg-green-500"
                                :aria-label="$t('Mark {name} ready', { name: item.product?.name_en ?? item.product_name })">
                                <Check class="w-4 h-4" />
                            </button>
                            <Check v-else class="w-5 h-5 text-green-400" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ChefHat, Check } from 'lucide-vue-next'
import api from '@/api/index.js'
import { useAuthStore } from '@/stores/auth.js'
import { useLocalization } from '@/localization.js'

const orders = ref([])
const auth = useAuthStore()
const { locale, setLocale } = useLocalization()
const router = useRouter()
let refreshTimer = null

async function handleLogout() {
    await auth.logout()
    router.push({ name: 'login' })
}

function orderTime(dt) {
    return Math.floor((Date.now() - new Date(dt)) / 60000)
}

function orderBorderColor(order) {
    const age = orderTime(order.created_at)
    if (age >= 20) return 'border-red-500'
    if (age >= 10) return 'border-yellow-500'
    if (order.status === 'preparing') return 'border-orange-500'
    return 'border-blue-500'
}

function hasQueueChanged(nextOrders) {
    if (nextOrders.length !== orders.value.length) return true

    return nextOrders.some((nextOrder, orderIndex) => {
        const currentOrder = orders.value[orderIndex]
        if (
            nextOrder.id !== currentOrder.id
            || nextOrder.status !== currentOrder.status
            || nextOrder.items.length !== currentOrder.items.length
        ) return true

        return nextOrder.items.some((nextItem, itemIndex) => {
            const currentItem = currentOrder.items[itemIndex]
            return nextItem.id !== currentItem.id
                || nextItem.item_status !== currentItem.item_status
                || nextItem.quantity !== currentItem.quantity
        })
    })
}

async function loadOrders() {
    const { data } = await api.get('/kds')
    if (hasQueueChanged(data.orders)) orders.value = data.orders
}

async function markPreparing(order) {
    await api.patch(`/kds/orders/${order.id}/status`, { status: 'preparing' })
    await loadOrders()
}

async function markItemReady(item) {
    await api.patch(`/kds/items/${item.id}/status`, { status: 'ready' })
    await loadOrders()
}

onMounted(() => {
    loadOrders()
    refreshTimer = setInterval(loadOrders, 15000)
})

onUnmounted(() => clearInterval(refreshTimer))
</script>
