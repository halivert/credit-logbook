<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
    active?: boolean;
}>();
</script>

<template>
    <Link :href="href" class="drawer-link" :class="{ 'is-active': active }">
        <slot />
    </Link>
</template>
