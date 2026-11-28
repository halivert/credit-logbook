<script setup lang="ts">
defineProps<{
    value?: string;
}>();
</script>

<template>
    <label class="block font-medium">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
