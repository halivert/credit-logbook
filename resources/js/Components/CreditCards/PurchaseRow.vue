<script setup lang="ts">
import type { Purchase } from "@/types"
import { useFormat } from "@/format"

defineProps<{
    purchase: Purchase
}>()

const { toCurrency, toDate } = useFormat()
</script>

<template>
    <li
        class="inline-flex justify-between w-full px-3 py-1 hover:bg-gray-50 focus-within:bg-gray-50 dark:hover:bg-gray-700/50 dark:focus-within:bg-gray-700/50 rounded items-center"
    >
        <span>
            {{ toDate(purchase.datetime) }}
            <br />

            <span :class="{ 'line-through': purchase.paid_at }">
                {{ purchase.concept }} -
                {{ toCurrency(purchase.amount) }}
            </span>
        </span>

        <slot name="actions" />
    </li>
</template>
