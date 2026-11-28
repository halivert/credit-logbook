<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-left text-xl hover:bg-primary hover:text-white focus:outline-none focus:bg-primary focus:text-white"
    >
        <slot />
    </Link>
</template>
