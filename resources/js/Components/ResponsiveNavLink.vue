<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'block w-full pl-3 pr-4 py-3 border-l-4 border-primary text-left font-pixel text-xs text-primary bg-primary/10 focus:outline-none'
        : 'block w-full pl-3 pr-4 py-3 border-l-4 border-transparent text-left font-pixel text-xs text-muted hover:text-ink hover:bg-line/5 hover:border-line/30 focus:outline-none focus:text-ink focus:bg-line/5'
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
