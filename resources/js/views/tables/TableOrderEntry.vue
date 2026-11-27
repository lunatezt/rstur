<template>
    <ContactlessMenu v-if="!auth.isAuthenticated" />
    <TableOrderTerminal v-else />
</template>

<script setup>
import { useAuthStore } from '@/stores/auth.js'
import ContactlessMenu from '@/views/tables/ContactlessMenu.vue'
import TableOrderTerminal from '@/views/tables/TableOrderTerminal.vue'

const auth = useAuthStore()
</script>
