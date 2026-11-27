<template>
    <router-view />
    <NotificationCenter v-if="auth.user?.role?.slug === 'admin'" />
</template>

<script setup>
import { useAuthStore } from '@/stores/auth.js'
import NotificationCenter from '@/components/NotificationCenter.vue'

const auth = useAuthStore()
</script>
