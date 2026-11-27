<template>
    <div class="p-6 max-w-4xl mx-auto">
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin w-10 h-10 border-4 border-orange-500 border-t-transparent rounded-full"></div>
        </div>
        <template v-else-if="order">
            <div class="flex items-center gap-4 mb-6">
                <button @click="$router.back()" class="text-gray-500 hover:text-gray-700">
                    <ArrowLeft class="w-6 h-6" />
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ order.order_number }}</h1>
                    <p class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</p>
                </div>
                <StatusBadge :status="order.status" />
                <StatusBadge :status="order.payment_status" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Items -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="font-bold text-gray-800 mb-4">{{ $t('Order Items') }}</h2>
                        <div class="divide-y">
                            <div v-for="item in order.items" :key="item.id" class="py-3 flex items-start gap-3">
                                <div :class="['w-3 h-3 rounded-full mt-1.5 flex-shrink-0', item.product?.is_veg ? 'bg-green-500' : 'bg-red-500']"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ item.product?.name_en ?? item.product_name }}</p>
                                    <p v-if="item.product?.name_fa" dir="rtl" class="text-sm text-gray-500">{{ item.product.name_fa }}</p>
                                    <p v-if="item.variant_name" class="text-sm text-gray-500">{{ item.variant_name }}</p>
                                    <div v-if="item.addons?.length" class="text-xs text-gray-500">
                                        + {{ item.addons.map(a => a.addon_name).join(', ') }}
                                    </div>
                                    <p v-if="item.special_instructions" class="text-xs text-orange-600 italic mt-1">
                                        "{{ item.special_instructions }}"
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">AFS {{ item.item_total }}</p>
                                    <p class="text-sm text-gray-500">{{ item.quantity }} × AFS {{ item.unit_price }}</p>
                                    <StatusBadge :status="item.item_status" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div v-if="order.payments?.length" class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="font-bold text-gray-800 mb-4">{{ $t('Payments') }}</h2>
                        <div v-for="p in order.payments" :key="p.id" class="flex items-center justify-between py-2">
                            <div>
                                <p class="font-medium capitalize text-gray-800">{{ $t(p.payment_method) }}</p>
                                <p v-if="p.upi_transaction_id" class="text-xs text-gray-500">{{ $t('Txn:') }} {{ p.upi_transaction_id }}</p>
                            </div>
                            <span class="font-bold text-green-700">AFS {{ p.amount }}</span>
                        </div>
                    </div>
                </div>

                <!-- Summary sidebar -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="font-bold text-gray-800 mb-4">{{ $t('Summary') }}</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>{{ $t('Type') }}</span><span class="capitalize font-medium">{{ $t(order.order_type) }}</span>
                            </div>
                            <div v-if="order.table" class="flex justify-between text-gray-600">
                                <span>{{ $t('Table') }}</span><span class="font-medium">{{ order.table.name }}</span>
                            </div>
                            <div v-if="order.customer" class="flex justify-between text-gray-600">
                                <span>{{ $t('Customer') }}</span><span class="font-medium">{{ order.customer.name }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600 pt-2 border-t">
                                <span>{{ $t('Subtotal') }}</span><span>AFS {{ order.sub_total }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>{{ $t('Tax') }}</span><span>AFS {{ order.tax_amount }}</span>
                            </div>
                            <div v-if="order.discount_amount > 0" class="flex justify-between text-green-600">
                                <span>{{ $t('Discount') }}</span><span>-AFS {{ order.discount_amount }}</span>
                            </div>
                            <div v-if="order.service_charge_amount > 0" class="flex justify-between text-gray-600">
                                <span>{{ $t('Service Charge') }}</span><span>AFS {{ order.service_charge_amount }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg border-t pt-2">
                                <span>{{ $t('Total') }}</span><span class="text-orange-600">AFS {{ order.grand_total }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white rounded-xl shadow-sm p-6 space-y-2">
                        <h2 class="font-bold text-gray-800 mb-3">{{ $t('Actions') }}</h2>
                        <select v-if="canChangeStatus" v-model="newStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="s in availableStatuses" :key="s" :value="s" class="capitalize">{{ $t(s) }}</option>
                        </select>
                        <button v-if="canChangeStatus" @click="updateStatus"
                            class="w-full py-2 bg-orange-500 text-white rounded-lg text-sm font-semibold hover:bg-orange-600">
                            {{ $t('Update Status') }}
                        </button>
                        <button v-if="auth.hasPermission('orders.delete') && order.status !== 'cancelled' && order.payment_status !== 'paid'" @click="cancelOrder"
                            class="w-full py-2 border border-red-300 text-red-600 rounded-lg text-sm hover:bg-red-50">
                            {{ $t('Cancel Order') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import { ArrowLeft } from 'lucide-vue-next'
import api from '@/api/index.js'
import { useAuthStore } from '@/stores/auth.js'
import StatusBadge from '@/components/StatusBadge.vue'
import { t } from '@/localization.js'

const route = useRoute()
const auth = useAuthStore()
const router = useRouter()
const order = ref(null)
const loading = ref(false)
const newStatus = ref('')

const statusFlow = { placed: 'confirmed', confirmed: 'preparing', preparing: 'ready', ready: 'served', served: 'completed' }
const canChangeStatus = computed(() => order.value && statusFlow[order.value.status])
const availableStatuses = computed(() => {
    const next = statusFlow[order.value?.status]
    return next ? [next] : []
})

function formatDate(dt) {
    return new Date(dt).toLocaleString('en-IN')
}

async function loadOrder() {
    loading.value = true
    try {
        const { data } = await api.get(`/orders/${route.params.id}`)
        order.value = data.order
        newStatus.value = availableStatuses.value[0] ?? ''
    } finally {
        loading.value = false
    }
}

async function updateStatus() {
    if (!newStatus.value) return
    await api.patch(`/orders/${order.value.id}/status`, { status: newStatus.value })
    toast.success(t('Order status updated to {status}', { status: t(newStatus.value) }))
    await loadOrder()
}

async function cancelOrder() {
    const reason = prompt(t('Reason for cancellation?'))
    if (!reason) return
    await api.patch(`/orders/${order.value.id}/status`, { status: 'cancelled', cancellation_reason: reason })
    toast.success(t('Order cancelled'))
    await loadOrder()
}

onMounted(loadOrder)
</script>
