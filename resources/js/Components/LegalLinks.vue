<script setup lang="ts">
import { Link } from "@inertiajs/vue3"
</script>

<template>
    <nav class="flex flex-wrap justify-center gap-x-4 gap-y-1 text-note">
        <Link :href="route('terms')" class="link">Términos de uso</Link>
        <Link :href="route('privacy')" class="link">Aviso de privacidad</Link>
    </nav>
</template>
