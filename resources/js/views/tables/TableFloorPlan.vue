<template>
    <div class="flex flex-col h-full overflow-hidden bg-gray-50">

        <!-- Top Bar -->
        <div class="bg-white border-b px-4 py-2.5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <h1 class="text-sm font-bold text-gray-800">{{ $t('Table View') }}</h1>
                <button @click="loadFloor" class="text-gray-400 hover:text-gray-700 p-1 rounded">
                    <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': floorLoading }" />
                </button>
            </div>
            <div class="flex items-center gap-1.5">
                <button @click="router.push({ name: 'new-order', query: { type: 'delivery' } })"
                    class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-semibold flex items-center gap-1.5 hover:bg-red-700">
                    <Truck class="w-3 h-3" /> {{ $t('Delivery') }}
                </button>
                <button @click="router.push({ name: 'new-order', query: { type: 'takeaway' } })"
                    class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-semibold flex items-center gap-1.5 hover:bg-red-700">
                    <ShoppingBag class="w-3 h-3" /> {{ $t('Take Away') }}
                </button>
            </div>
        </div>

        <!-- Sub Bar -->
        <div class="bg-white border-b px-4 py-2 flex items-center gap-2 shrink-0 flex-wrap">
            <button @click="showReservation = true"
                class="px-3 py-1.5 border border-red-500 text-red-600 rounded text-xs font-medium flex items-center gap-1 hover:bg-red-50">
                <CalendarDays class="w-3 h-3" /> {{ $t('Table Reservation') }}
            </button>
            <button @click="showContactless = true"
                class="px-3 py-1.5 border border-red-500 text-red-600 rounded text-xs font-medium flex items-center gap-1 hover:bg-red-50">
                <QrCode class="w-3 h-3" /> {{ $t('Contactless') }}
            </button>
            <button @click="openQrScanner"
                class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded text-xs font-medium flex items-center gap-1 hover:bg-gray-50">
                <ScanLine class="w-3 h-3" /> {{ $t('Scan Table QR') }}
            </button>
            <div class="flex-1" />
            <div class="hidden sm:flex items-center gap-2.5 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded border-2 border-dashed border-gray-400 bg-white inline-block"></span> {{ $t('Blank') }}</span>
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-blue-200 border border-blue-400 inline-block"></span> {{ $t('Running') }}</span>
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-yellow-200 border border-yellow-400 inline-block"></span> {{ $t('Cleaning') }}</span>
                <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-indigo-200 border border-indigo-400 inline-block"></span> {{ $t('Reserved') }}</span>
            </div>
        </div>

        <!-- Section Tabs -->
        <div class="bg-white border-b px-4 flex items-center justify-between shrink-0">
            <div class="flex overflow-x-auto">
                <button v-for="sec in [{ id: null, name: 'All' }, ...sections]" :key="sec.id ?? 'all'"
                    @click="activeSection = sec.id"
                    :class="['px-4 py-2.5 text-xs font-medium border-b-2 whitespace-nowrap transition-colors',
                        activeSection === sec.id ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    {{ sec.id === null ? $t(sec.name) : sec.name }}
                </button>
            </div>
            <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 shrink-0 pl-3">
                <span>{{ $t('Floor Plan') }}</span>
                <select class="border border-gray-200 rounded px-2 py-1 text-xs text-gray-700 bg-white">
                    <option>{{ $t('Default Layout') }}</option>
                </select>
            </div>
        </div>

        <!-- Tables Grid -->
        <div class="flex-1 overflow-y-auto p-3">
            <div v-if="floorLoading && !tables.length" class="flex items-center justify-center h-40">
                <div class="animate-spin w-7 h-7 border-4 border-red-500 border-t-transparent rounded-full"></div>
            </div>

            <template v-else v-for="section in visibleSections" :key="section.id">
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-700 mb-2 flex items-center gap-2">
                        {{ section.name }}
                        <span class="text-xs font-normal text-gray-400">({{ section.tables.length }} tables)</span>
                    </p>
                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 xl:grid-cols-12 gap-2">
                        <button v-for="table in section.tables" :key="table.id"
                            @click="goToTable(table)"
                            :class="['relative rounded-lg text-center border-2 transition-all focus:outline-none overflow-hidden',
                                table.status !== 'blocked' ? 'hover:shadow-md hover:scale-[1.03]' : '',
                                tableCardClass(table)]"
                            style="min-height: 68px; padding: 7px 5px 5px">

                            <p class="font-semibold text-xs leading-tight">{{ table.name }}</p>
                            <p class="text-gray-500 mt-0.5" style="font-size:10px">{{ table.capacity }} {{ $t('pax') }}</p>

                            <template v-if="table.status === 'occupied'">
                                <p class="font-bold mt-1 text-blue-900" style="font-size:10px">
                                    AFS {{ table.current_order?.grand_total ?? '–' }}
                                </p>
                                <p class="opacity-60" style="font-size:9px">{{ elapsed(table.current_order?.created_at) }}</p>
                            </template>
                            <template v-else-if="table.status === 'reserved'">
                                <p class="opacity-70 mt-1" style="font-size:9px">{{ $t('Reserved') }}</p>
                            </template>
                        </button>
                    </div>
                </div>
            </template>

            <div v-if="!floorLoading && !tables.length" class="text-center text-gray-400 py-16 text-xs">
                {{ $t('No tables found.') }}
                <router-link to="/table-setup" class="text-red-600 hover:underline">{{ $t('Go to Table Setup') }}</router-link>
                {{ $t('to add tables.') }}
            </div>
        </div>

        <!-- Stats Footer -->
        <div class="bg-white border-t px-4 py-2 flex gap-4 text-xs text-gray-500 shrink-0 flex-wrap">
            <span>{{ $t('Total') }} <b class="text-gray-700 ml-1">{{ tables.length }}</b></span>
            <span>{{ $t('Blank') }} <b class="text-gray-700 ml-1">{{ stats.available }}</b></span>
            <span>{{ $t('Running') }} <b class="text-blue-600 ml-1">{{ stats.occupied }}</b></span>
            <span>{{ $t('Reserved') }} <b class="text-indigo-600 ml-1">{{ stats.reserved }}</b></span>
            <span>{{ $t('Cleaning') }} <b class="text-yellow-600 ml-1">{{ stats.cleaning }}</b></span>
        </div>
    </div>

    <!-- Reservation Modal -->
    <Teleport to="body">
        <div v-if="showReservation" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-5 max-w-sm w-full shadow-2xl">
                <h3 class="font-bold text-sm text-gray-800 mb-4 flex items-center gap-2">
                    <CalendarDays class="w-4 h-4 text-red-500" /> {{ $t('New Reservation') }}
                </h3>
                <div class="space-y-2.5">
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Guest Name *') }}</label>
                        <input v-model="reservationForm.customer_name"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400"
                            :placeholder="$t('Customer name')" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Phone') }}</label>
                        <input v-model="reservationForm.customer_phone" type="tel"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400"
                            :placeholder="$t('Mobile number')" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Date *') }}</label>
                            <input v-model="reservationForm.reservation_date" type="date"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Time *') }}</label>
                            <input v-model="reservationForm.reservation_time" type="time"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Guests') }}</label>
                            <input v-model="reservationForm.number_of_guests" type="number" min="1"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Table') }}</label>
                            <select v-model="reservationForm.table_id"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400">
                                <option :value="null">{{ $t('Auto Assign') }}</option>
                                <option v-for="t in availableTables" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Occasion') }}</label>
                        <select v-model="reservationForm.occasion"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400">
                            <option value="">{{ $t('None') }}</option>
                            <option value="birthday">{{ $t('Birthday') }}</option>
                            <option value="anniversary">{{ $t('Anniversary') }}</option>
                            <option value="business">{{ $t('Business Lunch') }}</option>
                            <option value="date">{{ $t('Date Night') }}</option>
                            <option value="other">{{ $t('Other') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Special Requests') }}</label>
                        <textarea v-model="reservationForm.special_requests" rows="2"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm outline-none focus:border-red-400"
                            :placeholder="$t('Any special requests...')"></textarea>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="showReservation = false"
                        class="flex-1 py-2 border rounded text-xs text-gray-600 hover:bg-gray-50">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="saveReservation" :disabled="savingReservation"
                        class="flex-1 py-2 bg-red-600 text-white rounded text-xs font-bold hover:bg-red-700 disabled:opacity-40">
                        {{ savingReservation ? $t('Saving...') : $t('Reserve') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Table QR Scanner -->
    <Teleport to="body">
        <div v-if="showQrScanner" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-5 max-w-lg w-full shadow-2xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-base text-gray-800 flex items-center gap-2">
                        <ScanLine class="w-5 h-5 text-red-600" /> {{ $t('Scan Table QR') }}
                    </h3>
                    <button @click="closeQrScanner" class="text-gray-500 hover:text-gray-800" :aria-label="$t('Close scanner')">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <video ref="qrVideo" autoplay muted playsinline
                    class="w-full aspect-video rounded-lg bg-black object-cover"></video>
                <p v-if="scannerError" class="text-sm text-red-600 mt-3">{{ $t(scannerError) }}</p>
                <p v-else class="text-sm text-gray-500 mt-3">
                {{ $t('Point the camera at a table QR code to open that table.') }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                {{ $t('Camera access requires permission and a secure connection (HTTPS or localhost).') }}
                </p>
                <div class="flex gap-2 mt-4">
                    <button v-if="scannerError" @click="startQrScanner"
                        class="flex-1 py-2 bg-red-600 text-white rounded text-sm font-semibold hover:bg-red-700">
                        {{ $t('Try Again') }}
                    </button>
                    <button @click="closeQrScanner"
                        class="flex-1 py-2 border rounded text-sm text-gray-600 hover:bg-gray-50">
                        {{ $t('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Contactless QR Modal -->
    <Teleport to="body">
        <div v-if="showContactless" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-5 max-w-xs w-full shadow-2xl text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <QrCode class="w-6 h-6 text-red-600" />
                </div>
                <h3 class="font-bold text-sm text-gray-800 mb-1">{{ $t('Contactless Ordering') }}</h3>
                <p class="text-xs text-gray-500 mb-4">
                    {{ $t('Select a table to share a public digital menu link.') }}
                </p>
                <div class="mb-4 text-left">
                    <label class="text-xs font-medium text-gray-700 mb-1 block">{{ $t('Select Table') }}</label>
                    <select v-model="contactlessTable"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option :value="null">{{ $t('Choose table...') }}</option>
                        <option v-for="t in tables" :key="t.id" :value="t">{{ t.name }} ({{ t.capacity }} pax)</option>
                    </select>
                </div>
                <div v-if="contactlessTable" class="bg-gray-100 rounded-lg p-3 mb-4">
                    <img v-if="contactlessQr" :src="contactlessQr" alt="Contactless menu QR code"
                        class="w-44 h-44 mx-auto mb-2" />
                    <p class="text-xs font-mono text-gray-600 break-all">{{ contactlessUrl }}</p>
                    <button @click="copyContactlessUrl"
                        class="mt-2 text-xs text-red-600 hover:text-red-700 font-medium flex items-center gap-1 mx-auto">
                        <Copy class="w-3 h-3" /> {{ $t('Copy Link') }}
                    </button>
                </div>
                <p class="text-xs text-gray-400 mb-3">
                    {{ $t('Guests need a network-accessible address; localhost links only work on this device.') }}
                </p>
                <button @click="showContactless = false"
                    class="w-full py-2 border rounded text-xs text-gray-600 hover:bg-gray-50">
                    {{ $t('Close') }}
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { RefreshCw, CalendarDays, QrCode, Truck, ShoppingBag, Copy, ScanLine, X } from 'lucide-vue-next'
import { toast } from 'vue3-toastify'
import QRCode from 'qrcode'
import api from '@/api/index.js'
import { t } from '@/localization.js'

const router = useRouter()

// ── State ─────────────────────────────────────────────────────────
const tables        = ref([])
const sections      = ref([])
const floorLoading  = ref(false)
const activeSection = ref(null)

const showReservation   = ref(false)
const savingReservation = ref(false)
const reservationForm   = reactive({
    customer_name: '',
    customer_phone: '',
    reservation_date: new Date().toISOString().split('T')[0],
    reservation_time: '19:00',
    number_of_guests: 2,
    table_id: null,
    occasion: '',
    special_requests: '',
})

const showContactless  = ref(false)
const contactlessTable = ref(null)
const contactlessQr    = ref('')
const showQrScanner    = ref(false)
const scannerError     = ref('')
const qrVideo          = ref(null)
let qrReader           = null
let scannerControls    = null
let scannerGeneration  = 0
let handlingScan       = false
const contactlessUrl   = computed(() =>
    contactlessTable.value
        ? `${window.location.origin}/tables/${contactlessTable.value.id}/order`
        : ''
)

let qrGeneration = 0
watch(contactlessUrl, async (url) => {
    const generation = ++qrGeneration
    contactlessQr.value = ''
    if (!url) return

    try {
        const qr = await QRCode.toDataURL(url, { width: 220, margin: 1 })
        if (generation === qrGeneration) contactlessQr.value = qr
    } catch {
        if (generation === qrGeneration) toast.error(t('Could not generate the menu QR code'))
    }
})

// ── Computed ──────────────────────────────────────────────────────
const availableTables = computed(() => tables.value.filter(t => t.status === 'available'))

const stats = computed(() => ({
    available: tables.value.filter(t => t.status === 'available').length,
    occupied:  tables.value.filter(t => t.status === 'occupied').length,
    reserved:  tables.value.filter(t => t.status === 'reserved').length,
    cleaning:  tables.value.filter(t => t.status === 'cleaning').length,
}))

const visibleSections = computed(() =>
    sections.value.map(s => ({
        ...s,
        tables: tables.value.filter(t =>
            t.section_id === s.id &&
            (!activeSection.value || t.section_id === activeSection.value)
        ),
    })).filter(s => s.tables.length > 0)
)

// ── Load Floor ────────────────────────────────────────────────────
async function loadFloor() {
    if (floorLoading.value) return
    floorLoading.value = true
    try {
        const { data } = await api.get('/tables')
        tables.value   = data.tables ?? []
        sections.value = data.sections ?? []
    } catch {
        toast.error(t('Failed to load tables'))
    } finally {
        floorLoading.value = false
    }
}

// ── Navigation ────────────────────────────────────────────────────
function goToTable(table) {
    if (table.status === 'blocked') return
    router.push({ name: 'table-order', params: { tableId: table.id } })
}

// ── Reservation ───────────────────────────────────────────────────
async function saveReservation() {
    if (!reservationForm.customer_name.trim()) return toast.warning(t('Guest name is required'))
    if (!reservationForm.reservation_date)     return toast.warning(t('Date is required'))
    if (!reservationForm.reservation_time)     return toast.warning(t('Time is required'))

    savingReservation.value = true
    try {
        await api.post('/reservations', { ...reservationForm })
        toast.success(t('Reservation confirmed!'))
        showReservation.value = false
        Object.assign(reservationForm, {
            customer_name: '', customer_phone: '',
            reservation_date: new Date().toISOString().split('T')[0],
            reservation_time: '19:00', number_of_guests: 2,
            table_id: null, occasion: '', special_requests: '',
        })
        await loadFloor()
    } catch (err) {
        toast.error(err?.response?.data?.message ?? t('Failed to save reservation'))
    } finally {
        savingReservation.value = false
    }
}

// ── Contactless ───────────────────────────────────────────────────
function copyContactlessUrl() {
    navigator.clipboard.writeText(contactlessUrl.value)
        .then(() => toast.success(t('Link copied!')))
        .catch(() => toast.error(t('Could not copy link')))
}

async function openQrScanner() {
    showQrScanner.value = true
    await startQrScanner()
}

async function startQrScanner() {
    stopQrScanner()
    const generation = scannerGeneration
    scannerError.value = ''
    handlingScan = false

    await nextTick()
    if (!showQrScanner.value || !qrVideo.value) return

    try {
        if (!qrReader) {
            const { BrowserQRCodeReader } = await import('@zxing/browser')
            if (generation !== scannerGeneration || !showQrScanner.value) return
            qrReader = new BrowserQRCodeReader()
        }

        const controls = await qrReader.decodeFromConstraints(
            { video: { facingMode: { ideal: 'environment' } } },
            qrVideo.value,
            (result) => {
                if (result && !handlingScan) handleTableQr(result.getText())
            }
        )

        if (generation !== scannerGeneration || !showQrScanner.value) {
            controls.stop()
        } else {
            scannerControls = controls
        }
    } catch (error) {
        scannerError.value = error.name === 'NotAllowedError'
            ? 'Camera permission was denied. Allow camera access and try again.'
            : error.name === 'NotFoundError'
                ? 'No camera was found on this device.'
                : 'Could not start the camera. Use HTTPS or localhost and check browser permissions.'
    }
}

function handleTableQr(text) {
    handlingScan = true
    stopQrScanner()

    let scannedUrl
    try {
        scannedUrl = new URL(text, window.location.origin)
    } catch {
        scannerError.value = 'This QR code does not contain a valid table link.'
        handlingScan = false
        return
    }

    if (scannedUrl.origin !== window.location.origin) {
        scannerError.value = 'This QR code belongs to a different website.'
        handlingScan = false
        return
    }

    const match = scannedUrl.pathname.match(/^\/tables\/(\d+)\/order\/?$/)
    const table = match && tables.value.find(item => String(item.id) === match[1])
    if (!table || table.status === 'blocked') {
        scannerError.value = 'This QR code does not match an active table.'
        handlingScan = false
        return
    }

    closeQrScanner()
    router.push({ name: 'table-order', params: { tableId: table.id } })
}

function stopQrScanner() {
    scannerGeneration++
    scannerControls?.stop()
    scannerControls = null
}

function closeQrScanner() {
    showQrScanner.value = false
    stopQrScanner()
}

// ── Helpers ───────────────────────────────────────────────────────
function tableCardClass(table) {
    if (table.status === 'occupied')  return 'bg-blue-100 border-blue-400 text-blue-900'
    if (table.status === 'reserved')  return 'bg-indigo-100 border-indigo-400 text-indigo-900'
    if (table.status === 'cleaning')  return 'bg-yellow-100 border-yellow-400 text-yellow-900'
    if (table.status === 'blocked')   return 'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed'
    return 'bg-white border-dashed border-gray-400 text-gray-700 hover:border-gray-500'
}

function elapsed(dateStr) {
    if (!dateStr) return ''
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 60000)
    if (diff < 1)  return 'now'
    if (diff < 60) return `${diff}m`
    return `${Math.floor(diff / 60)}h ${diff % 60}m`
}

// ── Lifecycle ─────────────────────────────────────────────────────
let timer
onMounted(() => {
    loadFloor()
    timer = setInterval(loadFloor, 30000)
})
onUnmounted(() => {
    clearInterval(timer)
    stopQrScanner()
})
</script>
