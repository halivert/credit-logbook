<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
    active?: boolean;
}>();
</script>

<template>
    <Link :href="href" class="nav-link leading-5" :class="{ 'is-active': active }">
        <slot />
    </Link>
</template>
