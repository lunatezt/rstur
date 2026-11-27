<template>
    <div class="p-4 max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-sm font-bold text-gray-800">{{ $t('Staff Management') }}</h1>
                <p class="text-xs text-gray-500 mt-1">{{ $t('Create staff PINs and assign cashier or kitchen access.') }}</p>
            </div>
            <button @click="openCreate" class="bg-red-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-red-700 flex items-center gap-1.5">
                <Plus class="w-3.5 h-3.5" /> {{ $t('Add Staff') }}
            </button>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">{{ $t('Name') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">{{ $t('Role') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-500">{{ $t('Status') }}</th>
                        <th class="text-right px-4 py-3 font-semibold text-gray-500">{{ $t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="member in staff" :key="member.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ member.name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $t(member.role?.name ?? 'Unassigned') }}</td>
                        <td class="px-4 py-3">
                            <span :class="member.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                class="px-2 py-1 rounded-full text-xs">
                                {{ $t(member.is_active ? 'Active' : 'Inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button @click="openEdit(member)" class="text-blue-600 hover:text-blue-800 mr-3">{{ $t('Edit') }}</button>
                            <button @click="deleteStaff(member)" :disabled="member.id === auth.user?.id"
                                class="text-red-600 hover:text-red-800 disabled:text-gray-300 disabled:cursor-not-allowed">
                                {{ $t('Delete') }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!loading && !staff.length" class="text-center py-10 text-gray-400">
                <Users class="w-8 h-8 mx-auto mb-2 opacity-40" />
                <p class="text-xs">{{ $t('No staff members found.') }}</p>
            </div>
            <div v-if="loading" class="text-center py-10 text-gray-400 text-xs">{{ $t('Loading staff...') }}</div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <form @submit.prevent="saveStaff" class="bg-white rounded-xl w-full max-w-sm p-5">
                <h2 class="font-bold text-sm text-gray-800 mb-4">{{ $t(editingStaff ? 'Edit Staff Member' : 'Add Staff Member') }}</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Name') }} *</label>
                        <input v-model.trim="form.name" required maxlength="255"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Role') }} *</label>
                        <select v-model="form.role_id" required
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">
                            {{ $t(editingStaff ? 'PIN (leave blank to keep current)' : 'PIN') }} {{ editingStaff ? '' : '*' }}
                        </label>
                        <input v-model="form.pin_code" type="password" inputmode="numeric" maxlength="4"
                            :required="!editingStaff" pattern="[0-9]{4}" autocomplete="new-password"
                            :placeholder="$t('4 digits')"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                    </div>
                    <label v-if="editingStaff" class="flex items-center gap-2 text-xs text-gray-700">
                        <input v-model="form.is_active" type="checkbox" class="accent-red-600" />
                        {{ $t('Account active') }}
                    </label>
                </div>
                <div class="flex gap-2 mt-5">
                    <button type="button" @click="showForm = false"
                        class="flex-1 py-2 border rounded text-xs text-gray-600 hover:bg-gray-50">{{ $t('Cancel') }}</button>
                    <button type="submit" :disabled="saving || !roles.length"
                        class="flex-1 py-2 bg-red-600 text-white rounded text-xs font-semibold disabled:opacity-60">
                        {{ saving ? $t('Saving...') : $t('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { Plus, Users } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth.js'
import api from '@/api/index.js'
import { t } from '@/localization.js'

const auth = useAuthStore()
const staff = ref([])
const roles = ref([])
const loading = ref(false)
const saving = ref(false)
const showForm = ref(false)
const editingStaff = ref(null)
const form = reactive({ name: '', role_id: '', pin_code: '', is_active: true })

async function loadData() {
    loading.value = true
    try {
        const [staffResponse, rolesResponse] = await Promise.all([
            api.get('/staff'),
            api.get('/roles'),
        ])
        staff.value = staffResponse.data.staff
        roles.value = rolesResponse.data.roles
    } catch (error) {
        toast.error(error.response?.data?.message ?? t('Failed to load staff.'))
    } finally {
        loading.value = false
    }
}

function openCreate() {
    editingStaff.value = null
    Object.assign(form, {
        name: '',
        role_id: roles.value.find(role => role.slug === 'cashier')?.id ?? roles.value[0]?.id ?? '',
        pin_code: '',
        is_active: true,
    })
    showForm.value = true
}

function openEdit(member) {
    editingStaff.value = member
    Object.assign(form, {
        name: member.name,
        role_id: member.role_id,
        pin_code: '',
        is_active: member.is_active,
    })
    showForm.value = true
}

async function saveStaff() {
    if (!roles.value.length) {
        toast.error(t('No cashier or kitchen roles are configured.'))
        return
    }
    saving.value = true
    try {
        const payload = {
            name: form.name,
            role_id: form.role_id,
            is_active: form.is_active,
        }
        if (form.pin_code) payload.pin_code = form.pin_code

        if (editingStaff.value) {
            await api.put(`/staff/${editingStaff.value.id}`, payload)
            toast.success(t('Staff member updated.'))
        } else {
            await api.post('/staff', { ...payload, pin_code: form.pin_code })
            toast.success(t('Staff member created.'))
        }
        showForm.value = false
        await loadData()
    } catch (error) {
        toast.error(error.response?.data?.message ?? t('Failed to save staff member.'))
    } finally {
        saving.value = false
    }
}

async function deleteStaff(member) {
    if (member.id === auth.user?.id || !confirm(t('Delete {name}?', { name: member.name }))) return

    try {
        await api.delete(`/staff/${member.id}`)
        toast.success(t('Staff member deleted.'))
        await loadData()
    } catch (error) {
        toast.error(error.response?.data?.message ?? t('Failed to delete staff member.'))
    }
}

onMounted(loadData)
</script>
