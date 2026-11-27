<template>
    <div class="min-h-screen bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <UtensilsCrossed class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Restaurant POS</h1>
                <p class="text-gray-500 text-sm mt-1">Restaurant Point of Sale System</p>
            </div>

            <form @submit.prevent="handlePinLogin" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PIN</label>
                    <div class="flex gap-2 justify-center">
                        <input v-for="i in 4" :key="i" v-model="pinDigits[i-1]"
                            type="text" maxlength="1"
                            class="w-12 h-12 text-center text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-orange-500 outline-none"
                            @input="onPinInput(i-1, $event)"
                            @keydown.backspace="onPinBackspace(i-1)"
                            :ref="el => pinRefs[i-1] = el" />
                    </div>
                </div>
                <div v-if="error" class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-lg">{{ error }}</div>
                <button type="submit" :disabled="auth.loading"
                    class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors disabled:opacity-60">
                    {{ auth.loading ? 'Verifying...' : 'Sign In with PIN' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { UtensilsCrossed } from 'lucide-vue-next'

onMounted(() => { document.title = 'Restaurant POS' })

const auth = useAuthStore()
const router = useRouter()
const error = ref('')

const pinDigits = ref(['', '', '', ''])
const pinRefs = ref([])

async function handlePinLogin() {
    error.value = ''
    const pin = pinDigits.value.join('')
    if (pin.length < 4) { error.value = 'Please enter full PIN'; return }
    const result = await auth.pinLogin(1, pin)
    if (result.success) {
        router.push('/')
    } else {
        error.value = result.message
    }
}

function onPinInput(index, event) {
    const val = event.target.value.replace(/[^0-9]/g, '')
    pinDigits.value[index] = val
    if (val && index < 3) pinRefs.value[index + 1]?.focus()
}

function onPinBackspace(index) {
    if (!pinDigits.value[index] && index > 0) {
        pinDigits.value[index - 1] = ''
        pinRefs.value[index - 1]?.focus()
    }
}
</script>
