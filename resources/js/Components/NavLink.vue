<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-1 pt-1 border-b-4 border-primary text-xl leading-5 uppercase tracking-wider text-ink focus:outline-none'
        : 'inline-flex items-center px-1 pt-1 border-b-4 border-transparent text-xl leading-5 uppercase tracking-wider text-muted hover:text-ink hover:border-line/30 focus:outline-none focus:text-ink'
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
