<script setup lang="ts">
import { Link } from "@inertiajs/vue3"
const props = defineProps<{
    links?: {
        label: string
        url: string
        active: boolean
    }[]
    next_page_url?: string | null
    prev_page_url?: string | null
}>()

const links =
    props.links?.length > 3
        ? props.links
        : props.next_page_url || props.prev_page_url
          ? [
                { label: "Anterior", url: props.prev_page_url, active: false },
                { label: "Siguiente", url: props.next_page_url, active: false },
            ]
          : []
</script>

<template>
    <div class="flex flex-wrap -mb-1">
        <template v-for="link in links" :key="link.url">
            <Link
                v-if="link.url"
                class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                :href="link.url"
                v-html="link.label"
            />
        </template>
    </div>
</template>
