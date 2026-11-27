<template>
    <div class="min-w-0 p-3 sm:p-6">
        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 sm:gap-4 mb-4 sm:mb-6">
            <button @click="tab = 'categories'" :class="['flex-1 sm:flex-none px-3 sm:px-5 py-2.5 rounded-lg font-semibold text-xs sm:text-sm transition-colors', tab === 'categories' ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100']">
                {{ $t('Categories ({count})', { count: categories.length }) }}
            </button>
            <button @click="tab = 'products'" :class="['flex-1 sm:flex-none px-3 sm:px-5 py-2.5 rounded-lg font-semibold text-xs sm:text-sm transition-colors', tab === 'products' ? 'bg-orange-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100']">
                {{ $t('Products ({count})', { count: products.total ?? 0 }) }}
            </button>
        </div>

        <!-- Categories Tab -->
        <div v-if="tab === 'categories'">
            <div class="flex justify-end mb-4">
                <button @click="openCategoryForm()" class="w-full sm:w-auto justify-center bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 flex items-center gap-2">
                    <Plus class="w-4 h-4" /> {{ $t('Add Category') }}
                </button>
            </div>
            <div class="overflow-x-auto overscroll-x-contain rounded-xl bg-white shadow-sm">
                <table class="w-full min-w-[680px]">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Name') }}</th>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Products') }}</th>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Display') }}</th>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Status') }}</th>
                            <th class="px-3 sm:px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <span class="text-orange-500 text-sm">{{ cat.name.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ cat.name_en ?? cat.name }}</p>
                                        <p v-if="cat.name_fa" dir="rtl" class="text-xs text-gray-500">{{ cat.name_fa }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 text-gray-600">{{ cat.products_count }}</td>
                            <td class="px-3 sm:px-6 py-4">
                                <div class="flex gap-1">
                                    <span v-if="cat.display_in_pos" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ $t('POS') }}</span>
                                    <span v-if="cat.display_in_kds" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">{{ $t('KDS') }}</span>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4">
                                <span :class="cat.is_active ? 'text-green-600' : 'text-red-500'" class="text-sm font-medium">
                                    {{ $t(cat.is_active ? 'Active' : 'Inactive') }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-6 py-4 text-right">
                                <button @click="openCategoryForm(cat)" class="text-orange-500 hover:underline text-sm mr-3">{{ $t('Edit') }}</button>
                                <button @click="deleteCategory(cat)" class="text-red-500 hover:underline text-sm">{{ $t('Delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Products Tab -->
        <div v-else>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
                <select v-model="productFilters.category_id" class="w-full sm:w-auto min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">{{ $t('All Categories') }}</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name_en ?? c.name }}{{ c.name_fa ? ` / ${c.name_fa}` : '' }}</option>
                </select>
                <input v-model="productFilters.search" :placeholder="$t('Search products...')" class="w-full sm:w-auto min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1" />
                <button @click="openProductForm()" class="w-full sm:w-auto justify-center bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 flex items-center gap-2 flex-shrink-0">
                    <Plus class="w-4 h-4" /> {{ $t('Add Product') }}
                </button>
            </div>
            <div class="overflow-x-auto overscroll-x-contain rounded-xl bg-white shadow-sm">
                <table class="w-full min-w-[900px]">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Product') }}</th>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Category') }}</th>
                            <th class="text-right px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Price') }}</th>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Type') }}</th>
                            <th class="text-left px-3 sm:px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('Availability') }}</th>
                            <th class="px-3 sm:px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="product in productList" :key="product.id" class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                        <img :src="product.image_url || '/images/default-food.svg'" :alt="product.name_en ?? product.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ product.name_en ?? product.name }}</p>
                                        <p v-if="product.name_fa" dir="rtl" class="text-xs text-gray-500">{{ product.name_fa }}</p>
                                        <p class="text-xs text-gray-400">{{ product.sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-4 text-sm text-gray-600">
                                <span>{{ product.category?.name_en ?? product.category?.name }}</span>
                                <span v-if="product.category?.name_fa" dir="rtl" class="block text-xs text-gray-500">{{ product.category.name_fa }}</span>
                            </td>
                            <td class="px-3 sm:px-6 py-4 text-right font-semibold text-gray-900">AFS {{ product.price }}</td>
                            <td class="px-3 sm:px-6 py-4">
                                <div :class="['w-4 h-4 rounded-full border-2', product.is_veg ? 'border-green-500 bg-green-100' : 'border-red-500 bg-red-100']"></div>
                            </td>
                            <td class="px-3 sm:px-6 py-4">
                                <button @click="toggleAvailability(product)"
                                    :class="['px-3 py-1 rounded-full text-xs font-medium transition-colors', product.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                                    {{ $t(product.is_available ? 'Available' : 'Unavailable') }}
                                </button>
                            </td>
                            <td class="px-3 sm:px-6 py-4 text-right">
                                <button @click="openProductForm(product)" class="text-orange-500 hover:underline text-sm mr-3">{{ $t('Edit') }}</button>
                                <button @click="deleteProduct(product)" class="text-red-500 hover:underline text-sm">{{ $t('Delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Form Modal -->
    <Teleport to="body">
        <div v-if="showCategoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <h3 class="font-bold text-lg mb-4">{{ $t(editingCategory?.id ? 'Edit' : 'Add') }} {{ $t('Category') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('English Name *') }}</label>
                        <input v-model.trim="categoryForm.name_en" required maxlength="255" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 outline-none" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Persian Name *') }}</label>
                        <input v-model.trim="categoryForm.name_fa" required maxlength="255" dir="rtl" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 outline-none" />
                    </div>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="categoryForm.is_active" type="checkbox" class="w-4 h-4 accent-orange-500" />
                            {{ $t('Active') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="categoryForm.display_in_pos" type="checkbox" class="w-4 h-4 accent-orange-500" />
                            {{ $t('Show in POS') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="categoryForm.display_in_kds" type="checkbox" class="w-4 h-4 accent-orange-500" />
                            {{ $t('Show in KDS') }}
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="showCategoryModal = false" class="flex-1 py-3 border rounded-lg text-gray-600 hover:bg-gray-50">{{ $t('Cancel') }}</button>
                    <button @click="saveCategory" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600">{{ $t('Save') }}</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Product Form Modal -->
    <Teleport to="body">
        <div v-if="showProductModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6 my-4">
                <h3 class="font-bold text-lg mb-4">{{ $t(editingProduct?.id ? 'Edit' : 'Add') }} {{ $t('Product') }}</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('English Name *') }}</label>
                            <input v-model.trim="productForm.name_en" required maxlength="255" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Persian Name *') }}</label>
                            <input v-model.trim="productForm.name_fa" required maxlength="255" dir="rtl" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Category *') }}</label>
                            <select v-model="productForm.category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5">
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name_en ?? c.name }}{{ c.name_fa ? ` / ${c.name_fa}` : '' }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Price *') }}</label>
                            <input v-model="productForm.price" type="number" min="0" step="0.01" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Cost Price') }}</label>
                            <input v-model="productForm.cost_price" type="number" step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('SKU') }}</label>
                            <input v-model="productForm.sku" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Prep Time (min)') }}</label>
                            <input v-model="productForm.preparation_time_minutes" type="number" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none" />
                        </div>
                    </div>
                    <div class="flex gap-4 flex-wrap">
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_veg" type="checkbox" class="w-4 h-4 accent-green-500" />{{ $t('Veg') }}</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_available" type="checkbox" class="w-4 h-4 accent-orange-500" />{{ $t('Available') }}</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_featured" type="checkbox" class="w-4 h-4 accent-orange-500" />{{ $t('Featured') }}</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="productForm.is_active" type="checkbox" class="w-4 h-4 accent-orange-500" />{{ $t('Active') }}</label>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Description') }}</label>
                        <textarea v-model="productForm.short_description" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 outline-none"></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">{{ $t('Food Photo') }}</label>
                        <div class="flex items-center gap-3">
                            <img :src="productImagePreview" alt="Product preview" class="w-20 h-20 rounded-lg object-cover bg-gray-100" />
                            <div>
                                <input type="file" accept="image/*" @change="handleProductImageChange"
                                    class="block w-full text-xs text-gray-600 file:mr-2 file:px-3 file:py-1.5 file:rounded file:border-0 file:bg-orange-100 file:text-orange-700" />
                                <button v-if="editingProduct?.image" type="button" @click="clearProductImage"
                                    class="mt-2 text-xs text-red-600 hover:underline">{{ $t('Remove photo') }}</button>
                                <p class="mt-1 text-xs text-gray-400">{{ $t('JPG, PNG, or WebP; up to 5 MB.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="closeProductForm" class="flex-1 py-3 border rounded-lg text-gray-600 hover:bg-gray-50">{{ $t('Cancel') }}</button>
                    <button @click="saveProduct" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600">{{ $t('Save') }}</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { toast } from 'vue3-toastify'
import { Plus } from 'lucide-vue-next'
import api from '@/api/index.js'
import { t } from '@/localization.js'

const tab = ref('categories')
const categories = ref([])
const products = ref({})
const productFilters = reactive({ category_id: '', search: '' })

const productList = computed(() => products.value.data ?? [])

const showCategoryModal = ref(false)
const showProductModal = ref(false)
const editingCategory = ref(null)
const editingProduct = ref(null)

const categoryForm = reactive({ name_en: '', name_fa: '', is_active: true, display_in_pos: true, display_in_kds: true })
const productForm = reactive({
    name_en: '', name_fa: '', category_id: '', price: '', cost_price: '', sku: '',
    is_veg: true, is_available: true, is_featured: false, is_active: true,
    short_description: '', preparation_time_minutes: 15,
})
const productImageFile = ref(null)
const productImagePreview = ref('/images/default-food.svg')
const removeProductImage = ref(false)

async function loadCategories() {
    const { data } = await api.get('/categories')
    categories.value = data.categories
}

async function loadProducts() {
    const params = {}
    if (productFilters.category_id) params.category_id = productFilters.category_id
    if (productFilters.search) params.search = productFilters.search
    const { data } = await api.get('/products', { params })
    products.value = data.products
}

function openCategoryForm(cat = null) {
    editingCategory.value = cat
    if (cat) Object.assign(categoryForm, { name_en: cat.name_en ?? cat.name, name_fa: cat.name_fa ?? '', is_active: cat.is_active, display_in_pos: cat.display_in_pos, display_in_kds: cat.display_in_kds })
    else Object.assign(categoryForm, { name_en: '', name_fa: '', is_active: true, display_in_pos: true, display_in_kds: true })
    showCategoryModal.value = true
}

async function saveCategory() {
    const payload = { ...categoryForm }
    try {
        if (editingCategory.value?.id) {
            await api.put(`/categories/${editingCategory.value.id}`, payload)
            toast.success(t('Category updated'))
        } else {
            await api.post('/categories', payload)
            toast.success(t('Category created'))
        }
        showCategoryModal.value = false
        await loadCategories()
    } catch (error) {
        toast.error(error.response?.data?.message ?? t('Failed to save category'))
    }
}

async function deleteCategory(cat) {
    const categoryName = [cat.name_en ?? cat.name, cat.name_fa].filter(Boolean).join(' / ')
    if (!confirm(t('Delete category "{name}"?', { name: categoryName }))) return
    await api.delete(`/categories/${cat.id}`)
    toast.success(t('Category deleted'))
    await loadCategories()
}

function openProductForm(prod = null) {
    if (productImagePreview.value.startsWith('blob:')) URL.revokeObjectURL(productImagePreview.value)
    editingProduct.value = prod
    if (prod) {
        Object.assign(productForm, {
            name_en: prod.name_en ?? prod.name, name_fa: prod.name_fa ?? '', category_id: prod.category_id, price: prod.price,
            cost_price: prod.cost_price, sku: prod.sku, is_veg: prod.is_veg,
            is_available: prod.is_available, is_featured: prod.is_featured,
            is_active: prod.is_active, short_description: prod.short_description,
            preparation_time_minutes: prod.preparation_time_minutes,
        })
        productImagePreview.value = prod.image_url
    } else {
        Object.assign(productForm, {
            name_en: '', name_fa: '', category_id: categories.value[0]?.id ?? '', price: '', cost_price: '', sku: '',
            is_veg: true, is_available: true, is_featured: false, is_active: true,
            short_description: '', preparation_time_minutes: 15,
        })
        productImagePreview.value = '/images/default-food.svg'
    }
    productImageFile.value = null
    removeProductImage.value = false
    showProductModal.value = true
}

function handleProductImageChange(event) {
    const file = event.target.files?.[0]
    if (!file) return
    if (productImagePreview.value.startsWith('blob:')) URL.revokeObjectURL(productImagePreview.value)
    productImageFile.value = file
    productImagePreview.value = URL.createObjectURL(file)
    removeProductImage.value = false
}

function clearProductImage() {
    if (productImagePreview.value.startsWith('blob:')) URL.revokeObjectURL(productImagePreview.value)
    productImageFile.value = null
    productImagePreview.value = '/images/default-food.svg'
    removeProductImage.value = true
}

function closeProductForm() {
    if (productImagePreview.value.startsWith('blob:')) URL.revokeObjectURL(productImagePreview.value)
    showProductModal.value = false
}

function productPayload() {
    const payload = new FormData()
    Object.entries(productForm).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
            payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value)
        }
    })
    if (productImageFile.value) payload.append('image', productImageFile.value)
    else if (removeProductImage.value) payload.append('image', '')
    if (editingProduct.value?.id) payload.append('_method', 'PUT')
    return payload
}

async function saveProduct() {
    const payload = productPayload()
    try {
        if (editingProduct.value?.id) {
            await api.post(`/products/${editingProduct.value.id}`, payload, { headers: { 'Content-Type': 'multipart/form-data' } })
            toast.success(t('Product updated'))
        } else {
            await api.post('/products', payload, { headers: { 'Content-Type': 'multipart/form-data' } })
            toast.success(t('Product created'))
        }
        closeProductForm()
        await loadProducts()
    } catch (error) {
        toast.error(error.response?.data?.message ?? t('Failed to save product'))
    }
}

async function deleteProduct(product) {
    const productName = [product.name_en ?? product.name, product.name_fa].filter(Boolean).join(' / ')
    if (!confirm(t('Delete product "{name}"?', { name: productName }))) return
    await api.delete(`/products/${product.id}`)
    toast.success(t('Product deleted'))
    await loadProducts()
}

async function toggleAvailability(product) {
    await api.patch(`/products/${product.id}/toggle`)
    product.is_available = !product.is_available
}

let searchTimer = null
watch(productFilters, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(loadProducts, 400)
}, { deep: true })

onMounted(() => {
    loadCategories()
    loadProducts()
})
</script>
