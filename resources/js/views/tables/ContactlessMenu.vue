<template>
    <div class="min-h-screen bg-gray-50">
        <header class="bg-gray-900 text-white px-4 py-4 shadow">
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                <div>
                    <p class="text-lg font-bold">{{ menu?.restaurant.name ?? 'Restaurant Menu' }}</p>
                    <p v-if="menu" class="text-sm text-gray-300">Table {{ menu.table.name }}</p>
                </div>
                <span v-if="menu" class="text-sm font-semibold">{{ menu.restaurant.currency }}</span>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 py-6">
            <div v-if="loading" class="text-center py-20 text-gray-500">Loading menu...</div>
            <div v-else-if="error" class="max-w-lg mx-auto mt-12 bg-white rounded-xl shadow-sm p-6 text-center">
                <h1 class="font-bold text-lg text-gray-800 mb-2">Menu unavailable</h1>
                <p class="text-sm text-gray-600">{{ error }}</p>
            </div>
            <template v-else-if="menu">
                <section class="bg-white rounded-xl shadow-sm p-5 mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Our Menu</h1>
                    <p class="text-sm text-gray-600 mt-1">Browse the menu and ask a staff member when you are ready to order.</p>
                    <nav v-if="menu.categories.length" class="flex gap-2 overflow-x-auto mt-4 pb-1">
                        <a v-for="category in menu.categories" :key="category.id"
                            :href="`#category-${category.id}`"
                            class="shrink-0 rounded-full bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-red-100 hover:text-red-700">
                            {{ category.name_en }}
                        </a>
                    </nav>
                </section>

                <p v-if="!menu.categories.length" class="bg-white rounded-xl p-8 text-center text-gray-500">
                    The menu is not available right now.
                </p>

                <section v-for="category in menu.categories" :id="`category-${category.id}`" :key="category.id" class="mb-8 scroll-mt-4">
                    <div class="mb-3">
                        <h2 class="text-xl font-bold text-gray-900">{{ category.name_en }}</h2>
                        <p v-if="category.name_fa" dir="rtl" class="text-gray-600">{{ category.name_fa }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <article v-for="product in category.products" :key="product.id" class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <img :src="product.image_url" :alt="product.name_en" class="w-full aspect-[4/3] object-cover" />
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ product.name_en }}</h3>
                                        <p v-if="product.name_fa" dir="rtl" class="text-sm text-gray-600 mt-1">{{ product.name_fa }}</p>
                                    </div>
                                    <span v-if="product.is_veg" class="shrink-0 w-3 h-3 mt-1 rounded-full bg-green-500" title="Vegetarian"></span>
                                </div>
                                <p v-if="product.description" class="text-sm text-gray-600 mt-2">{{ product.description }}</p>
                                <p class="font-bold text-red-600 mt-3">
                                    {{ menu.restaurant.currency }} {{ product.price }}
                                    <span class="font-normal text-gray-500">/ {{ product.unit_type }}</span>
                                </p>
                            </div>
                        </article>
                    </div>
                </section>
            </template>
        </main>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/index.js'

const route = useRoute()
const menu = ref(null)
const loading = ref(true)
const error = ref('')

async function loadMenu(tableId) {
    menu.value = null
    error.value = ''

    const id = Number(tableId)
    if (!Number.isSafeInteger(id) || id < 1) {
        error.value = 'This menu link is invalid. Please scan the QR code again.'
        loading.value = false
        return
    }

    loading.value = true
    try {
        const { data } = await api.get(`/public/contactless/tables/${id}/menu`)
        menu.value = data
    } catch (requestError) {
        error.value = requestError.response?.status === 404
            ? 'This table or menu link is no longer available.'
            : 'Could not load the menu. Please check your connection and try again.'
    } finally {
        loading.value = false
    }
}

watch(() => route.params.tableId ?? route.query.table, loadMenu, { immediate: true })
</script>
